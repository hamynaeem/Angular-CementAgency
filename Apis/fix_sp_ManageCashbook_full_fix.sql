-- Composite fix: drop triggers that call sp_ManageCashbook and recreate the procedure without returning result sets
USE db_cement;

-- Drop any triggers on vouchers that call sp_ManageCashbook
-- This searches triggers whose ACTION_STATEMENT contains the procedure name and drops them.
SET @schema_name = DATABASE();

SELECT CONCAT('DROP TRIGGER IF EXISTS `', TRIGGER_NAME, '`;') INTO @drop_sql
FROM information_schema.triggers
WHERE TRIGGER_SCHEMA = @schema_name AND ACTION_STATEMENT LIKE '%sp_ManageCashbook%'
LIMIT 1;

-- Execute drop statements for all matching triggers
PREPARE stmt FROM
    (SELECT GROUP_CONCAT(CONCAT('DROP TRIGGER IF EXISTS `', TRIGGER_NAME, '`') SEPARATOR ';')
     FROM information_schema.triggers
     WHERE TRIGGER_SCHEMA = @schema_name AND ACTION_STATEMENT LIKE '%sp_ManageCashbook%');

-- If there were matching triggers, run the drop commands
SET @triggers_drop = (SELECT GROUP_CONCAT(CONCAT('DROP TRIGGER IF EXISTS `', TRIGGER_NAME, '`') SEPARATOR ';')
                      FROM information_schema.triggers
                      WHERE TRIGGER_SCHEMA = @schema_name AND ACTION_STATEMENT LIKE '%sp_ManageCashbook%');

IF @triggers_drop IS NOT NULL THEN
    -- execute the drop statements
    SET @full_drop = CONCAT(@triggers_drop, ';');
    PREPARE dropTriggers FROM @full_drop;
    EXECUTE dropTriggers;
    DEALLOCATE PREPARE dropTriggers;
END IF;

-- Recreate the stored procedure without SELECTs (no result sets)
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

    SELECT COALESCE(Balance, 0) INTO v_current_balance
    FROM customers
    WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID
    LIMIT 1;

    SET v_new_balance = v_current_balance + p_Debit - p_Credit;

    UPDATE customers
    SET Balance = v_new_balance
    WHERE CustomerID = p_CustomerID AND BusinessID = p_BusinessID;

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

    -- Do not return any result set from here.
END$$

DELIMITER ;

-- End of composite fix
