# Turtle Boss — Ecommerce Platform

Custom Laravel 13 / PHP 8.3 ecommerce platform (storefront + admin dashboard) for **Turtle Boss — "Everything Is Possible."**
Stack: Laravel · MySQL/MariaDB · Blade · Tailwind CSS 4 · Alpine.js · Vite. No page-builder or ecommerce plugins.

## 1. Install

```bash
composer install --no-dev --optimize-autoloader   # (omit --no-dev for development/tests)
npm ci && npm run build                           # compiles Tailwind + Alpine into public/build
cp .env.example .env && php artisan key:generate
# edit .env: APP_URL, DB_* (MySQL 8 / MariaDB 10.6+), MAIL_*, ADMIN_EMAIL / ADMIN_PASSWORD
php artisan migrate --force
php artisan db:seed --force          # roles, Super Admin, 7 categories, menus, pages, homepage layout, settings (NO products)
php artisan storage:link
```

The seeder prints the Super Admin credentials (uses `ADMIN_EMAIL` / `ADMIN_PASSWORD`, or generates a password). **Change it after first login.**
Admin: `/admin` · Storefront: `/`.

Optional demo data (clearly flagged `DEMO`, removable): `php artisan db:seed --class=DemoSeeder` → remove with `php artisan turtleboss:remove-demo`.

**Production checklist**: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, point the web root at `public/`, `php artisan config:cache route:cache view:cache`,
cron `* * * * * php /path/artisan schedule:run` (runs the daily DB backup at 03:00), `SESSION_SECURE_COOKIE=true`.
`public/.htaccess` already ships gzip + 1-year asset caching (Nginx: replicate with `expires`/`gzip`).

## 2. Brand assets (action needed)

* **Logo** — the official logo file was not available to the build, so a plain text wordmark is shown as a stand-in. Upload the original in
  *Admin → General Settings → Logo* (or drop it at `public/images/logo.png`/`.svg`). It is used in the header, footer, login and invoices **unmodified**.
* Category images, slider images and banners are uploaded from the admin (placeholders show until then).

## 3. Admin map

Dashboard · Products (variations, images, specs, related/cross-sell/upsell, SEO, duplicate, bulk edit/delete/price/category, CSV import/export) · Categories (nested, SEO) · Product Attributes ·
Orders (status, notes, tracking, refunds, print + PDF invoice) · Customers (+CSV) · Inventory (adjust + history) · Coupons · Reviews (approval) · Wishlist analytics ·
Homepage Builder (enable/disable, reorder, edit every section) · Sliders (scheduled, desktop + mobile images) · Menus (nested, mega menu) · Pages · Blog (posts, categories, tags, scheduling) ·
SEO (global, redirects, sitemap, robots) · Media Library · Shipping (flat/free-threshold/city rules) · Payment · Email · WhatsApp · General · Users & Roles · Reports · Backup & Maintenance.

Roles (permissions in `config/permissions.php`): **Super Admin**, **Store Manager**, **Product Manager**, **Order Manager**, **Content Manager**.

## 4. Integrations (nothing pretends to work without credentials)

| Integration | State | To enable |
|---|---|---|
| Email (order confirmation/status) | Implemented via Laravel Mail; every attempt logged on the order | Set `MAIL_MAILER=smtp` + `MAIL_*` in `.env`. With the default `log` mailer, emails are recorded as *skipped*. |
| WhatsApp | Implemented against Meta WhatsApp Cloud API; logged as sent/failed/skipped | Admin → WhatsApp Settings: Phone-number ID + permanent token. Business-initiated messages outside 24 h need approved templates. |
| Online payment gateway | **Architecture only** (order/payment records, `gateway` method, settings fields). No provider adapter is written. | Implement an adapter for your provider (PayFast, Safepay, Stripe…) and test with real credentials. The option stays hidden at checkout until a key is saved. |
| Google Analytics / Search Console | Ready | Paste GA4 ID / verification code in Global SEO. Sitemap: `/sitemap.xml`. |

## 5. Security & SEO notes

CSRF on all forms/AJAX, rate limiting (login, register, cart, coupon, checkout, reviews, contact), bcrypt hashing, role middleware on every admin route, server-side validation,
server-side re-pricing / stock / coupon checks on every cart read and at checkout, allow-list HTML sanitiser for rich text, uploaded images re-encoded (MIME verified, resized, WebP) and SVGs sanitised,
CSV formula-injection guard, security headers, `noindex` on admin/cart/checkout/account/filtered listings.
One canonical + one title per page; Product, BreadcrumbList, Organization, WebSite, BlogPosting JSON-LD; Open Graph / Twitter cards; XML sitemap; managed robots.txt; 301/302 redirect manager (applied on 404s); rel=prev/next.

## 6. Tests

```bash
php artisan test        # 35 feature tests (SQLite in memory)
DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_DATABASE=turtleboss_test DB_USERNAME=… DB_PASSWORD=… php artisan test   # same suite on MySQL/MariaDB
```
Covers pages/SEO tags, variants & stock, cart maths, coupons, shipping, checkout, order creation, cancel/refund restock, cart merge on login, wishlist, reviews (verified flag), search, filters, redirects,
role permissions on every admin module, product CRUD/duplicate/bulk, CSV, sanitisation, image upload, invoices/PDF, dashboard numbers.

## 7. Known limits / honest status

* No real-device or Lighthouse/PageSpeed run was possible here; mobile was verified with Playwright device emulation (no horizontal scroll on any page, bottom-nav present, no JS errors). Measure PageSpeed on your live domain.
* The reference product-page screenshot was not attached; the product page follows the written spec (vertical thumbnails, zoom, lightbox, variants, sticky mobile add-to-cart).
* Cross-sell products can be assigned but are not yet surfaced in the cart UI (upsells show on the product page).
* Product videos are stored as a link. Image ordering uses order numbers (no drag-and-drop). The rich-text editor is a lightweight built-in one.
* The spec's separate `inventory` table is represented by stock columns on products/variants plus `inventory_logs`.
* Refunds are recorded in the system; money is not moved automatically.
