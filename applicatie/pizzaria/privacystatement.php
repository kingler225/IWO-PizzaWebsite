<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Statement - Pizzeria Sole Machina</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
    <h1>Privacy Statement</h1>

    <p>At Pizzeria Sole Machina, we highly value the protection of your personal data. In this privacy statement, you will find what information we collect, why we collect it, and what your rights are under GDPR.</p>

    <h2>Information We Collect</h2>
    <ul>
        <li>Identification data such as name and email address</li>
        <li>Login and user information (username and role)</li>
        <li>Delivery addresses for orders</li>
        <li>Order history (items and quantities)</li>
        <li>Feedback or contact inquiries</li>
    </ul>

    <h2>Why We Collect Your Data</h2>
    <ul>
        <li>To correctly process and deliver your orders</li>
        <li>To enhance your user experience</li>
        <li>To provide you with easy access to your order history</li>
        <li>To improve our services and menu offerings</li>
        <li>To send promotional communications (only with your consent)</li>
    </ul>

    <h2>Data Retention</h2>
    <p>We retain your personal data only for as long as necessary. Order history is kept as long as your account remains active. You may request deletion at any time.</p>

    <h2>Who Has Access to Your Data?</h2>
    <p>Only authorized personnel of Pizzeria Sole Machina have access to your data. We do not sell or share your information with third parties, except for services necessary to complete your order (e.g., payment processors, delivery companies).</p>

    <h2>Cookies</h2>
    <p>We use functional cookies for session management (such as login status). No tracking or third-party cookies are used.</p>

    <h2>Your Rights</h2>
    <p>Under the General Data Protection Regulation (GDPR), you have the right to:</p>
    <ul>
        <li>Access your personal data</li>
        <li>Request correction or deletion of your data</li>
        <li>Object to the processing of your data</li>
    </ul>
    <p>To exercise your rights, please contact us at <a href="mailto:privacy@solemachina.nl">privacy@solemachina.nl</a>.</p>

    <h2>Security Measures</h2>
    <p>We protect your data using modern security practices such as password hashing, prepared SQL statements to prevent injection, and secure session management.</p>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Pizzeria Sole Machina. All rights reserved. |
        <a href="privacystatement.php">Privacy Statement</a>
    </p>
</footer>

</body>
</html>
