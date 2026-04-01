
# Tuberculosis MIS

A lightweight TB case management information system built in PHP (XAMPP-friendly). It covers patient registration, visit/check-in, clinical workflows, labs, radiology, pharmacy, referrals, and reporting with auto-assignment to the least-loaded staff.

## Features
- Patient registry with check-in and visit tracking
- Auto-assign doctor at check-in and auto-assign nurse on request (least-workload)
- Clinical consultation, vitals, diagnosis, treatment planning, prescriptions
- Lab and radiology ordering with auto assignment
- Pharmacy dispensing and referral flows
- PDF/print exports via Dompdf; spreadsheet exports via PhpSpreadsheet
- Role-based workspaces (Admin, Clerk, Doctor, Nurse, Pharmacist, Lab, Radiologist)

## Tech Stack
- PHP 8.x (tested with XAMPP stack)
- MySQL/MariaDB
- Composer dependencies: dompdf/dompdf, phpoffice/phpspreadsheet

## Quick Start
1) Clone or copy the project into your web root (e.g., `htdocs/wbtmis`).
2) Install PHP dependencies: `composer install` (already present: Dompdf, PhpSpreadsheet).
3) Create database and import schema: use `table.sql` (and `tbmis_migration.sql` if provided) to seed tables.
4) Configure DB connection in [config/database.php](config/database.php).
5) Start Apache/MySQL (e.g., XAMPP) and open the app via `http://localhost/wbtmis/index.php`.

## Default Roles & Credentials
- demo.admin@mattu.edu — Admin
- demo.clerk@mattu.edu — Clerk
- demo.doctor@mattu.edu — Doctor
- demo.nurse@mattu.edu — Nurse
- demo.pharmacist@mattu.edu — Pharmacist
- demo.radiologist@mattu.edu — Radiologist
- demo.lab@mattu.edu — Lab Technician

Password (all accounts): `password123`

## Key Paths
- Core classes: [includes/classes](includes/classes)
- Registration & check-in: [modules/Registration](modules/Registration)
- Clinical workflows: [modules/medical-record](modules/medical-record)
- Reports/exports: [modules/reports](modules/reports)
- Assets: [assets](assets)

## Notable Behaviors
- Check-in auto-assigns the least-loaded doctor and surfaces their details to the clerk.
- Doctors can request nursing support; the least-loaded nurse is auto-assigned and displayed.
- Active encounters page shows flash messages after redirects so assignment details persist across pagination.

## Database Setup Tips
- Ensure InnoDB and utf8mb4.
- Run provided SQL files in order (base schema first, then migrations).
- If using XAMPP defaults: host `localhost`, user `root`, empty password (adjust in `config/database.php`).

## Troubleshooting
- Blank page or 500: check `config/database.php` credentials and PHP error log.
- Missing exports: confirm Composer vendor folder exists and Dompdf/PhpSpreadsheet are installed.
- Auto-assignment issues: verify `users` entries are active and have the correct roles.

## License
Internal/demo use. Add your own license if distributing.
