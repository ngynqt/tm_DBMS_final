<?php
/**
 * Generate and Insert 1 Million Products
 * Optimized for high-performance bulk insert
 */

require_once 'db.php';

set_time_limit(0); // No time limit
ini_set('memory_limit', '1G'); // Increase memory limit

echo "=== 1 Million Products Generator ===\n\n";

// Configuration
$TOTAL_PRODUCTS = 1000000;
$BATCH_SIZE = 1000; // Insert 1000 rows at a time
$BRANDS = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance', 'Under Armour', 'Converse', 'Vans', 'Fila', 'Asics'];
$CATEGORIES = ['Shoes', 'Bags', 'Accessories', 'Clothing', 'Sports Equipment'];

// Disable autocommit for better performance
mysqli_autocommit($conn, FALSE);

// Disable keys for faster insert
echo "Disabling indexes...\n";
mysqli_query($conn, "ALTER TABLE products DISABLE KEYS");

// Start timer
$start_time = microtime(true);
$inserted = 0;

echo "Starting insert of $TOTAL_PRODUCTS products...\n";
echo "Batch size: $BATCH_SIZE rows per query\n\n";

// Generate and insert in batches
for ($batch = 0; $batch < ($TOTAL_PRODUCTS / $BATCH_SIZE); $batch++) {
    $values = [];

    for ($i = 0; $i < $BATCH_SIZE; $i++) {
        $product_num = ($batch * $BATCH_SIZE) + $i + 1;

        // Generate random product data
        $brand = $BRANDS[array_rand($BRANDS)];
        $category = $CATEGORIES[array_rand($CATEGORIES)];
        $name = mysqli_real_escape_string($conn, "$brand $category Product #$product_num");
        $description = mysqli_real_escape_string($conn, "High quality $brand $category. Perfect for daily use. Product ID: $product_num");

        $original_price = rand(100000, 2000000);
        $price = $original_price - rand(0, $original_price * 0.3); // 0-30% discount
        $fulfillment_type = rand(0, 1) ? 'seller_delivery' : 'tiki_delivery';
        $review_count = rand(0, 1000);
        $rating_average = rand(30, 50) / 10; // 3.0 to 5.0

        $values[] = "('$name', '$description', $original_price, $price, '$fulfillment_type', '$brand', $review_count, $rating_average)";
    }

    // Prepare bulk insert query
    $sql = "INSERT INTO products (name, description, original_price, price, fulfillment_type, brand, review_count, rating_average) VALUES " . implode(',', $values);

    if (mysqli_query($conn, $sql)) {
        $inserted += $BATCH_SIZE;

        // Commit every 10 batches (10,000 rows)
        if ($batch % 10 == 0) {
            mysqli_commit($conn);
            $elapsed = microtime(true) - $start_time;
            $rate = $inserted / $elapsed;
            $progress = ($inserted / $TOTAL_PRODUCTS) * 100;

            printf(
                "Progress: %d/%d (%.1f%%) | Rate: %.0f rows/sec | Elapsed: %.1fs\n",
                $inserted,
                $TOTAL_PRODUCTS,
                $progress,
                $rate,
                $elapsed
            );
        }
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
        mysqli_rollback($conn);
        break;
    }
}

// Final commit
mysqli_commit($conn);

// Re-enable keys
echo "\nRe-enabling indexes...\n";
mysqli_query($conn, "ALTER TABLE products ENABLE KEYS");

// Enable autocommit
mysqli_autocommit($conn, TRUE);

// Calculate performance metrics
$end_time = microtime(true);
$total_time = $end_time - $start_time;
$rows_per_second = $inserted / $total_time;

echo "\n=== COMPLETION REPORT ===\n";
echo "Total products inserted: " . number_format($inserted) . "\n";
echo "Total time: " . number_format($total_time, 2) . " seconds\n";
echo "Average rate: " . number_format($rows_per_second, 0) . " rows/second\n";
echo "Time per 1000 rows: " . number_format(($total_time / $inserted) * 1000, 2) . " seconds\n";

// Verify count
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products");
$row = mysqli_fetch_assoc($result);
echo "\nVerification: Database contains " . number_format($row['count']) . " products\n";

// Close connection
mysqli_close($conn);

echo "\n✓ DONE!\n";
?>