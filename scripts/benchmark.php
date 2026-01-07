<?php
/**
 * Performance Benchmark Script
 * Tests replication and query performance
 */

// Configuration
$MASTER_HOST = '127.0.0.1';
$MASTER_PORT = 3306;
$SLAVE_HOST = '127.0.0.1';
$SLAVE_PORT = 3307;
$DB_USER = 'root';
$DB_PASS = 'rootpassword';
$DB_NAME = 'my_store';

echo "=== MySQL Replication Performance Benchmark ===\n\n";

// Connect to Master
$master = mysqli_connect($MASTER_HOST, $DB_USER, $DB_PASS, $DB_NAME, $MASTER_PORT);
if (!$master) {
    die("Cannot connect to Master: " . mysqli_connect_error() . "\n");
}
echo "✓ Connected to Master (port $MASTER_PORT)\n";

// Connect to Slave
$slave = mysqli_connect($SLAVE_HOST, $DB_USER, $DB_PASS, $DB_NAME, $SLAVE_PORT);
if (!$slave) {
    die("Cannot connect to Slave: " . mysqli_connect_error() . "\n");
}
echo "✓ Connected to Slave (port $SLAVE_PORT)\n\n";

// Test 1: Count products
echo "--- TEST 1: Count Products ---\n";
$queries = [
    "SELECT COUNT(*) as count FROM products",
    "SELECT COUNT(*) as count FROM products WHERE price BETWEEN 100000 AND 500000",
    "SELECT COUNT(*) as count FROM products WHERE brand = 'Nike'",
];

foreach ($queries as $query) {
    // Master
    $start = microtime(true);
    $result = mysqli_query($master, $query);
    $master_time = (microtime(true) - $start) * 1000;
    $row = mysqli_fetch_assoc($result);
    $master_count = $row['count'];

    // Slave
    $start = microtime(true);
    $result = mysqli_query($slave, $query);
    $slave_time = (microtime(true) - $start) * 1000;
    $row = mysqli_fetch_assoc($result);
    $slave_count = $row['count'];

    echo "Query: $query\n";
    echo "  Master: " . number_format($master_count) . " rows in " . number_format($master_time, 2) . "ms\n";
    echo "  Slave:  " . number_format($slave_count) . " rows in " . number_format($slave_time, 2) . "ms\n";
    echo "  Replicated: " . ($master_count == $slave_count ? "✓ YES" : "✗ NO") . "\n\n";
}

// Test 2: Index Performance
echo "--- TEST 2: Index Performance ---\n";
$test_queries = [
    ["name" => "Simple WHERE", "query" => "SELECT * FROM products WHERE price = 199000 LIMIT 100"],
    ["name" => "Range Query", "query" => "SELECT * FROM products WHERE price BETWEEN 100000 AND 500000 LIMIT 100"],
    ["name" => "Composite Index", "query" => "SELECT * FROM products WHERE brand = 'Nike' AND price < 500000 LIMIT 100"],
    ["name" => "ORDER BY", "query" => "SELECT * FROM products ORDER BY rating_average DESC LIMIT 100"],
];

foreach ($test_queries as $test) {
    $start = microtime(true);
    $result = mysqli_query($slave, $test['query']);
    $query_time = (microtime(true) - $start) * 1000;
    $rows = mysqli_num_rows($result);

    echo $test['name'] . ": " . $rows . " rows in " . number_format($query_time, 2) . "ms\n";

    // Get EXPLAIN
    $explain = mysqli_query($slave, "EXPLAIN " . $test['query']);
    $explain_row = mysqli_fetch_assoc($explain);
    echo "  → type: " . $explain_row['type'] . ", key: " . ($explain_row['key'] ?? 'NULL') . "\n\n";
}

// Test 3: Write to Master, Read from Slave
echo "--- TEST 3: Replication Lag Test ---\n";
$test_name = "Replication Test Product " . time();
$test_name_escaped = mysqli_real_escape_string($master, $test_name);

// Insert on Master
echo "Inserting test product on Master...\n";
$insert_query = "INSERT INTO products (name, price, brand) VALUES ('$test_name_escaped', 999999, 'TestBrand')";
mysqli_query($master, $insert_query);
$inserted_id = mysqli_insert_id($master);
echo "  → Inserted product ID: $inserted_id\n";

// Wait and check on Slave
sleep(1);
$check_query = "SELECT * FROM products WHERE id = $inserted_id";
$result = mysqli_query($slave, $check_query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "  → Replicated to Slave: ✓ YES\n";
    echo "  → Product name: " . $row['name'] . "\n";
} else {
    echo "  → Replicated to Slave: ✗ NO (replication lag?)\n";
}

// Clean up test data
mysqli_query($master, "DELETE FROM products WHERE id = $inserted_id");
echo "  → Test data cleaned up\n\n";

// Test 4: Replication Status
echo "--- TEST 4: Replication Status ---\n";
$status = mysqli_query($slave, "SHOW SLAVE STATUS");
if ($status && mysqli_num_rows($status) > 0) {
    $row = mysqli_fetch_assoc($status);
    echo "Slave_IO_Running: " . $row['Slave_IO_Running'] . "\n";
    echo "Slave_SQL_Running: " . $row['Slave_SQL_Running'] . "\n";
    echo "Seconds_Behind_Master: " . $row['Seconds_Behind_Master'] . " seconds\n";
    echo "Last_Error: " . ($row['Last_Error'] ?: "(none)") . "\n";
} else {
    echo "⚠ Replication not configured on Slave\n";
}

// Close connections
mysqli_close($master);
mysqli_close($slave);

echo "\n=== Benchmark Complete ===\n";
?>