-- Create the database if it doesn't already exist
CREATE
DATABASE IF NOT EXISTS draivi_backend_test;

-- Use the created database
USE
draivi_backend_test;

-- Create the table if it doesn't already exist
CREATE TABLE IF NOT EXISTS products
(
    number
    INT
    PRIMARY
    KEY,
    name
    VARCHAR
(
    255
),
    bottlesize VARCHAR
(
    50
),
    price DECIMAL
(
    10,
    2
),
    priceGBP DECIMAL
(
    10,
    2
),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    orderamount INT DEFAULT 0
    );
