-- Test script to verify expense system is working
-- Run this in your MySQL database to test the expense saving functionality

USE db_cement;

-- 1. Check if expend table exists and has correct structure
DESCRIBE expend;

-- 2. Check if expenseheads table exists  
DESCRIBE expenseheads;

-- 3. Check if categories table exists
DESCRIBE categories;

-- 4. Verify we have expense heads and categories to test with
SELECT * FROM expenseheads LIMIT 5;
SELECT * FROM categories LIMIT 5;

-- 5. Check for any triggers on the expend table that might be causing issues
SHOW TRIGGERS WHERE `Table` = 'expend';

-- 6. Test inserting an expense record manually (this simulates what the API does)
SET @test_head_id = (SELECT HeadID FROM expenseheads LIMIT 1);
SET @test_category_id = (SELECT CatID FROM categories LIMIT 1);

-- If the above queries return NULL, create test data
INSERT IGNORE INTO expenseheads (HeadID, HeadName) VALUES (1, 'Test Expense Head');
INSERT IGNORE INTO categories (CatID, CatName) VALUES (1, 'Test Category');

-- Now test the expense insertion
INSERT INTO expend (
    Date, HeadID, CategoryID, Desc, Amount
) VALUES (
    CURDATE(), 
    COALESCE(@test_head_id, 1), 
    COALESCE(@test_category_id, 1), 
    'Test Expense Entry', 
    100.00
);

-- 7. Check if the expense was inserted successfully
SELECT * FROM expend WHERE Desc = 'Test Expense Entry';

-- 8. Clean up test data
DELETE FROM expend WHERE Desc = 'Test Expense Entry';

-- 9. If everything works, you should see:
-- ✅ Tables exist and have proper structure
-- ✅ Expense heads and categories exist for testing  
-- ✅ Expense can be inserted without trigger errors
-- ✅ Test data cleaned up successfully

SELECT 'Expense system database check completed successfully!' as result;