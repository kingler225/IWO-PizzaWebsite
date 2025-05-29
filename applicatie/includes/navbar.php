<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user']['role'] ?? null;
$username = $_SESSION['user']['username'] ?? null;

// Detect if we're inside /pizzaria/ folder
$inPizzaria = str_contains($_SERVER['PHP_SELF'], '/pizzaria/');
$basePath = $inPizzaria ? '../' : '';
?>

<nav>
    <ul>
        <li><a href="<?= $basePath ?>index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a></li>

        <?php if ($role === 'Client'): ?>
            <li><a href="<?= $basePath ?>pizzaria/menu.php" class="<?= $current === 'menu.php' ? 'active' : '' ?>">Menu</a></li>
            <li><a href="<?= $basePath ?>pizzaria/shoppingcart.php" class="<?= $current === 'shoppingcart.php' ? 'active' : '' ?>">Cart</a></li>
            <li><a href="<?= $basePath ?>pizzaria/orders.php" class="<?= $current === 'orders.php' ? 'active' : '' ?>">My Orders</a></li>

        <?php elseif ($role === 'Personnel'): ?>
            <li><a href="<?= $basePath ?>pizzaria/orders.php" class="<?= $current === 'orders.php' ? 'active' : '' ?>">All Orders</a></li>
        <?php endif; ?>

        <li><a href="<?= $basePath ?>pizzaria/orderdetails.php" class="<?= $current === 'orderdetails.php' ? 'active' : '' ?>">Order Details</a></li>
        <li><a href="<?= $basePath ?>pizzaria/privacystatement.php" class="<?= $current === 'privacystatement.php' ? 'active' : '' ?>">Privacy</a></li>

        <?php if (!isset($_SESSION['user'])): ?>
            <li><a href="<?= $basePath ?>pizzaria/login.php" class="<?= $current === 'login.php' ? 'active' : '' ?>">Login</a></li>
            <li><a href="<?= $basePath ?>pizzaria/signup.php" class="<?= $current === 'signup.php' ? 'active' : '' ?>">Sign Up</a></li>
        <?php else: ?>
            <li><a href="<?= $basePath ?>pizzaria/logout.php">Logout (<?= htmlspecialchars($username) ?>)</a></li>
        <?php endif; ?>
    </ul>
</nav>
