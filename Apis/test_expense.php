<?php
// Simple test script to debug expense saving issue
// Place this in your Apis folder and access via browser: http://localhost:4200/apis/test_expense.php

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');

// Include CI framework files  
define('BASEPATH', TRUE);
require_once 'index.php';

try {
    echo "=== EXPENSE SYSTEM DEBUG TEST ===\n\n";
    
    // Get CI instance
    $CI =& get_instance();
    $CI->load->database();
    
    echo "✅ Database connection: OK\n\n";
    
    // Check table structure
    echo "--- EXPEND TABLE STRUCTURE ---\n";
    $fields = $CI->db->field_data('expend');
    foreach ($fields as $field) {
        echo "Field: {$field->name} | Type: {$field->type} | Max Length: {$field->max_length}\n";
    }
    echo "\n";
    
    // Check expense heads
    echo "--- EXPENSE HEADS ---\n";
    $heads = $CI->db->get('expenseheads')->result_array();
    if (empty($heads)) {
        echo "❌ No expense heads found!\n";
        // Create a test head
        $CI->db->insert('expenseheads', ['HeadID' => 1, 'Head' => 'Test Head']);
        echo "✅ Created test expense head\n";
    } else {
        echo "✅ Found " . count($heads) . " expense heads:\n";
        foreach (array_slice($heads, 0, 3) as $head) {
            echo "  - ID: {$head['HeadID']}, Name: {$head['Head']}\n";
        }
    }
    echo "\n";
    
    // Check categories
    echo "--- CATEGORIES ---\n";
    $categories = $CI->db->get('categories')->result_array();
    if (empty($categories)) {
        echo "❌ No categories found!\n";
        // Create a test category
        $CI->db->insert('categories', ['CatID' => 1, 'CatName' => 'Test Category']);
        echo "✅ Created test category\n";
    } else {
        echo "✅ Found " . count($categories) . " categories:\n";
        foreach (array_slice($categories, 0, 3) as $cat) {
            echo "  - ID: {$cat['CatID']}, Name: {$cat['CatName']}\n";
        }
    }
    echo "\n";
    
    // Check for triggers
    echo "--- TRIGGERS ON EXPEND TABLE ---\n";
    $triggers = $CI->db->query("SHOW TRIGGERS WHERE `Table` = 'expend'")->result_array();
    if (empty($triggers)) {
        echo "✅ No triggers found on expend table\n";
    } else {
        echo "❌ Found triggers (these might cause issues):\n";
        foreach ($triggers as $trigger) {
            echo "  - Trigger: {$trigger['Trigger']}, Event: {$trigger['Event']}, Timing: {$trigger['Timing']}\n";
        }
    }
    echo "\n";
    
    // Test expense insertion
    echo "--- TESTING EXPENSE INSERTION ---\n";
    
    $testData = [
        'Date' => date('Y-m-d'),
        'HeadID' => 1,
        'CategoryID' => 1,
        'Desc' => 'Test Expense - ' . date('Y-m-d H:i:s'),
        'Amount' => 100.00
    ];
    
    echo "Test data: " . json_encode($testData) . "\n";
    
    // Disable triggers for testing
    $CI->db->query("SET foreign_key_checks = 0");
    
    try {
        $CI->db->trans_begin();
        $CI->db->insert('expend', $testData);
        $insertId = $CI->db->insert_id();
        $CI->db->trans_commit();
        
        echo "✅ Test expense inserted successfully with ID: {$insertId}\n";
        
        // Clean up test data
        $CI->db->where('ExpendID', $insertId);
        $CI->db->delete('expend');
        echo "✅ Test data cleaned up\n";
        
    } catch (Exception $e) {
        $CI->db->trans_rollback();
        echo "❌ Test insertion failed: " . $e->getMessage() . "\n";
    }
    
    // Re-enable foreign key checks
    $CI->db->query("SET foreign_key_checks = 1");
    
    // Test the specific query that's causing the 500 error
    echo "\n--- TESTING QRYEXPENSES QUERY ---\n";
    
    $testFilter = "e.Date between '2025-9-23' and '2025-9-23'";
    echo "Testing filter: {$testFilter}\n";
    
    // Check if we have any data for this date
    $countQuery = "SELECT COUNT(*) as count FROM expend WHERE Date between '2025-9-23' and '2025-9-23'";
    $countResult = $CI->db->query($countQuery)->row_array();
    echo "Records for date 2025-9-23: {$countResult['count']}\n";
    
    if ($countResult['count'] == 0) {
        echo "No data for test date, inserting test record...\n";
        $CI->db->insert('expend', [
            'Date' => '2025-09-23',
            'HeadID' => 1, 
            'CategoryID' => 1,
            'Desc' => 'Test expense for API test',
            'Amount' => 500.00
        ]);
        echo "✅ Test record inserted\n";
    }
    
    // Test the actual query from the API
    $apiQuery = "SELECT 
                    e.ExpendID,
                    e.Date,
                    e.HeadID,
                    COALESCE(eh.HeadName, CONCAT('Head ID: ', e.HeadID)) as HeadName,
                    e.CategoryID,
                    e.`Desc` as Description,
                    e.Amount
                FROM expend e
                LEFT JOIN expenseheads eh ON e.HeadID = eh.HeadID
                WHERE {$testFilter}
                ORDER BY e.Date DESC";
    
    echo "Executing API query...\n";
    try {
        $result = $CI->db->query($apiQuery);
        if ($result) {
            $rows = $result->result_array();
            echo "✅ Query successful, returned " . count($rows) . " rows\n";
            if (count($rows) > 0) {
                echo "Sample result: " . json_encode($rows[0]) . "\n";
            }
        } else {
            $error = $CI->db->error();
            echo "❌ Query failed: " . json_encode($error) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Query exception: " . $e->getMessage() . "\n";
    }
    
    // Test without join for comparison
    echo "\nTesting simple query without join...\n";
    $simpleQuery = "SELECT * FROM expend WHERE Date between '2025-9-23' and '2025-9-23'";
    try {
        $result = $CI->db->query($simpleQuery);
        if ($result) {
            $rows = $result->result_array();
            echo "✅ Simple query successful, returned " . count($rows) . " rows\n";
        } else {
            $error = $CI->db->error();
            echo "❌ Simple query failed: " . json_encode($error) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Simple query exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== TEST COMPLETED ===\n";
    echo "\nTo test this manually, access:\n";
    echo "http://localhost:4200/apis/index.php/apis/qryexpenses?filter=" . urlencode($testFilter) . "\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
}
?>