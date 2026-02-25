<?php
$db = 'db_cement';
$user = $argv[1] ?? 'root';
$pass = $argv[2] ?? '';
$mysqli = new mysqli('127.0.0.1', $user, $pass, $db);
if ($mysqli->connect_errno) { echo "Connect failed: " . $mysqli->connect_error . "\n"; exit(1); }

$res = $mysqli->query("SELECT MAX(VoucherID) AS mx FROM vouchers");
if (!$res) { echo "Query failed: " . $mysqli->error . "\n"; exit(1); }
$row = $res->fetch_assoc();
$mx = intval($row['mx']);
$start = $mx + 1;
echo "Current max VoucherID = $mx; setting AUTO_INCREMENT start=$start\n";

$alter = "ALTER TABLE vouchers MODIFY VoucherID INT NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=$start";
if (!$mysqli->query($alter)) {
    echo "ALTER TABLE failed: " . $mysqli->error . "\n";
    exit(1);
}
echo "ALTER TABLE succeeded.\n";
$mysqli->close();
