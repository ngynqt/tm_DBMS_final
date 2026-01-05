<?php
require 'db.php';
?>
<!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>DBMS Shop - Cửa hàng trực tuyến</title>
	<link rel="stylesheet" href="styles.css">
</head>
<body>
	<header class="site-header">
		<h1>DBMS SHOP</h1>
		<div class="header-actions">
			<input id="search-input" class="search-input" type="search" placeholder="Tìm sản phẩm...">
			<button id="cart-btn" class="btn">Giỏ (<span id="cart-count">0</span>)</button>
			<a id="auth-btn" class="btn" href="#">Đăng nhập</a>
		</div>
	</header>

	<main>
		<section id="products" class="products-grid"></section>
		<div id="pagination"></div>
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
				<!-- Login Form -->
				<form id="login-form" class="auth-form">
					<label>Email<div><input id="login-email" type="email" required></div></label>
					<label>Mật khẩu<div><input id="login-password" type="password" required></div></label>
					<div style="margin-top:10px;display:flex;gap:8px">
						<button type="submit" class="btn primary">Đăng nhập</button>
						<button type="button" id="show-register" class="btn">Đăng ký</button>
					</div>
				</form>

				<!-- Register Form -->
				<form id="register-form" class="auth-form hidden">
					<label>Tên<div><input id="reg-name" type="text" required></div></label>
					<label>Email<div><input id="reg-email" type="email" required></div></label>
					<label>Mật khẩu<div><input id="reg-password" type="password" required></div></label>
					<div style="margin-top:10px;display:flex;gap:8px">
						<button type="submit" class="btn primary">Đăng ký</button>
						<button type="button" id="show-login" class="btn">Đã có tài khoản</button>
					</div>
				</form>

				<!-- Skip Auth -->
				<div style="margin-top:12px">
					<button id="skip-auth" class="btn">Bỏ qua</button>
				</div>

				<!-- Profile View -->
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

	<script src="app.js"></script>
</body>
</html>