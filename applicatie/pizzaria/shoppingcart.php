<?php
session_start();
require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

$conn = maakVerbinding();

// Handle item removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    unset($_SESSION['cart'][$_POST['remove']]);
    header("Location: shoppingcart.php");
    exit;
}

// Load cart contents from session via db_querys helper
$cart = $_SESSION['cart'] ?? [];
$data = haalProductenUitWinkelmand($conn, $cart);
$cartItems = $data['items'];
$total = $data['total'];

// Handle checkout confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $cart = $_SESSION['cart'] ?? [];
    $username = $_SESSION['user']['username'] ?? null;
    $clientName = $_POST['name'] ?? null;
    $address = $_POST['address'] ?? null;

    if (!empty($cart) && !empty($address)) {
        $orderId = plaatsBestelling($conn, $username, $clientName, $address, $cart);

        if ($orderId !== null) {
            unset($_SESSION['cart']);
            header("Location: orderdetails.php?order_id=$orderId");
            exit;
        } else {
            $error = "Something went wrong while placing your order.";
        }
    } else {
        $error = "Please provide all required information.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
    <h1>Your Shopping Cart</h1>

    <?php if (empty($cartItems)): ?>
    <p>Your cart is empty.</p>
<?php else: ?>
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <table border="1" width="100%">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>€<?= number_format($item['price'], 2, ',', '.') ?></td>
                        <td>€<?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                        <td>
                            <button type="submit" name="remove" value="<?= htmlspecialchars($item['name']) ?>">Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>

    <h2>Total: €<?= number_format($total, 2, ',', '.') ?></h2>

    <form method="POST">
        <?php if (!isset($_SESSION['user'])): ?>
            <label>Your Name: <input type="text" name="name" required></label><br>
        <?php else: ?>
            <input type="hidden" name="name" value="<?= htmlspecialchars($_SESSION['user']['username']) ?>">
        <?php endif; ?>
        <label>Delivery Address: <input type="text" name="address" required></label><br><br>

        <button type="submit" name="checkout">Place Order</button>
    </form>
<?php endif; ?>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. All rights reserved. |
        <a href="privacystatement.php">Privacy Statement</a>
    </p>
</footer>

</body>
</html>
