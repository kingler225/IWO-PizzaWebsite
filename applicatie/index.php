<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Pizza Order</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main>
    <h1>Welkom bij onze Pizzeria!</h1>
    <p>Bestel snel en gemakkelijk je favoriete pizza's en dranken.</p>

    <div style="margin: 30px;">
        <a href="pizzaria/menu.php"><button>Bekijk het Menu</button></a>
    </div>

    <section style="max-width: 800px; margin: auto; text-align: left;">
        <h2>Wat kun je hier doen?</h2>
        <ul>
            <li><strong>Nieuwe klant?</strong> Maak een account aan en begin met bestellen.</li>
            <li><strong>Pizza fan?</strong> Bekijk ons uitgebreide menu en stel je eigen bestelling samen.</li>
            <li><strong>Account?</strong> Log in om je bestellingen te bekijken of te volgen.</li>
            <li><strong>Personeel?</strong> Bekijk en beheer alle klantbestellingen.</li>
        </ul>
    </section>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Pizzeria. Alle rechten voorbehouden.</p>
</footer>
</body>
</html>
