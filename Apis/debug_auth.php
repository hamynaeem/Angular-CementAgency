<?php
// Debug script to check authentication
defined('BASEPATH') or define('BASEPATH', true);

$host = 'localhost';
$dbname = 'db_cement';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DEBUG AUTH ===\n";
    
    // Check user with exact params
    $stmt = $pdo->prepare("SELECT * FROM users WHERE UserName = ? AND BusinessID = ?");
    $stmt->execute(['admin', 1]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User found:\n";
        print_r($user);
        
        // Test password verification
        $testPassword = 'admin123';
        $verified = password_verify($testPassword, $user['Password']);
        echo "\nPassword verification result: " . ($verified ? 'SUCCESS' : 'FAILED') . "\n";
        
    } else {
        echo "User NOT found with UserName='admin' and BusinessID=1\n";
        
        // Check all users
        $stmt = $pdo->query("SELECT UserID, UserName, BusinessID FROM users");
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "All users:\n";
        print_r($allUsers);
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>