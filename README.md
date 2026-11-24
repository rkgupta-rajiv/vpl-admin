# VPL Registration – Laravel API + Main Admin Panel

## Requirements
PHP 8.2+ (extensions: pdo_mysql, mbstring, fileinfo, openssl), Composer 2, MySQL 8 / MariaDB 10.6+.

## Install
```bash
composer install
cp .env.example .env
php artisan key:generate
# edit .env → DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL, MAIL_* (for OTP emails)
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```
Optional demo data (12 players, 10 registrations): `php artisan db:seed --class=DemoPlayersSeeder`

**Main Admin login:** `/admin` → `admin@vpl.local` / `Admin@12345` (set `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env` before seeding, and change the password under Settings).
Forgot admin password: `php artisan vpl:admin admin@vpl.local --password="New@12345"`

## Production checklist
- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS only, `APP_URL=https://your-domain`.
- Configure `MAIL_*` (SMTP) so players receive password-reset OTPs and approval emails.
- `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- Make sure `storage/app/private` is **not** web-accessible (it isn't by default — only `storage/app/public` is linked).
- Back up the database **and** `storage/app/private` (documents + payment screenshots).

## Structure
```
app/
  Http/Controllers/Api/      Player API (Auth, Player, Registration/Payment, Notification)
  Http/Controllers/Admin/    Admin panel (Auth, Dashboard, Player, Payment, Tournament, System)
  Http/Middleware/           EnsureRole (RBAC), ForceJsonResponse
  Models/                    User, Player, PlayerDocument, Tournament, PaymentSetting,
                             Registration, Payment, StatusHistory, ActivityLog, Setting
  Services/RegistrationWorkflow.php   ← all status transitions (single source of truth)
  Services/FileStorage.php            ← private document / screenshot storage
  Support/                   PlayerPresenter (API JSON), PlayerRules (validation), CsvExport, AdminContext
database/migrations/         Full schema
database/seeders/            Main Admin + VPL Season 2; DemoPlayersSeeder
resources/views/admin/       Blade admin panel (sidebar layout)
routes/api.php  routes/web.php  routes/console.php (vpl:admin command)
public/css/admin.css         Pre-built Tailwind CSS (no CDN needed)
```

## Database (main tables)
| Table | Purpose |
|---|---|
| users | login accounts; `role` = `super_admin` / `player`; unique email + mobile |
| players | player profile (personal + cricket info), 1:1 with user |
| player_documents | photo / aadhaar_front / aadhaar_back, verification status + remarks |
| tournaments | name, season, code (Registration ID prefix), dates, open/closed, rules, instructions, active flag |
| payment_settings | per tournament: fee, UPI ID, payee, QR image, instructions, online on/off |
| registrations | player × tournament: jersey, status (pending/approved/rejected), payment_status, **locked fee**, registration_no |
| payments | every proof attempt: amount, UTR, date, screenshot, status, admin remarks, verifier (gateway fields reserved for Razorpay) |
| status_histories | registration / payment / document status timeline |
| activity_logs | admin actions (approvals, exports, document views, settings) |
| notifications | in-app player notifications |

## Statuses
- Registration: `pending` → `approved` / `rejected` (player can fix & resubmit).
- Payment: `payment_pending` → `proof_submitted` → `under_verification` (when admin opens it) → `payment_approved` / `payment_rejected` (player can submit again).
- `payment_approved` ⇒ registration `approved` + `completed_at` + Registration ID `{CODE}-0001`.

## Adding Razorpay later
`payments` already has `method`, `gateway`, `gateway_reference`. Add a webhook controller that creates a `Payment` with `method=gateway` and calls `RegistrationWorkflow::completeRegistration()` after signature verification — no other changes needed.

## Admin CSS
The admin panel uses a pre-compiled Tailwind file. If you change Blade classes: `npm install && npm run build:admin-css`.
