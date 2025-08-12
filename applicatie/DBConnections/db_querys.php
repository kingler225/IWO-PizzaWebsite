<?php
require_once __DIR__ . '/db_connection.php';

function getProductenPerCategorie(): array
{
    $verbinding = maakVerbinding();

    try {
        $stmt = $verbinding->query("SELECT name, price, type_id FROM Product ORDER BY type_id, name");
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

function plaatsBestelling(PDO $conn, ?string $username, string $clientName, string $address, array $producten): ?int
{
    try {
        // ✅ Get a valid personnel user from the database
        $stmt = $conn->query("SELECT TOP 1 username FROM [User] WHERE role = 'Personnel'");
        $personnelUsername = $stmt->fetchColumn();

        if (!$personnelUsername) {
            throw new Exception("No personnel user available to assign the order.");
        }

        // ✅ Insert the order
        $stmt = $conn->prepare("
            INSERT INTO Pizza_Order (client_username, client_name, address, personnel_username, datetime, status)
            VALUES (:client_username, :client_name, :address, :personnel_username, GETDATE(), 1)
        ");
        $stmt->execute([
            'client_username' => $username,
            'client_name' => $clientName,
            'address' => $address,
            'personnel_username' => $personnelUsername
        ]);

        $orderId = $conn->lastInsertId();

        // ✅ Insert products
        foreach ($producten as $productName => $quantity) {
            if ((int) $quantity > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO Pizza_Order_Product (order_id, product_name, quantity)
                    VALUES (:order_id, :product, :quantity)
                ");
                $stmt->execute([
                    'order_id' => $orderId,
                    'product' => $productName,
                    'quantity' => (int) $quantity
                ]);
            }
        }

        return $orderId;

    } catch (Exception | PDOException $e) {
        error_log("Fout bij plaatsen van bestelling: " . $e->getMessage());
        return null;
    }
}

function checkWachtwoord(string $ingevoerd, string $opgeslagen): bool
{
    // Is het wachtwoord al gehashed?
    if (password_get_info($opgeslagen)['algo']) {
        return password_verify($ingevoerd, $opgeslagen);
    }

    // Plaintext wachtwoord (alleen tijdelijk toegestaan)
    return $ingevoerd === $opgeslagen;
}

function loginGebruiker(array $postData): ?string
{
    require_once __DIR__ . '/db_connection.php';
    $conn = maakVerbinding();

    $username = $postData['username'] ?? '';
    $password = $postData['password'] ?? '';

    try {
        // Haal alleen de nodige velden op
        $stmt = $conn->prepare("SELECT username, password, role FROM [User] WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !checkWachtwoord($password, $user['password'])) {
            return "Ongeldige gebruikersnaam of wachtwoord.";
        }

        // ✅ Upgrade wachtwoord naar hash als het nog plaintext was
        if (!password_get_info($user['password'])['algo']) {
            $nieuwHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE [User] SET password = :hash WHERE username = :username");
            $update->execute([
                'hash' => $nieuwHash,
                'username' => $user['username']
            ]);
        }

        // ✅ Login en sessie
        session_start();
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
                    po.status
                FROM Pizza_Order po
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
                    po.status
                FROM Pizza_Order po
                WHERE po.client_username = :username
                ORDER BY po.datetime ASC
            ");
            $stmt->execute(['username' => $username]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching orders: " . $e->getMessage());
        return [];
    }
}


function haalProductenUitWinkelmand(PDO $conn, array $cart): array
{
    if (empty($cart))
        return [];

    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $conn->prepare("SELECT name, price FROM Product WHERE name IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    $total = 0;

    foreach ($products as $product) {
        $name = $product['name'];
        $quantity = (int) $cart[$name];
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

function haalSamenvattingVanProducten(PDO $conn, array $producten): array
{
    $orderSummary = [];
    $totalPrice = 0;

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
                    'price' => $price
                ];
                $totalPrice += $price * $qty;
            }
        }
    }

    return ['summary' => $orderSummary, 'total' => $totalPrice];
}

function registreerNieuweGebruiker(PDO $conn, string $username, string $password, string $confirmPassword, string $firstName, string $lastName, string $address): ?string
{
    try {
        // Input sanitization
        $username = trim($username);
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $address = trim($address);

        // Basic validation
        if (empty($username) || empty($password) || empty($confirmPassword)) {
            return "Username and password fields are required.";
        }

        if (strlen($username) < 4 || strlen($username) > 30) {
            return "Username must be between 4 and 30 characters.";
        }

        if ($password !== $confirmPassword) {
            return "Passwords do not match.";
        }

        if (strlen($password) < 8) {
            return "Password must be at least 8 characters long.";
        }

        // Check if username is already taken
        $stmt = $conn->prepare("SELECT COUNT(*) FROM [User] WHERE username = :username");
        $stmt->execute(['username' => $username]);

        if ($stmt->fetchColumn() > 0) {
            return "Username already exists. Please choose another.";
        }

        // Hash the password with bcrypt
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user with default role 'Client'
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

        return null; // success

    } catch (PDOException $e) {
        error_log("Signup error: " . $e->getMessage());
        return "An error occurred while creating your account. Please try again later.";
    }
}


function haalGebruikerOp(PDO $conn, string $username): ?array
{
    $stmt = $conn->prepare("SELECT username, first_name, last_name, address FROM [User] WHERE username = :username");
    $stmt->execute(['username' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function werkGebruikerBij(PDO $conn, string $username, string $firstName, string $lastName, string $address, string $newPassword = null): mixed
{
    try {
        if ($newPassword) {
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
        error_log("Profile update error: " . $e->getMessage());
        return "Something went wrong.";
    }
}

function wijzigBestelStatus(PDO $conn, int $orderId, int $newStatus): bool
{
    try {
        $stmt = $conn->prepare("UPDATE Pizza_Order SET status = :status WHERE order_id = :id");
        return $stmt->execute(['status' => $newStatus, 'id' => $orderId]);
    } catch (PDOException $e) {
        error_log("Error updating status: " . $e->getMessage());
        return false;
    }
}


?>