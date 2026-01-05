<<<<<<< HEAD
<?php
$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$dbname = "my_store";

$conn = mysqli_connect($host, $user, $pass);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    echo "✓ Database created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

mysqli_select_db($conn, $dbname);

// Create products table
$sql_products = "CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description LONGTEXT,
    original_price FLOAT,
    price FLOAT NOT NULL,
    fulfillment_type VARCHAR(100),
    brand VARCHAR(100),
    review_count INT DEFAULT 0,
    rating_average FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_price (price),
    INDEX idx_brand (brand),
    INDEX idx_rating (rating_average),
    INDEX idx_review (review_count),
    FULLTEXT INDEX idx_name_search (name),
    FULLTEXT INDEX idx_desc_search (description)
)";

if (mysqli_query($conn, $sql_products)) {
    echo "✓ Products table created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

// Add additional composite indexes for common filter combinations
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_price_rating ON products(price, rating_average)",
    "CREATE INDEX IF NOT EXISTS idx_brand_price ON products(brand, price)",
    "CREATE INDEX IF NOT EXISTS idx_price_range ON products(price, review_count)"
];

foreach ($indexes as $idx_sql) {
    if (mysqli_query($conn, $idx_sql)) {
        echo "✓ Index created<br>";
    } else {
        echo "Index Error: " . mysqli_error($conn) . "<br>";
    }
}

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql_users)) {
    echo "✓ Users table created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

// Create orders table
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NULL,
    total_price FLOAT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql_orders)) {
    echo "✓ Orders table created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

// Create order_items table
$sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price FLOAT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql_order_items)) {
    echo "✓ Order_items table created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

echo "<br><strong style='color:green;'>✓ Setup complete! Now import data via import_csv.php</strong>";
mysqli_close($conn);
?>
=======
<?php
$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$dbname = "my_store";

$conn = mysqli_connect($host, $user, $pass);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    echo "✓ Database created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

mysqli_select_db($conn, $dbname);

// Create products table
$sql_products = "CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description LONGTEXT,
    original_price FLOAT,
    price FLOAT NOT NULL,
    fulfillment_type VARCHAR(100),
    brand VARCHAR(100),
    review_count INT DEFAULT 0,
    rating_average FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_price (price),
    INDEX idx_brand (brand),
    INDEX idx_rating (rating_average),
    INDEX idx_review (review_count),
    FULLTEXT INDEX idx_name_search (name),
    FULLTEXT INDEX idx_desc_search (description)
)";

if (mysqli_query($conn, $sql_products)) {
    echo "✓ Products table created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

// Add additional composite indexes for common filter combinations
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_price_rating ON products(price, rating_average)",
    "CREATE INDEX IF NOT EXISTS idx_brand_price ON products(brand, price)",
    "CREATE INDEX IF NOT EXISTS idx_price_range ON products(price, review_count)"
];

foreach ($indexes as $idx_sql) {
    if (mysqli_query($conn, $idx_sql)) {
        echo "✓ Index created<br>";
    } else {
        echo "Index Error: " . mysqli_error($conn) . "<br>";
    }
}

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql_users)) {
    echo "✓ Users table created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

// Create orders table
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NULL,
    total_price FLOAT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql_orders)) {
    echo "✓ Orders table created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

// Create order_items table
$sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price FLOAT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql_order_items)) {
    echo "✓ Order_items table created<br>";
} else {
    echo "Error: " . mysqli_error($conn) . "<br>";
}

echo "<br><strong style='color:green;'>✓ Setup complete! Now import data via import_csv.php</strong>";
mysqli_close($conn);
?>
>>>>>>> 5f79eaeba4311ce083ded1cf198a4a984c0b8b86
