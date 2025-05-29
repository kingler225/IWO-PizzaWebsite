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

function loginGebruiker(array $postData): ?string
{
    require_once __DIR__ . '/db_connection.php';
    $conn = maakVerbinding();

    $username = $postData['username'] ?? '';
    $password = $postData['password'] ?? '';

    try {
        $stmt = $conn->prepare("SELECT * FROM [User] WHERE username = :username AND password = :password");
        $stmt->execute([
            'username' => $username,
            'password' => $password // 🔐 Use password_hash() in production
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            session_start();
            $_SESSION['user'] = [
                'username' => $user['username'],
                'role' => $user['role']
            ];
            header("Location: ../index.php");
            exit;
        } else {
            return "Ongeldige gebruikersnaam of wachtwoord.";
        }
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
                SELECT po.order_id, po.client_name, po.datetime, po.status, po.address,
                       (
                           SELECT STRING_AGG(p.name + ' x' + CAST(pop.quantity AS VARCHAR), ', ')
                           FROM Pizza_Order_Product pop
                           JOIN Product p ON p.name = pop.product_name
                           WHERE pop.order_id = po.order_id
                       ) AS items,
                       (
                           SELECT SUM(p.price * pop.quantity)
                           FROM Pizza_Order_Product pop
                           JOIN Product p ON p.name = pop.product_name
                           WHERE pop.order_id = po.order_id
                       ) AS total_price
                FROM Pizza_Order po
                ORDER BY po.datetime DESC
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT po.order_id, po.client_name, po.datetime, po.status, po.address,
                       (
                           SELECT STRING_AGG(p.name + ' x' + CAST(pop.quantity AS VARCHAR), ', ')
                           FROM Pizza_Order_Product pop
                           JOIN Product p ON p.name = pop.product_name
                           WHERE pop.order_id = po.order_id
                       ) AS items,
                       (
                           SELECT SUM(p.price * pop.quantity)
                           FROM Pizza_Order_Product pop
                           JOIN Product p ON p.name = pop.product_name
                           WHERE pop.order_id = po.order_id
                       ) AS total_price
                FROM Pizza_Order po
                WHERE po.client_username = :username
                ORDER BY po.datetime DESC
            ");
            $stmt->execute(['username' => $username]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fout bij ophalen bestellingen: " . $e->getMessage());
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
        // Validation
        if (empty($username) || empty($password) || empty($confirmPassword)) {
            return "Username and password fields are required.";
        }

        if ($password !== $confirmPassword) {
            return "Passwords do not match.";
        }

        // Check if username exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM [User] WHERE username = :username");
        $stmt->execute(['username' => $username]);

        if ($stmt->fetchColumn() > 0) {
            return "Username already exists. Please choose another.";
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user with role Client
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

        // Success
        return null;

    } catch (PDOException $e) {
        error_log("Signup error: " . $e->getMessage());
        return "An error occurred while creating your account. Please try again later.";
    }
}

?>