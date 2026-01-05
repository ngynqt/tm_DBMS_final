<<<<<<< HEAD
# 📚 Hướng dẫn Tối ưu Hiệu năng - Product Filter

## 1️⃣ Nguyên tắc cơ bản: Hiệu năng trên dữ liệu lớn (1 triệu dòng)

### A. Quét toàn bộ bảng (Full Table Scan) ❌ 🐢
```sql
-- Chậm nhất: Phải kiểm tra từng dòng
SELECT * FROM products WHERE price = 500000;
-- Với 1 triệu dòng: ~500ms - 1000ms
```

**Tại sao chậm?**
- MySQL phải đọc từ dòng đầu tiên đến dòng cuối
- Không biết kết quả ở đâu
- Thời gian O(n) - tuyến tính

### B. Dùng INDEX (Index Scan) ✅ ⚡
```sql
-- Nhanh: Nhảy trực tiếp đến vị trí cần tìm
SELECT * FROM products WHERE price = 500000;
-- Với INDEX: ~1ms - 5ms (50-100x nhanh hơn!)
```

**Tại sao nhanh?**
- INDEX là cấu trúc dữ liệu (B-Tree)
- Tìm kiếm nhị phân O(log n)
- Chỉ đọc ít dòng cần thiết

### C. So sánh trực quan
```
1 triệu dòng, tìm 100 sản phẩm

❌ Full Scan:  Đọc 1,000,000 dòng → lấy 100 sản phẩm (chậm)
✅ With INDEX: Đọc 1,000 dòng → lấy 100 sản phẩm (nhanh 1000x)
```

---

## 2️⃣ Các loại INDEX và khi nào dùng

### Type 1: Simple INDEX (Chỉ số đơn)
```sql
-- INDEX trên 1 cột
CREATE INDEX idx_price ON products(price);
CREATE INDEX idx_brand ON products(brand);
CREATE INDEX idx_rating ON products(rating_average);
```

**Sử dụng cho:**
- Tìm kiếm đơn giản
- WHERE price = 500000
- WHERE brand = "Nike"

**Query Plan:**
```
type: RANGE (tốt)
key: idx_price
rows: ~100 (quét ít)
```

---

### Type 2: Composite INDEX (Chỉ số kết hợp)
```sql
-- INDEX trên nhiều cột
CREATE INDEX idx_price_rating ON products(price, rating_average);
CREATE INDEX idx_brand_price ON products(brand, price);
```

**Sử dụng cho:**
- Filter kết hợp nhiều điều kiện
- WHERE price BETWEEN 100k AND 500k AND rating_average >= 4.0

**Quy tắc sắp xếp cột trong Composite INDEX:**
1. **Equality columns first** - Cột dấu "="
2. **Range columns next** - Cột dấu "BETWEEN", ">", "<"
3. **Sorting columns last** - Cột dấu ORDER BY

**Ví dụ tốt:**
```sql
-- Query: WHERE brand = "Nike" AND price BETWEEN 100k AND 500k ORDER BY rating DESC
CREATE INDEX idx_composite ON products(brand, price, rating_average);
--                                      ^^^^^^  ^^^^^  ^^^^^^^^^^^
--                                       =      RANGE   ORDER BY
```

---

### Type 3: FULLTEXT INDEX (Tìm kiếm toàn văn)
```sql
-- INDEX cho tìm kiếm text
CREATE FULLTEXT INDEX idx_name_search ON products(name);
CREATE FULLTEXT INDEX idx_desc_search ON products(description);
```

**Sử dụng cho:**
- Tìm kiếm từ khóa (tốt hơn LIKE)
- MATCH(name) AGAINST('keyword' IN BOOLEAN MODE)

**So sánh:**
```sql
-- ❌ Chậm: LIKE '%keyword%'
SELECT * FROM products WHERE name LIKE '%nike%';  -- Full scan

-- ✅ Nhanh: FULLTEXT
SELECT * FROM products WHERE MATCH(name) AGAINST('nike' IN BOOLEAN MODE);
```

---

## 3️⃣ Vấn đề: LIKE '%keyword%' - Vì sao chậm?

### Vấn đề
```sql
-- LIKE '%keyword%' không thể dùng INDEX
SELECT * FROM products WHERE name LIKE '%nike%';
-- Phải kiểm tra từng dòng xem có chứa "nike" không
```

### Tại sao?
```
INDEX B-Tree có thứ tự: Abc, Adidas, Converse, Nike, Puma, Reebok, Vans

Tìm '%nike%':
- "Nike" nằm ở vị trí index #4
- Nhưng "%nike%" có thể ở bất kỳ vị trí nào
- Không thể dùng tìm kiếm nhị phân
- Phải quét toàn bộ
```

### Giải pháp
```sql
-- 1. LIKE 'keyword%' (Tìm ở đầu)
SELECT * FROM products WHERE name LIKE 'nike%';  -- CÓ thể dùng INDEX

-- 2. FULLTEXT INDEX (Tốt nhất)
CREATE FULLTEXT INDEX idx_name ON products(name);
SELECT * FROM products WHERE MATCH(name) AGAINST('nike' IN BOOLEAN MODE);

-- 3. VARCHAR với CHARACTER SET utf8mb4
-- Để hỗ trợ tìm kiếm tiếng Việt tốt
```

---

## 4️⃣ Schema tối ưu cho dự án này

```sql
-- ✅ TỐTIMIZED SCHEMA

CREATE TABLE products (
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
    
    -- Simple Indexes
    INDEX idx_price (price),
    INDEX idx_brand (brand),
    INDEX idx_rating (rating_average),
    INDEX idx_review (review_count),
    
    -- Composite Indexes (cho filter kết hợp)
    INDEX idx_price_rating (price, rating_average),
    INDEX idx_brand_price (brand, price),
    INDEX idx_price_range (price, review_count),
    
    -- FULLTEXT Indexes (cho tìm kiếm)
    FULLTEXT INDEX idx_name_search (name),
    FULLTEXT INDEX idx_desc_search (description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5️⃣ Code PHP tối ưu - Prepared Statements

### ❌ Nguy hiểm (SQL Injection)
```php
$search = $_GET['search'];
$sql = "SELECT * FROM products WHERE name LIKE '%$search%'";
// Nguy hiểm! Nếu $search = "%' OR '1'='1", sẽ lộ toàn bộ dữ liệu
```

### ✅ An toàn (Prepared Statements)
```php
$search = $_GET['search'];
$search_term = '%' . $search . '%';

$stmt = mysqli_prepare($conn, 
    "SELECT * FROM products WHERE name LIKE ? LIMIT 100"
);
mysqli_stmt_bind_param($stmt, 's', $search_term);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

---

## 6️⃣ Phân tích Query với EXPLAIN

```sql
-- Xem MySQL sẽ thực thi query như thế nào
EXPLAIN SELECT * FROM products WHERE price = 500000;
```

**Kết quả:**
```
id | select_type | table    | type   | key       | rows  | Extra
1  | SIMPLE      | products | RANGE  | idx_price | 5000  | NULL
```

### Giải thích cột:
- **type**: Cách MySQL tìm dữ liệu
  - `ALL` = Full Table Scan (❌ Chậm)
  - `RANGE` = Range scan (✅ Tốt)
  - `REF` = Index lookup (✅ Tốt)
  - `EQ_REF` = Primary key (✅ Tốt nhất)

- **key**: Index được dùng
  - `NULL` = Không dùng index (❌)
  - `idx_price` = Dùng index (✅)

- **rows**: Số hàng MySQL cần quét
  - Càng ít càng tốt
  - Nếu `rows = 1000000` thì `type = ALL` (chậm)

---

## 7️⃣ Performance Testing Guide

### Chạy trên máy tính của bạn

**URL:** `http://localhost/DBMS/performance_test.php`

**Test 1: Index Effects**
- So sánh truy vấn đơn giản (price, brand, rating)
- Xem loại INDEX được dùng
- Kiểm tra số hàng quét

**Test 2: LIKE Performance**
- So sánh `LIKE '%keyword%'` vs `LIKE 'keyword%'`
- Hiểu tại sao `LIKE '%text%'` chậm

**Test 3: Range Queries**
- Test BETWEEN, >=, <=
- Xem INDEX giúp bao nhiêu

**Test 4: Composite Indexes**
- Filter kết hợp (price + rating + reviews)
- Composite INDEX tối ưu bao nhiêu

**Test 5: EXPLAIN Analysis**
- Xem EXPLAIN PLAN của các query khác nhau
- Hiểu cách DB làm việc

**Test 6: Generate Sample Data**
- Tạo 10,000 sản phẩm mẫu
- Test trên dữ liệu lớn

---

## 8️⃣ Kinh nghiệm thực tế

### Khi nào dùng INDEX
✅ **Dùng INDEX cho:**
- Cột thường xuyên được filter (price, brand, rating)
- Cột thường xuyên dùng trong WHERE
- Cột thường xuyên dùng trong ORDER BY
- Cột thường xuyên dùng trong JOIN

❌ **Không dùng INDEX cho:**
- Cột BOOLEAN (chỉ có 2 giá trị)
- Cột có ít giá trị khác nhau
- Cột hiếm khi được query
- LONGTEXT (description)

### Số lượng INDEX
- Quá ít: Query chậm
- Quá nhiều: Insert/Update/Delete chậm
- **Tối ưu:** 3-5 INDEX per table

### Index Size
```sql
-- Kiểm tra kích thước index
SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_NAME = 'products';
```

---

## 9️⃣ Một số tối ưu khác

### 1. Column Selection
```php
// ❌ Lấy toàn bộ
SELECT * FROM products WHERE price BETWEEN 100k AND 500k;

// ✅ Lấy chỉ cần thiết
SELECT id, name, price, rating_average FROM products 
WHERE price BETWEEN 100k AND 500k;
```

### 2. LIMIT
```php
// ❌ Lấy tất cả (nếu có 1 triệu kết quả)
SELECT * FROM products WHERE price BETWEEN 100k AND 500k;

// ✅ Phân trang
SELECT * FROM products WHERE price BETWEEN 100k AND 500k LIMIT 20 OFFSET 0;
```

### 3. ORDER BY
```php
// ❌ ORDER BY không có INDEX
SELECT * FROM products ORDER BY custom_field;

// ✅ ORDER BY có INDEX
SELECT * FROM products ORDER BY rating_average DESC;
```

### 4. Caching
```php
// Cache kết quả filter phổ biến
$key = "filter_" . md5(json_encode($_GET));
if ($cached = apcu_fetch($key)) {
    return $cached;
}
// Query DB
$result = filterProducts($_GET);
apcu_store($key, $result, 3600); // Cache 1 giờ
```

---

## 🔟 Cheat Sheet: Prepared Statements

```php
// 1. Chuẩn bị statement
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE price = ? AND brand = ?");

// 2. Bind parameters
// 'd' = double, 'i' = integer, 's' = string
mysqli_stmt_bind_param($stmt, 'ds', $price, $brand);

// 3. Thực thi
mysqli_stmt_execute($stmt);

// 4. Lấy kết quả
$result = mysqli_stmt_get_result($stmt);

// 5. Fetch dữ liệu
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['name'];
}

// 6. Đóng statement
mysqli_stmt_close($stmt);
```

---

## 🎯 Recap: Performance Tuning Checklist

- [x] Schema optimization - Cấu trúc bảng tối ưu
- [x] Index strategy - Chiến lược index
- [x] Query optimization - Tối ưu câu query
- [x] Prepared statements - Bảo mật + hiệu năng
- [x] Pagination - Phân trang kết quả
- [x] Monitoring - Theo dõi performance
- [x] Caching - Cache kết quả

---

**Tham khảo:**
- MySQL Index docs: https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html
- EXPLAIN docs: https://dev.mysql.com/doc/refman/8.0/en/explain.html
- Prepared Statements: https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php
=======
# 📚 Hướng dẫn Tối ưu Hiệu năng - Product Filter

## 1️⃣ Nguyên tắc cơ bản: Hiệu năng trên dữ liệu lớn (1 triệu dòng)

### A. Quét toàn bộ bảng (Full Table Scan) ❌ 🐢
```sql
-- Chậm nhất: Phải kiểm tra từng dòng
SELECT * FROM products WHERE price = 500000;
-- Với 1 triệu dòng: ~500ms - 1000ms
```

**Tại sao chậm?**
- MySQL phải đọc từ dòng đầu tiên đến dòng cuối
- Không biết kết quả ở đâu
- Thời gian O(n) - tuyến tính

### B. Dùng INDEX (Index Scan) ✅ ⚡
```sql
-- Nhanh: Nhảy trực tiếp đến vị trí cần tìm
SELECT * FROM products WHERE price = 500000;
-- Với INDEX: ~1ms - 5ms (50-100x nhanh hơn!)
```

**Tại sao nhanh?**
- INDEX là cấu trúc dữ liệu (B-Tree)
- Tìm kiếm nhị phân O(log n)
- Chỉ đọc ít dòng cần thiết

### C. So sánh trực quan
```
1 triệu dòng, tìm 100 sản phẩm

❌ Full Scan:  Đọc 1,000,000 dòng → lấy 100 sản phẩm (chậm)
✅ With INDEX: Đọc 1,000 dòng → lấy 100 sản phẩm (nhanh 1000x)
```

---

## 2️⃣ Các loại INDEX và khi nào dùng

### Type 1: Simple INDEX (Chỉ số đơn)
```sql
-- INDEX trên 1 cột
CREATE INDEX idx_price ON products(price);
CREATE INDEX idx_brand ON products(brand);
CREATE INDEX idx_rating ON products(rating_average);
```

**Sử dụng cho:**
- Tìm kiếm đơn giản
- WHERE price = 500000
- WHERE brand = "Nike"

**Query Plan:**
```
type: RANGE (tốt)
key: idx_price
rows: ~100 (quét ít)
```

---

### Type 2: Composite INDEX (Chỉ số kết hợp)
```sql
-- INDEX trên nhiều cột
CREATE INDEX idx_price_rating ON products(price, rating_average);
CREATE INDEX idx_brand_price ON products(brand, price);
```

**Sử dụng cho:**
- Filter kết hợp nhiều điều kiện
- WHERE price BETWEEN 100k AND 500k AND rating_average >= 4.0

**Quy tắc sắp xếp cột trong Composite INDEX:**
1. **Equality columns first** - Cột dấu "="
2. **Range columns next** - Cột dấu "BETWEEN", ">", "<"
3. **Sorting columns last** - Cột dấu ORDER BY

**Ví dụ tốt:**
```sql
-- Query: WHERE brand = "Nike" AND price BETWEEN 100k AND 500k ORDER BY rating DESC
CREATE INDEX idx_composite ON products(brand, price, rating_average);
--                                      ^^^^^^  ^^^^^  ^^^^^^^^^^^
--                                       =      RANGE   ORDER BY
```

---

### Type 3: FULLTEXT INDEX (Tìm kiếm toàn văn)
```sql
-- INDEX cho tìm kiếm text
CREATE FULLTEXT INDEX idx_name_search ON products(name);
CREATE FULLTEXT INDEX idx_desc_search ON products(description);
```

**Sử dụng cho:**
- Tìm kiếm từ khóa (tốt hơn LIKE)
- MATCH(name) AGAINST('keyword' IN BOOLEAN MODE)

**So sánh:**
```sql
-- ❌ Chậm: LIKE '%keyword%'
SELECT * FROM products WHERE name LIKE '%nike%';  -- Full scan

-- ✅ Nhanh: FULLTEXT
SELECT * FROM products WHERE MATCH(name) AGAINST('nike' IN BOOLEAN MODE);
```

---

## 3️⃣ Vấn đề: LIKE '%keyword%' - Vì sao chậm?

### Vấn đề
```sql
-- LIKE '%keyword%' không thể dùng INDEX
SELECT * FROM products WHERE name LIKE '%nike%';
-- Phải kiểm tra từng dòng xem có chứa "nike" không
```

### Tại sao?
```
INDEX B-Tree có thứ tự: Abc, Adidas, Converse, Nike, Puma, Reebok, Vans

Tìm '%nike%':
- "Nike" nằm ở vị trí index #4
- Nhưng "%nike%" có thể ở bất kỳ vị trí nào
- Không thể dùng tìm kiếm nhị phân
- Phải quét toàn bộ
```

### Giải pháp
```sql
-- 1. LIKE 'keyword%' (Tìm ở đầu)
SELECT * FROM products WHERE name LIKE 'nike%';  -- CÓ thể dùng INDEX

-- 2. FULLTEXT INDEX (Tốt nhất)
CREATE FULLTEXT INDEX idx_name ON products(name);
SELECT * FROM products WHERE MATCH(name) AGAINST('nike' IN BOOLEAN MODE);

-- 3. VARCHAR với CHARACTER SET utf8mb4
-- Để hỗ trợ tìm kiếm tiếng Việt tốt
```

---

## 4️⃣ Schema tối ưu cho dự án này

```sql
-- ✅ TỐTIMIZED SCHEMA

CREATE TABLE products (
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
    
    -- Simple Indexes
    INDEX idx_price (price),
    INDEX idx_brand (brand),
    INDEX idx_rating (rating_average),
    INDEX idx_review (review_count),
    
    -- Composite Indexes (cho filter kết hợp)
    INDEX idx_price_rating (price, rating_average),
    INDEX idx_brand_price (brand, price),
    INDEX idx_price_range (price, review_count),
    
    -- FULLTEXT Indexes (cho tìm kiếm)
    FULLTEXT INDEX idx_name_search (name),
    FULLTEXT INDEX idx_desc_search (description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5️⃣ Code PHP tối ưu - Prepared Statements

### ❌ Nguy hiểm (SQL Injection)
```php
$search = $_GET['search'];
$sql = "SELECT * FROM products WHERE name LIKE '%$search%'";
// Nguy hiểm! Nếu $search = "%' OR '1'='1", sẽ lộ toàn bộ dữ liệu
```

### ✅ An toàn (Prepared Statements)
```php
$search = $_GET['search'];
$search_term = '%' . $search . '%';

$stmt = mysqli_prepare($conn, 
    "SELECT * FROM products WHERE name LIKE ? LIMIT 100"
);
mysqli_stmt_bind_param($stmt, 's', $search_term);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

---

## 6️⃣ Phân tích Query với EXPLAIN

```sql
-- Xem MySQL sẽ thực thi query như thế nào
EXPLAIN SELECT * FROM products WHERE price = 500000;
```

**Kết quả:**
```
id | select_type | table    | type   | key       | rows  | Extra
1  | SIMPLE      | products | RANGE  | idx_price | 5000  | NULL
```

### Giải thích cột:
- **type**: Cách MySQL tìm dữ liệu
  - `ALL` = Full Table Scan (❌ Chậm)
  - `RANGE` = Range scan (✅ Tốt)
  - `REF` = Index lookup (✅ Tốt)
  - `EQ_REF` = Primary key (✅ Tốt nhất)

- **key**: Index được dùng
  - `NULL` = Không dùng index (❌)
  - `idx_price` = Dùng index (✅)

- **rows**: Số hàng MySQL cần quét
  - Càng ít càng tốt
  - Nếu `rows = 1000000` thì `type = ALL` (chậm)

---

## 7️⃣ Performance Testing Guide

### Chạy trên máy tính của bạn

**URL:** `http://localhost/DBMS/performance_test.php`

**Test 1: Index Effects**
- So sánh truy vấn đơn giản (price, brand, rating)
- Xem loại INDEX được dùng
- Kiểm tra số hàng quét

**Test 2: LIKE Performance**
- So sánh `LIKE '%keyword%'` vs `LIKE 'keyword%'`
- Hiểu tại sao `LIKE '%text%'` chậm

**Test 3: Range Queries**
- Test BETWEEN, >=, <=
- Xem INDEX giúp bao nhiêu

**Test 4: Composite Indexes**
- Filter kết hợp (price + rating + reviews)
- Composite INDEX tối ưu bao nhiêu

**Test 5: EXPLAIN Analysis**
- Xem EXPLAIN PLAN của các query khác nhau
- Hiểu cách DB làm việc

**Test 6: Generate Sample Data**
- Tạo 10,000 sản phẩm mẫu
- Test trên dữ liệu lớn

---

## 8️⃣ Kinh nghiệm thực tế

### Khi nào dùng INDEX
✅ **Dùng INDEX cho:**
- Cột thường xuyên được filter (price, brand, rating)
- Cột thường xuyên dùng trong WHERE
- Cột thường xuyên dùng trong ORDER BY
- Cột thường xuyên dùng trong JOIN

❌ **Không dùng INDEX cho:**
- Cột BOOLEAN (chỉ có 2 giá trị)
- Cột có ít giá trị khác nhau
- Cột hiếm khi được query
- LONGTEXT (description)

### Số lượng INDEX
- Quá ít: Query chậm
- Quá nhiều: Insert/Update/Delete chậm
- **Tối ưu:** 3-5 INDEX per table

### Index Size
```sql
-- Kiểm tra kích thước index
SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_NAME = 'products';
```

---

## 9️⃣ Một số tối ưu khác

### 1. Column Selection
```php
// ❌ Lấy toàn bộ
SELECT * FROM products WHERE price BETWEEN 100k AND 500k;

// ✅ Lấy chỉ cần thiết
SELECT id, name, price, rating_average FROM products 
WHERE price BETWEEN 100k AND 500k;
```

### 2. LIMIT
```php
// ❌ Lấy tất cả (nếu có 1 triệu kết quả)
SELECT * FROM products WHERE price BETWEEN 100k AND 500k;

// ✅ Phân trang
SELECT * FROM products WHERE price BETWEEN 100k AND 500k LIMIT 20 OFFSET 0;
```

### 3. ORDER BY
```php
// ❌ ORDER BY không có INDEX
SELECT * FROM products ORDER BY custom_field;

// ✅ ORDER BY có INDEX
SELECT * FROM products ORDER BY rating_average DESC;
```

### 4. Caching
```php
// Cache kết quả filter phổ biến
$key = "filter_" . md5(json_encode($_GET));
if ($cached = apcu_fetch($key)) {
    return $cached;
}
// Query DB
$result = filterProducts($_GET);
apcu_store($key, $result, 3600); // Cache 1 giờ
```

---

## 🔟 Cheat Sheet: Prepared Statements

```php
// 1. Chuẩn bị statement
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE price = ? AND brand = ?");

// 2. Bind parameters
// 'd' = double, 'i' = integer, 's' = string
mysqli_stmt_bind_param($stmt, 'ds', $price, $brand);

// 3. Thực thi
mysqli_stmt_execute($stmt);

// 4. Lấy kết quả
$result = mysqli_stmt_get_result($stmt);

// 5. Fetch dữ liệu
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['name'];
}

// 6. Đóng statement
mysqli_stmt_close($stmt);
```

---

## 🎯 Recap: Performance Tuning Checklist

- [x] Schema optimization - Cấu trúc bảng tối ưu
- [x] Index strategy - Chiến lược index
- [x] Query optimization - Tối ưu câu query
- [x] Prepared statements - Bảo mật + hiệu năng
- [x] Pagination - Phân trang kết quả
- [x] Monitoring - Theo dõi performance
- [x] Caching - Cache kết quả

---

**Tham khảo:**
- MySQL Index docs: https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html
- EXPLAIN docs: https://dev.mysql.com/doc/refman/8.0/en/explain.html
- Prepared Statements: https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php
>>>>>>> 5f79eaeba4311ce083ded1cf198a4a984c0b8b86
