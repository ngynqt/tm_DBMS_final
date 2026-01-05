<?php
require 'db.php';

// Get initial data
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$brands_result = mysqli_query($conn, "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand LIMIT 30");
$brands = [];
while ($row = mysqli_fetch_assoc($brands_result)) {
    $brands[] = $row['brand'];
}
$price_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(price) as min, MAX(price) as max FROM products"));
$min_price = (int)$price_info['min'];
$max_price = (int)$price_info['max'];
?>
<!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>DBMS Shop - Cửa hàng trực tuyến</title>
	<link rel="stylesheet" href="styles.css">
	<style>
		.main-container { display: grid; grid-template-columns: 280px 1fr; gap: 20px; padding: 20px; max-width: 1400px; margin: 0 auto; }
		.filter-sidebar { background: white; padding: 20px; border-radius: 8px; height: fit-content; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 80px; }
		.filter-sidebar h2 { margin: 0 0 20px 0; font-size: 18px; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
		.filter-group { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
		.filter-group label { display: block; margin-bottom: 8px; font-weight: 600; }
		.filter-input { width: 100%; padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 4px; }
		.brand-list { max-height: 250px; overflow-y: auto; }
		.brand-item { display: flex; align-items: center; margin-bottom: 6px; font-size: 13px; }
		.brand-item input { margin-right: 8px; cursor: pointer; }
		.filter-btn { width: 100%; padding: 10px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; margin-bottom: 8px; }
		.filter-btn:hover { background: #1565c0; }
		.filter-btn.reset { background: #999; }
		.filter-btn.reset:hover { background: #777; }
		.perf-info { background: #e3f2fd; padding: 12px; border-radius: 4px; margin-top: 15px; font-size: 13px; display: none; }
		.perf-info.show { display: block; }
		.products-container { display: flex; flex-direction: column; }
		.stats { background: #e3f2fd; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
		.error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 4px; margin-bottom: 15px; display: none; }
		.error.show { display: block; }
		@media (max-width: 768px) {
			.main-container { grid-template-columns: 1fr; }
			.filter-sidebar { position: static; }
		}
	</style>
</head>
<body>
	<header class="site-header">
		<h1>DBMS SHOP</h1>
		<div class="header-actions">
			<input id="search-input" class="search-input" type="search" placeholder="Tìm sản phẩm...">
			<a href="compare_index_performance.php" class="btn" title="So sánh performance Index">⚡ Performance</a>
			<button id="cart-btn" class="btn">Giỏ (<span id="cart-count">0</span>)</button>
			<a id="auth-btn" class="btn" href="#">Đăng nhập</a>
		</div>
	</header>

	<main>
		<div class="main-container">
			<!-- FILTER SIDEBAR -->
			<aside class="filter-sidebar">
				<h2>Bộ lọc sản phẩm</h2>
				
				<div class="filter-group">
					<label>🔍 Tìm kiếm</label>
					<input type="text" id="filterSearch" class="filter-input" placeholder="Nhập tên...">
				</div>

				<div class="filter-group">
					<label>💰 Giá (VND)</label>
					<input type="number" id="filterPriceMin" class="filter-input" placeholder="Tối thiểu">
					<input type="number" id="filterPriceMax" class="filter-input" placeholder="Tối đa" value="<?php echo $max_price; ?>">
					<small id="priceDisplay" style="color: #666;">Chọn khoảng</small>
				</div>

				<div class="filter-group">
					<label>🏷️ Thương hiệu</label>
					<div class="brand-list" id="brandList">
						<?php foreach ($brands as $brand): ?>
							<label class="brand-item">
								<input type="checkbox" value="<?php echo htmlspecialchars($brand); ?>" class="brandCheck">
								<span><?php echo htmlspecialchars($brand); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="filter-group">
					<label>⭐ Đánh giá</label>
					<select id="filterRating" class="filter-input">
						<option value="">Tất cả</option>
						<option value="5">⭐⭐⭐⭐⭐ 5 sao</option>
						<option value="4">⭐⭐⭐⭐ 4+ sao</option>
						<option value="3">⭐⭐⭐ 3+ sao</option>
					</select>
				</div>

				<button class="filter-btn" onclick="applyFilter()">🔍 Áp dụng</button>
				<button class="filter-btn reset" onclick="resetFilter()">↺ Đặt lại</button>

				<div class="perf-info" id="perfInfo">
					<strong>⏱️ Hiệu năng:</strong><br>
					Thời gian: <span id="perfTime">-</span>ms<br>
					Kết quả: <span id="perfResults">-</span>
				</div>
			</aside>

			<!-- PRODUCTS SECTION -->
			<section class="products-container">
				<div class="stats" id="stats">⏳ Đang tải...</div>
				<div class="error" id="error"></div>
				<div id="products" class="products-grid"></div>
				<div id="pagination"></div>
			</section>
		</div>
	</main>

	<!-- Cart Modal -->
	<div id="cart-modal" class="cart-modal hidden">
		<div class="cart-sheet">
			<div class="cart-header">
				<h2>Giỏ hàng</h2>
				<button id="close-cart" class="btn small">Đóng</button>
			</div>
			<ul id="cart-items" class="cart-items"></ul>
			<div class="cart-footer">
				<div class="cart-total">Tổng: <strong id="cart-total">0 đ</strong></div>
				<div>
					<button id="checkout" class="btn primary">Thanh toán</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Auth Modal -->
	<div id="auth-modal" class="cart-modal hidden">
		<div class="cart-sheet">
			<div class="cart-header">
				<h2 id="auth-title">Đăng nhập</h2>
				<button id="close-auth" class="btn small">Đóng</button>
			</div>
			<div class="auth-body">
				<form id="login-form" class="auth-form">
					<label>Email<div><input id="login-email" type="email" required></div></label>
					<label>Mật khẩu<div><input id="login-password" type="password" required></div></label>
					<div style="margin-top:10px;display:flex;gap:8px">
						<button type="submit" class="btn primary">Đăng nhập</button>
						<button type="button" id="show-register" class="btn">Đăng ký</button>
					</div>
				</form>

				<form id="register-form" class="auth-form hidden">
					<label>Tên<div><input id="reg-name" type="text" required></div></label>
					<label>Email<div><input id="reg-email" type="email" required></div></label>
					<label>Mật khẩu<div><input id="reg-password" type="password" required></div></label>
					<div style="margin-top:10px;display:flex;gap:8px">
						<button type="submit" class="btn primary">Đăng ký</button>
						<button type="button" id="show-login" class="btn">Đã có tài khoản</button>
					</div>
				</form>

				<div style="margin-top:12px">
					<button id="skip-auth" class="btn">Bỏ qua</button>
				</div>

				<div id="profile-view" class="hidden" style="margin-top:8px">
					<div>Xin chào, <strong id="profile-name"></strong></div>
					<div class="muted" id="profile-email"></div>
					<div style="margin-top:12px;display:flex;gap:8px">
						<button id="btn-logout" class="btn">Đăng xuất</button>
						<button id="btn-close-profile" class="btn">Đóng</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		let cartItems = [];
		let currentUser = null;

		// ====== FILTER FUNCTIONS ======
		async function applyFilter() {
			console.log('🔍 Applying filter...');
			const search = document.getElementById('filterSearch').value.trim();
			const priceMin = document.getElementById('filterPriceMin').value;
			const priceMax = document.getElementById('filterPriceMax').value;
			const rating = document.getElementById('filterRating').value;
			const brands = Array.from(document.querySelectorAll('.brandCheck:checked')).map(el => el.value);

			let url = 'api.php?action=filter_products_compare&page=1';
			if (search) url += '&search=' + encodeURIComponent(search);
			if (priceMin) url += '&price_min=' + priceMin;
			if (priceMax) url += '&price_max=' + priceMax;
			if (rating) url += '&min_rating=' + rating;
			brands.forEach(b => url += '&brands[]=' + encodeURIComponent(b));

			try {
				document.getElementById('error').classList.remove('show');
				document.getElementById('stats').textContent = '⏳ Đang tải...';

				const response = await fetch(url);
				const data = await response.json();

				if (data.success) {
					const total = data.pagination.total;
					const timeWith = data.performance?.with_index?.time_ms || '?';
					const timeWithout = data.performance?.without_index?.time_ms || '?';
					const speedup = data.performance?.speedup_percent || 0;
					
					document.getElementById('stats').innerHTML = `
						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
							<div style="background: #e8f5e9; padding: 10px; border-radius: 4px; border-left: 3px solid #4CAF50;">
								<strong>✅ Có Index</strong><br>
								✓ Tìm thấy ${total} sản phẩm<br>
								⏱️ <strong style="color: #4CAF50;">${timeWith}ms</strong>
							</div>
							<div style="background: #ffebee; padding: 10px; border-radius: 4px; border-left: 3px solid #f44336;">
								<strong>❌ Không Index</strong><br>
								✓ Tìm thấy ${total} sản phẩm<br>
								⏱️ <strong style="color: #f44336;">${timeWithout}ms</strong>
							</div>
						</div>
						<div style="margin-top: 10px; text-align: center; background: #fff3cd; padding: 8px; border-radius: 4px; color: #856404;">
							🚀 Index nhanh hơn <strong>${speedup}%</strong>
						</div>
					`;
					
					if (data.performance) {
						document.getElementById('perfTime').textContent = timeWith;
						document.getElementById('perfResults').textContent = total;
						document.getElementById('perfInfo').classList.add('show');
					}

					showProducts(data.products);
				} else {
					document.getElementById('error').textContent = '❌ ' + data.message;
					document.getElementById('error').classList.add('show');
					document.getElementById('products').innerHTML = '';
				}
			} catch (error) {
				console.error('❌ Error:', error);
				document.getElementById('error').textContent = '❌ Lỗi: ' + error.message;
				document.getElementById('error').classList.add('show');
			}
		}

		function resetFilter() {
			document.getElementById('filterSearch').value = '';
			document.getElementById('filterPriceMin').value = '';
			document.getElementById('filterPriceMax').value = '<?php echo $max_price; ?>';
			document.getElementById('filterRating').value = '';
			document.querySelectorAll('.brandCheck').forEach(el => el.checked = false);
			applyFilter();
		}

		function showProducts(products) {
			const container = document.getElementById('products');
			if (!products || products.length === 0) {
				container.innerHTML = '<p style="text-align:center;padding:40px;">Không có sản phẩm</p>';
				return;
			}

			container.innerHTML = products.map(p => `
				<div class="product-card">
					<div class="product-image-wrapper">
						<img src="https://via.placeholder.com/200?text=${encodeURIComponent(p.name)}" alt="${p.name}" class="product-image">
					</div>
					<div class="product-info">
						<h3 class="product-name">${p.name}</h3>
						<p class="product-brand">${p.brand || 'N/A'}</p>
						<div class="product-rating">
							<span class="rating-stars">★ ${parseFloat(p.rating_average).toFixed(1)}</span>
							<span class="review-count">(${p.review_count})</span>
						</div>
						<div class="product-price">
							<span class="price-current">${formatPrice(p.price)}</span>
						</div>
						<button class="btn btn-add-cart" onclick="addToCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price})">
							Thêm vào giỏ
						</button>
					</div>
				</div>
			`).join('');
		}

		function formatPrice(p) {
			return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Math.round(p));
		}

		// ====== CART & AUTH ======
		function addToCart(id, name, price) {
			const item = cartItems.find(i => i.id === id);
			if (item) {
				item.quantity++;
			} else {
				cartItems.push({id, name, price, quantity: 1});
			}
			updateCartUI();
		}

		function updateCartUI() {
			const count = cartItems.reduce((sum, i) => sum + i.quantity, 0);
			document.getElementById('cart-count').textContent = count;
		}

		// Cart modal
		document.getElementById('cart-btn').addEventListener('click', () => {
			document.getElementById('cart-modal').classList.remove('hidden');
			const list = document.getElementById('cart-items');
			list.innerHTML = '';
			let total = 0;
			if (cartItems.length === 0) {
				list.innerHTML = '<li style="padding:20px;text-align:center;">Giỏ trống</li>';
			} else {
				cartItems.forEach(item => {
					const itemTotal = item.price * item.quantity;
					total += itemTotal;
					list.innerHTML += `<li class="cart-item"><div>${item.name}</div><div>x${item.quantity}</div><div>${formatPrice(itemTotal)}</div></li>`;
				});
			}
			document.getElementById('cart-total').textContent = formatPrice(total);
		});

		document.getElementById('close-cart').addEventListener('click', () => {
			document.getElementById('cart-modal').classList.add('hidden');
		});

		// Auth
		document.getElementById('auth-btn').addEventListener('click', (e) => {
			e.preventDefault();
			document.getElementById('auth-modal').classList.remove('hidden');
		});

		document.getElementById('close-auth').addEventListener('click', () => {
			document.getElementById('auth-modal').classList.add('hidden');
		});

		document.getElementById('skip-auth').addEventListener('click', () => {
			document.getElementById('auth-modal').classList.add('hidden');
		});

		// Initialize
		document.addEventListener('DOMContentLoaded', function() {
			console.log('✅ Page loaded, applying initial filter...');
			applyFilter();
			updateCartUI();
		});

		// Auto-filter on brand/rating change
		document.querySelectorAll('.brandCheck').forEach(el => {
			el.addEventListener('change', applyFilter);
		});

		document.getElementById('filterRating').addEventListener('change', applyFilter);
	</script>
</body>
=======
<?php
require 'db.php';

// Get initial data
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$brands_result = mysqli_query($conn, "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand LIMIT 30");
$brands = [];
while ($row = mysqli_fetch_assoc($brands_result)) {
    $brands[] = $row['brand'];
}
$price_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(price) as min, MAX(price) as max FROM products"));
$min_price = (int)$price_info['min'];
$max_price = (int)$price_info['max'];
?>
<!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>DBMS Shop - Cửa hàng trực tuyến</title>
	<link rel="stylesheet" href="styles.css">
	<style>
		.main-container { display: grid; grid-template-columns: 280px 1fr; gap: 20px; padding: 20px; max-width: 1400px; margin: 0 auto; }
		.filter-sidebar { background: white; padding: 20px; border-radius: 8px; height: fit-content; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 80px; }
		.filter-sidebar h2 { margin: 0 0 20px 0; font-size: 18px; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
		.filter-group { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
		.filter-group label { display: block; margin-bottom: 8px; font-weight: 600; }
		.filter-input { width: 100%; padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 4px; }
		.brand-list { max-height: 250px; overflow-y: auto; }
		.brand-item { display: flex; align-items: center; margin-bottom: 6px; font-size: 13px; }
		.brand-item input { margin-right: 8px; cursor: pointer; }
		.filter-btn { width: 100%; padding: 10px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; margin-bottom: 8px; }
		.filter-btn:hover { background: #1565c0; }
		.filter-btn.reset { background: #999; }
		.filter-btn.reset:hover { background: #777; }
		.perf-info { background: #e3f2fd; padding: 12px; border-radius: 4px; margin-top: 15px; font-size: 13px; display: none; }
		.perf-info.show { display: block; }
		.products-container { display: flex; flex-direction: column; }
		.stats { background: #e3f2fd; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
		.error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 4px; margin-bottom: 15px; display: none; }
		.error.show { display: block; }
		@media (max-width: 768px) {
			.main-container { grid-template-columns: 1fr; }
			.filter-sidebar { position: static; }
		}
	</style>
</head>
<body>
	<header class="site-header">
		<h1>DBMS SHOP</h1>
		<div class="header-actions">
			<input id="search-input" class="search-input" type="search" placeholder="Tìm sản phẩm...">
			<a href="compare_index_performance.php" class="btn" title="So sánh performance Index">⚡ Performance</a>
			<button id="cart-btn" class="btn">Giỏ (<span id="cart-count">0</span>)</button>
			<a id="auth-btn" class="btn" href="#">Đăng nhập</a>
		</div>
	</header>

	<main>
		<div class="main-container">
			<!-- FILTER SIDEBAR -->
			<aside class="filter-sidebar">
				<h2>Bộ lọc sản phẩm</h2>
				
				<div class="filter-group">
					<label>🔍 Tìm kiếm</label>
					<input type="text" id="filterSearch" class="filter-input" placeholder="Nhập tên...">
				</div>

				<div class="filter-group">
					<label>💰 Giá (VND)</label>
					<input type="number" id="filterPriceMin" class="filter-input" placeholder="Tối thiểu">
					<input type="number" id="filterPriceMax" class="filter-input" placeholder="Tối đa" value="<?php echo $max_price; ?>">
					<small id="priceDisplay" style="color: #666;">Chọn khoảng</small>
				</div>

				<div class="filter-group">
					<label>🏷️ Thương hiệu</label>
					<div class="brand-list" id="brandList">
						<?php foreach ($brands as $brand): ?>
							<label class="brand-item">
								<input type="checkbox" value="<?php echo htmlspecialchars($brand); ?>" class="brandCheck">
								<span><?php echo htmlspecialchars($brand); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="filter-group">
					<label>⭐ Đánh giá</label>
					<select id="filterRating" class="filter-input">
						<option value="">Tất cả</option>
						<option value="5">⭐⭐⭐⭐⭐ 5 sao</option>
						<option value="4">⭐⭐⭐⭐ 4+ sao</option>
						<option value="3">⭐⭐⭐ 3+ sao</option>
					</select>
				</div>

				<button class="filter-btn" onclick="applyFilter()">🔍 Áp dụng</button>
				<button class="filter-btn reset" onclick="resetFilter()">↺ Đặt lại</button>

				<div class="perf-info" id="perfInfo">
					<strong>⏱️ Hiệu năng:</strong><br>
					Thời gian: <span id="perfTime">-</span>ms<br>
					Kết quả: <span id="perfResults">-</span>
				</div>
			</aside>

			<!-- PRODUCTS SECTION -->
			<section class="products-container">
				<div class="stats" id="stats">⏳ Đang tải...</div>
				<div class="error" id="error"></div>
				<div id="products" class="products-grid"></div>
				<div id="pagination"></div>
			</section>
		</div>
	</main>

	<!-- Cart Modal -->
	<div id="cart-modal" class="cart-modal hidden">
		<div class="cart-sheet">
			<div class="cart-header">
				<h2>Giỏ hàng</h2>
				<button id="close-cart" class="btn small">Đóng</button>
			</div>
			<ul id="cart-items" class="cart-items"></ul>
			<div class="cart-footer">
				<div class="cart-total">Tổng: <strong id="cart-total">0 đ</strong></div>
				<div>
					<button id="checkout" class="btn primary">Thanh toán</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Auth Modal -->
	<div id="auth-modal" class="cart-modal hidden">
		<div class="cart-sheet">
			<div class="cart-header">
				<h2 id="auth-title">Đăng nhập</h2>
				<button id="close-auth" class="btn small">Đóng</button>
			</div>
			<div class="auth-body">
				<form id="login-form" class="auth-form">
					<label>Email<div><input id="login-email" type="email" required></div></label>
					<label>Mật khẩu<div><input id="login-password" type="password" required></div></label>
					<div style="margin-top:10px;display:flex;gap:8px">
						<button type="submit" class="btn primary">Đăng nhập</button>
						<button type="button" id="show-register" class="btn">Đăng ký</button>
					</div>
				</form>

				<form id="register-form" class="auth-form hidden">
					<label>Tên<div><input id="reg-name" type="text" required></div></label>
					<label>Email<div><input id="reg-email" type="email" required></div></label>
					<label>Mật khẩu<div><input id="reg-password" type="password" required></div></label>
					<div style="margin-top:10px;display:flex;gap:8px">
						<button type="submit" class="btn primary">Đăng ký</button>
						<button type="button" id="show-login" class="btn">Đã có tài khoản</button>
					</div>
				</form>

				<div style="margin-top:12px">
					<button id="skip-auth" class="btn">Bỏ qua</button>
				</div>

				<div id="profile-view" class="hidden" style="margin-top:8px">
					<div>Xin chào, <strong id="profile-name"></strong></div>
					<div class="muted" id="profile-email"></div>
					<div style="margin-top:12px;display:flex;gap:8px">
						<button id="btn-logout" class="btn">Đăng xuất</button>
						<button id="btn-close-profile" class="btn">Đóng</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		let cartItems = [];
		let currentUser = null;

		// ====== FILTER FUNCTIONS ======
		async function applyFilter() {
			console.log('🔍 Applying filter...');
			const search = document.getElementById('filterSearch').value.trim();
			const priceMin = document.getElementById('filterPriceMin').value;
			const priceMax = document.getElementById('filterPriceMax').value;
			const rating = document.getElementById('filterRating').value;
			const brands = Array.from(document.querySelectorAll('.brandCheck:checked')).map(el => el.value);

			let url = 'api.php?action=filter_products_compare&page=1';
			if (search) url += '&search=' + encodeURIComponent(search);
			if (priceMin) url += '&price_min=' + priceMin;
			if (priceMax) url += '&price_max=' + priceMax;
			if (rating) url += '&min_rating=' + rating;
			brands.forEach(b => url += '&brands[]=' + encodeURIComponent(b));

			try {
				document.getElementById('error').classList.remove('show');
				document.getElementById('stats').textContent = '⏳ Đang tải...';

				const response = await fetch(url);
				const data = await response.json();

				if (data.success) {
					const total = data.pagination.total;
					const timeWith = data.performance?.with_index?.time_ms || '?';
					const timeWithout = data.performance?.without_index?.time_ms || '?';
					const speedup = data.performance?.speedup_percent || 0;
					
					document.getElementById('stats').innerHTML = `
						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
							<div style="background: #e8f5e9; padding: 10px; border-radius: 4px; border-left: 3px solid #4CAF50;">
								<strong>✅ Có Index</strong><br>
								✓ Tìm thấy ${total} sản phẩm<br>
								⏱️ <strong style="color: #4CAF50;">${timeWith}ms</strong>
							</div>
							<div style="background: #ffebee; padding: 10px; border-radius: 4px; border-left: 3px solid #f44336;">
								<strong>❌ Không Index</strong><br>
								✓ Tìm thấy ${total} sản phẩm<br>
								⏱️ <strong style="color: #f44336;">${timeWithout}ms</strong>
							</div>
						</div>
						<div style="margin-top: 10px; text-align: center; background: #fff3cd; padding: 8px; border-radius: 4px; color: #856404;">
							🚀 Index nhanh hơn <strong>${speedup}%</strong>
						</div>
					`;
					
					if (data.performance) {
						document.getElementById('perfTime').textContent = timeWith;
						document.getElementById('perfResults').textContent = total;
						document.getElementById('perfInfo').classList.add('show');
					}

					showProducts(data.products);
				} else {
					document.getElementById('error').textContent = '❌ ' + data.message;
					document.getElementById('error').classList.add('show');
					document.getElementById('products').innerHTML = '';
				}
			} catch (error) {
				console.error('❌ Error:', error);
				document.getElementById('error').textContent = '❌ Lỗi: ' + error.message;
				document.getElementById('error').classList.add('show');
			}
		}

		function resetFilter() {
			document.getElementById('filterSearch').value = '';
			document.getElementById('filterPriceMin').value = '';
			document.getElementById('filterPriceMax').value = '<?php echo $max_price; ?>';
			document.getElementById('filterRating').value = '';
			document.querySelectorAll('.brandCheck').forEach(el => el.checked = false);
			applyFilter();
		}

		function showProducts(products) {
			const container = document.getElementById('products');
			if (!products || products.length === 0) {
				container.innerHTML = '<p style="text-align:center;padding:40px;">Không có sản phẩm</p>';
				return;
			}

			container.innerHTML = products.map(p => `
				<div class="product-card">
					<div class="product-image-wrapper">
						<img src="https://via.placeholder.com/200?text=${encodeURIComponent(p.name)}" alt="${p.name}" class="product-image">
					</div>
					<div class="product-info">
						<h3 class="product-name">${p.name}</h3>
						<p class="product-brand">${p.brand || 'N/A'}</p>
						<div class="product-rating">
							<span class="rating-stars">★ ${parseFloat(p.rating_average).toFixed(1)}</span>
							<span class="review-count">(${p.review_count})</span>
						</div>
						<div class="product-price">
							<span class="price-current">${formatPrice(p.price)}</span>
						</div>
						<button class="btn btn-add-cart" onclick="addToCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price})">
							Thêm vào giỏ
						</button>
					</div>
				</div>
			`).join('');
		}

		function formatPrice(p) {
			return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Math.round(p));
		}

		// ====== CART & AUTH ======
		function addToCart(id, name, price) {
			const item = cartItems.find(i => i.id === id);
			if (item) {
				item.quantity++;
			} else {
				cartItems.push({id, name, price, quantity: 1});
			}
			updateCartUI();
		}

		function updateCartUI() {
			const count = cartItems.reduce((sum, i) => sum + i.quantity, 0);
			document.getElementById('cart-count').textContent = count;
		}

		// Cart modal
		document.getElementById('cart-btn').addEventListener('click', () => {
			document.getElementById('cart-modal').classList.remove('hidden');
			const list = document.getElementById('cart-items');
			list.innerHTML = '';
			let total = 0;
			if (cartItems.length === 0) {
				list.innerHTML = '<li style="padding:20px;text-align:center;">Giỏ trống</li>';
			} else {
				cartItems.forEach(item => {
					const itemTotal = item.price * item.quantity;
					total += itemTotal;
					list.innerHTML += `<li class="cart-item"><div>${item.name}</div><div>x${item.quantity}</div><div>${formatPrice(itemTotal)}</div></li>`;
				});
			}
			document.getElementById('cart-total').textContent = formatPrice(total);
		});

		document.getElementById('close-cart').addEventListener('click', () => {
			document.getElementById('cart-modal').classList.add('hidden');
		});

		// Auth
		document.getElementById('auth-btn').addEventListener('click', (e) => {
			e.preventDefault();
			document.getElementById('auth-modal').classList.remove('hidden');
		});

		document.getElementById('close-auth').addEventListener('click', () => {
			document.getElementById('auth-modal').classList.add('hidden');
		});

		document.getElementById('skip-auth').addEventListener('click', () => {
			document.getElementById('auth-modal').classList.add('hidden');
		});

		// Initialize
		document.addEventListener('DOMContentLoaded', function() {
			console.log('✅ Page loaded, applying initial filter...');
			applyFilter();
			updateCartUI();
		});

		// Auto-filter on brand/rating change
		document.querySelectorAll('.brandCheck').forEach(el => {
			el.addEventListener('change', applyFilter);
		});

		document.getElementById('filterRating').addEventListener('change', applyFilter);
	</script>
</body>
>>>>>>> 5f79eaeba4311ce083ded1cf198a4a984c0b8b86
</html>
