-- Fix for missing/mismatched AcctTypeID in customers
USE db_cement;

-- Insert default account types if table is empty
INSERT IGNORE INTO accttypes (AcctTypeID, AcctType) VALUES
(1, 'Customer'),
(2, 'Supplier'),
(3, 'Dealer'),
(4, 'Employee'),
(5, 'Other');

-- Update customers with missing AcctTypeID to default
UPDATE customers SET AcctTypeID = 1 WHERE AcctTypeID IS NULL OR AcctTypeID NOT IN (SELECT AcctTypeID FROM accttypes);

-- Verify
SELECT CustomerID, CustomerName, AcctTypeID FROM customers LIMIT 10;
SELECT * FROM accttypes;
SELECT * FROM qrycustomers LIMIT 10;
-- After running this script, your balance sheet endpoint should work without errors.
