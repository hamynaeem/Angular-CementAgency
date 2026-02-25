<?php
$user = $argv[1] ?? 'root';
$pass = $argv[2] ?? '';
$db = 'db_cement';
$mysqli = new mysqli('127.0.0.1', $user, $pass, $db);
if ($mysqli->connect_errno) { echo "Connect failed: " . $mysqli->connect_error . "\n"; exit(1); }

$sql = "DROP PROCEDURE IF EXISTS sp_ManageCashbook; ";

$sql .= "CREATE PROCEDURE sp_ManageCashbook( ";
$sql .= "IN p_CustomerID INT, IN p_Date DATE, IN p_Debit DECIMAL(15,2), IN p_Credit DECIMAL(15,2), ";
$sql .= "IN p_Description VARCHAR(500), IN p_VoucherID INT, IN p_BusinessID INT, IN p_RefType INT) ";
$sql .= "BEGIN ";
$sql .= "DECLARE v_current_balance DECIMAL(15,2) DEFAULT 0; ";
$sql .= "DECLARE v_new_balance DECIMAL(15,2) DEFAULT 0; ";
$sql .= "SELECT COALESCE(Balance,0) INTO v_current_balance FROM customers WHERE CustomerID = p_CustomerID LIMIT 1; ";
$sql .= "SET v_new_balance = v_current_balance + p_Debit - p_Credit; ";
$sql .= "UPDATE customers SET Balance = v_new_balance WHERE CustomerID = p_CustomerID; ";
$sql .= "INSERT INTO customeraccts (CustomerID, Date, Debit, Credit, Description, Balance, RefID, RefType) ";
$sql .= "VALUES (p_CustomerID, p_Date, p_Debit, p_Credit, p_Description, v_new_balance, p_VoucherID, p_RefType); ";
$sql .= "END;";

if (!$mysqli->multi_query($sql)) {
    echo "Failed to create procedure: " . $mysqli->error . "\n";
    exit(1);
}
// flush
do { if ($res = $mysqli->store_result()) { $res->free(); } } while ($mysqli->more_results() && $mysqli->next_result());

echo "Procedure recreated successfully.\n";
$mysqli->close();
