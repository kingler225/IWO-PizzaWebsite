<?php
require_once __DIR__ . '/../DBConnections/db_querys.php';

session_start();
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $error = loginGebruiker($_POST); // returns error message or redirects
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Pizza Order</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
    <h1>Login</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="username">Gebruikersnaam:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Wachtwoord:</label>
        <input type="password" id="password" name="password" required><br><br>

        <button type="submit">Login</button>
    </form>

    <p>Heb je nog geen account? <a href="signup.php">Registreer hier</a></p>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Pizzeria. All rights reserved.</p>
</footer>
</body>
</html>
