<?php
// Load from environment (or .env via vlucas/phpdotenv)
$db_host = getenv('DB_HOST') ?: 'localhost,1434';
$db_name = getenv('DB_NAME') ?: 'pizzeria';
$db_user = getenv('DB_USER') ?: 'sa';
$db_password = getenv('DB_PASSWORD') ?: 'abc123!@#';
