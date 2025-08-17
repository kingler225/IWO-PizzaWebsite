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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valideer CSRF-token
    $postedToken = $_POST['csrf'] ?? '';
    if (!$postedToken || !hash_equals($_SESSION['csrf'], $postedToken)) {
        http_response_code(403);
        exit('CSRF-validatie mislukt');
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $error = registreerNieuweGebruiker(
        $conn,
        $username,
        $password,
        $confirmPassword,
        $firstName,
        $lastName,
        $address
    );

    if (!$error) {
        header("Location: login.php?registered=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <title>Registreren - Pizzeria</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Registreren</h1>

        <?php if (!empty($error)): ?>
            <p role="alert" style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <label for="username">Gebruikersnaam</label><br>
            <input type="text" id="username" name="username" required autocomplete="username"><br><br>

            <label for="password">Wachtwoord</label><br>
            <input type="password" id="password" name="password" required autocomplete="new-password"><br><br>

            <label for="confirm_password">Bevestig wachtwoord</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required
                autocomplete="new-password"><br><br>

            <label for="first_name">Voornaam</label><br>
            <input type="text" id="first_name" name="first_name" autocomplete="given-name"><br><br>

            <label for="last_name">Achternaam</label><br>
            <input type="text" id="last_name" name="last_name" autocomplete="family-name"><br><br>

            <label for="address">Adres</label><br>
            <input type="text" id="address" name="address" autocomplete="street-address"><br><br>

            <button type="submit">Registreren</button>
        </form>

        <p>Heb je al een account? <a href="login.php">Inloggen</a></p>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. Alle rechten voorbehouden. |
            <a href="privacystatement.php">Privacyverklaring</a>
        </p>
    </footer>
</body>

</html>