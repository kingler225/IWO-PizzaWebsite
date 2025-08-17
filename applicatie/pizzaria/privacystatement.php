<?php session_start(); ?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <title>Privacyverklaring - Pizzeria Sole Machina</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

    <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

    <main>
        <h1>Privacyverklaring</h1>

        <p>Bij Pizzeria Sole Machina hechten we veel waarde aan de bescherming van jouw persoonsgegevens.
            In deze privacyverklaring lees je welke gegevens we verzamelen, waarom we die verwerken en welke rechten je
            hebt op grond van de AVG.</p>

        <h2>Welke gegevens verzamelen wij?</h2>
        <ul>
            <li>Identificatiegegevens zoals naam en e-mailadres</li>
            <li>Inlog- en gebruikersgegevens (gebruikersnaam en rol)</li>
            <li>Bezorgadressen voor bestellingen</li>
            <li>Bestelgeschiedenis (artikelen en aantallen)</li>
            <li>Eventuele feedback of contactaanvragen</li>
        </ul>

        <h2>Waarom verwerken wij jouw gegevens?</h2>
        <ul>
            <li>Om bestellingen correct te verwerken en te bezorgen</li>
            <li>Om jouw gebruikerservaring te verbeteren</li>
            <li>Om je eenvoudig toegang te geven tot je bestelgeschiedenis</li>
            <li>Om onze diensten en ons menu te verbeteren</li>
            <li>Om je (alleen met jouw toestemming) promotionele berichten te sturen</li>
        </ul>

        <h2>Bewaartermijnen</h2>
        <p>Wij bewaren persoonsgegevens niet langer dan noodzakelijk. Je bestelgeschiedenis wordt bewaard zolang je
            account actief is.
            Je kunt op ieder moment verzoeken om verwijdering van je account en bijbehorende gegevens.</p>

        <h2>Wie heeft toegang tot jouw gegevens?</h2>
        <p>Alleen geautoriseerd personeel van Pizzeria Sole Machina heeft toegang tot jouw gegevens.
            Wij verkopen of delen jouw gegevens niet met derden, behalve waar dit nodig is om je bestelling uit te
            voeren
            (bijvoorbeeld betaalproviders of bezorgdiensten).</p>

        <h2>Cookies</h2>
        <p>Wij gebruiken uitsluitend functionele cookies voor sessiebeheer (zoals je inlogstatus).
            Er worden geen tracking- of third-party cookies geplaatst.</p>

        <h2>Jouw rechten</h2>
        <p>Op grond van de Algemene Verordening Gegevensbescherming (AVG) heb je onder meer recht op:</p>
        <ul>
            <li>Inzage in jouw persoonsgegevens</li>
            <li>Rectificatie (verbetering) of verwijdering van jouw gegevens</li>
            <li>Beperking van de verwerking</li>
            <li>Dataportabiliteit (overdraagbaarheid van jouw gegevens)</li>
            <li>Bezwaar tegen (bepaalde) verwerkingen</li>
        </ul>
        <p>Om een recht uit te oefenen kun je contact opnemen via
            <a href="mailto:privacy@solemachina.nl">privacy@solemachina.nl</a>.
            Je hebt ook het recht een klacht in te dienen bij de Autoriteit Persoonsgegevens.
        </p>

        <h2>Beveiligingsmaatregelen</h2>
        <p>Wij beschermen jouw gegevens met moderne beveiligingspraktijken zoals wachtwoord-hashing,
            prepared statements tegen SQL-injectie en veilig sessiebeheer.</p>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. Alle rechten voorbehouden. |
            <a href="privacystatement.php">Privacyverklaring</a>
        </p>
    </footer>

</body>

</html>