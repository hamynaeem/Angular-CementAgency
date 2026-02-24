-- Fix for missing sp_ManageCashbook stored procedure
-- This script creates a functional stored procedure to resolve the database error
-- Based on the voucher system requirements

USE db_cement;

-- Drop the procedure if it exists
DROP PROCEDURE IF EXISTS sp_ManageCashbook;

-- Create a functional stored procedure that manages cashbook entries
DELIMITER $$

CREATE PROCEDURE sp_ManageCashbook(
    IN p_CustomerID INT,
    IN p_Date DATE,
    IN p_Debit DECIMAL(15,2),
    IN p_Credit DECIMAL(15,2),
    IN p_Description VARCHAR(500),
    IN p_VoucherID INT,
    IN p_BusinessID INT,
    IN p_RefType INT DEFAULT 4
)
BEGIN
    DECLARE v_current_balance DECIMAL(15,2) DEFAULT 0;
    DECLARE v_new_balance DECIMAL(15,2) DEFAULT 0;
    DECLARE v_error_code INT DEFAULT 0;
    
    -- Error handling
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION 
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_code = MYSQL_ERRNO;
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get current customer balance
    SELECT COALESCE(Balance, 0) INTO v_current_balance 
    FROM customers 
    WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID;
    
    -- Calculate new balance (Debit increases balance, Credit decreases)
    SET v_new_balance = v_current_balance + p_Debit - p_Credit;
    
    -- Update customer balance
    UPDATE customers 
    SET Balance = v_new_balance
    WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID;
    
    -- Insert into customer accounts (cashbook)
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
    
    COMMIT;
    
    -- Return success message
    SELECT 'Cashbook updated successfully' as message, v_new_balance as new_balance;
    
END$$

DELIMITER ;

-- Grant execute permissions if needed
-- GRANT EXECUTE ON PROCEDURE db_cement.sp_ManageCashbook TO 'your_db_user'@'%';

-- Test the procedure creation
SHOW PROCEDURE STATUS WHERE Name = 'sp_ManageCashbook';

-- To manually test the procedure (uncomment and modify values):
-- CALL sp_ManageCashbook(1, '2024-01-01', 100.00, 0.00, 'Test entry', 1, 1, 4);