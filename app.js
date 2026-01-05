// E-commerce App
let currentUser = null;
let cartItems = [];
let currentPage = 1;
let totalPages = 1;
let lastSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    checkUserStatus();
    loadProducts();
    updateCartUI();
});

function loadProducts(page = 1) {
    let url = 'api.php?action=get_products&page=' + page;
    if (lastSearch) {
        url += '&search=' + encodeURIComponent(lastSearch);
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products) {
                currentPage = data.pagination.page;
                totalPages = data.pagination.pages;
                showProducts(data.products);
                showPagination(data.pagination);
            } else {
                document.getElementById('products').innerHTML = '<p>No products found</p>';
            }
        })
        .catch(error => {
            document.getElementById('products').innerHTML = '<p>Error loading products</p>';
        });
}

function showProducts(products) {
    const container = document.getElementById('products');
    container.innerHTML = '';
    
    if (!products || products.length === 0) {
        container.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:40px;">No products found</p>';
        return;
    }
    
    products.forEach(product => {
        const originalPrice = parseFloat(product.original_price) || 0;
        const price = parseFloat(product.price) || 0;
        const discount = originalPrice > price 
            ? Math.round((1 - price / originalPrice) * 100)
            : 0;
        const rating = parseFloat(product.rating_average) || 0;
        const reviewCount = parseInt(product.review_count) || 0;
        
        const html = `
            <div class="product-card">
                <div class="product-image-wrapper">
                    <img src="https://via.placeholder.com/200?text=${encodeURIComponent(product.name)}" 
                         alt="${product.name}" class="product-image">
                    ${discount > 0 ? '<span class="discount-badge">-' + discount + '%</span>' : ''}
                </div>
                <div class="product-info">
                    <h3 class="product-name">${product.name}</h3>
                    <p class="product-brand">${product.brand || 'Unknown'}</p>
                    <div class="product-rating">
                        <span class="rating-stars">★ ${rating.toFixed(1)}</span>
                        <span class="review-count">(${reviewCount})</span>
                    </div>
                    <div class="product-price">
                        <span class="price-current">${formatPrice(price)}</span>
                        ${originalPrice > price ? '<span class="price-original">' + formatPrice(originalPrice) + '</span>' : ''}
                    </div>
                    <p class="fulfillment">${product.fulfillment_type || 'Standard'}</p>
                    <button class="btn btn-add-cart" onclick="addToCart(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${price})">
                        Add to Cart
                    </button>
                </div>
            </div>
        `;
        container.innerHTML += html;
    });
}

function showPagination(pagination) {
    const paginationEl = document.getElementById('pagination');
    if (!paginationEl) return;
    
    paginationEl.innerHTML = '';
    
    if (pagination.pages <= 1) return;
    
    let html = '<div style="display:flex;justify-content:center;gap:8px;margin:30px 0;flex-wrap:wrap;">';
    
    // Previous
    if (pagination.page > 1) {
        html += `<button class="btn" onclick="loadProducts(${pagination.page - 1})">← Previous</button>`;
    }
    
    // Page numbers
    const startPage = Math.max(1, pagination.page - 2);
    const endPage = Math.min(pagination.pages, pagination.page + 2);
    
    if (startPage > 1) {
        html += `<button class="btn" onclick="loadProducts(1)">1</button>`;
        if (startPage > 2) html += '<span style="padding:8px;">...</span>';
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.page) {
            html += `<button class="btn" style="background:#FF6B6B;color:white;">Page ${i}</button>`;
        } else {
            html += `<button class="btn" onclick="loadProducts(${i})">Page ${i}</button>`;
        }
    }
    
    if (endPage < pagination.pages) {
        if (endPage < pagination.pages - 1) html += '<span style="padding:8px;">...</span>';
        html += `<button class="btn" onclick="loadProducts(${pagination.pages})">Page ${pagination.pages}</button>`;
    }
    
    // Next
    if (pagination.page < pagination.pages) {
        html += `<button class="btn" onclick="loadProducts(${pagination.page + 1})">Next →</button>`;
    }
    
    html += `<span style="flex-basis:100%;text-align:center;color:#666;font-size:14px;">
        Showing ${pagination.page} of ${pagination.pages} pages (${pagination.total} total products)
    </span>`;
    html += '</div>';
    
    paginationEl.innerHTML = html;
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(Math.round(price));
}

// Cart
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
    
    const list = document.getElementById('cart-items');
    if (!list) return;
    
    list.innerHTML = '';
    let total = 0;
    
    if (cartItems.length === 0) {
        list.innerHTML = '<li style="padding:20px;text-align:center;">Cart is empty</li>';
    } else {
        cartItems.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            list.innerHTML += `
                <li class="cart-item">
                    <div class="cart-item-name">${item.name}</div>
                    <div>x${item.quantity}</div>
                    <div>${formatPrice(itemTotal)}</div>
                </li>
            `;
        });
    }
    
    const totalEl = document.getElementById('cart-total');
    if (totalEl) totalEl.textContent = formatPrice(total);
}

// Cart Modal
const cartBtn = document.getElementById('cart-btn');
const cartModal = document.getElementById('cart-modal');
const closeCart = document.getElementById('close-cart');
const checkoutBtn = document.getElementById('checkout');

if (cartBtn) cartBtn.addEventListener('click', () => {
    if (cartModal) cartModal.classList.remove('hidden');
});

if (closeCart) closeCart.addEventListener('click', () => {
    if (cartModal) cartModal.classList.add('hidden');
});

if (cartModal) {
    cartModal.addEventListener('click', (e) => {
        if (e.target === cartModal) cartModal.classList.add('hidden');
    });
}

// Checkout
if (checkoutBtn) {
    checkoutBtn.addEventListener('click', async () => {
        if (cartItems.length === 0) {
            alert('Cart is empty');
            return;
        }
        
        try {
            const response = await fetch('api.php?action=checkout', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({items: cartItems})
            });
            const data = await response.json();
            
            if (data.success) {
                alert('✓ Order placed!\nOrder ID: ' + data.order_id + '\nTotal: ' + formatPrice(data.total));
                cartItems = [];
                updateCartUI();
                if (cartModal) cartModal.classList.add('hidden');
            } else {
                alert('❌ ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
}

// Auth
const authBtn = document.getElementById('auth-btn');
const authModal = document.getElementById('auth-modal');
const closeAuth = document.getElementById('close-auth');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const showRegisterBtn = document.getElementById('show-register');
const showLoginBtn = document.getElementById('show-login');
const skipAuthBtn = document.getElementById('skip-auth');
const logoutBtn = document.getElementById('btn-logout');
const closeProfileBtn = document.getElementById('btn-close-profile');

if (authBtn) {
    authBtn.addEventListener('click', (e) => {
        e.preventDefault();
        checkUserStatus();
    });
}

if (closeAuth) {
    closeAuth.addEventListener('click', () => {
        if (authModal) authModal.classList.add('hidden');
    });
}

if (authModal) {
    authModal.addEventListener('click', (e) => {
        if (e.target === authModal) authModal.classList.add('hidden');
    });
}

// Toggle forms
if (showRegisterBtn) {
    showRegisterBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (loginForm) loginForm.classList.add('hidden');
        if (registerForm) registerForm.classList.remove('hidden');
    });
}

if (showLoginBtn) {
    showLoginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (registerForm) registerForm.classList.add('hidden');
        if (loginForm) loginForm.classList.remove('hidden');
    });
}

// Login
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        
        try {
            const response = await fetch('api.php?action=login', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            });
            const data = await response.json();
            
            if (data.success) {
                currentUser = data.user;
                showProfileView();
                loginForm.reset();
            } else {
                alert('❌ ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
}

// Register
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('reg-name').value;
        const email = document.getElementById('reg-email').value;
        const password = document.getElementById('reg-password').value;
        
        if (password.length < 6) {
            alert('Password must be at least 6 characters');
            return;
        }
        
        try {
            const response = await fetch('api.php?action=register', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            });
            const data = await response.json();
            
            if (data.success) {
                currentUser = data.user;
                showProfileView();
                registerForm.reset();
            } else {
                alert('❌ ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
}

// Skip auth
if (skipAuthBtn) {
    skipAuthBtn.addEventListener('click', () => {
        if (authModal) authModal.classList.add('hidden');
    });
}

// Logout
if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
        try {
            await fetch('api.php?action=logout');
            currentUser = null;
            showLoginForm();
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
}

if (closeProfileBtn) {
    closeProfileBtn.addEventListener('click', () => {
        if (authModal) authModal.classList.add('hidden');
    });
}

function checkUserStatus() {
    fetch('api.php?action=get_profile')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.user) {
                currentUser = data.user;
                showProfileView();
            } else {
                showLoginForm();
            }
            if (authModal) authModal.classList.remove('hidden');
        });
}

function showLoginForm() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const profileView = document.getElementById('profile-view');
    
    if (loginForm) loginForm.classList.remove('hidden');
    if (registerForm) registerForm.classList.add('hidden');
    if (profileView) profileView.classList.add('hidden');
    
    const authTitle = document.getElementById('auth-title');
    if (authTitle) authTitle.textContent = 'Đăng nhập';
}

function showProfileView() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const profileView = document.getElementById('profile-view');
    const profileName = document.getElementById('profile-name');
    const profileEmail = document.getElementById('profile-email');
    const authTitle = document.getElementById('auth-title');
    
    if (loginForm) loginForm.classList.add('hidden');
    if (registerForm) registerForm.classList.add('hidden');
    if (profileView) profileView.classList.remove('hidden');
    if (profileName) profileName.textContent = currentUser.name || 'User';
    if (profileEmail) profileEmail.textContent = currentUser.email || '';
    if (authTitle) authTitle.textContent = 'Tài khoản';
    
    const authBtn = document.getElementById('auth-btn');
    if (authBtn) authBtn.textContent = currentUser.name || 'Account';
}

// Search
const searchInput = document.getElementById('search-input');
if (searchInput) {
    let timeout;
    searchInput.addEventListener('keyup', (e) => {
        clearTimeout(timeout);
        const search = e.target.value.trim();
        lastSearch = search;
        currentPage = 1;
        
        if (search.length > 2) {
            timeout = setTimeout(() => {
                loadProducts(1);
            }, 300);
        } else if (search.length === 0) {
            timeout = setTimeout(() => loadProducts(1), 300);
        }
    });
}
