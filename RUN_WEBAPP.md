# 🚀 Hướng dẫn Chạy Web App với Docker MySQL

## Chuẩn bị

Bạn đã có:
- ✅ Docker containers đang chạy (Master + Slave)
- ✅ 1 triệu products trong database
- ✅ File `db.php` đã config sẵn

---

## Cách 1: Sử dụng XAMPP (Khuyến nghị)

### Bước 1: Mở XAMPP Control Panel

1. Tìm và mở **XAMPP Control Panel**
2. Click **Start** cho **Apache**
3. Đợi Apache chuyển sang màu xanh

### Bước 2: Copy project vào htdocs

```powershell
# Copy toàn bộ folder vào htdocs
Copy-Item -Path "d:\QuTriCSDL\tm_DBMS_final" -Destination "C:\xampp\htdocs\tm_DBMS_final" -Recurse -Force
```

Hoặc thủ công:
- Copy folder `d:\QuTriCSDL\tm_DBMS_final`
- Paste vào `C:\xampp\htdocs\`

### Bước 3: Truy cập Web App

Mở browser và vào:

```
http://localhost/tm_DBMS_final/index.php
```

---

## Cách 2: PHP Built-in Server (Nếu có PHP)

### Tìm PHP trong XAMPP:

```powershell
cd d:\QuTriCSDL\tm_DBMS_final
C:\xampp\php\php.exe -S localhost:8000
```

Sau đó truy cập:

```
http://localhost:8000/index.php
```

---

## Cách 3: Sử dụng Python HTTP Server (Đơn giản nhất)

Nếu không có XAMPP, dùng Python:

```powershell
cd d:\QuTriCSDL\tm_DBMS_final
python -m http.server 8000
```

Sau đó truy cập:

```
http://localhost:8000/index.php
```

**⚠️ Lưu ý**: Python server không chạy được PHP! Chỉ hiển thị HTML tĩnh.

---

## Kiểm tra kết nối Database

### Test kết nối nhanh:

Tạo file `test_connection.php`:

```php
<?php
require_once 'db.php';

echo "<h1>Database Connection Test</h1>";

// Test Master
$master = getWriteConnection();
if ($master) {
    echo "<p style='color:green'>✓ Master Connected (Port 3308)</p>";
    
    $result = mysqli_query($master, "SELECT COUNT(*) as count FROM products");
    $row = mysqli_fetch_assoc($result);
    echo "<p>Master Products: " . number_format($row['count']) . "</p>";
} else {
    echo "<p style='color:red'>✗ Master Connection Failed</p>";
}

// Test Slave
$slave = getReadConnection();
if ($slave && $slave !== $master) {
    echo "<p style='color:green'>✓ Slave Connected (Port 3307)</p>";
    
    $result = mysqli_query($slave, "SELECT COUNT(*) as count FROM products");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Slave Products: " . number_format($row['count']) . "</p>";
    }
} else if ($slave === $master) {
    echo "<p style='color:orange'>⚠ Slave using same connection as Master</p>";
} else {
    echo "<p style='color:red'>✗ Slave Connection Failed</p>";
}

echo "<h2>✓ Ready to use!</h2>";
?>
```

Truy cập: `http://localhost/tm_DBMS_final/test_connection.php`

---

## Demo cho thầy

### 1. Hiển thị Products trên Web

- Mở `http://localhost/tm_DBMS_final/index.php`
- Sẽ thấy danh sách products với filter
- **Data đọc từ Slave** (port 3307)

### 2. Test Replication Real-time

**Terminal 1** - Insert vào Master:
```powershell
docker exec -i mysql-master mysql -uroot -prootpassword my_store -e "INSERT INTO products (name, price, brand) VALUES ('LIVE Demo Product', 888888, 'DemoBrand')"
```

**Browser** - Refresh trang ngay lập tức:
- F5 để refresh
- Sẽ thấy product mới xuất hiện!
- **Replication lag < 1 giây**

### 3. Test Failover

**Tắt Master:**
```powershell
docker stop mysql-master
```

**Refresh browser:**
- ✓ Vẫn hiển thị products (đọc từ Slave)
- ✗ Không thể thêm mới (Master down)

**Bật lại Master:**
```powershell
docker start mysql-master
```

**Kết quả:**
- ✓ Replication tự động resume
- ✓ Web app hoạt động bình thường

---

## Troubleshooting

### Lỗi: Connection refused

**Nguyên nhân**: Docker containers chưa chạy

**Giải pháp**:
```powershell
docker ps
# Nếu không thấy containers, chạy:
docker-compose up -d
```

### Lỗi: Access denied for user 'root'

**Nguyên nhân**: Sai password trong `db.php`

**Giải pháp**: Mở `db.php`, kiểm tra:
```php
$master_pass = "rootpassword";  // Phải đúng
```

### Lỗi: Apache không start

**Nguyên nhân**: Port 80 hoặc 443 bị chiếm

**Giải pháp**:
1. Đóng Skype / IIS
2. Hoặc đổi port Apache trong `httpd.conf`

---

## 📊 Performance Testing trên Web

### Test Query Speed:

Thêm vào `index.php` để hiển thị query time:

```php
$start = microtime(true);
$result = executeQuery("SELECT * FROM products LIMIT 20");
$time = (microtime(true) - $start) * 1000;

echo "Query time: " . number_format($time, 2) . "ms";
```

### Expected Performance:

- Simple query: **< 10ms**
- Query with WHERE: **< 50ms**
- Query with complex filters: **< 200ms**

---

## 🎓 Summary

**Bây giờ bạn có:**
1. ✅ MySQL Docker Replication hoạt động
2. ✅ 1 triệu products trong database
3. ✅ Web app kết nối Master/Slave
4. ✅ Load balancing (Write→Master, Read→Slave)
5. ✅ Failover capability

**Để chạy:**
- Start XAMPP Apache
- Truy cập `http://localhost/tm_DBMS_final/index.php`
- Hoặc copy vào `htdocs` nếu chưa có

**Để demo:**
- Show web interface
- Insert data → Refresh → Thấy ngay
- Stop Master → Vẫn đọc được
- Show 1M products count

**DONE! 🎉**
