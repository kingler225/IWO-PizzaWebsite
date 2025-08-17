<?php
session_start();
require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$conn = maakVerbinding();
$username = $_SESSION['user']['username'];
$userData = haalGebruikerOp($conn, $username);

$success = '';
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

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== '') {
        if ($newPassword === $confirmPassword) {
            $result = werkGebruikerBij($conn, $username, $firstName, $lastName, $address, $newPassword);
        } else {
            $result = "Wachtwoorden komen niet overeen.";
        }
    } else {
        $result = werkGebruikerBij($conn, $username, $firstName, $lastName, $address);
    }

    if ($result === true) {
        $success = "Profiel succesvol bijgewerkt.";
        $userData = haalGebruikerOp($conn, $username); // Vernieuw gegevens
    } else {
        $error = $result; // Verwacht string met foutmelding
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <title>Jouw profiel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Beheer je profiel</h1>

        <?php if ($success): ?>
            <p role="alert" style="color: green;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($error): ?>
            <p role="alert" style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <label for="username">Gebruikersnaam (alleen-lezen):</label><br>
            <input type="text" id="username"
                value="<?= htmlspecialchars($userData['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly><br><br>

            <label for="first_name">Voornaam:</label><br>
            <input type="text" id="first_name" name="first_name"
                value="<?= htmlspecialchars($userData['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="given-name"><br><br>

            <label for="last_name">Achternaam:</label><br>
            <input type="text" id="last_name" name="last_name"
                value="<?= htmlspecialchars($userData['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="family-name"><br><br>

            <label for="address">Adres:</label><br>
            <input type="text" id="address" name="address"
                value="<?= htmlspecialchars($userData['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="street-address"><br><br>

            <hr>
            <h3>Wachtwoord wijzigen</h3>

            <label for="new_password">Nieuw wachtwoord:</label><br>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password"><br><br>

            <label for="confirm_password">Bevestig nieuw wachtwoord:</label><br>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password"><br><br>

            <button type="submit">Wijzigingen opslaan</button>
        </form>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. Alle rechten voorbehouden. |
            <a href="privacystatement.php">Privacyverklaring</a>
        </p>
    </footer>
</body>

</html>