# Organic Pesticide - Krishna Worldwide

E-commerce website for natural pesticides, bio-gas fertilizers and organic inputs. Serving farmers, orchard caretakers, nurseries and garden caretakers across Maharashtra, Gujarat & Madhya Pradesh. English + Hindi toggle.

**Payment gateways:** Razorpay (primary) + Paytm (fallback) + Cashfree (additional) + Cash on Delivery.
**Logistics:** Full Shiprocket integration — create shipment, generate shipping label (AWB), real-time tracking + an admin shipping manager.

---

## Project Structure

```
organic-pesticide-krishna-worldwide/
├── index.html            # Homepage (hero carousel + featured products)
├── cart.html             # Shopping cart
├── checkout.html         # Checkout + payment
├── thankyou.html         # Order confirmation
├── privacy-policy.html   # Legal: Privacy Policy
├── refund-policy.html    # Legal: Refund & Cancellation Policy
├── terms-conditions.html # Legal: Terms & Conditions
├── css/
│   ├── styles.css        # Main styles + carousel + product cards
│   ├── cart.css          # Cart & checkout styles
│   └── animations.css    # Animations
├── js/
│   ├── main.js           # Navbar, scroll, testimonials, language toggle
│   ├── cart.js           # Cart management (localStorage)
│   ├── catalog.js        # Product catalog + API fallback
│   └── carousel.js       # Hero carousel (2s autoplay)
├── images/               # 9 branded product images generated via PHP GD (all uniform 800x800,
│                         #   incl. GrowthVita.png for growth-promoter)
├── pages/
│   ├── product.html      # Product detail (dynamic by ?id=)
│   └── all-products.html # Full catalog with filters + sorting (URL persistable)
│   + 6 category pages    # pesticides, biogas-fertilizer, soil-health,
│                         # growth-promoters, seeds, micro-nutrition
└── backend/
    ├── config/
    │   ├── config.php    # SECURE — keys via env vars / .env (never committed)
    │   └── products.php  # Product database (server-authoritative prices)
    ├── api/
    │   ├── products.php  # GET product list
    │   └── order.php     # POST create order (server-side totals, CSRF/origin guard)
    ├── razorpay/         # createOrder.php, verify.php, razorpay-curl.php
    ├── paytm/            # initiate.php, paytm-response.php, Paytm.class.php
    ├── cashfree/         # createOrder.php, verify.php, cashfree-curl.php
    ├── shiprocket/       # createShipment.php, label.php, track.php,
    │                     #   shiprocket-curl.php, shiprocket-helper.php, admin.php
    ├── orders/          # Saved orders (JSON) - protected
    ├── logs/            # Error logs - protected
    └── .htaccess        # Blocks web access to config/, orders/, logs/, libs
.env.example             # Template for gateway/logistics credentials (no real secrets)
```

---

## Security (READ FIRST)

- **Live Paytm / Razorpay / Cashfree / Shiprocket credentials must NEVER be committed to the repo or sent in chat.**
- `backend/config/config.php` reads credentials from **environment variables** (`getenv()`).
  It also auto-loads an optional `.env` file placed **outside the web root** (see below).
  No real keys are stored in any tracked file. The repo is clean.
- **Fail-fast guards**: if a gateway is switched to live/prod without real credentials,
  the app dies with a 500 instead of processing payments with bogus keys.
- `backend/.htaccess` and root `.htaccess` block browser access to `.env`, `config/`,
  `orders/`, `logs/` and internal library files.
- Order **totals are always recalculated server-side**; the client can never fake prices.
- Endpoints validate/whitelist order IDs (regex `^OP\d{17}$`) to prevent path traversal.
- Same-origin CSRF guard on `order.php`, `initiate.php` and gateway endpoints.
- Order files written with `chmod 0600`.

---

## Running Locally

Requires **PHP 8+** with `curl` and `openssl` extensions.

```powershell
cd "C:\Users\hp\Documents\Default Project\organic-pesticide-krishna-worldwide"
php -S localhost:8000
```

Open `http://localhost:8000`. This runs both the static site and the PHP backend
(products API, order creation, payment endpoints).

### Local payment keys (test mode only)

To test online payments locally, set **test** keys as environment variables before starting:

```powershell
$env:RAZORPAY_ENV      = "test"
$env:RAZORPAY_KEY_ID   = "rzp_test_xxxxxxxx"
$env:RAZORPAY_KEY_SECRET = "xxxxxxxx"
php -S localhost:8000
```

**Alternative — `.env` file (recommended):** create a file named `.env` one level ABOVE the
project root (outside the web root) and fill in the variable names from `.env.example` with your
test values. The backend auto-loads it. System env vars always override the .env file.

Without keys the store works fully except online payment (shows "select Cash on Delivery").

---

## cPanel Production Deployment Checklist

1. **Upload** the entire project folder into `public_html/<your-domain-or-subfolder>/`.
2. **Make writeable** (recommended: set permissions to `755` for `/backend/orders` and `/backend/logs`):
   - `backend/orders/`
   - `backend/logs/`
3. **Set credentials via cPanel → "Environment Variables"** (not in code):
   - `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_ENV=prod`
   - `PAYTM_MERCHANT_ID`, `PAYTM_MERCHANT_KEY`, `PAYTM_WEBSITE`, `PAYTM_ENV=prod` (fallback)
   - Optional: `APP_HOST` (your domain) to lock the origin guard.
4. **Verify `.htaccess` is active** (`Options -Indexes`, RewriteEngine). Confirm that
   visiting `yourdomain.com/backend/config/config.php`, `/backend/orders/`, `/backend/logs/`
   returns **403 Forbidden**, not a PHP/JSON dump. (PHP built-in server ignores `.htaccess`,
   so this only takes effect on Apache/cPanel — that is expected.)
5. **Set your real domain** as the payment callback URLs:
   - Razorpay: dashboard Webhook URL → `https://yourdomain.com/backend/razorpay/verify.php`
   - Paytm: `PAYTM_CALLBACK_URL` → `https://yourdomain.com/backend/paytm/paytm-response.php`
6. **Add `images/GrowthVita.png`** for the growth-promoter product (unique image; a branded
   placeholder shows until then).
7. (Recommended) Move `backend/config/config.php` **outside** `public_html` (e.g. one level up)
   and adjust the `require` path — strongest protection for credentials.
8. **Test a real order**: browse → cart → COD order (verify it saves to `orders/`) and one
   Razorpay test payment (verify `verify.php` flips `payment_status` to `PAID`).
9. **HTTPS**: enable SSL in cPanel (Let's Encrypt) so payment/sensitive pages run over HTTPS.

---

## 💳 Razorpay Review Readiness Report

Checklist for passing the Razorpay onboarding/technical review:

| Area | Status | Notes |
|------|--------|-------|
| Payment initiated server-side | ✅ | `createOrder.php` creates a Razorpay Order via cURL (never rely on client-only orders) |
| Payment verified server-side | ✅ | `verify.php` fetches the payment from Razorpay, checks status + amount, marks `PAID` |
| Amount never trusted from client | ✅ | totals recomputed in `order.php` using server product prices |
| Amounts in paise | ✅ | Razorpay expects small units; backend handles conversion |
| Order IDs unique & stable | ✅ | `OP` + 17-digit timestamp+suffix, reused for the Razorpay order |
| Test keys vs live keys separated | ✅ | via `RAZORPAY_ENV` env var |
| Refund policy present | ✅ | `refund-policy.html`, linked in every footer |
| Privacy policy present | ✅ | `privacy-policy.html`, linked in every footer |
| Terms & conditions present | ✅ | `terms-conditions.html`, linked in every footer |
| Contact details visible | ✅ | footer + WhatsApp widget on every page |
| HTTPS handling | ✅ | deferred to cPanel SSL; required before go-live |
| Webhook / callback URL | ⚠️ | configure real domain under Step 5 above |
| Live key secrecy | ✅ | env-var only, not in repo |

**Still to do before go-live (not code):**
- Add real production keys in cPanel env vars.
- Set the production webhook/callback URL to your live domain.
- Provide the live site URL, business details (PAN/bank), and agree to Razorpay terms in the dashboard.
- Enable HTTPS.
- Add `images/GrowthVita.png` for the growth-promoter product.

---

## How the store works

1. Customer browses products (index or all-products).
2. Clicks **Add** → item stored in browser `localStorage` (`js/cart.js`).
3. **Cart** (`cart.html`) shows items + totals.
4. **Checkout** (`checkout.html`) collects delivery details.
5. Order sent to `backend/api/order.php` (server validates, computes totals, stores order).
6. Payment: **Razorpay** (primary) / **Paytm** (fallback) / COD.
7. On success → redirected to `thankyou.html`.

**No database required** — orders are stored as JSON files in `backend/orders/`. For high
volume, migrate to MySQL (update `backend/api/order.php`).

---

## Customization

- **Products**: edit `backend/config/products.php` (server) + `js/catalog.js` (front-end fallback).
- **Prices/Shipping**: `FREE_SHIPPING_THRESHOLD` and `SHIPPING_FEE` in `backend/config/config.php`
  (and matching calc in `js/cart.js`).
- **Phone/WhatsApp**: search & replace `919876543210`.
- **Colors** (leaf-green theme): edit `:root` variables in `css/styles.css`.
- **New product images**: drop files into `images/` and update the `image:` field in `js/catalog.js`
  (all pages read from there; missing images show a branded placeholder).

---

## Status

- [x] Storefront (products, cart, checkout)
- [x] Razorpay primary + Paytm fallback + COD
- [x] Server-side order creation, verification, refund-ready
- [x] Security hardening (env keys, .htaccess, origin guard, server-side totals, path traversal)
- [x] Legal pages (Privacy, Refund, Terms) linked in all footers
- [x] Social links (Facebook/Instagram/LinkedIn/WhatsApp) in all footers
- [x] Consistent nav/cart/WhatsApp across all pages
- [x] 9 products (growth-promoter image pending: `images/GrowthVita.png`)
- [x] Backend API + JSON order storage + logging
