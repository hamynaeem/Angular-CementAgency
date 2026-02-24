-- Script to check for database triggers that might be causing the sp_ManageCashbook error
-- Run these queries in your MySQL database to investigate the issue

USE db_cement;

-- 1. Check for triggers on the vouchers table
SHOW TRIGGERS WHERE `Table` = 'vouchers';

-- 2. Show all triggers in the database
SHOW TRIGGERS;

-- 3. Check if any triggers contain references to sp_ManageCashbook
-- (You'll need to examine the trigger definitions manually)

-- 4. If you find triggers calling sp_ManageCashbook, you can drop them temporarily:
-- DROP TRIGGER IF EXISTS trigger_name_here;

-- 5. Check table structure
DESCRIBE vouchers;

-- 6. Check for any foreign key constraints that might have ON INSERT actions
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    UPDATE_RULE,
    DELETE_RULE
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    TABLE_SCHEMA = 'db_cement' 
    AND TABLE_NAME = 'vouchers'
    AND REFERENCED_TABLE_NAME IS NOT NULL;