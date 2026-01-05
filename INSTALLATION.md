<<<<<<< HEAD
# INSTALLATION & USAGE GUIDE

## Overview
A complete e-commerce web application with MySQL database backend. Built with vanilla JavaScript, PHP, and MySQL.

---

## QUICK START (3 STEPS)

### Step 1: Initialize Database
1. Open your browser
2. Go to: `http://localhost/DBMS/setup_db.php`
3. This creates all necessary database tables automatically

### Step 2: Import Product Data
1. Go to: `http://localhost/DBMS/import.php`
2. This imports products from the CSV file into the database
3. (Optional) Edit import.php to import different CSV files

### Step 3: Start Using the App
1. Go to: `http://localhost/DBMS/index.php`
2. Browse products, search, add to cart, register/login, checkout!

---

## FILE STRUCTURE

```
app.js              - Frontend JavaScript (13KB)
                      ├─ Load products from API
                      ├─ Shopping cart logic
                      ├─ User authentication
                      └─ Search functionality

api.php             - Backend REST API (5.5KB)
                      ├─ GET /products
                      ├─ POST /login, /register
                      ├─ GET /profile, /logout
                      └─ POST /checkout

db.php              - Database connection (377 bytes)
                      └─ MySQL connection & charset config

setup_db.php        - Database initialization (2.9KB)
                      ├─ Create my_store database
                      ├─ products table
                      ├─ users table
                      ├─ orders table
                      └─ order_items table

import_csv.php      - CSV data import (1.7KB)
                      └─ Imports product data into database

index.php           - Main store page (3.1KB)
                      ├─ Product grid
                      ├─ Search bar
                      ├─ Shopping cart modal
                      └─ Login/Register modal

styles.css          - Responsive styling (8KB)
                      ├─ Mobile-first design
                      ├─ Product cards
                      ├─ Cart interface
                      └─ Forms & modals

setup.html          - Setup instructions (5.7KB)
                      └─ Interactive setup guide

data/               - CSV product files
                      ├─ vietnamese_tiki_products_backpacks_suitcases.csv
                      ├─ vietnamese_tiki_products_fashion_accessories.csv
                      ├─ vietnamese_tiki_products_men_bags.csv
                      ├─ vietnamese_tiki_products_men_shoes.csv
                      ├─ vietnamese_tiki_products_women_bags.csv
                      └─ vietnamese_tiki_products_women_shoes.csv
```

---

## DATABASE SCHEMA

### products table
```sql
id (INT, PRIMARY KEY)
name (VARCHAR 255)
description (LONGTEXT)
original_price (FLOAT)
price (FLOAT)
fulfillment_type (VARCHAR 100)
brand (VARCHAR 100)
review_count (INT)
rating_average (FLOAT)
created_at (TIMESTAMP)
```

### users table
```sql
id (INT, PRIMARY KEY, AUTO_INCREMENT)
name (VARCHAR 100)
email (VARCHAR 100, UNIQUE)
password (VARCHAR 255) - hashed with password_hash()
created_at (TIMESTAMP)
```

### orders table
```sql
id (INT, PRIMARY KEY, AUTO_INCREMENT)
user_id (INT, FOREIGN KEY)
total_price (FLOAT)
status (VARCHAR 50) - 'pending', 'completed', etc
created_at (TIMESTAMP)
```

### order_items table
```sql
id (INT, PRIMARY KEY, AUTO_INCREMENT)
order_id (INT, FOREIGN KEY)
product_id (INT, FOREIGN KEY)
quantity (INT)
price (FLOAT)
```

---

## API ENDPOINTS

### Get Products
- **Endpoint:** `api.php?action=get_products`
- **Method:** GET
- **Response:** JSON array of all products
- **Example:** `/api.php?action=get_products`

### Search Products
- **Endpoint:** `api.php?action=get_products&search=keyword`
- **Method:** GET
- **Response:** JSON array of matching products
- **Example:** `/api.php?action=get_products&search=shoes`

### Register User
- **Endpoint:** `api.php?action=register`
- **Method:** POST
- **Parameters:** name, email, password
- **Response:** JSON with user data or error message

### Login
- **Endpoint:** `api.php?action=login`
- **Method:** POST
- **Parameters:** email, password
- **Response:** JSON with user data or error message
- **Sets:** Session cookie

### Get Profile
- **Endpoint:** `api.php?action=get_profile`
- **Method:** GET
- **Response:** JSON with current user data (requires session)

### Logout
- **Endpoint:** `api.php?action=logout`
- **Method:** GET
- **Response:** JSON success message
- **Clears:** Session

### Checkout
- **Endpoint:** `api.php?action=checkout`
- **Method:** POST
- **Body:** JSON array of cart items
- **Response:** JSON with order_id and total_price

---

## USER AUTHENTICATION

### Register
1. Click "Đăng nhập" button
2. Click "Đăng ký" to show register form
3. Enter name, email, password
4. Click "Đăng ký" button
5. Password must be at least 6 characters

### Login
1. Click "Đăng nhập" button
2. Enter email and password
3. Click "Đăng nhập" button
4. Your name appears in header after successful login

### Logout
1. Click your name in header
2. Click "Đăng xuất" button
3. You will be logged out

### Continue as Guest
- Click "Bỏ qua" to close auth modal
- You can browse and add items to cart without logging in
- Orders will be placed without user account

✓ Product catalog from CSV data
✓ Real-time search by name, brand, description
✓ Shopping cart with add/remove/quantity management
✓ Persistent cart storage (localStorage)
✓ User registration with email verification
✓ Secure login with hashed passwords
✓ User profile view
✓ Order placement and tracking
✓ Responsive design (mobile, tablet, desktop)
✓ Professional UI with animations

---

## CONFIGURATION

### MySQL Connection (db.php)
```php
$host = "127.0.0.1";    // Your MySQL host
$user = "root";          // Your MySQL username
$pass = "";              // Your MySQL password
$dbname = "my_store";    // Database name (created automatically)
```

### Change CSV Import File (import.php)
```php
// Edit this line to import different CSV:
$csv_file = 'data/vietnamese_tiki_products_backpacks_suitcases.csv';

// Change to:
$csv_file = 'data/vietnamese_tiki_products_men_shoes.csv';
// or any other CSV in the data/ folder
```

---

## TROUBLESHOOTING

### Database Connection Error
**Problem:** "Connection failed"
**Solution:** 
- Check MySQL is running
- Verify credentials in db.php
- Check database name matches

### No Products Displaying
**Problem:** Store shows "No products found"
**Solution:**
- Run setup_db.php first
- Run import_csv.php to load products
- Check if products table has data

### Search Not Working
**Problem:** Search returns no results
**Solution:**
- Make sure products are imported (see above)
- Check search term matches product names/brands
- Open browser console (F12) for JavaScript errors

### Login Not Working
**Problem:** "Email or password incorrect"
**Solution:**
- Make sure users table exists
- Check password is correct
- Try registering a new account

### CSV Import Fails
**Problem:** "File not found"
**Solution:**
- Check CSV file exists in data/ folder
- Verify file path in import_csv.php
- Check file is readable

---

## SECURITY NOTES

- Passwords hashed with PHP password_hash() function
- Sessions for user authentication
- UTF-8 encoding for international characters
- SQL injection prevention with mysqli_real_escape_string()

**Production Note:** For production use, consider:
- Prepared statements instead of string concatenation
- HTTPS for secure connections
- CSRF tokens
- Input validation
- Rate limiting

---

## BROWSER SUPPORT

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## TECHNOLOGY STACK

- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Backend:** PHP 7+
- **Database:** MySQL 5.7+
- **Server:** Apache/Nginx with PHP-FDI

---

## USAGE EXAMPLES

### Add Product to Cart
```javascript
addToCart(productId, productName, productPrice);
```

### Search for Product
- Type in the search box
- Results update in real-time

### Place Order
1. Add items to cart
2. Click "Cart" button
3. Click "Checkout"
4. Login or continue as guest
5. Order created in database

### Check Orders
- Login to your account
- Orders stored in database (orders table)

---

## NOTES

- Product images are placeholder images (via.placeholder.com)
- All prices in Vietnamese Dong (VND)
- Cart data persists in browser via localStorage
- Orders require database to be running
- Sessions expire when browser closes (or configure in php.ini)

---

## SUPPORT

For issues:
1. Check browser console (F12) for JavaScript errors
2. Check server error logs
3. Verify all setup steps completed
4. Review database with phpMyAdmin

---

**Version:** 1.0
**Last Updated:** December 2024
**Status:** Ready for use
=======
# INSTALLATION & USAGE GUIDE

## Overview
A complete e-commerce web application with MySQL database backend. Built with vanilla JavaScript, PHP, and MySQL.

---

## QUICK START (3 STEPS)

### Step 1: Initialize Database
1. Open your browser
2. Go to: `http://localhost/DBMS/setup_db.php`
3. This creates all necessary database tables automatically

### Step 2: Import Product Data
1. Go to: `http://localhost/DBMS/import.php`
2. This imports products from the CSV file into the database
3. (Optional) Edit import.php to import different CSV files

### Step 3: Start Using the App
1. Go to: `http://localhost/DBMS/index.php`
2. Browse products, search, add to cart, register/login, checkout!

---

## FILE STRUCTURE

```
app.js              - Frontend JavaScript (13KB)
                      ├─ Load products from API
                      ├─ Shopping cart logic
                      ├─ User authentication
                      └─ Search functionality

api.php             - Backend REST API (5.5KB)
                      ├─ GET /products
                      ├─ POST /login, /register
                      ├─ GET /profile, /logout
                      └─ POST /checkout

db.php              - Database connection (377 bytes)
                      └─ MySQL connection & charset config

setup_db.php        - Database initialization (2.9KB)
                      ├─ Create my_store database
                      ├─ products table
                      ├─ users table
                      ├─ orders table
                      └─ order_items table

import_csv.php      - CSV data import (1.7KB)
                      └─ Imports product data into database

index.php           - Main store page (3.1KB)
                      ├─ Product grid
                      ├─ Search bar
                      ├─ Shopping cart modal
                      └─ Login/Register modal

styles.css          - Responsive styling (8KB)
                      ├─ Mobile-first design
                      ├─ Product cards
                      ├─ Cart interface
                      └─ Forms & modals

setup.html          - Setup instructions (5.7KB)
                      └─ Interactive setup guide

data/               - CSV product files
                      ├─ vietnamese_tiki_products_backpacks_suitcases.csv
                      ├─ vietnamese_tiki_products_fashion_accessories.csv
                      ├─ vietnamese_tiki_products_men_bags.csv
                      ├─ vietnamese_tiki_products_men_shoes.csv
                      ├─ vietnamese_tiki_products_women_bags.csv
                      └─ vietnamese_tiki_products_women_shoes.csv
```

---

## DATABASE SCHEMA

### products table
```sql
id (INT, PRIMARY KEY)
name (VARCHAR 255)
description (LONGTEXT)
original_price (FLOAT)
price (FLOAT)
fulfillment_type (VARCHAR 100)
brand (VARCHAR 100)
review_count (INT)
rating_average (FLOAT)
created_at (TIMESTAMP)
```

### users table
```sql
id (INT, PRIMARY KEY, AUTO_INCREMENT)
name (VARCHAR 100)
email (VARCHAR 100, UNIQUE)
password (VARCHAR 255) - hashed with password_hash()
created_at (TIMESTAMP)
```

### orders table
```sql
id (INT, PRIMARY KEY, AUTO_INCREMENT)
user_id (INT, FOREIGN KEY)
total_price (FLOAT)
status (VARCHAR 50) - 'pending', 'completed', etc
created_at (TIMESTAMP)
```

### order_items table
```sql
id (INT, PRIMARY KEY, AUTO_INCREMENT)
order_id (INT, FOREIGN KEY)
product_id (INT, FOREIGN KEY)
quantity (INT)
price (FLOAT)
```

---

## API ENDPOINTS

### Get Products
- **Endpoint:** `api.php?action=get_products`
- **Method:** GET
- **Response:** JSON array of all products
- **Example:** `/api.php?action=get_products`

### Search Products
- **Endpoint:** `api.php?action=get_products&search=keyword`
- **Method:** GET
- **Response:** JSON array of matching products
- **Example:** `/api.php?action=get_products&search=shoes`

### Register User
- **Endpoint:** `api.php?action=register`
- **Method:** POST
- **Parameters:** name, email, password
- **Response:** JSON with user data or error message

### Login
- **Endpoint:** `api.php?action=login`
- **Method:** POST
- **Parameters:** email, password
- **Response:** JSON with user data or error message
- **Sets:** Session cookie

### Get Profile
- **Endpoint:** `api.php?action=get_profile`
- **Method:** GET
- **Response:** JSON with current user data (requires session)

### Logout
- **Endpoint:** `api.php?action=logout`
- **Method:** GET
- **Response:** JSON success message
- **Clears:** Session

### Checkout
- **Endpoint:** `api.php?action=checkout`
- **Method:** POST
- **Body:** JSON array of cart items
- **Response:** JSON with order_id and total_price

---

## USER AUTHENTICATION

### Register
1. Click "Đăng nhập" button
2. Click "Đăng ký" to show register form
3. Enter name, email, password
4. Click "Đăng ký" button
5. Password must be at least 6 characters

### Login
1. Click "Đăng nhập" button
2. Enter email and password
3. Click "Đăng nhập" button
4. Your name appears in header after successful login

### Logout
1. Click your name in header
2. Click "Đăng xuất" button
3. You will be logged out

### Continue as Guest
- Click "Bỏ qua" to close auth modal
- You can browse and add items to cart without logging in
- Orders will be placed without user account

✓ Product catalog from CSV data
✓ Real-time search by name, brand, description
✓ Shopping cart with add/remove/quantity management
✓ Persistent cart storage (localStorage)
✓ User registration with email verification
✓ Secure login with hashed passwords
✓ User profile view
✓ Order placement and tracking
✓ Responsive design (mobile, tablet, desktop)
✓ Professional UI with animations

---

## CONFIGURATION

### MySQL Connection (db.php)
```php
$host = "127.0.0.1";    // Your MySQL host
$user = "root";          // Your MySQL username
$pass = "";              // Your MySQL password
$dbname = "my_store";    // Database name (created automatically)
```

### Change CSV Import File (import.php)
```php
// Edit this line to import different CSV:
$csv_file = 'data/vietnamese_tiki_products_backpacks_suitcases.csv';

// Change to:
$csv_file = 'data/vietnamese_tiki_products_men_shoes.csv';
// or any other CSV in the data/ folder
```

---

## TROUBLESHOOTING

### Database Connection Error
**Problem:** "Connection failed"
**Solution:** 
- Check MySQL is running
- Verify credentials in db.php
- Check database name matches

### No Products Displaying
**Problem:** Store shows "No products found"
**Solution:**
- Run setup_db.php first
- Run import_csv.php to load products
- Check if products table has data

### Search Not Working
**Problem:** Search returns no results
**Solution:**
- Make sure products are imported (see above)
- Check search term matches product names/brands
- Open browser console (F12) for JavaScript errors

### Login Not Working
**Problem:** "Email or password incorrect"
**Solution:**
- Make sure users table exists
- Check password is correct
- Try registering a new account

### CSV Import Fails
**Problem:** "File not found"
**Solution:**
- Check CSV file exists in data/ folder
- Verify file path in import_csv.php
- Check file is readable

---

## SECURITY NOTES

- Passwords hashed with PHP password_hash() function
- Sessions for user authentication
- UTF-8 encoding for international characters
- SQL injection prevention with mysqli_real_escape_string()

**Production Note:** For production use, consider:
- Prepared statements instead of string concatenation
- HTTPS for secure connections
- CSRF tokens
- Input validation
- Rate limiting

---

## BROWSER SUPPORT

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## TECHNOLOGY STACK

- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Backend:** PHP 7+
- **Database:** MySQL 5.7+
- **Server:** Apache/Nginx with PHP-FDI

---

## USAGE EXAMPLES

### Add Product to Cart
```javascript
addToCart(productId, productName, productPrice);
```

### Search for Product
- Type in the search box
- Results update in real-time

### Place Order
1. Add items to cart
2. Click "Cart" button
3. Click "Checkout"
4. Login or continue as guest
5. Order created in database

### Check Orders
- Login to your account
- Orders stored in database (orders table)

---

## NOTES

- Product images are placeholder images (via.placeholder.com)
- All prices in Vietnamese Dong (VND)
- Cart data persists in browser via localStorage
- Orders require database to be running
- Sessions expire when browser closes (or configure in php.ini)

---

## SUPPORT

For issues:
1. Check browser console (F12) for JavaScript errors
2. Check server error logs
3. Verify all setup steps completed
4. Review database with phpMyAdmin

---

**Version:** 1.0
**Last Updated:** December 2024
**Status:** Ready for use
>>>>>>> 5f79eaeba4311ce083ded1cf198a4a984c0b8b86
