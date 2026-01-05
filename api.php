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

// Default response
echo json_encode(['success' => false, 'message' => 'Invalid action']);
mysqli_close($conn);
?>
