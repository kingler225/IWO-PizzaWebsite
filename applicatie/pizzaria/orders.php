<?php
session_start();

if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

$conn = maakVerbinding();
$role = $_SESSION['user']['role'] ?? null;
$username = $_SESSION['user']['username'] ?? null;
$isPersonnel = ($role === 'Personnel');

/* ---- Inline CSRF ---- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
/* ---------------------- */

/* Statuslabels (NL) */
$statusLabels = [
    1 => 'Ontvangen',
    2 => 'In behandeling',
    3 => 'Afgerond',
];

/* Handteer status-update (alleen Personeel) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isPersonnel) {
    $postedToken = $_POST['csrf'] ?? '';
    if (!$postedToken || !hash_equals($_SESSION['csrf'], $postedToken)) {
        http_response_code(403);
        exit('CSRF-validatie mislukt');
    }

    $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $newStatus = filter_var($_POST['new_status'] ?? null, FILTER_VALIDATE_INT);

    if ($orderId > 0 && in_array($newStatus, [1, 2, 3], true)) {
        wijzigBestelStatus($conn, $orderId, $newStatus);
    }
    header("Location: orders.php");
    exit;
}

/* Haal bestellingen op via helper (autorisatielogica in helper) */
$orders = haalBestellingenOp($conn, $role, $username);

/* Titel/kop */
$pageTitle = $isPersonnel ? 'Alle bestellingen' : 'Mijn bestellingen';
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if (empty($orders)): ?>
            <p>Geen bestellingen gevonden.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Bestelnr.</th>
                        <th>Klant</th>
                        <th>Adres</th>
                        <th>Datum/tijd</th>
                        <th>Status</th>
                        <?php if ($isPersonnel): ?>
                            <th>Actie</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $orderId = (int) $order['order_id'];
                        $klantWeergave = $order['klant_weergave']
                            ?? ($order['client_name'] ?? $order['client_username'] ?? '');
                        $status = (int) ($order['status'] ?? 0);
                        ?>
                        <tr id="order<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8') ?>">
                            <td>
                                <a href="orderdetails.php?order_id=<?= urlencode((string) $orderId) ?>">
                                    #<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($klantWeergave, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($order['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($order['datetime'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($isPersonnel): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="csrf"
                                            value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="order_id"
                                            value="<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8') ?>">
                                        <select name="new_status"
                                            aria-label="Wijzig status voor bestelling #<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8') ?>">
                                            <option value="1" <?= $status === 1 ? 'selected' : '' ?>><?= $statusLabels[1] ?></option>
                                            <option value="2" <?= $status === 2 ? 'selected' : '' ?>><?= $statusLabels[2] ?></option>
                                            <option value="3" <?= $status === 3 ? 'selected' : '' ?>><?= $statusLabels[3] ?></option>
                                        </select>
                                        <button type="submit">Bijwerken</button>
                                    </form>
                                <?php else: ?>
                                    <?= htmlspecialchars($statusLabels[$status] ?? 'Onbekend', ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($isPersonnel): ?>
                                <td><!-- Eventuele extra actie voor personeel --></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria. Alle rechten voorbehouden.</p>
    </footer>
</body>

</html>