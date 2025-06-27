<?php
session_start();
require_once __DIR__ . '/../DBConnections/db_connection.php';
require_once __DIR__ . '/../DBConnections/db_querys.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$conn = maakVerbinding();
$username = $_SESSION['user']['username'];
$userData = getUserByUsername($conn, $username);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!empty($newPassword)) {
        if ($newPassword === $confirmPassword) {
            $result = updateUserProfile($conn, $username, $firstName, $lastName, $address, $newPassword);
        } else {
            $result = "Passwords do not match.";
        }
    } else {
        $result = updateUserProfile($conn, $username, $firstName, $lastName, $address);
    }

    if ($result === true) {
        $success = "Profile updated successfully.";
        $userData = getUserByUsername($conn, $username); // Refresh user data
    } else {
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Your Profile</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Manage Your Profile</h1>

        <?php if ($success): ?>
            <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <?php elseif ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="username">Username (readonly):</label><br>
            <input type="text" id="username" value="<?= htmlspecialchars($userData['username']) ?>" readonly><br><br>

            <label for="first_name">First Name:</label><br>
            <input type="text" name="first_name" value="<?= htmlspecialchars($userData['first_name'] ?? '') ?>"><br><br>

            <label for="last_name">Last Name:</label><br>
            <input type="text" name="last_name" value="<?= htmlspecialchars($userData['last_name'] ?? '') ?>"><br><br>

            <label for="address">Address:</label><br>
            <input type="text" name="address" value="<?= htmlspecialchars($userData['address'] ?? '') ?>"><br><br>

            <hr>
            <h3>Change Password</h3>
            <label for="new_password">New Password:</label><br>
            <input type="password" name="new_password"><br><br>

            <label for="confirm_password">Confirm Password:</label><br>
            <input type="password" name="confirm_password"><br><br>

            <button type="submit">Save Changes</button>
        </form>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. All rights reserved. |
            <a href="privacystatement.php">Privacy Statement</a>
        </p>
    </footer>
</body>

</html>