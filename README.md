# QR Code Inventory Management System

Production-ready Laravel 12 inventory system with QR generation, camera scanning, stock movements, borrowing, transfers, reports, audit logs, and role-based access control.

## Requirements

- PHP 8.2+ with extensions: `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`
- Composer
- Optional: Imagick (for PNG QR downloads; SVG works without it)

## Quick start

```bash
cd c:\xampp\htdocs\inventory_system
composer install
copy .env.example .env   # if needed
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000

### Default users

| Role    | Email                     | Password |
|---------|---------------------------|----------|
| Admin   | admin@inventory.local     | password |
| Staff   | staff@inventory.local     | password |
| Viewer  | viewer@inventory.local    | password |

## Features

- Inventory registration with unique item codes and QR identifiers (`INV-YYYY-######`)
- QR generate / download / print / batch label layouts (1, 2, 4, 8, sticker)
- Mobile-friendly camera scanner + manual code entry + USB scanner support
- Stock-in / stock-out with quantity guards
- Borrow / return with overdue awareness
- Location / department / custodian transfers
- Condition tracking and immutable history
- Low-stock alerts and in-app notifications
- Dashboard analytics (Chart.js)
- Reports: PDF, Excel, CSV, print
- CSV/Excel import with preview and validation
- Database backup / restore (SQLite)
- Audit logs and session timeout
- Roles: Administrator, Inventory Staff, Viewer

## Database

Default configuration uses **SQLite** (`database/database.sqlite`).

To use MySQL, update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then run:

```bash
php artisan migrate --seed
```

## Timezone

Application timezone is **Asia/Manila** (`APP_TIMEZONE` / `config/app.php`).

## Security notes

- Passwords hashed with bcrypt
- CSRF protection on all state-changing forms
- Eloquent / query builder (parameterized queries)
- Blade escaping for XSS protection
- Server-side role middleware (`auth`, `active`, `role:admin,staff`)
- Session lifetime / idle timeout via settings
- Soft archive for inventory (no casual hard deletes)

## Key routes

| Path | Purpose |
|------|---------|
| `/login` | Authentication |
| `/` | Dashboard |
| `/inventory` | Inventory list (AJAX search) |
| `/scan` | QR scanner |
| `/qr/batch` | Batch QR labels |
| `/reports` | Report center |
| `/settings` | System settings (admin) |
| `/backups` | Backup & restore (admin) |
| `/i/{qr_code}` | Public QR deep-link (requires login) |

## Extensibility

Domain services live under `app/Services/` (`InventoryService`, `QrCodeService`, `AuditService`, `NotificationService`, `BackupService`) so procurement, asset depreciation, maintenance, and disposal modules can be added without redesigning the core inventory flow.
