-- Recreate sp_ManageCashbook without SELECTs and without referencing BusinessID
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

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_code = MYSQL_ERRNO;
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Get current customer balance without BusinessID (for schemas that don't have BusinessID column)
    SELECT COALESCE(Balance, 0) INTO v_current_balance
    FROM customers
    WHERE CustomerID = p_CustomerID
    LIMIT 1;

    SET v_new_balance = v_current_balance + p_Debit - p_Credit;

    UPDATE customers
    SET Balance = v_new_balance
    WHERE CustomerID = p_CustomerID;

    INSERT INTO customeraccts (
        CustomerID,
        Date,
        Debit,
        Credit,
        Description,
        Balance,
        RefID,
        RefType
    ) VALUES (
        p_CustomerID,
        p_Date,
        p_Debit,
        p_Credit,
        p_Description,
        v_new_balance,
        p_VoucherID,
        p_RefType
    );

    COMMIT;

END$$

DELIMITER ;

-- End
