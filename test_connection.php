<?php
/**
 * Test Database Connection
 * Simple script to verify Master/Slave connections
 */

require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
        }

        .success {
            color: #28a745;
            font-weight: bold;
        }

        .error {
            color: #dc3545;
            font-weight: bold;
        }

        .warning {
            color: #ffc107;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #007bff;
            color: white;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }

        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <div class="box">
        <h1>🗄️ Database Connection Test</h1>

        <h2>Master Connection (Write)</h2>
        <?php
        $master = getWriteConnection();
        if ($master) {
            echo "<p class='success'>✓ Master Connected (localhost:3308)</p>";

            // Get product count
            $result = mysqli_query($master, "SELECT COUNT(*) as count FROM products");
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                echo "<p><strong>Total Products:</strong> " . number_format($row['count']) . " rows</p>";
            }

            // Test query performance
            $start = microtime(true);
            $result = mysqli_query($master, "SELECT * FROM products WHERE price BETWEEN 500000 AND 1000000 LIMIT 100");
            $time = (microtime(true) - $start) * 1000;

            echo "<p><strong>Query Performance:</strong> " . number_format($time, 2) . " ms (100 rows)</p>";
        } else {
            echo "<p class='error'>✗ Master Connection Failed</p>";
            echo "<p>Error: " . mysqli_connect_error() . "</p>";
        }
        ?>

        <h2>Slave Connection (Read)</h2>
        <?php
        $slave = getReadConnection();
        if ($slave && $slave !== $master) {
            echo "<p class='success'>✓ Slave Connected (localhost:3307)</p>";

            // Get product count from slave
            $result = mysqli_query($slave, "SELECT COUNT(*) as count FROM products");
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                echo "<p><strong>Total Products on Slave:</strong> " . number_format($row['count']) . " rows</p>";
            }

            // Check replication lag
            $result = mysqli_query($slave, "SHOW SLAVE STATUS");
            if ($result && mysqli_num_rows($result) > 0) {
                $status = mysqli_fetch_assoc($result);
                $lag = $status['Seconds_Behind_Master'];

                if ($lag === '0' || $lag === 0) {
                    echo "<p class='success'>✓ Replication Lag: 0 seconds (real-time)</p>";
                } else if ($lag === NULL) {
                    echo "<p class='warning'>⚠ Replication not configured</p>";
                } else {
                    echo "<p class='warning'>⚠ Replication Lag: $lag seconds</p>";
                }
            }
        } else if ($slave === $master) {
            echo "<p class='warning'>⚠ Using Master for reads (Slave not available)</p>";
        } else {
            echo "<p class='error'>✗ Slave Connection Failed</p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>📊 Sample Products</h2>
        <?php
        $result = mysqli_query($slave ?: $master, "SELECT id, name, brand, price, rating_average FROM products ORDER BY RAND() LIMIT 10");

        if ($result && mysqli_num_rows($result) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Brand</th><th>Price</th><th>Rating</th></tr>";

            while ($product = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td>" . htmlspecialchars($product['brand']) . "</td>";
                echo "<td>" . number_format($product['price'], 0, ',', '.') . " VND</td>";
                echo "<td>" . number_format($product['rating_average'], 1) . " ⭐</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p class='error'>No products found</p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>✅ Connection Status</h2>
        <?php
        if ($master && ($slave && $slave !== $master || !$slave)) {
            echo "<p class='success' style='font-size: 18px;'>✓ Database connections are working!</p>";
            echo "<p>You can now use the web application.</p>";
            echo "<a href='index.php' class='btn'>Go to Main Page</a>";
        } else {
            echo "<p class='error'>Please check Docker containers are running:</p>";
            echo "<pre>docker ps</pre>";
            echo "<p>If not running, start them:</p>";
            echo "<pre>docker-compose up -d</pre>";
        }
        ?>
    </div>
</body>

</html>