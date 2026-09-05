/**
 * ============================================================
 *  CART MANAGEMENT
 *  Manages the shopping cart using localStorage.
 *  Product data is loaded from the backend API or a fallback.
 * ============================================================
 */

// Branded placeholder shown when a product image is missing (defined here so
// it works on pages that do not load catalog.js; identical to catalog.js).
if (typeof PLACEHOLDER_IMG === 'undefined') {
    var PLACEHOLDER_IMG = 'data:image/svg+xml;utf8,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600">' +
        '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">' +
        '<stop offset="0" stop-color="#dcfce7"/><stop offset="1" stop-color="#bbf7d0"/>' +
        '</linearGradient></defs>' +
        '<rect width="600" height="600" fill="url(#g)"/>' +
        '<g transform="translate(300,300)"><circle r="120" fill="#ffffff" opacity="0.85"/>' +
        '<path d="M-70,-40 C-10,-80 50,-60 70,-20 C40,-10 10,-30 -10,-10 L-70,-40 Z M80,-10 C60,50 10,60 -40,40 L20,10 C40,-20 60,-20 80,-10 Z" fill="#16a34a"/>' +
        '</g></svg>'
    );
}

const Cart = (function () {
    const STORAGE_KEY = 'organic_pesticide_cart';
    let cart = [];

    // Load cart from localStorage
    function load() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            cart = saved ? JSON.parse(saved) : [];
        } catch (e) {
            cart = [];
        }
        return cart;
    }

    // Save cart to localStorage
    function save() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
        updateCartUI();
    }

    // Add item to cart
    function addItem(product, qty = 1) {
        load();
        const existing = cart.find(i => i.id === product.id);
        if (existing) {
            existing.qty += qty;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image: product.image,
                unit: product.unit,
                qty: qty
            });
        }
        save();
    }

    // Remove item from cart
    function removeItem(id) {
        load();
        cart = cart.filter(i => i.id !== id);
        save();
    }

    // Update quantity of an item
    function updateQty(id, qty) {
        load();
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty = Math.max(1, qty);
        }
        save();
    }

    // Clear entire cart
    function clear() {
        cart = [];
        save();
    }

    // Get cart contents
    function getCart() {
        return load();
    }

    // Get number of items (sum of quantities)
    function count() {
        load();
        return cart.reduce((sum, i) => sum + i.qty, 0);
    }

    // Get subtotal
    function subtotal() {
        load();
        return cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    }

    // Shipping fee (above threshold => free)
    const FREE_THRESHOLD = 500;
    const SHIPPING_FEE = 49;
    function shipping() {
        const sub = subtotal();
        return (sub >= FREE_THRESHOLD || sub === 0) ? 0 : SHIPPING_FEE;
    }

    // Total (subtotal + shipping)
    function total() {
        return subtotal() + shipping();
    }

    // Update cart counter icon in navbar
    function updateCartUI() {
        const badge = document.querySelectorAll('.cart-badge');
        const n = count();
        const text = n > 99 ? '99+' : String(n);
        badge.forEach(b => {
            b.textContent = text;
            b.style.display = n > 0 ? 'flex' : 'none';
        });
    }

    // Render cart items (used by cart page)
    function renderCart() {
        const container = document.getElementById('cartItems');
        const emptyEl = document.getElementById('cartEmpty');
        const summaryEl = document.getElementById('cartSummary');
        if (!container) return;

        const items = getCart();

        if (items.length === 0) {
            container.innerHTML = '';
            if (emptyEl) emptyEl.style.display = 'block';
            if (summaryEl) summaryEl.style.display = 'none';
            updateTotals();
            return;
        }

        if (emptyEl) emptyEl.style.display = 'none';
        if (summaryEl) summaryEl.style.display = 'block';

        container.innerHTML = items.map(item => `
            <div class="cart-item" data-id="${item.id}">
                <div class="cart-item-img">
                    <img src="${item.image}" alt="${item.name}" onerror="this.onerror=null;this.src=PLACEHOLDER_IMG;">
                </div>
                <div class="cart-item-info">
                    <h4>${item.name}</h4>
                    <span class="cart-item-price">₹${item.price}</span>
                    <span class="cart-item-unit">/${item.unit}</span>
                </div>
                <div class="cart-item-qty">
                    <button class="qty-btn minus" onclick="Cart.decrement('${item.id}')">-</button>
                    <span class="qty-num">${item.qty}</span>
                    <button class="qty-btn plus" onclick="Cart.increment('${item.id}')">+</button>
                </div>
                <div class="cart-item-total">₹${item.price * item.qty}</div>
                <button class="cart-item-remove" onclick="Cart.remove('${item.id}')"><i class="fas fa-trash"></i></button>
            </div>
        `).join('');

        updateTotals();
    }

    // Update subtotal / shipping / total in summary
    function updateTotals() {
        const subEl = document.getElementById('subtotal');
        const shipEl = document.getElementById('shipping');
        const totalEl = document.getElementById('cartTotal');
        const checkoutBtn = document.getElementById('checkoutBtn');

        if (subEl) subEl.textContent = '₹' + subtotal();
        if (shipEl) {
            const s = shipping();
            shipEl.textContent = s === 0 ? 'FREE' : '₹' + s;
            shipEl.classList.toggle('free', s === 0);
        }
        if (totalEl) totalEl.textContent = '₹' + total();

        if (checkoutBtn) {
            const items = getCart();
            checkoutBtn.disabled = items.length === 0;
        }
    }

    // Public API
    return {
        add: addItem,
        remove: removeItem,
        updateQty: updateQty,
        clear: clear,
        get: getCart,
        count: count,
        subtotal: subtotal,
        shipping: shipping,
        total: total,
        render: renderCart,
        updateTotals: updateTotals,
        increment: (id) => { const it = getCart().find(i => i.id === id); if (it) updateQty(id, it.qty + 1); renderCart(); },
        decrement: (id) => { const it = getCart().find(i => i.id === id); if (it) updateQty(id, it.qty - 1); renderCart(); }
    };
})();

// Initialize cart UI counter on page load
document.addEventListener('DOMContentLoaded', () => {
    Cart.updateCartUI();
});
