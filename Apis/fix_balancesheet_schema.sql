-- Fix script for Balance Sheet: add BusinessID, accttypes table, and qrycustomers view
-- Run this script against the `db_cement` database.
-- PowerShell-friendly import:
-- Get-Content 'E:\Angular\cement-agency\apps\CementAgency\Apis\fix_balancesheet_schema.sql' -Raw | mysql -u root -p db_cement

USE db_cement;

-- 1) Ensure accttypes table exists
CREATE TABLE IF NOT EXISTS accttypes (
  AcctTypeID INT AUTO_INCREMENT PRIMARY KEY,
  AcctType VARCHAR(200) NOT NULL
);

INSERT IGNORE INTO accttypes (AcctTypeID, AcctType) VALUES
(1,'Retail'),(2,'Wholesale'),(3,'Other');

-- 2) Add BusinessID to customers if missing (safe check using a procedure)
DELIMITER $$
CREATE PROCEDURE ensure_businessid()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'BusinessID'
  ) THEN
    ALTER TABLE customers ADD COLUMN BusinessID INT NOT NULL DEFAULT 1 AFTER CustomerID;
  END IF;
END$$
DELIMITER ;

CALL ensure_businessid();
DROP PROCEDURE IF EXISTS ensure_businessid;

-- 3) Create or recreate the qrycustomers view expected by the API
DROP VIEW IF EXISTS qrycustomers;
CREATE VIEW qrycustomers AS
SELECT
  c.CustomerID,
  c.CustomerName,
  c.Address,
  c.City,
  COALESCE(c.PhoneNo, '') AS PhoneNo,
  COALESCE(c.Balance, 0) AS Balance,
  c.AcctTypeID,
  COALESCE(c.Status, 0) AS Status,
  COALESCE(c.NTNNo, '') AS NTNNo,
  COALESCE(c.CNICNo, '') AS CNICNo,
  COALESCE(c.TaxActive, 0) AS TaxActive,
  COALESCE(c.BalQty, 0) AS BalQty,
  COALESCE(c.Commission, 0) AS Commission,
  COALESCE(c.`Limit`, 0) AS `Limit`,
  COALESCE(a.AcctType, '') AS AcctType
FROM customers c
LEFT JOIN accttypes a ON a.AcctTypeID = c.AcctTypeID;

-- End of script
