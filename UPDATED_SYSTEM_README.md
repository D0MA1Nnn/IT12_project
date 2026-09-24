# Senador Coco Sales & Inventory Management System — Updated Build

This build aligns the Laravel project with the Chapter 1–3 documentation and supplied Figma screens: Owner and Sales Clerk access, products/inventory, categories, suppliers, purchasing/receiving, cashiering/sales, transaction monitoring, user management, activity logging, and backup/recovery.

## Apply to the existing database
1. Back up the current project/database.
2. Run `php artisan migrate` (do not use `migrate:fresh` on your existing data).
3. Run `php artisan optimize:clear`.
4. Run `php artisan serve`.
5. Open `http://127.0.0.1:8000`.

## Fresh development database only
Run `php artisan migrate:fresh --seed` only when you intentionally want to erase and recreate all tables.

Seeded development accounts: `owner / owner123` and `salesclerk / clerk123`.

## Notes
- Purchases are recorded after materials are physically counted, inspected, and accepted. Completing the purchase increases inventory using the product-unit conversion factor.
- Completed sales validate stock and deduct inventory using the same conversion factor.
- Product/category/supplier removal is implemented as archive/deactivate so historical transactions keep valid references.
- The Backup & Recovery screen can directly download/restore SQLite files. For XAMPP/MySQL, use phpMyAdmin or `mysqldump` for database backup/restore; the screen intentionally does not pretend a MySQL database is a copyable file.
