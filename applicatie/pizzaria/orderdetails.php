<?php
session_start();
require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

if (!isset($_GET['order_id'])) {
    echo "No order specified.";
    exit;
}

$orderId = (int) $_GET['order_id'];
$conn = maakVerbinding();
$stmt = $conn->prepare("
    SELECT po.*, u.first_name, u.last_name
    FROM Pizza_Order po
    LEFT JOIN [User] u ON po.client_username = u.username
    WHERE po.order_id = :id
");
$stmt->execute(['id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT product_name, quantity
    FROM Pizza_Order_Product
    WHERE order_id = :id
");
$stmt->execute(['id' => $orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order #<?= htmlspecialchars($orderId) ?> Details</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Order #<?= htmlspecialchars($orderId) ?> Details</h1>

        <?php if (!$order): ?>
            <p>Order not found.</p>
        <?php else: ?>
            <p><strong>Customer:</strong> <?= htmlspecialchars($order['client_name']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
            <p><strong>Date/Time:</strong> <?= htmlspecialchars($order['datetime']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>

            <h2>Items</h2>
            <ul>
                <?php foreach ($items as $item): ?>
                    <li><?= htmlspecialchars($item['quantity']) ?>x <?= htmlspecialchars($item['product_name']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina</p>
    </footer>
</body>

</html>