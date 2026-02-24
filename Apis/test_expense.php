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
    
    echo "\n=== TEST COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
}
?>