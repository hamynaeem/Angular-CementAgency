-- Minimal vouchers table to allow voucher inserts
-- Run: mysql -u root -p db_cement < create_minimal_vouchers_table.sql

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `vouchers` (
  `VoucherID` INT NOT NULL AUTO_INCREMENT,
  `Date` DATE DEFAULT NULL,
  `AcctType` INT DEFAULT NULL,
  `CustomerID` INT NOT NULL DEFAULT 0,
  `Description` VARCHAR(255) DEFAULT NULL,
  `Debit` DECIMAL(18,2) DEFAULT 0,
  `Credit` DECIMAL(18,2) DEFAULT 0,
  `RefID` INT DEFAULT 0,
  `RefType` INT DEFAULT NULL,
  `FinYearID` INT DEFAULT 0,
  `IsPosted` INT NOT NULL DEFAULT 0,
  `BusinessID` INT NOT NULL DEFAULT 0,
  `RouteID` INT DEFAULT NULL,
  `SalesmanID` INT DEFAULT NULL,
  `ClosingID` INT DEFAULT NULL,
  `UserID` INT DEFAULT NULL,
  PRIMARY KEY (`VoucherID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
