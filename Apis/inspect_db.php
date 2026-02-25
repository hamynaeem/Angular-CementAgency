<?php
// Usage: php inspect_db.php [dbname] [dbuser] [dbpass]
$dbname = $argv[1] ?? 'db_cement';
$dbuser = $argv[2] ?? 'root';
$dbpass = $argv[3] ?? '';
$mysqli = new mysqli('127.0.0.1', $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
    echo "Connect failed: " . $mysqli->connect_error . "\n";
    exit(1);
}
echo "Connected to $dbname\n";

// List triggers that reference sp_ManageCashbook
$sql = "SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING, ACTION_STATEMENT FROM information_schema.triggers WHERE TRIGGER_SCHEMA = DATABASE() AND ACTION_STATEMENT LIKE '%sp_ManageCashbook%';";
$res = $mysqli->query($sql);
if ($res) {
    echo "Triggers referencing sp_ManageCashbook:\n";
    while ($row = $res->fetch_assoc()) {
        echo "- " . $row['TRIGGER_NAME'] . " (" . $row['ACTION_TIMING'] . " " . $row['EVENT_MANIPULATION'] . ")\n";
        echo "  Statement: " . $row['ACTION_STATEMENT'] . "\n";
    }
} else {
    echo "Failed to query triggers: " . $mysqli->error . "\n";
}

// Show procedure definition
$res2 = $mysqli->query("SHOW CREATE PROCEDURE sp_ManageCashbook");
if ($res2) {
    $r = $res2->fetch_assoc();
    if (isset($r['Create Procedure'])) {
        echo "\nProcedure sp_ManageCashbook definition:\n";
        echo $r['Create Procedure'] . "\n";
    } else {
        // some MySQL versions return different key
        foreach ($r as $k => $v) {
            if (stripos($k, 'create') !== false) {
                echo "\nProcedure sp_ManageCashbook definition:\n";
                echo $v . "\n";
                break;
            }
        }
    }
} else {
    echo "Procedure sp_ManageCashbook not found or error: " . $mysqli->error . "\n";
}

$mysqli->close();
