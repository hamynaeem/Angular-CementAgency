<?php
// Directly drop known triggers and recreate sp_ManageCashbook without result sets
// Usage: php apply_fix_direct.php [dbname] [dbuser] [dbpass]
$dbname = $argv[1] ?? 'db_cement';
$dbuser = $argv[2] ?? 'root';
$dbpass = $argv[3] ?? '';
$mysqli = new mysqli('127.0.0.1', $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
    echo "Connect failed: " . $mysqli->connect_error . "\n";
    exit(1);
}
echo "Connected to $dbname\n";

$triggers = [
    'tr_booking_details_insert',
    'tr_booking_details_update',
    'tr_voucher_insert',
    'tr_voucher_update'
];
foreach ($triggers as $t) {
    $sql = "DROP TRIGGER IF EXISTS `$t`;";
    if (!$mysqli->query($sql)) {
        echo "Failed dropping trigger $t: " . $mysqli->error . "\n";
    } else {
        echo "Dropped trigger $t (if existed)\n";
    }
}

$dropProc = "DROP PROCEDURE IF EXISTS sp_ManageCashbook";
if (!$mysqli->query($dropProc)) {
    echo "Failed dropping procedure: " . $mysqli->error . "\n";
} else {
    echo "Dropped procedure sp_ManageCashbook (if existed)\n";
}

$create = <<<'SQL'
CREATE PROCEDURE sp_ManageCashbook(
    IN p_CustomerID INT,
    IN p_Date DATE,
    IN p_Debit DECIMAL(15,2),
    IN p_Credit DECIMAL(15,2),
    IN p_Description VARCHAR(500),
    IN p_VoucherID INT,
    IN p_BusinessID INT,
    IN p_RefType INT
)
BEGIN
    DECLARE v_current_balance DECIMAL(15,2) DEFAULT 0;
    DECLARE v_new_balance DECIMAL(15,2) DEFAULT 0;

    SELECT COALESCE(Balance, 0) INTO v_current_balance
    FROM customers
    WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID
    LIMIT 1;

    SET v_new_balance = v_current_balance + p_Debit - p_Credit;

    UPDATE customers
    SET Balance = v_new_balance
    WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID;

    INSERT INTO customeraccts (
        CustomerID,
        Date,
        Debit,
        Credit,
        Description,
        Balance,
        RefID,
        RefType,
        BusinessID
    ) VALUES (
        p_CustomerID,
        p_Date,
        p_Debit,
        p_Credit,
        p_Description,
        v_new_balance,
        p_VoucherID,
        p_RefType,
        p_BusinessID
    );
END
SQL;

if (!$mysqli->query($create)) {
    echo "Failed creating procedure: " . $mysqli->error . "\n";
    exit(1);
} else {
    echo "Created procedure sp_ManageCashbook\n";
}

echo "Done.\n";
$mysqli->close();
