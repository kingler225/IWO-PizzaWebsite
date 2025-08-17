<?php
session_start();

require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

/* Vereist ingelogde gebruiker */
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$rol = $_SESSION['user']['role'] ?? null;
$gebruikersnaam = $_SESSION['user']['username'] ?? null;

/* Valideer order-id */
if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo 'Geen bestelling opgegeven.';
    exit;
}
$orderId = (int) $_GET['order_id'];
if ($orderId <= 0) {
    http_response_code(400);
    echo 'Ongeldig bestelnummer.';
    exit;
}

$conn = maakVerbinding();

/* Gebruik query-helpers uit db_querys.php */
$order = haalBestellingOp($conn, $orderId, $rol, $gebruikersnaam);
$items = $order ? haalBestellingItemsOp($conn, $orderId) : [];
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <title>Bestelling #<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8') ?> - Details</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Bestelling #<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8') ?> - Details</h1>

        <?php if (!$order): ?>
            <p>Bestelling niet gevonden of je hebt geen toegang.</p>
        <?php else: ?>
            <p><strong>Klant:</strong> <?= htmlspecialchars($order['klant_weergave'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Adres:</strong> <?= htmlspecialchars($order['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Datum/tijd:</strong> <?= htmlspecialchars($order['datetime'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($order['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

            <h2>Artikelen</h2>
            <?php if (empty($items)): ?>
                <p>Geen artikelen gevonden voor deze bestelling.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li>
                            <?= (int) $item['quantity'] ?>×
                            <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. Alle rechten voorbehouden.</p>
    </footer>
</body>

</html>