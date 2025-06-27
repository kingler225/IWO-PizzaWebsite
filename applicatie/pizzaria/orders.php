<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

$conn = maakVerbinding();

$role = $_SESSION['user']['role'];
$username = $_SESSION['user']['username'];

// Handle status update if Personnel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status']) && $role === 'Personnel') {
    $orderId = (int) $_POST['order_id'];
    $newStatus = (int) $_POST['new_status'];
    updateOrderStatus($conn, $orderId, $newStatus);
    header("Location: orders.php");
    exit;
}

$orders = haalBestellingenOp($conn, $role, $username);

// Optional: status labels for better readability
$statusLabels = [
    1 => 'Received',
    2 => 'In Progress',
    3 => 'Completed'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $role === 'Personnel' ? 'All Orders' : 'My Orders' ?></title>
    <link rel="stylesheet" href="../css/styles.css">
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
                        <th>Address</th>
                        <th>Date/Time</th>
                        <th>Status</th>
                        <?php if ($role === 'Personnel'): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr id="order<?= htmlspecialchars($order['order_id']) ?>">
                            <td>
                                <a href="orderdetails.php?order_id=<?= urlencode($order['order_id']) ?>">
                                    #<?= htmlspecialchars($order['order_id']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($order['client_name'] ?? $order['client_username']) ?></td>
                            <td><?= htmlspecialchars($order['address']) ?></td>
                            <td><?= htmlspecialchars($order['datetime']) ?></td>
                            <td>
                                <?php if ($role === 'Personnel'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <select name="new_status">
                                            <option value="1" <?= $order['status'] == 1 ? 'selected' : '' ?>>Received</option>
                                            <option value="2" <?= $order['status'] == 2 ? 'selected' : '' ?>>In Progress</option>
                                            <option value="3" <?= $order['status'] == 3 ? 'selected' : '' ?>>Completed</option>
                                        </select>
                                        <button type="submit">Update</button>
                                    </form>
                                <?php else: ?>
                                    <?= $statusLabels[$order['status']] ?? 'Unknown' ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($role === 'Personnel'): ?>
                                <td><!-- Optional: Add extra personnel-only action here --></td>
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