<?php
session_start();

require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

$conn = maakVerbinding();
$gegroepeerd = getProductenPerCategorie();

$orderSummary = [];
$totalPrice = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producten = $_POST['products'] ?? [];

    // Save to session as cart
    foreach ($producten as $name => $qty) {
        if ((int)$qty > 0) {
            $_SESSION['cart'][$name] = (int)$qty;
        }
    }

    // Prepare preview summary using helper
    if (!empty($producten)) {
        $result = haalSamenvattingVanProducten($conn, $producten);
        $orderSummary = $result['summary'];
        $totalPrice = $result['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu - Pizza Order</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
    <h1>Menu</h1>

    <form method="POST" action="">
        <?php foreach ($gegroepeerd as $type => $producten): ?>
            <h2><?= htmlspecialchars($type) ?></h2>
            <?php foreach ($producten as $product): ?>
                <label>
                    <?= htmlspecialchars($product['name']) ?> - €<?= number_format($product['price'], 2, ',', '.') ?>
                    <input type="number" name="products[<?= htmlspecialchars($product['name']) ?>]" min="0" max="10" value="0">
                </label><br>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <br><button type="submit">Preview Order</button>
    </form>

    <?php if (!empty($orderSummary)): ?>
        <hr>
        <h2>Order Preview</h2>
        <ul>
            <?php foreach ($orderSummary as $item): ?>
                <li><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?> – €<?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></li>
            <?php endforeach; ?>
        </ul>
        <p><strong>Total: €<?= number_format($totalPrice, 2, ',', '.') ?></strong></p>
        <a href="shoppingcart.php"><button>Proceed to Shopping Cart</button></a>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. All rights reserved. |
        <a href="privacystatement.php">Privacy Statement</a>
    </p>
</footer>
</body>
</html>
