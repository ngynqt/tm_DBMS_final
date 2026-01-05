# DBMS Shop - Hệ thống bán hàng trực tuyến

Ứng dụng e-commerce hiệu năng cao với bộ lọc sản phẩm tối ưu được xây dựng bằng PHP + MySQL + JavaScript vanilla.

![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen) ![Language](https://img.shields.io/badge/Language-PHP%2FMySQL%2FJS-blue) ![Products](https://img.shields.io/badge/Products-41%2C573-orange)

## 🚀 Tính năng chính

✅ **Bộ lọc sản phẩm tối ưu**
- Tìm kiếm full-text theo tên sản phẩm
- Lọc theo khoảng giá (VND)
- Lọc theo thương hiệu (817 brands)
- Lọc theo đánh giá (1-5 sao)
- Áp dụng tức thời - không cần click "Apply"
- Hiển thị thời gian query (⏱️ Hiệu năng)

🛒 **Giỏ hàng & Xác thực**
- Thêm/xóa sản phẩm
- Quản lý số lượng
- Hệ thống đăng nhập/đăng ký
- Lưu trữ giỏ hàng (localStorage)
- Cart count badge in header

👤 **User Authentication**
- User registration with email validation
- Secure login with password hashing (PHP password_hash)
- User profile view with logout functionality
- Guest checkout option
- Session-based authentication

📦 **Order Management**
- Checkout with cart items
- Order creation with order ID
- Track order history
- Support for guest and registered users
- Real-time order confirmation

🎨 **User Interface**
- Responsive design (mobile, tablet, desktop)
- Modern card-based product layout
- Modal dialogs for cart and authentication
- Smooth animations and transitions
- Clean and intuitive navigation

🔒 **Security**
- Password hashing with PHP password_hash()
- SQL injection prevention with mysqli_real_escape_string()
- UTF-8 encoding for international characters
- Session-based authentication
- Cache headers for API responses

## 🛠 Technology Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3, Vanilla JavaScript (ES6+) |
| **Backend** | PHP 7+ |
| **Database** | MySQL 5.7+ |
| **Server** | Apache (XAMPP) |
| **Currency** | Vietnamese Dong (VND) |

## 📋 Prerequisites

- **XAMPP** (or Apache + PHP 7+ + MySQL 5.7+)
- **PHP** 7.0 or higher
- **MySQL** 5.7 or higher
- **Modern Web Browser** (Chrome, Firefox, Safari, Edge)

## 🚀 Quick Start (3 Steps)

### Step 1: Setup Database
```bash
http://localhost/DBMS/setup_db.php
```
✓ Creates database and all tables automatically

### Step 2: Import Products
```bash
http://localhost/DBMS/import.php
```
- Choose **"Import All (6 Files)"** to import ~2000+ products
- Or select individual CSV files
- Supports: Backpacks, Accessories, Men's Bags, Men's Shoes, Women's Bags, Women's Shoes

### Step 3: Open Store
```bash
http://localhost/DBMS/index.php
```
✓ Browse products, search, add to cart, register, checkout!

## 📁 Project Structure

```
DBMS/
├── api.php              - REST API backend (7 endpoints)
├── app.js               - Frontend logic (370+ lines)
├── db.php               - Database configuration
├── index.php            - Main e-commerce page
├── setup_db.php         - Database initialization
├── import.php           - CSV data import interface
├── styles.css           - Responsive styling (400+ lines)
├── data/                - CSV product files
│   ├── vietnamese_tiki_products_backpacks_suitcases.csv
│   ├── vietnamese_tiki_products_fashion_accessories.csv
│   ├── vietnamese_tiki_products_men_bags.csv
│   ├── vietnamese_tiki_products_men_shoes.csv
│   ├── vietnamese_tiki_products_women_bags.csv
│   └── vietnamese_tiki_products_women_shoes.csv
├── INSTALLATION.md      - Detailed setup guide
└── README.md            - This file
```

## 🗄 Database Schema

### Products Table
```sql
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Users Table
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Orders Table
```sql
CREATE TABLE orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NULL,
    total_price FLOAT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### Order Items Table
```sql
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price FLOAT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

## 🔌 API Endpoints

### Get Products
```http
GET /api.php?action=get_products&page=1
```
**Response:**
```json
{
  "success": true,
  "products": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 2000,
    "pages": 100
  }
}
```

### Search Products
```http
GET /api.php?action=get_products&search=shoes&page=1
```

### Register User
```http
POST /api.php?action=register
Content-Type: application/x-www-form-urlencoded

name=John Doe&email=john@example.com&password=123456
```

### Login
```http
POST /api.php?action=login
Content-Type: application/x-www-form-urlencoded

email=john@example.com&password=123456
```

### Get Profile
```http
GET /api.php?action=get_profile
```
Requires active session

### Checkout
```http
POST /api.php?action=checkout
Content-Type: application/json

{
  "items": [
    {"id": 1, "name": "Product", "price": 100000, "quantity": 2}
  ]
}
```

### Logout
```http
GET /api.php?action=logout
```

## 💻 Configuration

### Database Connection (db.php)
```php
$host = "127.0.0.1";      // MySQL host
$user = "root";            // MySQL username
$pass = "";                // MySQL password
$dbname = "my_store";      // Database name
```

### Import CSV Files (import.php)
- Automatically detects all CSV files in `data/` folder
- Supports multiple file imports
- Uses `ON DUPLICATE KEY UPDATE` to prevent duplicates

## 🎯 Usage Examples

### Browse Products
1. Visit `http://localhost/DBMS/index.php`
2. Scroll through products with pagination
3. Click "Previous" or "Next" to change page

### Search Products
1. Type in search box (minimum 3 characters)
2. Results update automatically
3. Supports search in name, brand, and description

### Add to Cart
1. Click "Add to Cart" on any product
2. Cart count updates in header
3. Click cart icon to view items

### Checkout
1. Click shopping cart icon
2. Review items and prices
3. Click "Thanh toán" (Checkout)
4. Login or continue as guest
5. Order placed successfully!

### User Registration
1. Click "Đăng nhập" (Login)
2. Click "Đăng ký" (Register)
3. Enter name, email, password (min 6 characters)
4. Account created and auto-logged in

## 📊 Sample Data

The application comes with 6 CSV files containing real Vietnamese Tiki product data:
- **Backpacks & Suitcases**: ~350 products
- **Fashion Accessories**: ~350 products
- **Men's Bags**: ~350 products
- **Men's Shoes**: ~350 products
- **Women's Bags**: ~350 products
- **Women's Shoes**: ~350 products

**Total**: 2000+ products ready to import

## 🔐 Security Considerations

✅ **Implemented:**
- Password hashing with `password_hash()`
- `password_verify()` for authentication
- Session-based user authentication
- SQL injection prevention with string escaping
- UTF-8 encoding for all text
- Cache headers to prevent stale data

⚠️ **Production Recommendations:**
- Use prepared statements instead of string concatenation
- Implement HTTPS/SSL encryption
- Add CSRF tokens to forms
- Implement rate limiting on API
- Add input validation on backend
- Use environment variables for credentials
- Enable CORS headers if needed

## 🚨 Troubleshooting

### Database Connection Error
```
Connection failed: (error message)
```
**Solution:**
- Verify MySQL is running
- Check credentials in `db.php`
- Ensure database name is correct

### No Products Displaying
**Solution:**
1. Run `setup_db.php` to initialize database
2. Run `import.php` to load products
3. Check Products table has data using phpMyAdmin

### Authentication Not Working
**Solution:**
- Verify Users table exists
- Check email/password are correct
- Clear browser cookies/cache
- Try registering new account

### Search Returns No Results
**Solution:**
- Check products are imported
- Search term might not match any products
- Try broader search terms

### CSV Import Fails
**Solution:**
- Verify CSV files exist in `data/` folder
- Check file permissions are readable
- Ensure CSV format matches expected structure

## 📝 File Descriptions

| File | Purpose | Lines |
|------|---------|-------|
| `api.php` | REST API with 7 endpoints | 174 |
| `app.js` | Frontend logic | 370+ |
| `index.php` | Main store page | 90 |
| `styles.css` | Responsive CSS | 400+ |
| `db.php` | DB connection | 17 |
| `setup_db.php` | Database init | 80 |
| `import.php` | CSV importer | 150+ |

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📜 License

This project is licensed under the MIT License - see LICENSE file for details.

## 👨‍💻 Author

Created as a learning project for full-stack e-commerce development.

## 📞 Support

For issues and questions:
1. Check [INSTALLATION.md](INSTALLATION.md) for setup help
2. Review API endpoints documentation above
3. Check browser console (F12) for JavaScript errors
4. Check server logs for PHP errors

## 🎓 Learning Resources

This project demonstrates:
- RESTful API design with PHP
- MySQL database relationships and constraints
- User authentication and session management
- Frontend JavaScript for dynamic content
- Responsive CSS design
- CSV data processing
- Password security best practices
- Pagination implementation
- Form validation
- Error handling
- JSON API communication

---

**Status**: ✅ Production Ready  
**Last Updated**: December 2024  
**PHP Version**: 7.0+  
**MySQL Version**: 5.7+

1. **products** - Product information from CSV
   - id, name, description, original_price, price
   - fulfillment_type, brand, review_count, rating_average

2. **users** - User accounts
   - id, name, email, password (hashed)

3. **orders** - Customer orders
   - id, user_id, total_price, status, created_at

4. **order_items** - Order line items
   - id, order_id, product_id, quantity, price

## Setup Instructions

### 1. Initialize Database

Visit `http://localhost/DBMS/setup_db.php` to create all required tables.

### 2. Import Product Data

Visit `http://localhost/DBMS/import_csv.php` to import products from CSV files.

You can modify the `$csvFile` variable to import different CSV datasets:
- `data\vietnamese_tiki_products_backpacks_suitcases.csv`
- `data\vietnamese_tiki_products_fashion_accessories.csv`
- `data\vietnamese_tiki_products_men_bags.csv`
- `data\vietnamese_tiki_products_men_shoes.csv`
- `data\vietnamese_tiki_products_women_bags.csv`
- `data\vietnamese_tiki_products_women_shoes.csv`

### 3. Access the Application

Visit `http://localhost/DBMS/bang_hang.html`

## API Endpoints

All endpoints use `api.php` with action parameters:

### Products
- `GET api.php?action=get_products` - Get all products
- `GET api.php?action=get_products&search=keyword` - Search products

### Authentication
- `POST api.php?action=register` - Register new user
- `POST api.php?action=login` - Login user
- `GET api.php?action=logout` - Logout user
- `GET api.php?action=get_profile` - Get current user profile

### Orders
- `POST api.php?action=checkout` - Create new order

## How to Use

### Shopping
1. Browse products on the homepage
2. Use search bar to find specific items
3. Click "Add to Cart" to add products
4. Open cart to view items and adjust quantities
5. Click "Checkout" to place order

### Account Management
1. Click "Login" button
2. Register new account or login with existing credentials
3. View profile by clicking your name (when logged in)
4. Click "Logout" to sign out

## Features

- **Product Display**: Shows product name, brand, price, discount, ratings
- **Search**: Real-time search by product name, brand, description
- **Cart Persistence**: Cart saves to browser localStorage
- **Secure Authentication**: Passwords hashed with PHP password_hash()
- **Session Management**: User sessions stored server-side
- **Order Tracking**: Each order stored in database with line items

## Configuration

Edit `db.php` to configure database connection:
```php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "my_store";
```

## Browser Compatibility

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers

## Notes

- All prices are formatted in Vietnamese Dong (VND)
- Product images are placeholder images
- Cart data stored locally in browser
- Orders require database connection
- Supports sessions for authentication

## Development

- Frontend: Vanilla JavaScript (no framework)
- Backend: PHP with MySQLi
- Database: MySQL
- Styling: Pure CSS with responsive design

## Security Notes

- Passwords are hashed using password_hash()
- Real escape strings used for SQL queries (production should use prepared statements)
- CORS-enabled for API calls
- Sessions for user authentication

---

Built for DBMS course project. Demonstrates full-stack web development with database integration.

Muốn tiếp theo:
- Thêm backend (Node/Express, PHP, v.v.) để lưu đơn hàng
- Kết nối cổng thanh toán (Stripe, VNPay)
- Thêm quản trị để quản lý sản phẩm
