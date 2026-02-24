-- Alternative solution: Remove the trigger calling sp_ManageCashbook
-- Run this if you prefer to handle cashbook updates in PHP instead of database triggers

USE db_cement;

-- First, check what triggers exist on vouchers table
SHOW TRIGGERS WHERE `Table` = 'vouchers';

-- If you find triggers calling sp_ManageCashbook, you can drop them with:
-- DROP TRIGGER IF EXISTS vouchers_after_insert;
-- DROP TRIGGER IF EXISTS vouchers_after_update;

-- For example, common trigger names might be:
-- DROP TRIGGER IF EXISTS tr_vouchers_insert;
-- DROP TRIGGER IF EXISTS tr_vouchers_update;
-- DROP TRIGGER IF EXISTS vouchers_trigger;

-- After dropping triggers, the voucher insertion will work without calling the stored procedure
-- The customer balance updates will then be handled by the existing PHP AddToAccount() method