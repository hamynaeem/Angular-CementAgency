-- Complete Database Setup for Expense System
-- This script creates all missing tables and stored procedures

USE db_cement;

-- Create expense heads table if it doesn't exist
CREATE TABLE IF NOT EXISTS expenseheads (
    HeadID INT AUTO_INCREMENT PRIMARY KEY,
    Head VARCHAR(255) NOT NULL,
    Description TEXT,
    IsActive TINYINT(1) DEFAULT 1,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default expense heads if table is empty
INSERT IGNORE INTO expenseheads (HeadID, Head, Description) VALUES
(1, 'Office Supplies', 'General office supplies and stationery'),
(2, 'Transportation', 'Vehicle fuel, maintenance, and transport costs'),
(3, 'Utilities', 'Electricity, water, phone, internet bills'),
(4, 'Rent', 'Office and warehouse rent payments'),
(5, 'Salaries', 'Employee wages and salaries'),
(6, 'Marketing', 'Advertising and promotional expenses'),
(7, 'Maintenance', 'Equipment and facility maintenance'),
(8, 'Legal & Professional', 'Legal fees, consultancy, professional services'),
(9, 'Miscellaneous', 'Other general expenses');

-- Drop existing procedures if they exist
DROP PROCEDURE IF EXISTS sp_ManageCashbook;
DROP PROCEDURE IF EXISTS sp_GetCashbookHistory;

-- Create sp_ManageCashbook procedure
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

-- Create sp_GetCashbookHistory procedure  
CREATE PROCEDURE sp_GetCashbookHistory(
    IN p_StartDate DATE,
    IN p_EndDate DATE
)
BEGIN
    DECLARE v_error_code INT DEFAULT 0;
    
    -- Error handling
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION 
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_code = MYSQL_ERRNO;
        ROLLBACK;
        RESIGNAL;
    END;
    
    -- Select cashbook history with customer details
    SELECT 
        v.VoucherID,
        v.Date,
        v.CustomerID,
        COALESCE(c.CustomerName, 'Unknown Customer') AS CustomerName,
        c.Address,
        v.Description,
        v.Debit,
        v.Credit,
        v.RefType,
        v.RefID,
        -- Calculate running balance (optional)
        @running_balance := COALESCE(@running_balance, 0) + v.Debit - v.Credit AS Balance
    FROM 
        vouchers v
    LEFT JOIN 
        customers c ON v.CustomerID = c.CustomerID
    CROSS JOIN 
        (SELECT @running_balance := 0) AS r
    WHERE 
        v.Date BETWEEN p_StartDate AND p_EndDate
    ORDER BY 
        v.Date ASC, v.VoucherID ASC;
        
END$$

DELIMITER ;

-- Grant execute permissions if needed
-- GRANT EXECUTE ON PROCEDURE db_cement.sp_ManageCashbook TO 'your_db_user'@'%';
-- GRANT EXECUTE ON PROCEDURE db_cement.sp_GetCashbookHistory TO 'your_db_user'@'%';

-- Show created procedures
SHOW PROCEDURE STATUS WHERE Db = 'db_cement' AND Name IN ('sp_ManageCashbook', 'sp_GetCashbookHistory');

-- Show created tables
SHOW TABLES LIKE '%expense%';

-- Display sample data
SELECT 'Expense Heads Created:' as Info;
SELECT HeadID, Head, Description FROM expenseheads LIMIT 5;