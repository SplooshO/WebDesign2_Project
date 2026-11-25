<?php
// db.php - PDO connection to the gaming_club database (XAMPP defaults).
// Usage on any page:  require 'db.php';   then use $pdo

$host    = 'localhost';
$dbname  = 'gamingdb';
$dbuser  = 'root';   // XAMPP default
$dbpass  = '';       // XAMPP default is an empty password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw errors instead of failing silently
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows come back as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                    // use real prepared statements
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    // Log the real error for yourself, but don't show details to visitors
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Sorry, the database is unavailable right now.');
}
