-- Fix: recreate sp_ManageCashbook so it DOES NOT return any result set
-- Triggers are not allowed to return result sets; remove final SELECT statements
USE db_cement;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_ManageCashbook$$

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

    -- Error handler: capture SQL exceptions and rollback
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
    WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID
    LIMIT 1;

    -- Calculate new balance (Debit increases, Credit decreases)
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

    -- IMPORTANT: Do not SELECT or return any result sets here — triggers cannot return rows.
END$$

DELIMITER ;

-- To apply: run this file against your db_cement database (mysql client or phpMyAdmin)
-- Example (Windows):
--   mysql -u <user> -p db_cement < fix_sp_ManageCashbook_no_select.sql
