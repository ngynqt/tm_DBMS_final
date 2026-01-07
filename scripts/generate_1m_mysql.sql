-- Generate 1 Million Products
-- Run directly in MySQL

SET @total = 1000000;
SET @batch = 1000;
SET @batches = @total / @batch;

-- Disable checks for faster insert
SET FOREIGN_KEY_CHECKS=0;
SET UNIQUE_CHECKS=0;
SET AUTOCOMMIT=0;

-- Disable indexes temporarily
ALTER TABLE products DISABLE KEYS;

-- Create stored procedure to generate data
DELIMITER $$

DROP PROCEDURE IF EXISTS generate_products$$
CREATE PROCEDURE generate_products()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE batch_num INT DEFAULT 1;
    DECLARE product_name VARCHAR(255);
    DECLARE brand_name VARCHAR(100);
    DECLARE category VARCHAR(100);
    DECLARE orig_price FLOAT;
    DECLARE final_price FLOAT;
    DECLARE rating FLOAT;
    DECLARE reviews INT;
    
    -- Brands array
    SET @brands = 'Nike,Adidas,Puma,Reebok,New Balance,Under Armour,Converse,Vans,Fila,Asics';
    SET @categories = 'Shoes,Bags,Accessories,Clothing,Sports Equipment';
    
    SELECT CONCAT('Starting generation of ', @total, ' products...') as status;
    
    WHILE batch_num <= @batches DO
        -- Start batch
        START TRANSACTION;
        
        SET i = 1;
        WHILE i <= @batch DO
            -- Generate random data
            SET brand_name = SUBSTRING_INDEX(SUBSTRING_INDEX(@brands, ',', FLOOR(1 + RAND() * 10)), ',', -1);
            SET category = SUBSTRING_INDEX(SUBSTRING_INDEX(@categories, ',', FLOOR(1 + RAND() * 5)), ',', -1);
            SET product_name = CONCAT(brand_name, ' ', category, ' Product #', (batch_num-1)*@batch + i);
            SET orig_price = FLOOR(100000 + RAND() * 1900000);
            SET final_price = orig_price - FLOOR(RAND() * orig_price * 0.3);
            SET rating = 3.0 + (RAND() * 2.0);
            SET reviews = FLOOR(RAND() * 1000);
            
            -- Insert
            INSERT INTO products (name, description, original_price, price, fulfillment_type, brand, review_count, rating_average)
            VALUES (
                product_name,
                CONCAT('High quality ', brand_name, ' ', category, '. Perfect for daily use.'),
                orig_price,
                final_price,
                IF(RAND() > 0.5, 'seller_delivery', 'tiki_delivery'),
                brand_name,
                reviews,
                rating
            );
            
            SET i = i + 1;
        END WHILE;
        
        -- Commit batch
        COMMIT;
        
        -- Progress update every 10 batches (10,000 rows)
        IF batch_num MOD 10 = 0 THEN
            SELECT CONCAT('Progress: ', batch_num * @batch, '/', @total, ' (', 
                   ROUND((batch_num * @batch / @total) * 100, 1), '%)') as status;
        END IF;
        
        SET batch_num = batch_num + 1;
    END WHILE;
    
    -- Re-enable everything
    COMMIT;
    
END$$

DELIMITER ;

-- Run the procedure
SELECT NOW() as start_time;
CALL generate_products();
SELECT NOW() as end_time;

-- Re-enable indexes
ALTER TABLE products ENABLE KEYS;

-- Reset settings
SET FOREIGN_KEY_CHECKS=1;
SET UNIQUE_CHECKS=1;
SET AUTOCOMMIT=1;

-- Final count
SELECT COUNT(*) as total_products FROM products;

SELECT 'Generation complete!' as status;
