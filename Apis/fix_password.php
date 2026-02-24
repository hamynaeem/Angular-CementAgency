<?php
// Generate and set a proper password hash
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Generated hash for 'admin123': $hash\n";
echo "Testing verification: " . (password_verify($password, $hash) ? 'SUCCESS' : 'FAILED') . "\n";

// Connect and update
$host = 'localhost';
$dbname = 'db_cement';
$username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("UPDATE users SET Password = ? WHERE UserName = 'admin' AND BusinessID = 1");
    $result = $stmt->execute([$hash]);
    
    echo "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Rows affected: " . $stmt->rowCount() . "\n";
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT Password FROM users WHERE UserName = 'admin' AND BusinessID = 1");
    $stmt->execute();
    $stored_hash = $stmt->fetchColumn();
    
    echo "Stored hash: $stored_hash\n";
    echo "Final verification: " . (password_verify($password, $stored_hash) ? 'SUCCESS' : 'FAILED') . "\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>