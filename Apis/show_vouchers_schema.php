<?php
$db = 'db_cement';
$user = $argv[1] ?? 'root';
$pass = $argv[2] ?? '';
$mysqli = new mysqli('127.0.0.1', $user, $pass, $db);
if ($mysqli->connect_errno) { echo "Connect failed: " . $mysqli->connect_error . "\n"; exit(1); }
$res = $mysqli->query("SHOW CREATE TABLE vouchers");
if (!$res) { echo "Query failed: " . $mysqli->error . "\n"; exit(1); }
$row = $res->fetch_assoc();
echo $row['Create Table'] . "\n";
$mysqli->close();
