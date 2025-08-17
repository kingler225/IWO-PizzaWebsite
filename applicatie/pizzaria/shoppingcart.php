<?php
session_start();
require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

$conn = maakVerbinding();

$error = '';

/* ---- Inline CSRF ---- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
/* ---------------------- */

/* POST-acties: altijd CSRF valideren */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf'] ?? '';
    if (!$postedToken || !hash_equals($_SESSION['csrf'], $postedToken)) {
        http_response_code(403);
        exit('CSRF-validatie mislukt');
    }

    // Item verwijderen
    if (isset($_POST['remove'])) {
        $removeName = (string) $_POST['remove'];
        if (isset($_SESSION['cart'][$removeName])) {
            unset($_SESSION['cart'][$removeName]);
        }
        header("Location: shoppingcart.php"); // PRG-patroon
        exit;
    }

    // Bestelling plaatsen (checkout)
    if (isset($_POST['checkout'])) {
        $cart = $_SESSION['cart'] ?? [];
        $username = $_SESSION['user']['username'] ?? null;
        $clientName = $_POST['name'] ?? null;
        $address = trim($_POST['address'] ?? '');

        if (!empty($cart) && $address !== '') {
            $orderId = plaatsBestelling($conn, $username, $clientName, $address, $cart);

            if ($orderId !== null) {
                unset($_SESSION['cart']);
                header("Location: orderdetails.php?order_id=" . urlencode((string) $orderId));
                exit;
            } else {
                $error = "Er ging iets mis bij het plaatsen van je bestelling.";
            }
        } else {
            $error = "Vul alle verplichte velden in.";
        }
    }
}

/* Winkelmand laden en berekenen */
$cart = $_SESSION['cart'] ?? [];
$data = haalProductenUitWinkelmand($conn, $cart);
$cartItems = $data['items'] ?? [];
$total = $data['total'] ?? 0.0;
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <title>Winkelmand</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Jouw winkelmand</h1>

        <?php if (empty($cartItems)): ?>
            <p>Je winkelmand is leeg.</p>
        <?php else: ?>
            <?php if (!empty($error)): ?>
                <p role="alert" style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <!-- Verwijderen van items -->
            <form method="POST" action="">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <table>
                    <thead>
                        <tr>
                            <th>Artikel</th>
                            <th>Aantal</th>
                            <th>Stukprijs</th>
                            <th>Subtotaal</th>
                            <th>Verwijderen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td>€<?= number_format((float) $item['price'], 2, ',', '.') ?></td>
                                <td>€<?= number_format((float) $item['subtotal'], 2, ',', '.') ?></td>
                                <td>
                                    <button type="submit" name="remove"
                                        value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        Verwijderen
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>

            <h2>Totaal: €<?= number_format((float) $total, 2, ',', '.') ?></h2>

            <!-- Afrekenen -->
            <form method="POST" action="">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <?php if (!isset($_SESSION['user'])): ?>
                    <label>Jouw naam:
                        <input type="text" name="name" required>
                    </label><br>
                <?php else: ?>
                    <input type="hidden" name="name"
                        value="<?= htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>

                <label>Bezorgadres:
                    <input type="text" name="address" required>
                </label><br><br>

                <button type="submit" name="checkout">Bestelling plaatsen</button>
            </form>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. Alle rechten voorbehouden. |
            <a href="privacystatement.php">Privacyverklaring</a>
        </p>
    </footer>

</body>

</html>