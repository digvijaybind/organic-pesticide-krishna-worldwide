# Organic Pesticide - Krishna Worldwide
## E-Commerce Website with Paytm Payment Gateway

Complete online store for natural pesticides & bio-gas fertilizers, serving farmers, orchard caretakers, nurseries and garden caretakers across Maharashtra, Gujarat & Madhya Pradesh.

---

## 📁 Project Structure

```
organic-pesticide-krishna-worldwide/
├── index.html            # Homepage (hero carousel + featured products)
├── cart.html             # Shopping cart
├── checkout.html         # Checkout + payment
├── thankyou.html         # Order confirmation
├── css/
│   ├── styles.css        # Main styles + carousel + product cards
│   ├── cart.css          # Cart & checkout styles
│   └── animations.css    # Animations
├── js/
│   ├── main.js           # Navbar, scroll, testimonials, language toggle
│   ├── cart.js           # Cart management (localStorage)
│   ├── catalog.js        # Product catalog + API fallback
│   └── carousel.js       # Hero carousel
├── images/               # Product images
├── pages/
│   ├── product.html      # Product detail (dynamic by ?id=)
│   └── all-products.html # Full catalog with filters
└── backend/
    ├── config/
    │   ├── config.php    # ⚠️ CONFIG - KEYS LIVE HERE (SECURE!)
    │   └── products.php  # Product database
    ├── api/
    │   ├── products.php  # GET product list
    │   └── order.php     # POST create order
    ├── paytm/
    │   ├── Paytm.class.php       # Server-side Paytm lib
    │   ├── initiate.php          # Get transaction token
    │   └── paytm-response.php    # Payment callback
    ├── orders/          # Saved orders (JSON) - protected
    ├── logs/            # Error logs - protected
    └── .htaccess        # Blocks web access to sensitive dirs
```

---

## 🚀 Deployment Instructions

### Prerequisite: PHP hosting
Your hosting MUST support **PHP** (most cPanel/shared hosts do). The site also works partly without PHP (static catalog fallback), but to process orders and payments you need PHP.

### Steps

1. **Upload** the entire `organic-pesticide-krishna-worldwide` folder to your hosting
   - e.g. into `public_html/organicpesticide/` (or the subdomain folder)
2. **Verify write permissions**: The `backend/orders` and `backend/logs` folders need to be writable by PHP.
3. **Set your domain/callback URL** in `backend/config/config.php`

---

## 💳 Paytm Payment Gateway Setup

### Secure credential handling

**IMPORTANT SECURITY:** Your Paytm merchant keys must ONLY exist on the server (in `backend/config/config.php`), **never** in HTML/JS/CSS files. This is why the front-end never contains keys.

### To activate payments:

1. **Get Paytm credentials** from the Paytm Business dashboard (https://business.paytm.com)
2. **Edit** `backend/config/config.php` and set:
   ```php
   define('PAYTM_ENV', 'TEST');   // or 'PROD'
   define('PAYTM_MERCHANT_ID', '<your id>');
   define('PAYTM_MERCHANT_KEY', '<your key>');
   define('PAYTM_WEBSITE', 'WEBSTAGING');  // 'DEFAULT' for prod
   define('PAYTM_CALLBACK_URL', 'https://yourdomain.com/backend/paytm/paytm-response.php');
   ```

3. **For production:**
   - Set `PAYTM_ENV` to `'PROD'`
   - Update `PAYTM_TRANSACTION_URL` and `PAYTM_INITIATE_URL` to securegw.paytm.in (already defaults to PROD in the code)
   - Update `PAYTM_CALLBACK_URL` to your real domain
   - **Move the config file OUTSIDE public_html** if possible for maximum security

### Test mode
- Keep `PAYTM_ENV = 'TEST'` while developing
- Put your **TEST** merchant credentials in the TEST block
- Test payments with Paytm's sandbox

---

## 🛒 How the store works

1. Customer browses products (index or all-products)
2. Clicks "Add" → item stored in browser `localStorage` (via `js/cart.js`)
3. Cart page (`cart.html`) shows items + totals
4. Checkout (`checkout.html`) collects delivery details
5. Order is sent to `backend/api/order.php` (server validates & calculates totals)
6. Payment: Paytm (UPI/NetBanking/Cards) or Cash on Delivery
7. On success → redirected to `thankyou.html`

**No database required** — orders are stored as JSON files in `backend/orders/`. For high volume, migrate to MySQL (update `backend/api/order.php`).

---

## ⚙️ Customization

- **Products**: edit `backend/config/products.php` (server) and `js/catalog.js` (front-end fallback)
- **Prices/Shipping**: edit `FREE_SHIPPING_THRESHOLD` and `SHIPPING_FEE` in `backend/config/config.php` (and `js/cart.js` for the front-end calc)
- **Phone/WhatsApp**: search & replace `919876543210` throughout
- **Colors** (leaf green theme): edit `:root` variables in `css/styles.css`

---

## 🔒 Security Notes

- `backend/.htaccess` blocks browser access to `config/`, `orders/`, `logs/`
- Merchant keys NEVER in front-end code
- Order totals are re-calculated server-side (client cannot fake prices)
- Change your hosting & Paytm credentials periodically

---

## ✅ Status

- [x] E-commerce storefront (products, cart, checkout)
- [x] Paytm server-side integration template (TEST mode)
- [x] Hero carousel (5 slides)
- [x] 9 products with real inventory images
- [x] Backend API (products + orders)
- [x] Order storage (JSON) & logging
