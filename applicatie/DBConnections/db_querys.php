<?php
require_once __DIR__ . '/db_connection.php';

/**
 * Haal alle producten op, gegroepeerd per type_id.
 */
function getProductenPerCategorie(): array
{
    $verbinding = maakVerbinding();

    try {
        $stmt = $verbinding->query("
            SELECT name, price, type_id
            FROM Product
            ORDER BY type_id, name
        ");
        $producten = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gegroepeerd = [];
        foreach ($producten as $product) {
            $type = $product['type_id'];
            $gegroepeerd[$type][] = $product;
        }

        return $gegroepeerd;
    } catch (PDOException $e) {
        error_log("Fout bij ophalen van producten: " . $e->getMessage());
        return [];
    }
}

/**
 * Plaats een bestelling met producten.
 * - kiest automatisch een personeelsgebruiker
 * - gebruikt een transactie
 * - retourneert het nieuwe order_id of null
 */
function plaatsBestelling(PDO $conn, ?string $username, string $clientName, string $address, array $producten): ?int
{
    try {
        // Minimale validatie
        if (trim($address) === '') {
            throw new Exception("Adres mag niet leeg zijn.");
        }

        // Start transactie
        $conn->beginTransaction();

        // Kies een personeelsgebruiker
        $stmt = $conn->query("SELECT TOP 1 username FROM [User] WHERE role = 'Personnel'");
        $personnelUsername = $stmt->fetchColumn();

        if (!$personnelUsername) {
            throw new Exception("Geen personeelsgebruiker beschikbaar om bestelling toe te wijzen.");
        }

        // Insert bestelling + direct het ID ophalen (SQL Server)
        $stmt = $conn->prepare("
            INSERT INTO Pizza_Order (client_username, client_name, address, personnel_username, datetime, status)
            OUTPUT INSERTED.order_id
            VALUES (:client_username, :client_name, :address, :personnel_username, GETDATE(), 1)
        ");
        $stmt->execute([
            'client_username' => $username,
            'client_name' => $clientName,
            'address' => $address,
            'personnel_username' => $personnelUsername
        ]);

        $orderId = (int) $stmt->fetchColumn();
        if ($orderId <= 0) {
            throw new Exception("Kon order_id niet bepalen na invoegen.");
        }

        // Producten invoegen (alleen qty > 0)
        $insertItem = $conn->prepare("
            INSERT INTO Pizza_Order_Product (order_id, product_name, quantity)
            VALUES (:order_id, :product, :quantity)
        ");

        $heeftItems = false;
        foreach ($producten as $productName => $quantity) {
            $qty = (int) $quantity;
            if ($qty > 0) {
                $insertItem->execute([
                    'order_id' => $orderId,
                    'product' => $productName,
                    'quantity' => $qty
                ]);
                $heeftItems = true;
            }
        }

        if (!$heeftItems) {
            throw new Exception("Bestelling bevat geen geldige artikelen.");
        }

        $conn->commit();
        return $orderId;

    } catch (Exception | PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Fout bij plaatsen van bestelling: " . $e->getMessage());
        return null;
    }
}

/**
 * Controleer wachtwoord en ondersteun eenmalige plaintext->hash upgrade.
 */
function checkWachtwoord(string $ingevoerd, string $opgeslagen): bool
{
    // Al gehashed?
    if (password_get_info($opgeslagen)['algo']) {
        return password_verify($ingevoerd, $opgeslagen);
    }
    // Tijdelijke achtervang voor oude (plaintext) wachtwoorden
    return hash_equals($ingevoerd, $opgeslagen);
}

/**
 * Login helper: haalt user op, verifieert wachtwoord, upgrade zo nodig, zet sessie en redirect.
 * Retourneert foutmelding (string) of beëindigt met redirect.
 */
function loginGebruiker(array $postData): ?string
{
    $conn = maakVerbinding();

    $username = $postData['username'] ?? '';
    $password = $postData['password'] ?? '';

    try {
        $stmt = $conn->prepare("
            SELECT username, password, role
            FROM [User]
            WHERE username = :username
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !checkWachtwoord($password, $user['password'])) {
            return "Ongeldige gebruikersnaam of wachtwoord.";
        }

        // Upgrade naar hash indien nodig
        if (!password_get_info($user['password'])['algo']) {
            $nieuwHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE [User] SET password = :hash WHERE username = :username");
            $update->execute([
                'hash' => $nieuwHash,
                'username' => $user['username']
            ]);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'username' => $user['username'],
            'role' => $user['role']
        ];

        header("Location: ../index.php");
        exit;

    } catch (PDOException $e) {
        error_log("Login fout: " . $e->getMessage());
        return "Er is een fout opgetreden bij het inloggen.";
    }
}

/**
 * Haal bestellingen op, voor personeel (alles) of voor een specifieke klant (eigen).
 * Levert ook 'klant_weergave' terug (Voornaam Achternaam of username).
 */
function haalBestellingenOp(PDO $conn, string $role, string $username): array
{
    try {
        if ($role === 'Personnel') {
            $stmt = $conn->query("
                SELECT 
                    po.order_id,
                    po.client_username,
                    po.client_name,
                    po.address,
                    po.datetime,
                    po.status,
                    COALESCE(
                        NULLIF(LTRIM(RTRIM(CONCAT(u.first_name, ' ', u.last_name))), ''),
                        po.client_username
                    ) AS klant_weergave
                FROM Pizza_Order po
                LEFT JOIN [User] u ON u.username = po.client_username
                ORDER BY po.datetime ASC
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT 
                    po.order_id,
                    po.client_username,
                    po.client_name,
                    po.address,
                    po.datetime,
                    po.status,
                    COALESCE(
                        NULLIF(LTRIM(RTRIM(CONCAT(u.first_name, ' ', u.last_name))), ''),
                        po.client_username
                    ) AS klant_weergave
                FROM Pizza_Order po
                LEFT JOIN [User] u ON u.username = po.client_username
                WHERE po.client_username = :username
                ORDER BY po.datetime ASC
            ");
            $stmt->execute(['username' => $username]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    } catch (PDOException $e) {
        error_log("Fout bij ophalen van bestellingen: " . $e->getMessage());
        return [];
    }
}

/**
 * Construeer items/totaal uit de winkelmand (naam => qty).
 * Retourneert altijd ['items'=>[], 'total'=>float].
 */
function haalProductenUitWinkelmand(PDO $conn, array $cart): array
{
    if (empty($cart)) {
        return ['items' => [], 'total' => 0.0];
    }

    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $conn->prepare("
        SELECT name, price
        FROM Product
        WHERE name IN ($placeholders)
    ");
    $stmt->execute(array_keys($cart));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    $total = 0.0;

    foreach ($products as $product) {
        $name = $product['name'];
        $quantity = (int) ($cart[$name] ?? 0);
        if ($quantity <= 0)
            continue;

        $price = (float) $product['price'];
        $subtotal = $quantity * $price;

        $items[] = [
            'name' => $name,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal
        ];
        $total += $subtotal;
    }

    return ['items' => $items, 'total' => $total];
}

/**
 * Bouw een samenvatting (items + totaal) uit een [naam => qty] array.
 */
function haalSamenvattingVanProducten(PDO $conn, array $producten): array
{
    $orderSummary = [];
    $totalPrice = 0.0;

    foreach ($producten as $name => $qty) {
        $qty = (int) $qty;
        if ($qty > 0) {
            $stmt = $conn->prepare("SELECT price FROM Product WHERE name = :name");
            $stmt->execute(['name' => $name]);
            $price = $stmt->fetchColumn();

            if ($price !== false) {
                $orderSummary[] = [
                    'name' => $name,
                    'quantity' => $qty,
                    'price' => (float) $price
                ];
                $totalPrice += ((float) $price) * $qty;
            }
        }
    }

    return ['summary' => $orderSummary, 'total' => $totalPrice];
}

/**
 * Registreer een nieuwe gebruiker (rol: Client).
 * Retourneert null bij succes, anders een NL-foutmelding.
 */
function registreerNieuweGebruiker(PDO $conn, string $username, string $password, string $confirmPassword, string $firstName, string $lastName, string $address): ?string
{
    try {
        // Normaliseren
        $username = trim($username);
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $address = trim($address);

        // Validaties
        if ($username === '' || $password === '' || $confirmPassword === '') {
            return "Gebruikersnaam en wachtwoord zijn verplicht.";
        }
        if (strlen($username) < 4 || strlen($username) > 30) {
            return "Gebruikersnaam moet tussen 4 en 30 tekens zijn.";
        }
        if ($password !== $confirmPassword) {
            return "Wachtwoorden komen niet overeen.";
        }
        if (strlen($password) < 8) {
            return "Wachtwoord moet minimaal 8 tekens lang zijn.";
        }

        // Bestaat de gebruikersnaam al?
        $stmt = $conn->prepare("SELECT COUNT(*) FROM [User] WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ((int) $stmt->fetchColumn() > 0) {
            return "Gebruikersnaam bestaat al. Kies een andere.";
        }

        // Hash het wachtwoord
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Voeg toe (rol: Client)
        $stmt = $conn->prepare("
            INSERT INTO [User] (username, password, first_name, last_name, address, role)
            VALUES (:username, :password, :first_name, :last_name, :address, 'Client')
        ");
        $stmt->execute([
            'username' => $username,
            'password' => $hashedPassword,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address' => $address
        ]);

        return null; // succes

    } catch (PDOException $e) {
        error_log("Registratiefout: " . $e->getMessage());
        return "Er is een fout opgetreden bij het aanmaken van je account. Probeer het later opnieuw.";
    }
}

/**
 * Haal gebruiker op (basisgegevens).
 */
function haalGebruikerOp(PDO $conn, string $username): ?array
{
    $stmt = $conn->prepare("
        SELECT username, first_name, last_name, address
        FROM [User]
        WHERE username = :username
    ");
    $stmt->execute(['username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Werk gebruiker bij; optioneel met nieuw wachtwoord.
 * Retourneert true bij succes of NL-foutmelding (string) bij fout.
 */
function werkGebruikerBij(PDO $conn, string $username, string $firstName, string $lastName, string $address, string $newPassword = null): mixed
{
    try {
        if ($newPassword !== null && $newPassword !== '') {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE [User]
                SET first_name = :first, last_name = :last, address = :addr, password = :pass
                WHERE username = :username
            ");
            $stmt->execute([
                'first' => $firstName,
                'last' => $lastName,
                'addr' => $address,
                'pass' => $hashed,
                'username' => $username
            ]);
        } else {
            $stmt = $conn->prepare("
                UPDATE [User]
                SET first_name = :first, last_name = :last, address = :addr
                WHERE username = :username
            ");
            $stmt->execute([
                'first' => $firstName,
                'last' => $lastName,
                'addr' => $address,
                'username' => $username
            ]);
        }
        return true;

    } catch (PDOException $e) {
        error_log("Fout bij bijwerken profiel: " . $e->getMessage());
        return "Er is iets misgegaan.";
    }
}

/**
 * Wijzig de status van een bestelling.
 */
function wijzigBestelStatus(PDO $conn, int $orderId, int $newStatus): bool
{
    try {
        $stmt = $conn->prepare("
            UPDATE Pizza_Order
            SET status = :status
            WHERE order_id = :id
        ");
        return $stmt->execute(['status' => $newStatus, 'id' => $orderId]);

    } catch (PDOException $e) {
        error_log("Fout bij bijwerken bestelstatus: " . $e->getMessage());
        return false;
    }
}

/**
 * Haal één bestelling op met autorisatie: personeel ziet alles, klant alleen eigen.
 */
function haalBestellingOp(PDO $conn, int $orderId, ?string $rol, ?string $gebruikersnaam)
{
    if ($orderId <= 0) {
        return false;
    }

    if ($rol === 'Personnel') {
        $sql = "
            SELECT 
                po.order_id,
                po.client_username,
                po.address,
                po.datetime,
                po.status,
                COALESCE(
                    NULLIF(LTRIM(RTRIM(CONCAT(u.first_name, ' ', u.last_name))), ''),
                    po.client_username
                ) AS klant_weergave
            FROM Pizza_Order po
            LEFT JOIN [User] u ON po.client_username = u.username
            WHERE po.order_id = :id
        ";
        $args = ['id' => $orderId];
    } else {
        $sql = "
            SELECT 
                po.order_id,
                po.client_username,
                po.address,
                po.datetime,
                po.status,
                COALESCE(
                    NULLIF(LTRIM(RTRIM(CONCAT(u.first_name, ' ', u.last_name))), ''),
                    po.client_username
                ) AS klant_weergave
            FROM Pizza_Order po
            LEFT JOIN [User] u ON po.client_username = u.username
            WHERE po.order_id = :id AND po.client_username = :user
        ";
        $args = ['id' => $orderId, 'user' => $gebruikersnaam];
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($args);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: false;
}

/**
 * Haal orderregels op voor een bestelling.
 */
function haalBestellingItemsOp(PDO $conn, int $orderId): array
{
    if ($orderId <= 0) {
        return [];
    }

    $stmt = $conn->prepare("
        SELECT product_name, quantity
        FROM Pizza_Order_Product
        WHERE order_id = :id
    ");
    $stmt->execute(['id' => $orderId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
