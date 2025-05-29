<?php
session_start();
require_once __DIR__ . '/../DBConnections/db_connection.php';

$conn = maakVerbinding();

// Get order_id from query
$orderId = $_GET['order_id'] ?? null;
$order = null;
$items = [];
$total = 0;

// Only try to fetch if a valid order ID is given
if ($orderId && is_numeric($orderId)) {
    // 1. Try fetching order
    $stmt = $conn->prepare("SELECT client_name, address FROM Pizza_Order WHERE order_id = :order_id");
    $stmt->execute(['order_id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // 2. Fetch products in the order
        $stmt = $conn->prepare("SELECT product_name, quantity FROM Pizza_Order_Product WHERE order_id = :order_id");
        $stmt->execute(['order_id' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch prices and calculate total
        foreach ($items as &$item) {
            $stmt = $conn->prepare("SELECT price FROM Product WHERE name = :name");
            $stmt->execute(['name' => $item['product_name']]);
            $item['price'] = $stmt->fetchColumn();
            $item['subtotal'] = $item['quantity'] * $item['price'];
            $total += $item['subtotal'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details<?= $order ? " #".htmlspecialchars($orderId) : '' ?></title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
    <h1>Order Details</h1>

    <?php if ($order): ?>
        <h2>Customer Information</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($order['client_name']) ?></p>

        <h2>Delivery Address</h2>
        <p><?= htmlspecialchars($order['address']) ?></p>

        <h2>Order Summary</h2>
        <?php if (!empty($items)): ?>
            <ul>
                <?php foreach ($items as $item): ?>
                    <li><?= htmlspecialchars($item['quantity']) ?>x <?= htmlspecialchars($item['product_name']) ?> –
                        €<?= number_format($item['subtotal'], 2, ',', '.') ?></li>
                <?php endforeach; ?>
            </ul>

            <h2>Total Price: €<?= number_format($total, 2, ',', '.') ?></h2>
        <?php else: ?>
            <p>No products found for this order.</p>
        <?php endif; ?>
    <?php else: ?>
        <p style="color: red;">No order found for this ID.</p>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Pizzeria. All rights reserved.</p>
</footer>
</body>
</html>
