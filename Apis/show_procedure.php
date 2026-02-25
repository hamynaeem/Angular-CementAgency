<?php
$db = 'db_cement';
$user = $argv[1] ?? 'root';
$pass = $argv[2] ?? '';
$name = $argv[3] ?? 'sp_ManageCashbook';
$mysqli = new mysqli('127.0.0.1', $user, $pass, $db);
if ($mysqli->connect_errno) { echo "Connect failed: " . $mysqli->connect_error . "\n"; exit(1); }
$sql = "SELECT ROUTINE_DEFINITION FROM information_schema.routines WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = '" . $mysqli->real_escape_string($name) . "'";
$res = $mysqli->query($sql);
if (!$res) { echo "Query failed: " . $mysqli->error . "\n"; exit(1); }
while ($row = $res->fetch_assoc()) {
    echo "Procedure definition:\n" . $row['ROUTINE_DEFINITION'] . "\n";
}
$mysqli->close();
