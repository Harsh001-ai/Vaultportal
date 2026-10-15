# Workspace — Secure Asset Collection & Delivery Portal

Workspace is a secure, enterprise-grade digital asset collection, organization, and delivery system built with a PHP 8.2+ MVC architecture, MariaDB/MySQL relational backend, UI/UX Pro Max design system tokens, and comprehensive item-level audit trails.

---

## 1. Key Architectural Features

- **Isolated Physical Storage Layer**: Uploaded assets are never stored in the public web root. All files reside in `storage/uploads/` with cryptographically randomized filesystem names (`sha256(time . random_bytes) . ext`), rendering direct web server file execution impossible.
- **Spec-First Validation & Anti-Malware Quarantine**:
  - Whitelist validation of extensions and server-side magic byte MIME inspection (`finfo_file`).
  - Automatic rejection of dangerous extensions (`.php`, `.exe`, `.bat`, `.cmd`, `.sh`, `.vbs`, etc.).
  - Heuristic analysis detects embedded executable PE headers and script tags, safely flagging suspicious assets as `quarantined` without accepting active code.
- **Intelligent Download & Export Center (Core Feature)**:
  - Multi-user selection scope analysis (uncompressed size, source user count, file type breakdown, conflict detection).
  - Genuine, distinct export formats:
    1. **ZIP Organized by Source User** (`user_{id}_{username}/{category}/{filename}`).
    2. **Combined ZIP** (Assets + root `manifest.json` and `manifest.csv`).
    3. **ZIP Organized by File Type** (Images, Documents, Spreadsheets, Presentations, Archives).
    4. **ZIP Organized by Collection** (Preserving departmental folder hierarchy).
    5. **Flat ZIP Archive** (Collision-safe filename numbering `name (1).ext`).
    6. **Audit Manifest Only (CSV)** (Spreadsheet with hashes, uploader identities, sizes).
    7. **Audit Manifest Only (JSON)** (Structured machine-readable metadata).
- **Zip-Slip & Path Traversal Prevention**:
  - Internal archive paths are normalized, null bytes stripped, leading/trailing slashes removed, and directory traversal sequences (`..`) strictly removed.
- **Immutable Download History & Audit Log**:
  - Every administrative export and file download records the administrator ID, admin name, target package, source users, export format, file count, archive size, IP address, user agent, and timestamp.
  - One-click CSV export of audit trails for compliance.
- **Asynchronous Background Processing Worker**:
  - CLI worker (`worker.php`) handles background packaging and automated expiration cleanup of temporary archives (`token_expires_at`).

---

## 2. Technology Stack

- **Backend**: PHP 8.2 (with PDO, OpenSSL, ZipArchive, Fileinfo, Mbstring, cURL, Hash).
- **Database**: MariaDB 10.4.32 / MySQL 8.0+ (InnoDB, UTF-8 MB4 Unicode).
- **Frontend**: HTML5, Modern CSS3 with UI/UX Pro Max design tokens, Lucide SVG icons (no generic emojis, no purple-blue gradients), vanilla JS modular upload and export wizard controllers.
- **Local Web Server**: PHP Built-in Server / Apache on Windows (`localhost:8000`).

---

## 3. Directory Layout

```text
d:/Vaultportal/
├── app/
│   ├── Controllers/       (AuthController, AssetController, ExportController, etc.)
│   ├── Helpers/           (Icon helper for Lucide SVG icons)
│   ├── Middleware/        (AuthMiddleware, RoleMiddleware, CsrfMiddleware, RateLimitMiddleware)
│   ├── Models/            (Data models)
│   ├── Services/          (AuthService, FileValidationService, StorageService, ArchiveService, etc.)
│   └── Views/             (Blade-style PHP views, layouts, modals)
├── config/                (database.php, app.php, mimetypes.php)
├── database/
│   ├── schema.sql         (Relational database schema with 10 tables)
│   └── seed.php           (Realistic seed data with actual binary assets)
├── public/
│   ├── index.php          (Front controller & REST router)
│   └── assets/            (CSS tokens, JS modules, icons)
├── storage/
│   ├── uploads/           (Secure storage isolated from web root)
│   ├── exports/           (Generated ZIP archives and manifests)
│   └── temporary/         (Staging buffers)
├── tests/
│   └── run_all.php        (Automated test suite with 33 test cases)
├── worker.php             (Background CLI worker for packaging & cleanup)
└── README.md
```

---

## 4. Setup and Run Instructions

### A. Prerequisites
- PHP 8.2+ with `pdo_mysql`, `zip`, `fileinfo`, `openssl` extensions enabled (e.g. via XAMPP in `C:\xampp\php`).
- MySQL 8.0+ or MariaDB 10.4+ running on port 3306.

### B. Database Migration & Seeding
Run the database schema migration and seed script:
```powershell
# 1. Apply Schema
cmd.exe /c "C:\xampp\mysql\bin\mysql.exe -u root secure_asset_portal < d:\Vaultportal\database\schema.sql"

# 2. Seed Initial Users, Collections, and Binary Assets
& "C:\xampp\php\php.exe" "d:\Vaultportal\database\seed.php"
```

### C. Start the Web Server
Launch the development server on port 8000:
```powershell
& "C:\xampp\php\php.exe" -S localhost:8000 -t public public/index.php
```
Access the application at [http://localhost:8000](http://localhost:8000).

### D. Run the Background Worker
For asynchronous export packaging and expired archive cleanup:
```powershell
# Single-pass execution:
& "C:\xampp\php\php.exe" "d:\Vaultportal\worker.php"

# Long-running daemon mode:
& "C:\xampp\php\php.exe" "d:\Vaultportal\worker.php" --daemon
```

---

## 5. Pre-Configured Test Credentials

| Role | Email | Password | Purpose |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@vaultportal.com`<br>*(alias: `admin@portal.local`)* | `Admin@2026#Secure` | Full platform access, selection manager, export center, audit log |
| **Client User 1** | `sarah@marketingagency.com` | `User@123456!` | Standard client user, marketing assets, quarantined test file |
| **Client User 2** | `alex@designstudio.io` | `User@123456!` | Creative lead, PDF brand guidelines, vector logos |
| **Client User 3** | `michael@financegroup.com` | `User@123456!` | CFO, financial budget CSV spreadsheets |

---

## 6. Running the Automated Test Suite

To run all 33 integration and security tests against the live database and storage layer:
```powershell
& "C:\xampp\php\php.exe" "d:\Vaultportal\tests\run_all.php"
```

**Verified Test Categories:**
- **Authentication & Roles**: Admin login, invalid password rejection, non-existent user protection, session fixation mitigation.
- **Upload Validation & Quarantine**: Blacklisted `.php`/`.exe` extension blocking, valid PNG MIME verification, storage isolation outside web root, randomized disk naming, embedded script heuristic quarantine, user storage quota enforcement.
- **Authorization & Access Control**: Cross-user asset download barrier (User B cannot download User A's assets), admin endpoint barrier for standard users.
- **Intelligent Export Center**: Selection review analysis, ZIP by User, Combined ZIP with manifests, Standalone CSV manifest, Standalone JSON manifest, ZIP by Type, ZIP-slip defense (`../../` neutralization).
- **Audit Trail & Download Events**: Granular `export_items` recording with source user IDs and SHA-256 checksums, `download_events` recording, admin query filtering, expired download token rejection.

---

## 7. Security Model & Production Readiness

- **CSRF Defense**: Cryptographic CSRF tokens (`Session::getCsrfToken()`) verified on all POST/PUT/DELETE requests.
- **Rate Limiting**: Sliding window rate limiter in MariaDB table `rate_limits` protecting `/login` (max 5 attempts per 15 minutes) and sensitive endpoints.
- **Prepared Statements**: All database operations utilize parameterized queries through PDO, preventing SQL injection.
- **Session Security**: `HttpOnly`, `SameSite=Lax`, strict mode enabled, session regenerated upon privilege elevation.
