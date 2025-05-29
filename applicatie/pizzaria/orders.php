<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

$role = $_SESSION['user']['role'];
$username = $_SESSION['user']['username'];

$conn = maakVerbinding();
$orders = haalBestellingenOp($conn, $role, $username);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $role === 'Personnel' ? 'All Orders' : 'My Orders' ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <script>
        function markAsCompleted(rowId) {
            document.getElementById(rowId).style.textDecoration = "line-through";
        }
    </script>
</head>

<body>
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1><?= $role === 'Personnel' ? 'All Orders' : 'My Orders' ?></h1>

        <?php if (empty($orders)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <table border="1" width="100%" cellpadding="10" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Items</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <?php if ($role === 'Personnel'): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $rowId = 'order' . htmlspecialchars($order['order_id']);
                        ?>
                        <tr id="<?= $rowId ?>">
                            <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                            <td><?= htmlspecialchars($order['client_name']) ?></td>
                            <td><?= htmlspecialchars($order['items']) ?></td>
                            <td>€<?= number_format($order['total_price'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <?php if ($role === 'Personnel'): ?>
                                <td><button onclick="markAsCompleted('<?= $rowId ?>')">Complete</button></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria. All rights reserved.</p>
    </footer>
</body>

</html>