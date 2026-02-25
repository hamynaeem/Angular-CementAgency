-- Minimal schema to support reports (safe, idempotent)
-- Run on your db_cement: mysql -u root -p db_cement < create_minimal_reports_schema.sql

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `customers` (
  `CustomerID` int NOT NULL AUTO_INCREMENT,
  `CustomerName` varchar(255) DEFAULT '',
  `Address` varchar(255) DEFAULT '',
  `City` varchar(100) DEFAULT '',
  `BusinessID` int DEFAULT NULL,
  `Balance` decimal(18,2) DEFAULT 0,
  `AcctTypeID` int DEFAULT NULL,
  PRIMARY KEY (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `invoices` (
  `InvoiceID` int NOT NULL AUTO_INCREMENT,
  `Date` date DEFAULT NULL,
  `CustomerID` int DEFAULT NULL,
  `NetAmount` decimal(18,2) DEFAULT 0,
  `DtCr` varchar(16) DEFAULT NULL,
  `BusinessID` int DEFAULT NULL,
  PRIMARY KEY (`InvoiceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `invoicedetails` (
  `DetailID` int NOT NULL AUTO_INCREMENT,
  `InvoiceID` int DEFAULT NULL,
  `ProductID` int DEFAULT NULL,
  `Qty` int DEFAULT 0,
  `Cost` decimal(18,2) DEFAULT 0,
  `NetAmount` decimal(18,2) DEFAULT 0,
  PRIMARY KEY (`DetailID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `accttypes` (
  `AcctTypeID` int NOT NULL AUTO_INCREMENT,
  `AcctType` varchar(100) DEFAULT '',
  PRIMARY KEY (`AcctTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Minimal view equivalents used by the app
DROP VIEW IF EXISTS `qryinvoices`;
CREATE VIEW `qryinvoices` AS
SELECT i.Date AS Date,
       i.InvoiceID AS InvoiceID,
       COALESCE(c.CustomerName, '') AS CustomerName,
       COALESCE(c.Address, '') AS Address,
       COALESCE(c.City, '') AS City,
       i.CustomerID AS CustomerID,
       i.NetAmount AS NetAmount,
       i.DtCr AS DtCr
FROM invoices i
LEFT JOIN customers c ON i.CustomerID = c.CustomerID;

DROP VIEW IF EXISTS `qryinvoicedetails`;
CREATE VIEW `qryinvoicedetails` AS
SELECT d.DetailID AS DetailID,
       d.InvoiceID AS InvoiceID,
       d.ProductID AS ProductID,
       d.Qty AS Qty,
       d.Cost AS Cost,
       d.NetAmount AS NetAmount
FROM invoicedetails d;

DROP VIEW IF EXISTS `qrysalereport`;
CREATE VIEW `qrysalereport` AS
SELECT d.InvoiceID AS InvoiceID,
       i.Date AS Date,
       d.ProductID AS ProductID,
       d.Qty AS Qty,
       d.Cost AS Cost,
       d.NetAmount AS NetAmount
FROM invoicedetails d
JOIN invoices i ON d.InvoiceID = i.InvoiceID;

DROP VIEW IF EXISTS `qrycustomers`;
CREATE VIEW `qrycustomers` AS
SELECT c.CustomerID,
       c.CustomerName,
       c.Address,
       c.City,
       COALESCE((
         SELECT SUM(d.NetAmount - d.Cost)
         FROM invoicedetails d
         JOIN invoices ii ON d.InvoiceID = ii.InvoiceID
         WHERE ii.CustomerID = c.CustomerID
       ), 0) AS Balance,
       c.AcctTypeID
FROM customers c;

SET FOREIGN_KEY_CHECKS=1;

-- End of minimal schema
