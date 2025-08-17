<?php
if (session_status() === PHP_SESSION_NONE) {
        session_start();
}

$current = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user']['role'] ?? null;
$username = $_SESSION['user']['username'] ?? null;

// Bepaal basispad als we binnen /pizzaria/ zitten
$inPizzaria = str_contains($_SERVER['PHP_SELF'], '/pizzaria/');
$basePath = $inPizzaria ? '../' : '';
?>

<nav>
        <ul>
                <li>
                        <a href="<?= $basePath ?>index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>"
                                <?= $current === 'index.php' ? 'aria-current="page"' : '' ?>>
                                Home
                        </a>
                </li>

                <?php if ($role === 'Client'): ?>
                        <li>
                                <a href="<?= $basePath ?>pizzaria/menu.php"
                                        class="<?= $current === 'menu.php' ? 'active' : '' ?>" <?= $current === 'menu.php' ? 'aria-current="page"' : '' ?>>
                                        Menu
                                </a>
                        </li>
                        <li>
                                <a href="<?= $basePath ?>pizzaria/shoppingcart.php"
                                        class="<?= $current === 'shoppingcart.php' ? 'active' : '' ?>"
                                        <?= $current === 'shoppingcart.php' ? 'aria-current="page"' : '' ?>>
                                        Winkelmand
                                </a>
                        </li>
                        <li>
                                <a href="<?= $basePath ?>pizzaria/orders.php"
                                        class="<?= $current === 'orders.php' ? 'active' : '' ?>" <?= $current === 'orders.php' ? 'aria-current="page"' : '' ?>>
                                        Mijn bestellingen
                                </a>
                        </li>
                <?php elseif ($role === 'Personnel'): ?>
                        <li>
                                <a href="<?= $basePath ?>pizzaria/orders.php"
                                        class="<?= $current === 'orders.php' ? 'active' : '' ?>" <?= $current === 'orders.php' ? 'aria-current="page"' : '' ?>>
                                        Alle bestellingen
                                </a>
                        </li>
                <?php endif; ?>

                <li>
                        <a href="<?= $basePath ?>pizzaria/privacystatement.php"
                                class="<?= $current === 'privacystatement.php' ? 'active' : '' ?>"
                                <?= $current === 'privacystatement.php' ? 'aria-current="page"' : '' ?>>
                                Privacyverklaring
                        </a>
                </li>

                <?php if (!isset($_SESSION['user'])): ?>
                        <li>
                                <a href="<?= $basePath ?>pizzaria/login.php"
                                        class="<?= $current === 'login.php' ? 'active' : '' ?>" <?= $current === 'login.php' ? 'aria-current="page"' : '' ?>>
                                        Inloggen
                                </a>
                        </li>
                        <li>
                                <a href="<?= $basePath ?>pizzaria/signup.php"
                                        class="<?= $current === 'signup.php' ? 'active' : '' ?>" <?= $current === 'signup.php' ? 'aria-current="page"' : '' ?>>
                                        Registreren
                                </a>
                        </li>
                <?php else: ?>
                        <!-- Toon Profiel alleen als ingelogd -->
                        <li>
                                <a href="<?= $basePath ?>pizzaria/profile.php"
                                        class="<?= $current === 'profile.php' ? 'active' : '' ?>" <?= $current === 'profile.php' ? 'aria-current="page"' : '' ?>>
                                        Mijn profiel
                                </a>
                        </li>
                        <li>
                                <a href="<?= $basePath ?>pizzaria/logout.php">
                                        Uitloggen (<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>)
                                </a>
                        </li>
                <?php endif; ?>
        </ul>
</nav>