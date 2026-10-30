# Ndanda Tributes

A digital funeral platform by Ndanda Events. It has tribute pages, interactive funeral programmes with GPS directions, QR codes, printable memorials, QR plaques, a free preparations workspace, M-Pesa (Daraja) payments and a full admin CMS.

**Stack:** vanilla PHP 7.4+ and MySQL 5.7+/MariaDB 10.3+. Bootstrap 5, Leaflet, Quill, Chart.js, qrcodejs and GLightbox all load from CDNs, so there is no build step.

## Install (XAMPP / cPanel)

1. Upload the folder (for example to `public_html/tributes`) and create an empty MySQL database.
2. Edit `config.php`: set the DB credentials and `BASE_URL` (no trailing slash).
3. Open `BASE_URL/install.php`. Create the admin account and choose whether to load the sample data.
4. Log in at `/login.php`. Admins land in `/admin`.

Sample planner login: `planner@test.com` / `Test@1234`

Requirements: the PHP extensions `pdo_mysql`, `curl`, `mbstring`, `fileinfo` and `iconv`, plus Apache `mod_rewrite` for the short links `/t/…`, `/p/…` and `/f/…`. If short links return 404, turn them off in Admin → Settings → System.

## Main areas

| Area | Path |
|---|---|
| Public site (Home, How it works, Pricing, Obituaries, Live stream, Photography, About, Contact, Blog, FAQ) | `/` |
| Create funeral account → checkout (STK push) | `create.php` → `checkout.php` |
| Tribute page / Programme / Family tribute form | `/t/{slug}`, `/p/{slug}`, `/f/{token}` |
| Planner dashboard | `/account/` |
| Print designs (memorial card, poster, programme booklet, QR plaque) | `print.php` |
| Admin | `/admin/` |
| **Go live / clean test data** | `/admin/go-live.php` |

## Payments pipeline (Daraja)

- **STK push:** `api/stk-push.php` sends the prompt. Safaricom then posts the result to `api/mpesa-callback.php?key=SECRET`.
- **Polling fallback:** `api/payment-status.php` uses STK Query when the callback is late, so payments never get stuck.
- **Paybill (C2B):** customers can pay manually using the order number as the account number. `api/c2b-confirmation.php` matches the payment to the order automatically. Payments it cannot match show up in Admin → Transactions for one-click reconciliation.
- **Activation:** a paid order (`mark_order_paid()`) unlocks the services it bought and activates the funeral account. Running it twice is safe.
- **Logging:** every request and callback is recorded in Admin → Transactions → API logs.
- **Testing without keys:** while in test mode, "Simulate successful payment" on the checkout page runs the full flow.

### Going live with M-Pesa

1. Admin → Settings → M-Pesa: paste the live consumer key/secret, shortcode and passkey, then set the environment to **Live**.
2. The callback base URL must be **HTTPS**. For local testing, use an ngrok URL.
3. Click **Test connection**, then **Register C2B URLs**, then send a KES 1 test STK push.
4. Admin → Go live: type `GO LIVE` and enter your password. This removes all test-flagged data and turns off test mode and simulation.

## Test data and Go Live

While **test mode** is on, every user, funeral, order, transaction, tribute, message and post created is flagged `is_test = 1`. Go Live deletes all of it, along with uploaded files. **Full reset** truncates every funeral, payment and planner user, including real ones. Settings, services and prices, CMS pages and admin users are always kept.

## Security notes

- Every form uses CSRF tokens and every query is a prepared statement. Passwords are hashed with `password_hash`.
- Uploads are checked by MIME type and given random names, and PHP cannot execute inside `/uploads`.
- Preparation documents are stored in `uploads/private` (no direct web access) and served only to their owner through `account/download.php`.
- M-Pesa callbacks require the secret `?key=`.
- Before launch, set `APP_DEBUG` to `false`.
