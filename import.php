<?php
require 'db.php';

$data_dir = 'data/';
$csv_files = array_filter(scandir($data_dir), function($file) {
    return pathinfo($file, PATHINFO_EXTENSION) === 'csv';
});
sort($csv_files);

$message = '';
$import_file = isset($_POST['csv_file']) ? $_POST['csv_file'] : '';
$import_all = isset($_POST['import_all']) ? true : false;

function import_csv_file($csv_path, $conn) {
    if (!file_exists($csv_path)) {
        return ['success' => false, 'count' => 0, 'error' => 'File not found'];
    }
    
    if (($handle = fopen($csv_path, 'r')) !== false) {
        fgetcsv($handle);
        $count = 0;
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            if (count($row) < 10) continue;

            $id = $row[1];
            $name = mysqli_real_escape_string($conn, $row[2]);
            $description = mysqli_real_escape_string($conn, $row[3]);
            $original_price = (float)$row[4];
            $price = (float)$row[5];
            $fulfillment = mysqli_real_escape_string($conn, $row[6]);
            $brand = mysqli_real_escape_string($conn, $row[7]);
            $review_count = (int)$row[8];
            $rating_average = (float)$row[9];

            $sql = "INSERT INTO products (id, name, description, original_price, price, fulfillment_type, brand, review_count, rating_average) 
                    VALUES ('$id', '$name', '$description', '$original_price', '$price', '$fulfillment', '$brand', '$review_count', '$rating_average')
                    ON DUPLICATE KEY UPDATE name = VALUES(name)";

            mysqli_query($conn, $sql);
            $count++;
        }
        fclose($handle);
        return ['success' => true, 'count' => $count];
    } else {
        return ['success' => false, 'count' => 0, 'error' => 'Cannot open file'];
    }
}

// Import All Files
if ($import_all) {
    $total = 0;
    $files_imported = 0;
    
    foreach ($csv_files as $file) {
        $result = import_csv_file($data_dir . $file, $conn);
        if ($result['success']) {
            $total += $result['count'];
            $files_imported++;
        }
    }
    
    $message = '<p style="color:green;"><strong>✓ Thành công!</strong> Đã import ' . $total . ' sản phẩm từ ' . $files_imported . ' file CSV</p>';
}
// Import Single File
elseif ($import_file) {
    $csv_path = $data_dir . basename($import_file);
    
    if (!in_array(basename($import_file), $csv_files)) {
        $message = '<p style="color:red;">❌ File không hợp lệ</p>';
    } else {
        $result = import_csv_file($csv_path, $conn);
        if ($result['success']) {
            $message = '<p style="color:green;"><strong>✓ Thành công!</strong> Đã import ' . $result['count'] . ' sản phẩm từ ' . htmlspecialchars(basename($csv_path)) . '</p>';
        } else {
            $message = '<p style="color:red;">❌ ' . $result['error'] . '</p>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Import Data - DBMS Shop</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .import-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .import-container h1 {
            margin-top: 0;
            color: #333;
        }
        .file-list {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .file-list h3 {
            margin-top: 0;
            color: #666;
        }
        .file-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-item label {
            flex: 1;
            margin: 0;
            cursor: pointer;
        }
        .file-item input[type="radio"] {
            margin-right: 10px;
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
            background: #f0f0f0;
            border-left: 4px solid #999;
        }
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .import-btn {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            flex: 1;
        }
        .import-btn:hover {
            background: #45a049;
        }
        .import-all-btn {
            background: #2196F3;
            flex: 1;
        }
        .import-all-btn:hover {
            background: #0b7dda;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .quick-setup {
            background: #e3f2fd;
            border: 2px solid #2196F3;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .quick-setup h3 {
            margin-top: 0;
            color: #1976d2;
        }
        .quick-setup p {
            margin: 10px 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="import-container">
        <h1>📊 Import Product Data</h1>
        
        <?php if ($message) echo $message; ?>
        
        <div class="quick-setup">
            <h3>⚡ Quick Setup (Nên làm trước)</h3>
            <p>Click <strong>"Import All"</strong> để import tất cả 6 file CSV (≈2000+ sản phẩm)</p>
            <p>Sau đó vào <strong>index.php</strong> để xem sản phẩm</p>
        </div>
        
        <form method="POST">
            <div class="buttons">
                <button type="submit" name="import_all" value="1" class="import-btn import-all-btn">
                    ✓ Import All (6 Files)
                </button>
            </div>
        </form>
        
        <form method="POST">
            <div class="file-list">
                <h3>Hoặc chọn từng file riêng:</h3>
                
                <?php if (empty($csv_files)): ?>
                    <p style="color:red;">❌ Không tìm thấy file CSV trong thư mục data/</p>
                <?php else: ?>
                    <?php foreach ($csv_files as $file): ?>
                        <div class="file-item">
                            <label>
                                <input type="radio" name="csv_file" value="<?php echo htmlspecialchars($file); ?>" 
                                    <?php echo ($import_file === $file) ? 'checked' : ''; ?>>
                                📄 <?php echo htmlspecialchars($file); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($csv_files)): ?>
                <button type="submit" class="import-btn">▶ Import File Được Chọn</button>
            <?php endif; ?>
        </form>
        
        <a href="index.php" class="back-link">← Quay lại cửa hàng</a>
        <a href="setup_db.php" class="back-link" style="margin-left: 20px;">⚙ Thiết lập Database</a>
    </div>
</body>
</html>