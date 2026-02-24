-- Fix for missing sp_GetCashbookHistory stored procedure
-- This script creates the cashbook history procedure for reports

USE db_cement;

-- Drop the procedure if it exists
DROP PROCEDURE IF EXISTS sp_GetCashbookHistory;

-- Create the stored procedure for cashbook history
DELIMITER $$

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
-- GRANT EXECUTE ON PROCEDURE db_cement.sp_GetCashbookHistory TO 'your_db_user'@'%';

-- Test the procedure creation
SHOW PROCEDURE STATUS WHERE Name = 'sp_GetCashbookHistory';

-- To manually test the procedure (uncomment and modify dates):
-- CALL sp_GetCashbookHistory('2024-01-01', '2024-12-31');