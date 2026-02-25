<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'db_cement');
if ($mysqli->connect_errno) {
    echo "Connect error: " . $mysqli->connect_error . "\n";
    exit(1);
}
$res = $mysqli->query("SELECT VoucherID, Date, Description, Debit, Credit FROM vouchers WHERE Description LIKE '%run_post_voucher%' ORDER BY VoucherID DESC LIMIT 10");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo json_encode($r) . "\n";
    }
} else {
    echo "Query failed: " . $mysqli->error . "\n";
}
$mysqli->close();
