<!-- Why do I need to have connect.php -->

<?php
// what are these for?
$host     = 'localhost';
$dbname   = 'OUANO_SIBI';
$username = 'root';
$password = '';

// What is this whole condition for 
try {
    // 1. what is $pdo and PDO, why are they different in terms of formatting
    // 2. what is the $ symbol for 
    // 3. what is the ATTR and ERRMODE
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 1. What is die?
    // 2. where does the json_encode go and what is it 
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}   
