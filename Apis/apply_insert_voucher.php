<?php
// Insert a test voucher directly and call sp_ManageCashbook to update accounts
$db = 'db_cement';
$user = $argv[1] ?? 'root';
$pass = $argv[2] ?? '';
$mysqli = new mysqli('127.0.0.1', $user, $pass, $db);
if ($mysqli->connect_errno) { echo "Connect failed: " . $mysqli->connect_error . "\n"; exit(1); }

$date = '2025-09-22';
$customer = 268;
$desc = 'Inserted by assistant';
$debit = 0.0;
$credit = 100.0;
$refid = 0;
$isposted = 0;
$finyear = 1;
$reftype = 0;
$business = 1;

$stmt = $mysqli->prepare("INSERT INTO vouchers (Date, CustomerID, Description, Debit, Credit, RefID, IsPosted, FinYearID, RefType, BusinessID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) { echo "Prepare failed: " . $mysqli->error . "\n"; exit(1); }
// types: s(date), i(customer), s(desc), d(debit), d(credit), i(refid), i(isposted), i(finyear), i(reftype), i(business)
$stmt->bind_param('sisddiiiii', $date, $customer, $desc, $debit, $credit, $refid, $isposted, $finyear, $reftype, $business);
if (!$stmt->execute()) { echo "Insert failed: " . $stmt->error . "\n"; exit(1); }
$vid = $mysqli->insert_id;
echo "Inserted voucher id: $vid\n";

// Call stored procedure to update accounts (match API call order)
$escDesc = "'" . $mysqli->real_escape_string($desc) . "'";
$call = sprintf("CALL sp_ManageCashbook(%d, '%s', %f, %f, %s, %d, %d, %d)", $customer, $date, $debit, $credit, $escDesc, $vid, $business, $reftype);
// Note: above ordering matches API's CALL in Tasks.php
if (!$mysqli->query($call)) {
    echo "Procedure call failed: " . $mysqli->error . "\n";
} else {
    echo "Procedure called successfully.\n";
}

$mysqli->close();
