-- Run ONCE on the live database (phpMyAdmin) to convert existing LKR prices to USD.
-- Only rows still on LKR are touched; rows you already changed to another currency are left alone.
USE codecrafters;

ALTER TABLE pricing_plans ALTER COLUMN currency SET DEFAULT 'USD';

UPDATE pricing_plans SET
  amount = CASE amount
    WHEN '225,000'    THEN '750'
    WHEN '550,000'    THEN '1,800'
    WHEN '350,000'    THEN '1,200'
    WHEN '950,000'    THEN '3,200'
    WHEN '850,000'    THEN '2,800'
    WHEN '120,000'    THEN '400'
    WHEN '250,000'    THEN '850'
    WHEN '75,000'     THEN '250'
    WHEN '150,000'    THEN '500'
    WHEN '300,000'    THEN '1,000'
    WHEN '35,000'     THEN '120'
    WHEN '220,000'    THEN '750'
    WHEN '450,000'    THEN '1,500'
    WHEN '2,500,000+' THEN '8,500+'
    WHEN '20,000'     THEN '70'
    WHEN '45,000'     THEN '150'
    WHEN '90,000'     THEN '300'
    WHEN '6,000'      THEN '20'
    WHEN '15,000'     THEN '50'
    WHEN '30,000'     THEN '100'
    ELSE amount
  END,
  currency = 'USD'
WHERE currency = 'LKR';
