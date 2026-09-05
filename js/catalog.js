/**
 * ============================================================
 *  PRODUCT CATALOG (Front-end)
 *  Contains the full product list so the store works even when
 *  served from static hosting (no PHP).
 *
 *  When the PHP backend is available (backend/api/products.php),
 *  prices/stock are authoritative from the server.
 * ============================================================
 */

// Fallback image shown when a product photo is missing (branded placeholder).
// Guarded + `var` so it can coexist with the identical definition in cart.js.
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


const CATALOG = [
    { id: 'cow-dung', name: 'Cow Dung Compost', name_hi: 'गोबर खाद', price: 299, old_price: 399, category: 'fertilizer', image: 'images/1Cow.png', unit: 'bag', short_desc: 'Nutrient-rich organic cow dung manure for all crops.', featured: true },
    { id: 'vermi-compost', name: 'Vermicompost', name_hi: 'वर्मीकम्पोस्ट', price: 375, old_price: 450, category: 'fertilizer', image: 'images/2Vermi.png', unit: 'bag', short_desc: 'Premium earthworm compost rich in NPK and humus.', featured: true },
    { id: 'green-compost', name: 'Green Compost', name_hi: 'हरी खाद', price: 349, old_price: 0, category: 'fertilizer', image: 'images/3Green.png', unit: 'bag', short_desc: 'Balanced green compost for healthy soil.', featured: true },
    { id: 'jeevaamrut', name: 'Jeevaamrut', name_hi: 'जीवामृत', price: 299, old_price: 400, category: 'soil', image: 'images/Jeevaamrut1.jpg', unit: 'bottle', short_desc: 'Traditional fermented microbial culture for soil life.', featured: true },
    { id: 'neem-oil', name: 'Neem Oil Pesticide', name_hi: 'नीम तेल', price: 420, old_price: 520, category: 'pesticide', image: 'images/Neem-Oil.jpg', unit: 'bottle', short_desc: 'Cold-pressed neem oil for organic pest control.', featured: true },
    { id: 'bio-gas-fertilizer', name: 'Bio-Gas Slurry Fertilizer', name_hi: 'बायोगैस स्लरी खाद', price: 449, old_price: 0, category: 'biogas', image: 'images/Frame-Compost2.jpg', unit: 'bag', short_desc: 'Nutrient-rich fertilizer from biogas slurry.', featured: true },
    { id: 'compost-blend', name: 'Premium Compost Blend', name_hi: 'प्रीमियम कम्पोस्ट', price: 499, old_price: 599, category: 'fertilizer', image: 'images/Frame-Compost7.jpg', unit: 'bag', short_desc: 'Multi-source compost blend for maximum enrichment.', featured: true },
    { id: 'organic-manure-pack', name: 'Organic Manure Pack', name_hi: 'जैविक खाद पैक', price: 649, old_price: 749, category: 'fertilizer', image: 'images/Frame-Compost9.jpg', unit: 'bundle', short_desc: 'Complete 3-in-1 organic manure bundle.', featured: true },
    { id: 'growth-promoter', name: 'GrowthVita Bio Stimulant', name_hi: 'ग्रोथविटा बायो', price: 349, old_price: 0, category: 'growth', image: 'images/GrowthVita.png', unit: 'bottle', short_desc: 'Bio-stimulant with amino acids & seaweed.', featured: false }
];

/**
 * Load products - tries the PHP backend first, falls back to static CATALOG.
 * Returns a Promise resolving to an array of products.
 */
function loadProducts(categoryFilter) {
    return new Promise((resolve) => {
        fetch('backend/api/products.php' + (categoryFilter ? '?category=' + categoryFilter : ''), { method: 'GET' })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    resolve(data.products);
                } else {
                    // Fallback to static catalog
                    let list = CATALOG.slice();
                    if (categoryFilter) {
                        list = list.filter(p => p.category === categoryFilter);
                    }
                    resolve(list);
                }
            })
            .catch(() => {
                // PHP not available - use static catalog
                let list = CATALOG.slice();
                if (categoryFilter) {
                    list = list.filter(p => p.category === categoryFilter);
                }
                resolve(list);
            });
    });
}

/**
 * Render featured products into #productsGrid.
 */
async function renderProducts() {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;

    // Show loading skeleton
    grid.innerHTML = '';
    for (let i = 0; i < 6; i++) {
        grid.innerHTML += '<div class="product-card skeleton-card"><div class="skeleton img"></div><div class="product-info"><div class="skeleton line"></div><div class="skeleton line short"></div></div></div>';
    }

    const products = await loadProducts();

    if ((!products || products.length === 0)) {
        grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#64748b;">No products found.</p>';
        return;
    }

    // Show featured first if any, else all (max 6)
    let featured = products.filter(p => p.featured);
    if (featured.length === 0) featured = products;
    featured = featured.slice(0, 6);

    grid.innerHTML = featured.map((p, idx) => `
        <div class="product-card scroll-reveal" data-category="${p.category}" style="--delay: ${0.1 + idx * 0.1}s">
            ${p.old_price > 0 ? `<div class="product-badge">Best Seller</div>` : ''}
            <div class="product-image"><img src="${p.image}" alt="${p.name}" onerror="this.onerror=null;this.src=PLACEHOLDER_IMG;"></div>
            <div class="product-info">
                <h3>${p.name}</h3>
                <p>${p.short_desc}</p>
                <div class="product-meta">
                    <span class="product-price">
                        ₹${p.price}
                        ${p.old_price > 0 ? `<span class="old-price">₹${p.old_price}</span>` : ''}
                    </span>
                    <button class="btn btn-sm add-cart-btn" onclick="addToCart('${p.id}')">
                        <i class="fas fa-cart-plus"></i> Add
                    </button>
                </div>
                <a href="pages/product.html?id=${p.id}" class="view-link">View Details →</a>
            </div>
        </div>
    `).join('');

    // Re-trigger scroll reveal
    document.querySelectorAll('#productsGrid .scroll-reveal').forEach(el => {
        if (window.revealObserver) {
            window.revealObserver.observe(el);
        } else {
            // No observer available - reveal immediately so cards aren't hidden
            el.classList.add('revealed');
        }
    });
}

/**
 * Add a product to the cart by id. Looks up price from catalog/API.
 */
function addToCart(id) {
    const p = CATALOG.find(x => x.id === id);
    if (!p) return;
    Cart.add(p, 1);
    showCartFeedback(p.name);
}

function showCartFeedback(name) {
    if (window.showNotification) {
        window.showNotification(name + ' added to cart!', 'success');
    } else {
        const el = document.createElement('div');
        el.className = 'notification';
        el.style.cssText = 'position:fixed;top:100px;right:20px;padding:14px 22px;background:#22c55e;color:#fff;border-radius:12px;z-index:10000;box-shadow:0 10px 30px rgba(0,0,0,0.2);animation:slideInR .5s ease,slideOutR .5s ease 2s forwards;font-family:Poppins,sans-serif;';
        el.innerHTML = '<i class="fas fa-check-circle"></i> ' + name + ' added to cart!';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2500);
    }
}

// Auto render on load
document.addEventListener('DOMContentLoaded', () => {
    renderProducts();
});
