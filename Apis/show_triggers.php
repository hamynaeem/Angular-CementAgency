<?php
$db = 'db_cement';
$user = $argv[1] ?? 'root';
$pass = $argv[2] ?? '';
$mysqli = new mysqli('127.0.0.1', $user, $pass, $db);
if ($mysqli->connect_errno) { echo "Connect failed: " . $mysqli->connect_error . "\n"; exit(1); }
$sql = "SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_STATEMENT FROM information_schema.triggers WHERE TRIGGER_SCHEMA = DATABASE()";
$res = $mysqli->query($sql);
if (!$res) { echo "Query failed: " . $mysqli->error . "\n"; exit(1); }
while ($row = $res->fetch_assoc()) {
    echo "Trigger: " . $row['TRIGGER_NAME'] . "\n";
    echo "Event: " . $row['EVENT_MANIPULATION'] . "\n";
    echo "Action: " . $row['ACTION_STATEMENT'] . "\n\n";
}
$mysqli->close();
