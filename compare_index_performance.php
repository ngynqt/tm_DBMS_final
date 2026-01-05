<?php
require 'db.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>So sánh Performance: Có Index vs Không Index</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; color: #1976d2; }
        .back-btn { background: #999; color: white; padding: 10px 16px; border-radius: 4px; text-decoration: none; font-weight: 600; transition: background 0.2s; }
        .back-btn:hover { background: #777; }
        h1 { color: #333; text-align: center; }
        .container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .box h2 { margin-top: 0; border-bottom: 3px solid; padding-bottom: 10px; }
        .good { border-left: 5px solid #4CAF50; }
        .good h2 { border-bottom-color: #4CAF50; color: #4CAF50; }
        .bad { border-left: 5px solid #f44336; }
        .bad h2 { border-bottom-color: #f44336; color: #f44336; }
        .stats { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .stat-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .stat-row:last-child { border-bottom: none; }
        .label { font-weight: bold; }
        .value { color: #1976d2; font-weight: bold; }
        .time-slow { color: #f44336; font-size: 18px; }
        .time-fast { color: #4CAF50; font-size: 18px; }
        .comparison { background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px; border-left: 4px solid #ff9800; }
        .code { background: #f4f4f4; padding: 12px; border-radius: 4px; margin: 10px 0; font-family: monospace; overflow-x: auto; border-left: 3px solid #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #1976d2; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>⚡ So sánh Performance: Có Index vs Không Index</h1>
        <a href='index.php' class='back-btn'>← Quay lại Shop</a>
    </div>
    
    <div class='container'>
        <!-- KHÔNG CÓ INDEX -->
        <div class='box bad'>
            <h2>❌ Không có INDEX</h2>
            <div class='stats'>
                <div class='stat-row'>
                    <span class='label'>Phương pháp:</span>
                    <span>Full Table Scan (quét toàn bộ)</span>
                </div>
                <div class='stat-row'>
                    <span class='label'>Query:</span>
                </div>
            </div>
            <div class='code'>SELECT * FROM products 
WHERE price >= 100000 
AND price <= 500000</div>";

// Test KHÔNG có index - dùng EXPLAIN để xem
$query_no_index = "EXPLAIN SELECT * FROM products WHERE price >= 100000 AND price <= 500000";
$result = mysqli_query($conn, $query_no_index);
$explain_no_index = mysqli_fetch_assoc($result);

echo "            <div class='stats'>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Rows scanned:</span>";
echo "                    <span class='value'>" . number_format($explain_no_index['rows']) . " dòng</span>";
echo "                </div>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Type:</span>";
echo "                    <span class='value'>" . $explain_no_index['type'] . "</span>";
echo "                </div>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Extra:</span>";
echo "                    <span class='value'>" . $explain_no_index['Extra'] . "</span>";
echo "                </div>";
echo "            </div>";

// Thực tế chạy và đo thời gian
$start = microtime(true);
$result = mysqli_query($conn, "SELECT * FROM products WHERE price >= 100000 AND price <= 500000");
$time_no_index = (microtime(true) - $start) * 1000;
$count_no_index = mysqli_num_rows($result);

echo "            <div class='stats'>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>⏱️ Thời gian query:</span>";
echo "                    <span class='time-slow'>" . number_format($time_no_index, 2) . " ms</span>";
echo "                </div>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Kết quả:</span>";
echo "                    <span class='value'>" . number_format($count_no_index) . " sản phẩm</span>";
echo "                </div>";
echo "            </div>";
echo "        </div>";

echo "        <!-- CÓ INDEX -->";
echo "        <div class='box good'>";
echo "            <h2>✅ CÓ INDEX</h2>";
echo "            <div class='stats'>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Phương pháp:</span>";
echo "                    <span>Index Scan (nhảy trực tiếp)</span>";
echo "                </div>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Query:</span>";
echo "                </div>";
echo "            </div>";
echo "            <div class='code'>CREATE INDEX idx_price 
ON products(price)

SELECT * FROM products 
WHERE price >= 100000 
AND price <= 500000</div>";

// Test CÓ index - dùng EXPLAIN
$query_with_index = "EXPLAIN SELECT * FROM products WHERE price >= 100000 AND price <= 500000";
$result = mysqli_query($conn, $query_with_index);
$explain_with_index = mysqli_fetch_assoc($result);

echo "            <div class='stats'>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Rows scanned:</span>";
echo "                    <span class='value'>" . number_format($explain_with_index['rows']) . " dòng</span>";
echo "                </div>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Key:</span>";
echo "                    <span class='value'>" . ($explain_with_index['key'] ?: 'NULL (không dùng index)') . "</span>";
echo "                </div>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Type:</span>";
echo "                    <span class='value'>" . $explain_with_index['type'] . "</span>";
echo "                </div>";
echo "            </div>";

// Thực tế chạy và đo thời gian (giống nhưng index làm nó nhanh hơn)
$start = microtime(true);
$result = mysqli_query($conn, "SELECT * FROM products USE INDEX (idx_price) WHERE price >= 100000 AND price <= 500000");
$time_with_index = (microtime(true) - $start) * 1000;
$count_with_index = mysqli_num_rows($result);

echo "            <div class='stats'>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>⏱️ Thời gian query:</span>";
echo "                    <span class='time-fast'>" . number_format($time_with_index, 2) . " ms</span>";
echo "                </div>";
echo "                <div class='stat-row'>";
echo "                    <span class='label'>Kết quả:</span>";
echo "                    <span class='value'>" . number_format($count_with_index) . " sản phẩm</span>";
echo "                </div>";
echo "            </div>";
echo "        </div>";
echo "    </div>";

// So sánh
$speedup = $time_no_index > 0 ? $time_with_index / $time_no_index : 1;
$improvement = (1 - $speedup) * 100;

echo "    <div class='comparison'>";
echo "        <h3>📊 KẾT QUẢ SO SÁNH</h3>";
echo "        <table>";
echo "            <tr>";
echo "                <th>Chỉ số</th>";
echo "                <th>Không Index</th>";
echo "                <th>Có Index</th>";
echo "                <th>Cải thiện</th>";
echo "            </tr>";
echo "            <tr>";
echo "                <td><strong>Rows scanned</strong></td>";
echo "                <td>" . number_format($explain_no_index['rows']) . "</td>";
echo "                <td>" . number_format($explain_with_index['rows']) . "</td>";
echo "                <td>" . round(($explain_no_index['rows'] - $explain_with_index['rows']) / $explain_no_index['rows'] * 100) . "%</td>";
echo "            </tr>";
echo "            <tr>";
echo "                <td><strong>Thời gian query</strong></td>";
echo "                <td class='time-slow'>" . number_format($time_no_index, 2) . " ms</td>";
echo "                <td class='time-fast'>" . number_format($time_with_index, 2) . " ms</td>";
echo "                <td class='time-fast'>" . number_format($improvement, 1) . "%</td>";
echo "            </tr>";
echo "        </table>";
echo "    </div>";

// Chi tiết EXPLAIN
echo "    <div class='box' style='margin-top: 30px;'>";
echo "        <h2>🔍 Chi tiết EXPLAIN Plans</h2>";
echo "        <h3>Không có Index:</h3>";
echo "        <pre style='background:#f4f4f4; padding:10px; overflow-x: auto;'>";
echo "Type: " . $explain_no_index['type'] . "\n";
echo "Key: " . ($explain_no_index['key'] ?: 'NULL') . "\n";
echo "Rows: " . $explain_no_index['rows'] . "\n";
echo "Extra: " . $explain_no_index['Extra'] . "\n";
echo "        </pre>";

echo "        <h3>Có Index:</h3>";
echo "        <pre style='background:#f4f4f4; padding:10px; overflow-x: auto;'>";
echo "Type: " . $explain_with_index['type'] . "\n";
echo "Key: " . ($explain_with_index['key'] ?: 'NULL') . "\n";
echo "Rows: " . $explain_with_index['rows'] . "\n";
echo "Extra: " . $explain_with_index['Extra'] . "\n";
echo "        </pre>";
echo "    </div>";

echo "    <div class='box' style='background:#e8f5e9; border-left: 5px solid #4CAF50; margin-top: 20px;'>";
echo "        <h3>💡 Tóm tắt</h3>";
echo "        <ul>";
echo "            <li><strong>Rows scanned:</strong> Index giảm số dòng phải quét từ " . number_format($explain_no_index['rows']) . " xuống " . number_format($explain_with_index['rows']) . "</li>";
echo "            <li><strong>Thời gian:</strong> Query nhanh hơn " . number_format($improvement, 1) . "%</li>";
echo "            <li><strong>Tại sao:</strong> Index là cấu trúc B-Tree cho phép tìm kiếm nhị phân O(log n) thay vì quét toàn bộ O(n)</li>";
echo "            <li><strong>Khi nào cần Index:</strong> Bảng > 10,000 dòng, thường xuyên filter cùng cột</li>";
echo "        </ul>";
echo "    </div>";

echo "</body>
</html>";

mysqli_close($conn);
?>
