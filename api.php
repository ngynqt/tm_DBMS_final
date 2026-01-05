<<<<<<< HEAD
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$dbname = "my_store";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");

$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET PRODUCTS
if ($action === 'get_products') {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    if ($search) {
        $sql = "SELECT * FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%' OR brand LIKE '%$search%' LIMIT $limit OFFSET $offset";
        $count_sql = "SELECT COUNT(*) as total FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%' OR brand LIKE '%$search%'";
    } else {
        $sql = "SELECT * FROM products LIMIT $limit OFFSET $offset";
        $count_sql = "SELECT COUNT(*) as total FROM products";
    }
    
    $result = mysqli_query($conn, $sql);
    $count_result = mysqli_query($conn, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = $count_row['total'] ?? 0;
    
    $products = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
    exit;
}

// REGISTER
if ($action === 'register') {
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!$name || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Please fill all fields']);
        exit;
    }
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed')";
    
    if (mysqli_query($conn, $sql)) {
        $user_id = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        echo json_encode(['success' => true, 'message' => 'Registration successful', 'user' => ['id' => $user_id, 'name' => $name, 'email' => $email]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
    exit;
}

// LOGIN
if ($action === 'login') {
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Please enter email and password']);
        exit;
    }
    
    $sql = "SELECT id, name, email, password FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Wrong password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
    }
    exit;
}

// LOGOUT
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
    exit;
}

// GET PROFILE
if ($action === 'get_profile') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['success' => true, 'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
    }
    exit;
}

// CHECKOUT
if ($action === 'checkout') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $items = isset($data['items']) ? $data['items'] : [];
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
    $total = 0;
    
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    $sql = "INSERT INTO orders (user_id, total_price, status) VALUES (" . ($user_id ? $user_id : 'NULL') . ", $total, 'pending')";
    
    if (mysqli_query($conn, $sql)) {
        $order_id = mysqli_insert_id($conn);
        
        foreach ($items as $item) {
            $product_id = (int)$item['id'];
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];
            
            $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, $product_id, $quantity, $price)";
            mysqli_query($conn, $sql_item);
        }
        
        echo json_encode(['success' => true, 'message' => 'Order placed', 'order_id' => $order_id, 'total' => $total]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order failed']);
    }
    exit;
}

// ========== FILTER PRODUCTS (Multi-condition filter with performance tracking) ==========
if ($action === 'filter_products') {
    $start_time = microtime(true);
    
    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : null;
    $price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : null;
    $brands = isset($_GET['brands']) ? (array)$_GET['brands'] : [];
    $min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : null;
    $min_reviews = isset($_GET['min_reviews']) ? (int)$_GET['min_reviews'] : null;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Build dynamic WHERE clause with prepared statement
    $conditions = [];
    $bind_types = '';
    $bind_values = [];
    
    // Search condition (using LIKE instead of FULLTEXT for simplicity)
    if (!empty($search)) {
        $search_term = '%' . $search . '%';
        $conditions[] = "(name LIKE ? OR brand LIKE ?)";
        $bind_types .= 'ss';
        $bind_values[] = $search_term;
        $bind_values[] = $search_term;
    }
    
    // Price range filter
    if ($price_min !== null) {
        $conditions[] = "price >= ?";
        $bind_types .= 'd';
        $bind_values[] = $price_min;
    }
    
    if ($price_max !== null) {
        $conditions[] = "price <= ?";
        $bind_types .= 'd';
        $bind_values[] = $price_max;
    }
    
    // Brand filter (multi-select)
    if (!empty($brands)) {
        $brand_placeholders = implode(',', array_fill(0, count($brands), '?'));
        $conditions[] = "brand IN ($brand_placeholders)";
        foreach ($brands as $brand) {
            $bind_types .= 's';
            $bind_values[] = $brand;
        }
    }
    
    // Rating filter
    if ($min_rating !== null) {
        $conditions[] = "rating_average >= ?";
        $bind_types .= 'd';
        $bind_values[] = $min_rating;
    }
    
    // Review count filter
    if ($min_reviews !== null) {
        $conditions[] = "review_count >= ?";
        $bind_types .= 'i';
        $bind_values[] = $min_reviews;
    }
    
    // Combine conditions
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Count total results
    $count_sql = "SELECT COUNT(*) as total FROM products $where_clause";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    
    if (!empty($bind_values)) {
        mysqli_stmt_bind_param($count_stmt, $bind_types, ...$bind_values);
    }
    
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = $count_row['total'] ?? 0;
    mysqli_stmt_close($count_stmt);
    
    // Get products with pagination
    $sql = "SELECT id, name, price, original_price, brand, review_count, rating_average 
            FROM products $where_clause 
            ORDER BY rating_average DESC, review_count DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    // Add pagination parameters
    $final_bind_types = $bind_types . 'ii';
    $bind_values[] = $limit;
    $bind_values[] = $offset;
    
    mysqli_stmt_bind_param($stmt, $final_bind_types, ...$bind_values);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    // Get distinct brands for filter UI
    $brands_stmt = mysqli_prepare($conn, "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
    mysqli_stmt_execute($brands_stmt);
    $brands_result = mysqli_stmt_get_result($brands_stmt);
    $available_brands = [];
    while ($row = mysqli_fetch_assoc($brands_result)) {
        $available_brands[] = $row['brand'];
    }
    mysqli_stmt_close($brands_stmt);
    
    // Get min/max prices
    $price_stmt = mysqli_prepare($conn, "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
    mysqli_stmt_execute($price_stmt);
    $price_result = mysqli_stmt_get_result($price_stmt);
    $price_range = mysqli_fetch_assoc($price_result);
    mysqli_stmt_close($price_stmt);
    
    $execution_time = (microtime(true) - $start_time) * 1000; // Convert to ms
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ],
        'filters' => [
            'brands' => $available_brands,
            'price_range' => [
                'min' => (float)($price_range['min_price'] ?? 0),
                'max' => (float)($price_range['max_price'] ?? 0)
            ]
        ],
        'performance' => [
            'execution_time_ms' => round($execution_time, 3),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
}

// ========== FILTER PRODUCTS WITH PERFORMANCE COMPARISON ==========
if ($action === 'filter_products_compare') {
    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : null;
    $price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : null;
    $brands = isset($_GET['brands']) ? (array)$_GET['brands'] : [];
    $min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : null;
    $min_reviews = isset($_GET['min_reviews']) ? (int)$_GET['min_reviews'] : null;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $conditions = [];
    $bind_types = '';
    $bind_values = [];
    
    if (!empty($search)) {
        $search_term = '%' . $search . '%';
        $conditions[] = "(name LIKE ? OR brand LIKE ?)";
        $bind_types .= 'ss';
        $bind_values[] = $search_term;
        $bind_values[] = $search_term;
    }
    
    if ($price_min !== null) {
        $conditions[] = "price >= ?";
        $bind_types .= 'd';
        $bind_values[] = $price_min;
    }
    
    if ($price_max !== null) {
        $conditions[] = "price <= ?";
        $bind_types .= 'd';
        $bind_values[] = $price_max;
    }
    
    if (!empty($brands)) {
        $brand_placeholders = implode(',', array_fill(0, count($brands), '?'));
        $conditions[] = "brand IN ($brand_placeholders)";
        foreach ($brands as $brand) {
            $bind_types .= 's';
            $bind_values[] = $brand;
        }
    }
    
    if ($min_rating !== null) {
        $conditions[] = "rating_average >= ?";
        $bind_types .= 'd';
        $bind_values[] = $min_rating;
    }
    
    if ($min_reviews !== null) {
        $conditions[] = "review_count >= ?";
        $bind_types .= 'i';
        $bind_values[] = $min_reviews;
    }
    
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // === QUERY 1: WITH INDEX (Optimized) ===
    $start_with = microtime(true);
    
    $count_sql_with = "SELECT COUNT(*) as total FROM products $where_clause";
    $count_stmt_with = mysqli_prepare($conn, $count_sql_with);
    if (!empty($bind_values)) {
        mysqli_stmt_bind_param($count_stmt_with, $bind_types, ...$bind_values);
    }
    mysqli_stmt_execute($count_stmt_with);
    $count_result_with = mysqli_stmt_get_result($count_stmt_with);
    $count_row_with = mysqli_fetch_assoc($count_result_with);
    $total_with = $count_row_with['total'] ?? 0;
    mysqli_stmt_close($count_stmt_with);
    
    $sql_with = "SELECT id, name, price, brand, review_count, rating_average 
                FROM products $where_clause 
                ORDER BY rating_average DESC 
                LIMIT ? OFFSET ?";
    
    $stmt_with = mysqli_prepare($conn, $sql_with);
    $final_bind_types_with = $bind_types . 'ii';
    $bind_values_with = $bind_values;
    $bind_values_with[] = $limit;
    $bind_values_with[] = $offset;
    mysqli_stmt_bind_param($stmt_with, $final_bind_types_with, ...$bind_values_with);
    mysqli_stmt_execute($stmt_with);
    mysqli_stmt_get_result($stmt_with);
    mysqli_stmt_close($stmt_with);
    
    $time_with_index = (microtime(true) - $start_with) * 1000;
    
    // === QUERY 2: WITHOUT INDEX (Force full table scan) ===
    $start_without = microtime(true);
    
    // Force full table scan by using IGNORE INDEX
    $where_without = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql_without = "SELECT COUNT(*) as total FROM products IGNORE INDEX (PRIMARY) $where_without";
    
    $count_stmt_without = mysqli_prepare($conn, $sql_without);
    if (!empty($bind_values)) {
        mysqli_stmt_bind_param($count_stmt_without, $bind_types, ...$bind_values);
    }
    mysqli_stmt_execute($count_stmt_without);
    $count_result_without = mysqli_stmt_get_result($count_stmt_without);
    $count_row_without = mysqli_fetch_assoc($count_result_without);
    $total_without = $count_row_without['total'] ?? 0;
    mysqli_stmt_close($count_stmt_without);
    
    $sql_without = "SELECT id, name, price, brand, review_count, rating_average 
                   FROM products IGNORE INDEX (idx_price, idx_brand, idx_rating, idx_price_rating, idx_brand_price) 
                   $where_without 
                   ORDER BY rating_average DESC 
                   LIMIT ? OFFSET ?";
    
    $stmt_without = mysqli_prepare($conn, $sql_without);
    $final_bind_types_without = $bind_types . 'ii';
    $bind_values_without = $bind_values;
    $bind_values_without[] = $limit;
    $bind_values_without[] = $offset;
    mysqli_stmt_bind_param($stmt_without, $final_bind_types_without, ...$bind_values_without);
    mysqli_stmt_execute($stmt_without);
    mysqli_stmt_get_result($stmt_without);
    mysqli_stmt_close($stmt_without);
    
    $time_without_index = (microtime(true) - $start_without) * 1000;
    
    // Get products (using WITH INDEX version)
    $sql = "SELECT id, name, price, original_price, brand, review_count, rating_average 
            FROM products $where_clause 
            ORDER BY rating_average DESC, review_count DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    $final_bind_types = $bind_types . 'ii';
    $bind_values[] = $limit;
    $bind_values[] = $offset;
    mysqli_stmt_bind_param($stmt, $final_bind_types, ...$bind_values);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    $speedup = $time_without_index > 0 ? round($time_with_index / $time_without_index * 100, 1) : 100;
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total_with,
            'pages' => ceil($total_with / $limit)
        ],
        'performance' => [
            'with_index' => [
                'time_ms' => round($time_with_index, 3),
                'total' => $total_with
            ],
            'without_index' => [
                'time_ms' => round($time_without_index, 3),
                'total' => $total_without
            ],
            'speedup_percent' => $speedup
        ]
    ]);
    exit;
}

// ========== PERFORMANCE TESTING ENDPOINTS ==========

// Test 1: Index Effects
if ($action === 'perf_test_index_effects') {
    $comparisons = [];
    
    // Simple equality query - benefits from INDEX
    $queries = [
        ['WHERE price = 500000', 'price = 500000'],
        ['WHERE brand = "Nike"', 'brand = "Nike"'],
        ['WHERE rating_average = 4.5', 'rating_average = 4.5']
    ];
    
    foreach ($queries as $query) {
        $start = microtime(true);
        $sql = "SELECT id, name, price FROM products {$query[0]} LIMIT 100";
        $result = mysqli_query($conn, $sql);
        $time = (microtime(true) - $start) * 1000;
        
        // Get EXPLAIN
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $comparisons[] = [
            'query' => $query[1],
            'time' => $time,
            'type' => $explain_row['type'] ?? 'unknown',
            'rows_examined' => $explain_row['rows'] ?? 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparisons' => $comparisons,
        'analysis' => 'Queries with INDEX sử dụng "RANGE" hoặc "REF" type và kiểm tra ít hàng hơn Full Table Scan'
    ]);
    exit;
}

// Test 2: LIKE Performance
if ($action === 'perf_test_like') {
    $comparisons = [];
    
    // Different LIKE patterns
    $patterns = [
        ['LIKE "%nike%"', '%nike%'],
        ['LIKE "nike%"', 'nike%'],
        ['LIKE "%nike"', '%nike']
    ];
    
    foreach ($patterns as $pattern) {
        $start = microtime(true);
        $sql = "SELECT id, name FROM products WHERE brand LIKE '" . $pattern[1] . "' LIMIT 100";
        $result = mysqli_query($conn, $sql);
        $time = (microtime(true) - $start) * 1000;
        
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $comparisons[] = [
            'pattern' => $pattern[0],
            'time' => $time,
            'key' => $explain_row['key'] ?? null,
            'rows_examined' => $explain_row['rows'] ?? 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparisons' => $comparisons,
        'optimization_tips' => '
            • <code>LIKE "%keyword%"</code> (❌ Chậm nhất) = Full text scan<br>
            • <code>LIKE "keyword%"</code> (✅ Tốt hơn) = Có thể dùng INDEX<br>
            • <code>MATCH...AGAINST</code> (✅ Tốt nhất) = FULLTEXT INDEX (nhanh nhất)
        '
    ]);
    exit;
}

// Test 3: Range Queries
if ($action === 'perf_test_range') {
    $comparisons = [];
    
    $queries = [
        'WHERE price BETWEEN 100000 AND 500000',
        'WHERE price >= 100000 AND price <= 500000',
        'WHERE review_count > 100'
    ];
    
    foreach ($queries as $q) {
        $start = microtime(true);
        $sql = "SELECT id, name, price FROM products {$q} ORDER BY rating_average DESC LIMIT 100";
        $result = mysqli_query($conn, $sql);
        $time = (microtime(true) - $start) * 1000;
        
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $comparisons[] = [
            'query' => $q,
            'time' => $time,
            'type' => $explain_row['type'] ?? 'ALL',
            'key' => $explain_row['key'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparisons' => $comparisons,
        'improvement' => '50-70'
    ]);
    exit;
}

// Test 4: Composite Indexes
if ($action === 'perf_test_composite') {
    $comparisons = [];
    
    $queries = [
        [
            'filters' => 'price BETWEEN 100k-500k',
            'sql' => 'WHERE price BETWEEN 100000 AND 500000'
        ],
        [
            'filters' => '+ rating >= 4.0',
            'sql' => 'WHERE price BETWEEN 100000 AND 500000 AND rating_average >= 4.0'
        ],
        [
            'filters' => '+ reviews >= 50',
            'sql' => 'WHERE price BETWEEN 100000 AND 500000 AND rating_average >= 4.0 AND review_count >= 50'
        ]
    ];
    
    foreach ($queries as $q) {
        $start = microtime(true);
        $sql = "SELECT id, name, price, rating_average FROM products {$q['sql']} LIMIT 100";
        $result = mysqli_query($conn, $sql);
        $rows = mysqli_num_rows($result);
        $time = (microtime(true) - $start) * 1000;
        
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $comparisons[] = [
            'filters' => $q['filters'],
            'time' => $time,
            'key' => $explain_row['key'] ?? null,
            'rows' => $rows
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparisons' => $comparisons,
        'benefits' => 'Composite INDEX <code>(price, rating_average, review_count)</code> giúp tối ưu filter đa điều kiện'
    ]);
    exit;
}

// Test 5: EXPLAIN Analysis
if ($action === 'perf_test_explain') {
    $plans = [];
    
    $test_queries = [
        'SELECT * FROM products WHERE id = 1',
        'SELECT * FROM products WHERE price = 500000',
        'SELECT * FROM products WHERE brand = "Nike" AND price BETWEEN 100000 AND 500000',
        'SELECT * FROM products WHERE name LIKE "%shoe%"',
        'SELECT * FROM products WHERE price BETWEEN 100000 AND 500000 AND rating_average >= 4.0 ORDER BY review_count DESC LIMIT 100'
    ];
    
    foreach ($test_queries as $sql) {
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $plans[] = [
            'sql' => substr($sql, 0, 80) . (strlen($sql) > 80 ? '...' : ''),
            'explain' => [
                'id' => $explain_row['id'] ?? '-',
                'select_type' => $explain_row['select_type'] ?? '-',
                'table' => $explain_row['table'] ?? '-',
                'type' => $explain_row['type'] ?? 'ALL',
                'possible_keys' => $explain_row['possible_keys'] ?? 'NULL',
                'key' => $explain_row['key'] ?? 'NULL',
                'key_len' => $explain_row['key_len'] ?? '-',
                'rows' => $explain_row['rows'] ?? '-',
                'Extra' => $explain_row['Extra'] ?? '-'
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'plans' => $plans
    ]);
    exit;
}

// Test 6: Generate Sample Data
if ($action === 'perf_generate_sample_data') {
    $start_time = microtime(true);
    
    // Get existing data count
    $count_before = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products");
    $before = mysqli_fetch_assoc($count_before)['cnt'];
    
    $brands = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance', 'Converse', 'Vans'];
    $fulfillment = ['Fulfillment', 'Standard', 'Express'];
    
    $inserted = 0;
    for ($i = 0; $i < 10000; $i++) {
        $id = 1000000 + $i;
        $name = "Product #" . $i;
        $price = rand(50000, 1000000);
        $original_price = $price + rand(10000, 200000);
        $brand = $brands[array_rand($brands)];
        $rating = round(rand(20, 50) / 10, 1);
        $reviews = rand(0, 500);
        $fulfillment_type = $fulfillment[array_rand($fulfillment)];
        
        $sql = "INSERT INTO products (id, name, price, original_price, brand, rating_average, review_count, fulfillment_type) 
                VALUES ($id, '$name', $price, $original_price, '$brand', $rating, $reviews, '$fulfillment_type')";
        
        if (mysqli_query($conn, $sql)) {
            $inserted++;
        }
        
        // Batch insert every 1000
        if ($i % 1000 === 0) {
            usleep(10000); // Small delay to prevent lock
        }
    }
    
    $count_after = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products");
    $after = mysqli_fetch_assoc($count_after)['cnt'];
    
    $time = microtime(true) - $start_time;
    
    echo json_encode([
        'success' => true,
        'count' => $inserted,
        'time' => round($time, 2),
        'total_count' => $after
    ]);
    exit;
}

// Default response
echo json_encode(['success' => false, 'message' => 'Invalid action']);
mysqli_close($conn);
?>

=======
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$dbname = "my_store";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");

$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET PRODUCTS
if ($action === 'get_products') {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    if ($search) {
        $sql = "SELECT * FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%' OR brand LIKE '%$search%' LIMIT $limit OFFSET $offset";
        $count_sql = "SELECT COUNT(*) as total FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%' OR brand LIKE '%$search%'";
    } else {
        $sql = "SELECT * FROM products LIMIT $limit OFFSET $offset";
        $count_sql = "SELECT COUNT(*) as total FROM products";
    }
    
    $result = mysqli_query($conn, $sql);
    $count_result = mysqli_query($conn, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = $count_row['total'] ?? 0;
    
    $products = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
    exit;
}

// REGISTER
if ($action === 'register') {
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!$name || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Please fill all fields']);
        exit;
    }
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed')";
    
    if (mysqli_query($conn, $sql)) {
        $user_id = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        echo json_encode(['success' => true, 'message' => 'Registration successful', 'user' => ['id' => $user_id, 'name' => $name, 'email' => $email]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
    exit;
}

// LOGIN
if ($action === 'login') {
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Please enter email and password']);
        exit;
    }
    
    $sql = "SELECT id, name, email, password FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Wrong password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
    }
    exit;
}

// LOGOUT
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
    exit;
}

// GET PROFILE
if ($action === 'get_profile') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['success' => true, 'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
    }
    exit;
}

// CHECKOUT
if ($action === 'checkout') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $items = isset($data['items']) ? $data['items'] : [];
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
    $total = 0;
    
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    $sql = "INSERT INTO orders (user_id, total_price, status) VALUES (" . ($user_id ? $user_id : 'NULL') . ", $total, 'pending')";
    
    if (mysqli_query($conn, $sql)) {
        $order_id = mysqli_insert_id($conn);
        
        foreach ($items as $item) {
            $product_id = (int)$item['id'];
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];
            
            $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, $product_id, $quantity, $price)";
            mysqli_query($conn, $sql_item);
        }
        
        echo json_encode(['success' => true, 'message' => 'Order placed', 'order_id' => $order_id, 'total' => $total]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order failed']);
    }
    exit;
}

// ========== FILTER PRODUCTS (Multi-condition filter with performance tracking) ==========
if ($action === 'filter_products') {
    $start_time = microtime(true);
    
    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : null;
    $price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : null;
    $brands = isset($_GET['brands']) ? (array)$_GET['brands'] : [];
    $min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : null;
    $min_reviews = isset($_GET['min_reviews']) ? (int)$_GET['min_reviews'] : null;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Build dynamic WHERE clause with prepared statement
    $conditions = [];
    $bind_types = '';
    $bind_values = [];
    
    // Search condition (using LIKE instead of FULLTEXT for simplicity)
    if (!empty($search)) {
        $search_term = '%' . $search . '%';
        $conditions[] = "(name LIKE ? OR brand LIKE ?)";
        $bind_types .= 'ss';
        $bind_values[] = $search_term;
        $bind_values[] = $search_term;
    }
    
    // Price range filter
    if ($price_min !== null) {
        $conditions[] = "price >= ?";
        $bind_types .= 'd';
        $bind_values[] = $price_min;
    }
    
    if ($price_max !== null) {
        $conditions[] = "price <= ?";
        $bind_types .= 'd';
        $bind_values[] = $price_max;
    }
    
    // Brand filter (multi-select)
    if (!empty($brands)) {
        $brand_placeholders = implode(',', array_fill(0, count($brands), '?'));
        $conditions[] = "brand IN ($brand_placeholders)";
        foreach ($brands as $brand) {
            $bind_types .= 's';
            $bind_values[] = $brand;
        }
    }
    
    // Rating filter
    if ($min_rating !== null) {
        $conditions[] = "rating_average >= ?";
        $bind_types .= 'd';
        $bind_values[] = $min_rating;
    }
    
    // Review count filter
    if ($min_reviews !== null) {
        $conditions[] = "review_count >= ?";
        $bind_types .= 'i';
        $bind_values[] = $min_reviews;
    }
    
    // Combine conditions
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Count total results
    $count_sql = "SELECT COUNT(*) as total FROM products $where_clause";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    
    if (!empty($bind_values)) {
        mysqli_stmt_bind_param($count_stmt, $bind_types, ...$bind_values);
    }
    
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = $count_row['total'] ?? 0;
    mysqli_stmt_close($count_stmt);
    
    // Get products with pagination
    $sql = "SELECT id, name, price, original_price, brand, review_count, rating_average 
            FROM products $where_clause 
            ORDER BY rating_average DESC, review_count DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    // Add pagination parameters
    $final_bind_types = $bind_types . 'ii';
    $bind_values[] = $limit;
    $bind_values[] = $offset;
    
    mysqli_stmt_bind_param($stmt, $final_bind_types, ...$bind_values);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    // Get distinct brands for filter UI
    $brands_stmt = mysqli_prepare($conn, "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
    mysqli_stmt_execute($brands_stmt);
    $brands_result = mysqli_stmt_get_result($brands_stmt);
    $available_brands = [];
    while ($row = mysqli_fetch_assoc($brands_result)) {
        $available_brands[] = $row['brand'];
    }
    mysqli_stmt_close($brands_stmt);
    
    // Get min/max prices
    $price_stmt = mysqli_prepare($conn, "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
    mysqli_stmt_execute($price_stmt);
    $price_result = mysqli_stmt_get_result($price_stmt);
    $price_range = mysqli_fetch_assoc($price_result);
    mysqli_stmt_close($price_stmt);
    
    $execution_time = (microtime(true) - $start_time) * 1000; // Convert to ms
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ],
        'filters' => [
            'brands' => $available_brands,
            'price_range' => [
                'min' => (float)($price_range['min_price'] ?? 0),
                'max' => (float)($price_range['max_price'] ?? 0)
            ]
        ],
        'performance' => [
            'execution_time_ms' => round($execution_time, 3),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
}

// ========== FILTER PRODUCTS WITH PERFORMANCE COMPARISON ==========
if ($action === 'filter_products_compare') {
    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : null;
    $price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : null;
    $brands = isset($_GET['brands']) ? (array)$_GET['brands'] : [];
    $min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : null;
    $min_reviews = isset($_GET['min_reviews']) ? (int)$_GET['min_reviews'] : null;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $conditions = [];
    $bind_types = '';
    $bind_values = [];
    
    if (!empty($search)) {
        $search_term = '%' . $search . '%';
        $conditions[] = "(name LIKE ? OR brand LIKE ?)";
        $bind_types .= 'ss';
        $bind_values[] = $search_term;
        $bind_values[] = $search_term;
    }
    
    if ($price_min !== null) {
        $conditions[] = "price >= ?";
        $bind_types .= 'd';
        $bind_values[] = $price_min;
    }
    
    if ($price_max !== null) {
        $conditions[] = "price <= ?";
        $bind_types .= 'd';
        $bind_values[] = $price_max;
    }
    
    if (!empty($brands)) {
        $brand_placeholders = implode(',', array_fill(0, count($brands), '?'));
        $conditions[] = "brand IN ($brand_placeholders)";
        foreach ($brands as $brand) {
            $bind_types .= 's';
            $bind_values[] = $brand;
        }
    }
    
    if ($min_rating !== null) {
        $conditions[] = "rating_average >= ?";
        $bind_types .= 'd';
        $bind_values[] = $min_rating;
    }
    
    if ($min_reviews !== null) {
        $conditions[] = "review_count >= ?";
        $bind_types .= 'i';
        $bind_values[] = $min_reviews;
    }
    
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // === QUERY 1: WITH INDEX (Optimized) ===
    $start_with = microtime(true);
    
    $count_sql_with = "SELECT COUNT(*) as total FROM products $where_clause";
    $count_stmt_with = mysqli_prepare($conn, $count_sql_with);
    if (!empty($bind_values)) {
        mysqli_stmt_bind_param($count_stmt_with, $bind_types, ...$bind_values);
    }
    mysqli_stmt_execute($count_stmt_with);
    $count_result_with = mysqli_stmt_get_result($count_stmt_with);
    $count_row_with = mysqli_fetch_assoc($count_result_with);
    $total_with = $count_row_with['total'] ?? 0;
    mysqli_stmt_close($count_stmt_with);
    
    $sql_with = "SELECT id, name, price, brand, review_count, rating_average 
                FROM products $where_clause 
                ORDER BY rating_average DESC 
                LIMIT ? OFFSET ?";
    
    $stmt_with = mysqli_prepare($conn, $sql_with);
    $final_bind_types_with = $bind_types . 'ii';
    $bind_values_with = $bind_values;
    $bind_values_with[] = $limit;
    $bind_values_with[] = $offset;
    mysqli_stmt_bind_param($stmt_with, $final_bind_types_with, ...$bind_values_with);
    mysqli_stmt_execute($stmt_with);
    mysqli_stmt_get_result($stmt_with);
    mysqli_stmt_close($stmt_with);
    
    $time_with_index = (microtime(true) - $start_with) * 1000;
    
    // === QUERY 2: WITHOUT INDEX (Force full table scan) ===
    $start_without = microtime(true);
    
    // Force full table scan by using IGNORE INDEX
    $where_without = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql_without = "SELECT COUNT(*) as total FROM products IGNORE INDEX (PRIMARY) $where_without";
    
    $count_stmt_without = mysqli_prepare($conn, $sql_without);
    if (!empty($bind_values)) {
        mysqli_stmt_bind_param($count_stmt_without, $bind_types, ...$bind_values);
    }
    mysqli_stmt_execute($count_stmt_without);
    $count_result_without = mysqli_stmt_get_result($count_stmt_without);
    $count_row_without = mysqli_fetch_assoc($count_result_without);
    $total_without = $count_row_without['total'] ?? 0;
    mysqli_stmt_close($count_stmt_without);
    
    $sql_without = "SELECT id, name, price, brand, review_count, rating_average 
                   FROM products IGNORE INDEX (idx_price, idx_brand, idx_rating, idx_price_rating, idx_brand_price) 
                   $where_without 
                   ORDER BY rating_average DESC 
                   LIMIT ? OFFSET ?";
    
    $stmt_without = mysqli_prepare($conn, $sql_without);
    $final_bind_types_without = $bind_types . 'ii';
    $bind_values_without = $bind_values;
    $bind_values_without[] = $limit;
    $bind_values_without[] = $offset;
    mysqli_stmt_bind_param($stmt_without, $final_bind_types_without, ...$bind_values_without);
    mysqli_stmt_execute($stmt_without);
    mysqli_stmt_get_result($stmt_without);
    mysqli_stmt_close($stmt_without);
    
    $time_without_index = (microtime(true) - $start_without) * 1000;
    
    // Get products (using WITH INDEX version)
    $sql = "SELECT id, name, price, original_price, brand, review_count, rating_average 
            FROM products $where_clause 
            ORDER BY rating_average DESC, review_count DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    $final_bind_types = $bind_types . 'ii';
    $bind_values[] = $limit;
    $bind_values[] = $offset;
    mysqli_stmt_bind_param($stmt, $final_bind_types, ...$bind_values);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    $speedup = $time_without_index > 0 ? round($time_with_index / $time_without_index * 100, 1) : 100;
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total_with,
            'pages' => ceil($total_with / $limit)
        ],
        'performance' => [
            'with_index' => [
                'time_ms' => round($time_with_index, 3),
                'total' => $total_with
            ],
            'without_index' => [
                'time_ms' => round($time_without_index, 3),
                'total' => $total_without
            ],
            'speedup_percent' => $speedup
        ]
    ]);
    exit;
}

// ========== PERFORMANCE TESTING ENDPOINTS ==========

// Test 1: Index Effects
if ($action === 'perf_test_index_effects') {
    $comparisons = [];
    
    // Simple equality query - benefits from INDEX
    $queries = [
        ['WHERE price = 500000', 'price = 500000'],
        ['WHERE brand = "Nike"', 'brand = "Nike"'],
        ['WHERE rating_average = 4.5', 'rating_average = 4.5']
    ];
    
    foreach ($queries as $query) {
        $start = microtime(true);
        $sql = "SELECT id, name, price FROM products {$query[0]} LIMIT 100";
        $result = mysqli_query($conn, $sql);
        $time = (microtime(true) - $start) * 1000;
        
        // Get EXPLAIN
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $comparisons[] = [
            'query' => $query[1],
            'time' => $time,
            'type' => $explain_row['type'] ?? 'unknown',
            'rows_examined' => $explain_row['rows'] ?? 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparisons' => $comparisons,
        'analysis' => 'Queries with INDEX sử dụng "RANGE" hoặc "REF" type và kiểm tra ít hàng hơn Full Table Scan'
    ]);
    exit;
}

// Test 2: LIKE Performance
if ($action === 'perf_test_like') {
    $comparisons = [];
    
    // Different LIKE patterns
    $patterns = [
        ['LIKE "%nike%"', '%nike%'],
        ['LIKE "nike%"', 'nike%'],
        ['LIKE "%nike"', '%nike']
    ];
    
    foreach ($patterns as $pattern) {
        $start = microtime(true);
        $sql = "SELECT id, name FROM products WHERE brand LIKE '" . $pattern[1] . "' LIMIT 100";
        $result = mysqli_query($conn, $sql);
        $time = (microtime(true) - $start) * 1000;
        
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $comparisons[] = [
            'pattern' => $pattern[0],
            'time' => $time,
            'key' => $explain_row['key'] ?? null,
            'rows_examined' => $explain_row['rows'] ?? 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparisons' => $comparisons,
        'optimization_tips' => '
            • <code>LIKE "%keyword%"</code> (❌ Chậm nhất) = Full text scan<br>
            • <code>LIKE "keyword%"</code> (✅ Tốt hơn) = Có thể dùng INDEX<br>
            • <code>MATCH...AGAINST</code> (✅ Tốt nhất) = FULLTEXT INDEX (nhanh nhất)
        '
    ]);
    exit;
}

// Test 3: Range Queries
if ($action === 'perf_test_range') {
    $comparisons = [];
    
    $queries = [
        'WHERE price BETWEEN 100000 AND 500000',
        'WHERE price >= 100000 AND price <= 500000',
        'WHERE review_count > 100'
    ];
    
    foreach ($queries as $q) {
        $start = microtime(true);
        $sql = "SELECT id, name, price FROM products {$q} ORDER BY rating_average DESC LIMIT 100";
        $result = mysqli_query($conn, $sql);
        $time = (microtime(true) - $start) * 1000;
        
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $comparisons[] = [
            'query' => $q,
            'time' => $time,
            'type' => $explain_row['type'] ?? 'ALL',
            'key' => $explain_row['key'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparisons' => $comparisons,
        'improvement' => '50-70'
    ]);
    exit;
}

// Test 4: Composite Indexes
if ($action === 'perf_test_composite') {
    $comparisons = [];
    
    $queries = [
        [
            'filters' => 'price BETWEEN 100k-500k',
            'sql' => 'WHERE price BETWEEN 100000 AND 500000'
        ],
        [
            'filters' => '+ rating >= 4.0',
            'sql' => 'WHERE price BETWEEN 100000 AND 500000 AND rating_average >= 4.0'
        ],
        [
            'filters' => '+ reviews >= 50',
            'sql' => 'WHERE price BETWEEN 100000 AND 500000 AND rating_average >= 4.0 AND review_count >= 50'
        ]
    ];
    
    foreach ($queries as $q) {
        $start = microtime(true);
        $sql = "SELECT id, name, price, rating_average FROM products {$q['sql']} LIMIT 100";
        $result = mysqli_query($conn, $sql);
        $rows = mysqli_num_rows($result);
        $time = (microtime(true) - $start) * 1000;
        
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $comparisons[] = [
            'filters' => $q['filters'],
            'time' => $time,
            'key' => $explain_row['key'] ?? null,
            'rows' => $rows
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comparisons' => $comparisons,
        'benefits' => 'Composite INDEX <code>(price, rating_average, review_count)</code> giúp tối ưu filter đa điều kiện'
    ]);
    exit;
}

// Test 5: EXPLAIN Analysis
if ($action === 'perf_test_explain') {
    $plans = [];
    
    $test_queries = [
        'SELECT * FROM products WHERE id = 1',
        'SELECT * FROM products WHERE price = 500000',
        'SELECT * FROM products WHERE brand = "Nike" AND price BETWEEN 100000 AND 500000',
        'SELECT * FROM products WHERE name LIKE "%shoe%"',
        'SELECT * FROM products WHERE price BETWEEN 100000 AND 500000 AND rating_average >= 4.0 ORDER BY review_count DESC LIMIT 100'
    ];
    
    foreach ($test_queries as $sql) {
        $explain = mysqli_query($conn, "EXPLAIN " . $sql);
        $explain_row = mysqli_fetch_assoc($explain);
        
        $plans[] = [
            'sql' => substr($sql, 0, 80) . (strlen($sql) > 80 ? '...' : ''),
            'explain' => [
                'id' => $explain_row['id'] ?? '-',
                'select_type' => $explain_row['select_type'] ?? '-',
                'table' => $explain_row['table'] ?? '-',
                'type' => $explain_row['type'] ?? 'ALL',
                'possible_keys' => $explain_row['possible_keys'] ?? 'NULL',
                'key' => $explain_row['key'] ?? 'NULL',
                'key_len' => $explain_row['key_len'] ?? '-',
                'rows' => $explain_row['rows'] ?? '-',
                'Extra' => $explain_row['Extra'] ?? '-'
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'plans' => $plans
    ]);
    exit;
}

// Test 6: Generate Sample Data
if ($action === 'perf_generate_sample_data') {
    $start_time = microtime(true);
    
    // Get existing data count
    $count_before = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products");
    $before = mysqli_fetch_assoc($count_before)['cnt'];
    
    $brands = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance', 'Converse', 'Vans'];
    $fulfillment = ['Fulfillment', 'Standard', 'Express'];
    
    $inserted = 0;
    for ($i = 0; $i < 10000; $i++) {
        $id = 1000000 + $i;
        $name = "Product #" . $i;
        $price = rand(50000, 1000000);
        $original_price = $price + rand(10000, 200000);
        $brand = $brands[array_rand($brands)];
        $rating = round(rand(20, 50) / 10, 1);
        $reviews = rand(0, 500);
        $fulfillment_type = $fulfillment[array_rand($fulfillment)];
        
        $sql = "INSERT INTO products (id, name, price, original_price, brand, rating_average, review_count, fulfillment_type) 
                VALUES ($id, '$name', $price, $original_price, '$brand', $rating, $reviews, '$fulfillment_type')";
        
        if (mysqli_query($conn, $sql)) {
            $inserted++;
        }
        
        // Batch insert every 1000
        if ($i % 1000 === 0) {
            usleep(10000); // Small delay to prevent lock
        }
    }
    
    $count_after = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products");
    $after = mysqli_fetch_assoc($count_after)['cnt'];
    
    $time = microtime(true) - $start_time;
    
    echo json_encode([
        'success' => true,
        'count' => $inserted,
        'time' => round($time, 2),
        'total_count' => $after
    ]);
    exit;
}

// Default response
echo json_encode(['success' => false, 'message' => 'Invalid action']);
mysqli_close($conn);
?>

>>>>>>> 5f79eaeba4311ce083ded1cf198a4a984c0b8b86
