-- Database verification and setup for CNIC functionality
-- This script ensures the customers table has proper CNIC-related columns

USE db_cement;

-- Check existing customers table structure
DESCRIBE customers;

-- Add CNIC-related columns if they don't exist
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS CNICNo VARCHAR(15) COMMENT 'CNIC Number in format 00000-0000000-0',
ADD COLUMN IF NOT EXISTS NTNNo VARCHAR(20) COMMENT 'National Tax Number';

-- Update existing records to ensure CNIC formatting is consistent
-- (This safely updates any existing improperly formatted CNIC numbers)
UPDATE customers 
SET CNICNo = CASE 
    WHEN CNICNo IS NOT NULL AND LENGTH(REPLACE(CNICNo, '-', '')) = 13 
    THEN CONCAT(
        LEFT(REPLACE(CNICNo, '-', ''), 5), 
        '-', 
        SUBSTRING(REPLACE(CNICNo, '-', ''), 6, 7), 
        '-', 
        RIGHT(REPLACE(CNICNo, '-', ''), 1)
    )
    ELSE CNICNo
END
WHERE CNICNo IS NOT NULL AND CNICNo != '';

-- Create an index on CNIC for faster searches
CREATE INDEX IF NOT EXISTS idx_customers_cnic ON customers(CNICNo);

-- Show updated table structure
DESCRIBE customers;

-- Display sample CNIC data to verify formatting
SELECT CustomerName, CNICNo, NTNNo, PhoneNo 
FROM customers 
WHERE CNICNo IS NOT NULL AND CNICNo != ''
LIMIT 10;