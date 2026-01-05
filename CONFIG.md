<<<<<<< HEAD
# ⚙️ Cấu hình DBMS Shop

## 📁 Cấu trúc thư mục

```
DBMS/
├── index.php              # 🏪 Trang chính - shop bán hàng
├── api.php                # 🔌 API filter sản phẩm
├── db.php                 # 🗄️ Kết nối database
├── setup_db.php           # 🛠️ Cài đặt database & indexes
├── styles.css             # 🎨 CSS toàn bộ trang
├── PERFORMANCE_GUIDE.md   # 📚 Hướng dẫn tối ưu hiệu năng
├── INSTALLATION.md        # 📖 Hướng dẫn cài đặt
├── README.md              # 📝 Mô tả dự án
└── data/                  # 📊 CSV data gốc
```

## 🎯 File chính sản xuất

**index.php** (Giao diện chính)
- Header: Tìm kiếm, giỏ hàng, đăng nhập
- Sidebar: Bộ lọc (tên, giá, thương hiệu, đánh giá)
- Grid: Hiển thị sản phẩm, cart, auth modals
- JavaScript inline (no dependencies)

**api.php** (API Backend)
- Route: `?action=filter_products`
- Params: `search`, `price_min`, `price_max`, `brands[]`, `min_rating`, `min_reviews`, `page`
- Output: JSON {success, products[], pagination, filters, performance}
- Security: Prepared statements

**db.php** (Database Connection)
- MySQLi connection
- Database: `my_store`
- Character: utf8mb4

## 🗄️ Database Schema

**Table: products**
```sql
id (PK)
name (VARCHAR 255) - FULLTEXT INDEX
description (TEXT) - FULLTEXT INDEX
original_price (DECIMAL)
price (DECIMAL) - INDEX
fulfillment_type (VARCHAR)
brand (VARCHAR) - INDEX
review_count (INT) - INDEX
rating_average (FLOAT) - INDEX
```

**Composite Indexes:**
- `idx_price_rating(price, rating_average)`
- `idx_brand_price(brand, price)`
- `idx_price_range(price)` for range queries

**Data Stats:**
- 41,573 products (cleaned)
- 817 unique brands
- Price range: 1,000 - 19,800,000 VND

## 🔧 Cách sử dụng

### Cài đặt lần đầu
```bash
1. Mở http://localhost/DBMS/setup_db.php
2. Click "Setup Database"
3. Hoàn tất import data
```

### Sử dụng thường xuyên
```bash
http://localhost/DBMS/index.php
- Tìm kiếm bằng thanh search
- Chọn brand/giá/đánh giá
- Filter tự động (no click needed)
- Xem thời gian query
```

### Test API trực tiếp
```bash
http://localhost/DBMS/api.php?action=filter_products&search=Nike&price_min=1000&price_max=500000
```

## ⚡ Tối ưu hiệu năng

✅ **Đã áp dụng:**
- Database indexes (simple + composite + fulltext)
- Prepared statements (SQL injection safe)
- LIMIT/OFFSET pagination
- microtime() performance tracking

✅ **Kết quả:**
- Filter query: 1-10ms (1 triệu dòng)
- Data load: <100ms
- Auto-apply on checkbox change

📖 Xem chi tiết: [PERFORMANCE_GUIDE.md](PERFORMANCE_GUIDE.md)

## 🧹 Code Structure

**No external dependencies**
- PHP 7.4+ only
- MySQL 5.7+
- Vanilla JavaScript (no jQuery)
- CSS Grid + Flexbox

**All-in-one index.php:**
- HTML structure
- Inline CSS
- Inline JavaScript (applyFilter, resetFilter, cart, auth)
- No require() for frontend code

## 🚀 Production Checklist

- ✅ Database indexed
- ✅ Data cleaned (41,573 valid products)
- ✅ Prepared statements (secure)
- ✅ Performance tracking
- ✅ Mobile responsive
- ✅ Cart functionality
- ✅ Auth modals
- ✅ Error handling

## 📝 Notes

- Filter updates in real-time as you select options
- Performance metrics shown in filter sidebar (⏱️ Hiệu năng)
- Images are placeholder (via placeholder.com)
- Cart stored in localStorage (browser)
=======
# ⚙️ Cấu hình DBMS Shop

## 📁 Cấu trúc thư mục

```
DBMS/
├── index.php              # 🏪 Trang chính - shop bán hàng
├── api.php                # 🔌 API filter sản phẩm
├── db.php                 # 🗄️ Kết nối database
├── setup_db.php           # 🛠️ Cài đặt database & indexes
├── styles.css             # 🎨 CSS toàn bộ trang
├── PERFORMANCE_GUIDE.md   # 📚 Hướng dẫn tối ưu hiệu năng
├── INSTALLATION.md        # 📖 Hướng dẫn cài đặt
├── README.md              # 📝 Mô tả dự án
└── data/                  # 📊 CSV data gốc
```

## 🎯 File chính sản xuất

**index.php** (Giao diện chính)
- Header: Tìm kiếm, giỏ hàng, đăng nhập
- Sidebar: Bộ lọc (tên, giá, thương hiệu, đánh giá)
- Grid: Hiển thị sản phẩm, cart, auth modals
- JavaScript inline (no dependencies)

**api.php** (API Backend)
- Route: `?action=filter_products`
- Params: `search`, `price_min`, `price_max`, `brands[]`, `min_rating`, `min_reviews`, `page`
- Output: JSON {success, products[], pagination, filters, performance}
- Security: Prepared statements

**db.php** (Database Connection)
- MySQLi connection
- Database: `my_store`
- Character: utf8mb4

## 🗄️ Database Schema

**Table: products**
```sql
id (PK)
name (VARCHAR 255) - FULLTEXT INDEX
description (TEXT) - FULLTEXT INDEX
original_price (DECIMAL)
price (DECIMAL) - INDEX
fulfillment_type (VARCHAR)
brand (VARCHAR) - INDEX
review_count (INT) - INDEX
rating_average (FLOAT) - INDEX
```

**Composite Indexes:**
- `idx_price_rating(price, rating_average)`
- `idx_brand_price(brand, price)`
- `idx_price_range(price)` for range queries

**Data Stats:**
- 41,573 products (cleaned)
- 817 unique brands
- Price range: 1,000 - 19,800,000 VND

## 🔧 Cách sử dụng

### Cài đặt lần đầu
```bash
1. Mở http://localhost/DBMS/setup_db.php
2. Click "Setup Database"
3. Hoàn tất import data
```

### Sử dụng thường xuyên
```bash
http://localhost/DBMS/index.php
- Tìm kiếm bằng thanh search
- Chọn brand/giá/đánh giá
- Filter tự động (no click needed)
- Xem thời gian query
```

### Test API trực tiếp
```bash
http://localhost/DBMS/api.php?action=filter_products&search=Nike&price_min=1000&price_max=500000
```

## ⚡ Tối ưu hiệu năng

✅ **Đã áp dụng:**
- Database indexes (simple + composite + fulltext)
- Prepared statements (SQL injection safe)
- LIMIT/OFFSET pagination
- microtime() performance tracking

✅ **Kết quả:**
- Filter query: 1-10ms (1 triệu dòng)
- Data load: <100ms
- Auto-apply on checkbox change

📖 Xem chi tiết: [PERFORMANCE_GUIDE.md](PERFORMANCE_GUIDE.md)

## 🧹 Code Structure

**No external dependencies**
- PHP 7.4+ only
- MySQL 5.7+
- Vanilla JavaScript (no jQuery)
- CSS Grid + Flexbox

**All-in-one index.php:**
- HTML structure
- Inline CSS
- Inline JavaScript (applyFilter, resetFilter, cart, auth)
- No require() for frontend code

## 🚀 Production Checklist

- ✅ Database indexed
- ✅ Data cleaned (41,573 valid products)
- ✅ Prepared statements (secure)
- ✅ Performance tracking
- ✅ Mobile responsive
- ✅ Cart functionality
- ✅ Auth modals
- ✅ Error handling

## 📝 Notes

- Filter updates in real-time as you select options
- Performance metrics shown in filter sidebar (⏱️ Hiệu năng)
- Images are placeholder (via placeholder.com)
- Cart stored in localStorage (browser)
>>>>>>> 5f79eaeba4311ce083ded1cf198a4a984c0b8b86
