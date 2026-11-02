# UR Internship Portal - University of Rwanda

Official Year 4 Internship Placement Portal built in pure PHP 8.2+ with PDO and MySQL/MariaDB, following the University of Rwanda visual identity (light theme).

## Local Setup Instructions

1. **Prerequisites**: Ensure you have PHP 8.2+ and MySQL/MariaDB installed (e.g., via XAMPP).
2. **Clone / Place Files**: Place the `ur-internship` folder inside your XAMPP root directory (`C:\xampp\htdocs\ur-internship`).
3. **Configure Environment**:
   - Save the `.env` file in the project root.
4. **Create Database**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`), create a database named `ur_internship`.
   - Run the SQL schema and seed queries that you prepared.
5. **Logo Asset**:
   - Place an optional official logo PNG file at `assets/img/ur-logo.png`. If missing, a clean text fallback ("University of Rwanda") is automatically displayed.
6. **Run Locally**:
   - Start Apache and MySQL in XAMPP Control Panel.
   - Open `http://localhost/ur-internship/index.php` in your browser.

## Customizing Colors
All CSS variables are centralized in `/css/style.css` under the `:root` selector. You can modify `--blue` (#12356B), `--amber` (#E8A317), and other color variables easily.