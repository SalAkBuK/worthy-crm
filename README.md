# Real Estate Agent Performance Check System (PHP 8 + MySQL)

A ready-to-run web app with 3 roles:

- **Admin** (Lead Entry): `kiran / admin1234`
- **CEO** (Performance Dashboard): `abbas / ceo1234`
- **Agents** (Follow-ups): `afi, altaleb, aurangzeb, benyam, mohamed, raunak, sanaya / agent123`

> Uses your existing DB `wortuckd_attendance` and your existing `employees` table **without modifying it**.

---

## Requirements

- PHP **8.0+** (tested on PHP 8.2)
- MySQL 5.7+ / MariaDB 10.4+ (JSON supported recommended)
- Apache (XAMPP/WAMP/LAMP)
- (Optional) Node.js if you want to rebuild TypeScript (already compiled JS included)

---

## Quick Setup (XAMPP / WAMP)

### 1) Put project in your web root
Example:
- `C:\xampp\htdocs\agent_performance_app\`

### 2) Set Apache DocumentRoot to `/public`
Recommended:
- Open this URL in browser:
  - `http://localhost/agent_performance_app/public/`

(Alternative) Create an Apache VirtualHost pointing to the `/public` folder.

### 3) Configure DB credentials
Copy `.env.example` to `.env` and set:
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

Default dummy values (as you provided) already exist in `.env.example` and `config/database.php`.

### 4) Import SQL
In phpMyAdmin (database: `wortuckd_attendance`):
1. Import `database/schema.sql`
2. Import `database/seed.sql`

Then open:
- `http://localhost/agent_performance_app/public/login`

### 5) Folder permissions
Make sure these are writable by PHP:
- `public/uploads/`
- `storage/logs/`

---

## How it works

### Admin (Kiran)
- Bulk add leads in a table (add/remove rows)
- Save all rows in **one transaction** (rollback if any row invalid)
- Search/filter/sort/paginate leads
- View lead details + followups (read-only)

### Agent
- Sees ONLY assigned leads
- Adds follow-ups with:
  - Required contact datetime
  - Required screenshot (call proof)
  - Required call status + interested status
  - Notes minimum **50** characters (live counter)
  - WhatsApp checkbox makes WhatsApp screenshot mandatory
- Attempts are sequential (attempt #3 requires #1 and #2 first)
- Lead status auto updates:
  - `NEW` (0 followups)
  - `IN_PROGRESS` (1–2 followups)
  - `CLOSED` (3+ followups)

### CEO (Abbas)
- Date-filtered dashboard with KPIs and Chart.js charts
- Agent detail performance page + export CSV

---

## TypeScript (Optional build)

Compiled JS is already included in:
- `public/assets/js/*.js`

If you want to recompile:
```bash
npm install
npm run build
```

---

## UI Theme (Lahomes)

Use the Lahomes admin template as the UI source of truth.

- Source theme files: `Lahomes_v1.0/Admin/dist`
- App theme assets: `public/assets/lahomes/`
- App layout partials: `app/Views/layouts/lahomes/`

When building new modules, pick the closest Lahomes page and adapt its structure/classes to the PHP view. Avoid introducing a new design system or standalone styling.

---

## Security Notes

- PDO prepared statements everywhere
- CSRF tokens for all POST forms
- Session-based auth + role checks + 403 pages
- Basic brute-force lockout: 5 failures → 10 minutes lock (per username + IP)
- File uploads validated (mime/type/size), random filenames
- Output escaped in views to prevent XSS
- App logs: `storage/logs/app.log`

---

## Deployment (cPanel / Shared Hosting)

1. Upload all files
2. Point your domain/subdomain document root to the `public/` folder  
   (or move `public/` contents into `public_html/` and adjust paths carefully)
3. Create MySQL DB + user in cPanel, update `.env`
4. Import `database/schema.sql` then `database/seed.sql`
5. Ensure `public/uploads` and `storage/logs` are writable

---

## Troubleshooting

- **Blank page / 500**: check `storage/logs/app.log`
- **CSRF 419**: refresh and login again
- **FK error**: ensure `employees.employee_code` exists and `employees` table uses InnoDB
- **Agent names**: if `employee_code` mapping fails (employee_name not starting with username), the UI will fallback to username (still works)
