<?php
// Simple script to update admin password to a known value
// This should be run once to set up the admin account

$host = 'localhost';
$dbname = 'db_cement'; // Update this with your actual database name
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set admin password to 'admin123'
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET Password = ? WHERE UserName = 'admin' AND BusinessID = 1");
    $stmt->execute([$hashedPassword]);
    
    echo "Admin password updated successfully! You can now login with:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "BusinessID: 1\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>