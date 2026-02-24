-- PERMANENT FIX: Remove problematic triggers from expend table
-- Run this in MySQL to permanently resolve the "Not allowed to return a result set from a trigger" error

USE db_cement;

-- Show all triggers on expend table before removal
SELECT 'TRIGGERS BEFORE REMOVAL:' as info;
SHOW TRIGGERS WHERE `Table` = 'expend';

-- Drop all triggers on expend table that might be causing issues
DROP TRIGGER IF EXISTS expend_after_insert;
DROP TRIGGER IF EXISTS expend_before_insert;
DROP TRIGGER IF EXISTS expend_after_update;
DROP TRIGGER IF EXISTS expend_before_update;
DROP TRIGGER IF EXISTS expend_after_delete;
DROP TRIGGER IF EXISTS expend_before_delete;

-- Try common trigger naming patterns
DROP TRIGGER IF EXISTS tr_expend_insert;
DROP TRIGGER IF EXISTS tr_expend_update;
DROP TRIGGER IF EXISTS tr_expend_delete;
DROP TRIGGER IF EXISTS trigger_expend;
DROP TRIGGER IF EXISTS expend_trigger;

-- Get a list of all triggers in the database and check for any containing 'expend'
SELECT
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE,
    ACTION_STATEMENT
FROM 
    INFORMATION_SCHEMA.TRIGGERS 
WHERE 
    TRIGGER_SCHEMA = 'db_cement' 
    AND (EVENT_OBJECT_TABLE = 'expend' OR TRIGGER_NAME LIKE '%expend%');

-- Show triggers after removal to confirm they're gone
SELECT 'TRIGGERS AFTER REMOVAL:' as info;
SHOW TRIGGERS WHERE `Table` = 'expend';

-- Test insertion after trigger removal
INSERT INTO expend (Date, HeadID, CategoryID, Desc, Amount) 
VALUES (CURDATE(), 1, '1', 'Test after trigger removal', 1.00);

-- Check if the test insert was successful
SELECT * FROM expend WHERE Desc = 'Test after trigger removal';

-- Clean up test data
DELETE FROM expend WHERE Desc = 'Test after trigger removal';

SELECT 'Expend table trigger cleanup completed successfully!' as result;