<?php                 // bevat sessie + (optioneel) CSRF helpers
require_once __DIR__ . '/../DBConnections/db_querys.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $error = loginGebruiker($_POST); // geeft foutmelding terug of doet redirect bij succes
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Inloggen - Pizzeria</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Inloggen</h1>

        <?php if ($error): ?>
            <p role="alert" style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <?= function_exists('csrf_field') ? csrf_field() : '' ?>

            <label for="username">Gebruikersnaam</label>
            <input type="text" id="username" name="username" required autocomplete="username">

            <label for="password">Wachtwoord</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit">Inloggen</button>
        </form>

        <p>Heb je nog geen account? <a href="signup.php">Registreer hier</a></p>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria. Alle rechten voorbehouden.</p>
    </footer>
</body>

</html>