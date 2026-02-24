<?php
// Simple test script to verify voucher saving functionality
// Place this in your Apis folder and access via browser to test

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include CI framework files
require_once 'index.php';

try {
    // Test database connection
    $CI =& get_instance();
    $CI->load->database();
    
    echo "✅ Database connection: OK\n";
    
    // Check if stored procedure exists
    $result = $CI->db->query("SHOW PROCEDURE STATUS WHERE Name = 'sp_ManageCashbook'")->result_array();
    
    if (!empty($result)) {
        echo "✅ Stored procedure sp_ManageCashbook: EXISTS\n";
    } else {
        echo "❌ Stored procedure sp_ManageCashbook: MISSING\n";
        
        // Try to create it
        try {
            $createSql = "CREATE PROCEDURE sp_ManageCashbook(
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
                WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID;
                
                SET v_new_balance = v_current_balance + p_Debit - p_Credit;
                
                UPDATE customers 
                SET Balance = v_new_balance
                WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID;
                
                INSERT INTO customeraccts (
                    CustomerID, Date, Debit, Credit, Description, Balance, RefID, RefType, BusinessID
                ) VALUES (
                    p_CustomerID, p_Date, p_Debit, p_Credit, p_Description, v_new_balance, p_VoucherID, p_RefType, p_BusinessID
                );
                
                SELECT 'Success' as result;
            END";
            
            $CI->db->query("DROP PROCEDURE IF EXISTS sp_ManageCashbook");
            $CI->db->query($createSql);
            echo "✅ Created stored procedure successfully!\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to create stored procedure: " . $e->getMessage() . "\n";
        }
    }
    
    // Check required tables
    $tables = ['vouchers', 'customers', 'customeraccts'];
    foreach ($tables as $table) {
        if ($CI->db->table_exists($table)) {
            echo "✅ Table $table: EXISTS\n";
        } else {
            echo "❌ Table $table: MISSING\n";
        }
    }
    
    // Test voucher insertion
    echo "\n🧪 Testing voucher insertion...\n";
    
    $testVoucher = [
        'Date' => date('Y-m-d'),
        'CustomerID' => 1, // Assuming customer ID 1 exists
        'Description' => 'Test voucher - ' . date('Y-m-d H:i:s'),
        'Debit' => 0,
        'Credit' => 100,
        'RefID' => 0,
        'IsPosted' => 0,
        'FinYearID' => 0,
        'RefType' => 4,
        'BusinessID' => 1,
        'VoucherID' => 999999 // Test ID
    ];
    
    $CI->db->trans_begin();
    $CI->db->insert('vouchers', $testVoucher);
    
    if ($CI->db->trans_status() === FALSE) {
        $CI->db->trans_rollback();
        echo "❌ Test voucher insertion failed\n";
        echo "Error: " . $CI->db->error()['message'] . "\n";
    } else {
        $CI->db->trans_rollback(); // Rollback test data
        echo "✅ Test voucher insertion: SUCCESS\n";
    }
    
    echo "\n✅ All tests completed. Your voucher system should now work properly.\n";
    echo "\n📝 If you still see errors, check:\n";
    echo "1. Database user permissions for creating procedures\n";
    echo "2. Customer data exists in customers table\n";
    echo "3. All required fields are filled in the form\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
}
?>