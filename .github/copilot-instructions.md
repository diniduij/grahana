# Copilot Instructions for grahana Codebase

## Overview
This is a PHP-based web application for field data collection, primarily structured for user authentication, data entry, and management of land/building records. The project uses server-side PHP (with PDO for database access), HTML, and Tailwind CSS for UI, and some client-side JavaScript libraries.

## Architecture & Key Components
- **Entry Point:** `index.php` handles user login and session management.
- **Dashboard:** `dashboard.php` is the main landing page after login.
- **Database:** `db.php` sets up the PDO connection. All DB access uses prepared statements for security.
- **Admin Functions:** All admin-related actions (user management, GND assignment) are in `admin/`.
- **User Data Collection:** All user-side data collection and map features are in `user/` (e.g., `collect_landuse.php`, `field_map.php`).
- **APIs:** Lightweight PHP endpoints for AJAX/data operations are in `user/api/` and `user/forms/`.
- **Assets:** Static files (CSS, JS, images) are in `assets/`.
- **Uploads:** User-uploaded images and documents are stored in `uploads/`.

## Developer Workflows
- **Local Development:** Run on XAMPP (Windows) with Apache and MySQL. Place code in `htdocs` and access via `localhost/grahana`.
- **No Build Step:** PHP is interpreted; changes are live on refresh.
- **Debugging:** Use `error_log()` or output to browser for debugging. Check Apache/PHP error logs for issues.
- **Database:** Update connection settings in `db.php` if needed. Use MySQL Workbench or phpMyAdmin for DB management.

## Project-Specific Patterns
- **Session Management:** All user authentication uses PHP sessions (`$_SESSION`).
- **Prepared Statements:** All DB queries use PDO prepared statements for security.
- **Error Handling:** Errors are shown inline in forms (see `index.php` for example) or logged.
- **Role-Based Access:** User roles (admin, user) are checked via session data.
- **AJAX APIs:** Data operations (CRUD) are handled via small PHP endpoints in `user/api/` and `user/forms/`.
- **Tailwind CSS:** UI uses Tailwind classes; custom styles are rare.

## Integration Points
- **Client-Side JS:** Uses Dexie.js for local IndexedDB, OpenLayers (`ol.js`) for mapping, and Proj4.js for projections.
- **Image Uploads:** Uploaded files are stored in `uploads/` and referenced by DB records.
- **Map Features:** Map-related data is loaded via AJAX from PHP endpoints.

## Examples
- **Login Flow:** See `index.php` for session setup and redirect.
- **User Management:** See `admin/user_manage.php` for CRUD operations.
- **Data Collection:** See `user/collect_landuse.php` and related API endpoints.

## Conventions
- **File Naming:** Use snake_case for PHP files, camelCase for JS variables.
- **Directory Structure:** Keep admin, user, assets, and uploads separate.
- **Security:** Always use prepared statements and validate user input.

---

For questions or unclear patterns, review the referenced files or ask for clarification on specific workflows or integration points.
