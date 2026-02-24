-- Drop triggers on `vouchers` that call sp_ManageCashbook
-- This script finds triggers on the vouchers table whose action contains the stored procedure
-- and executes DROP TRIGGER IF EXISTS for each. Use with caution — dropping triggers removes DB-side logic.

USE db_cement;

SET @drops = NULL;
SELECT GROUP_CONCAT(CONCAT('DROP TRIGGER IF EXISTS `', TRIGGER_NAME, '`;') SEPARATOR ' ') INTO @drops
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND EVENT_OBJECT_TABLE = 'vouchers'
  AND ACTION_STATEMENT LIKE '%sp_ManageCashbook%';

SELECT @drops as drop_statements;

-- If @drops is not null, execute the drop statements
IF @drops IS NOT NULL THEN
  PREPARE stmt FROM @drops;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;
END IF;

-- After running this, run fix_sp_ManageCashbook_no_select.sql to recreate the procedure without SELECTs.
