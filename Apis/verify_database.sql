-- Test script to verify voucher system is working
-- Run this in your MySQL database to test the voucher saving functionality

USE db_cement;

-- 1. Check if vouchers table exists and has correct structure
DESCRIBE vouchers;

-- 2. Check if customers table exists  
DESCRIBE customers;

-- 3. Check if customeraccts table exists (optional for ledger)
DESCRIBE customeraccts;

-- 4. Verify we have at least one customer to test with
SELECT CustomerID, CustomerName, Balance, BusinessID FROM customers LIMIT 5;

-- 5. Test inserting a voucher manually (this simulates what the API does)
SET @test_customer_id = (SELECT CustomerID FROM customers LIMIT 1);
SET @test_business_id = (SELECT COALESCE(BusinessID, 1) FROM customers WHERE CustomerID = @test_customer_id);

INSERT INTO vouchers (
    Date, CustomerID, Description, Debit, Credit, RefID, IsPosted, FinYearID, RefType, BusinessID, VoucherID
) VALUES (
    CURDATE(), @test_customer_id, 'Test Voucher', 0, 100, 0, 0, 0, 4, @test_business_id, 999998
);

-- 6. Check if the voucher was inserted successfully
SELECT * FROM vouchers WHERE VoucherID = 999998;

-- 7. Clean up test data
DELETE FROM vouchers WHERE VoucherID = 999998;

-- 8. If everything works, you should see:
-- ✅ Tables exist and have proper structure
-- ✅ At least one customer exists for testing  
-- ✅ Voucher can be inserted without errors
-- ✅ Test data cleaned up successfully

SELECT 'Voucher system database check completed successfully!' as result;