<?php
session_start();

require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

$conn = maakVerbinding();
$gegroepeerd = getProductenPerCategorie();

$orderSummary = [];
$totalPrice = 0.0;

/* ---- Eenvoudige inline CSRF ---- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
/* -------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valideer CSRF-token
    $postedToken = $_POST['csrf'] ?? '';
    if (!$postedToken || !hash_equals($_SESSION['csrf'], $postedToken)) {
        http_response_code(403);
        exit('CSRF-validatie mislukt');
    }

    $producten = $_POST['products'] ?? [];

    // Sla op in sessie als winkelmand
    foreach ($producten as $naam => $aantal) {
        $qty = filter_var($aantal, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 10]
        ]);
        if ($qty === false) {
            continue;
        }
        if ($qty > 0) {
            $_SESSION['cart'][$naam] = $qty;
        } else {
            unset($_SESSION['cart'][$naam]);
        }
    }

    // Voorbeeldoverzicht opbouwen via helper
    if (!empty($producten)) {
        $result = haalSamenvattingVanProducten($conn, $producten);
        $orderSummary = $result['summary'];
        $totalPrice = $result['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Menu - Pizzeria</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Menu</h1>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <?php foreach ($gegroepeerd as $type => $producten): ?>
                <h2><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></h2>

                <?php foreach ($producten as $product): ?>
                    <label>
                        <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                        – €<?= number_format((float) $product['price'], 2, ',', '.') ?>
                        <input type="number" name="products[<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>]"
                            min="0" max="10" value="0" inputmode="numeric"
                            aria-label="Aantal voor <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                    </label><br>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <br>
            <button type="submit">Voorbeeld tonen</button>
        </form>

        <?php if (!empty($orderSummary)): ?>
            <hr>
            <h2>Voorbeeld van bestelling</h2>
            <ul>
                <?php foreach ($orderSummary as $item): ?>
                    <li>
                        <?= (int) $item['quantity'] ?>×
                        <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                        – €<?= number_format((float) $item['price'] * (int) $item['quantity'], 2, ',', '.') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Totaal: €<?= number_format((float) $totalPrice, 2, ',', '.') ?></strong></p>

            <!-- Naar winkelmand als echte knop (semantisch correct) -->
            <form action="shoppingcart.php" method="get">
                <button type="submit">Ga naar winkelmand</button>
            </form>
        <?php endif; ?>
    </main>

    <footer>
        <p>
            &copy; <?= date('Y') ?> Pizzeria Sole Machina. Alle rechten voorbehouden. |
            <a href="privacystatement.php">Privacyverklaring</a>
        </p>
    </footer>
</body>

</html>