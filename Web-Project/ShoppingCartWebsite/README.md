# Mahazon — Shopping Cart System

Mahazon is a full-featured e-commerce platform built with PHP and MySQL. Designed to support marketplace-style applications, it includes product catalogs, user accounts, shopping carts, secure checkout, order tracking, and an admin dashboard for managing products, categories, users, and orders. Mahazon provides a production-oriented foundation that can be extended toward large-scale, marketplace-like solutions.

## Features
- Product listing and categories
- Product detail pages
- Shopping cart with add/remove/update actions
- Checkout and order tracking pages
- User registration/login and basic account pages
- Admin area for managing products, categories, orders, and users

## Folder overview
- `assets/` — CSS and JavaScript files
- `category_images/`, `images/` — uploaded images and product/category pictures
- `*.php` — application pages and admin pages
- `shoppingdb.sql` — SQL dump to create the database and sample data

## Requirements
- XAMPP (Apache + MySQL) or any PHP 7.0+ 
- A browser to open the site at `http://localhost`

## Quick install (local)
1. Copy the project folder into your web root. For XAMPP on Windows place this folder in `htdocs` (example path: C:\xampp\htdocs\shopping_cart).
2. Start Apache and MySQL from the XAMPP control panel.
3. Create a database and import the SQL dump:
   - Open `http://localhost/phpmyadmin`
   - Create a new database (e.g., `shoppingdb`)
   - Select the new database and use Import → choose `shoppingdb.sql` from this repo → Execute
4. Update database credentials if needed:
   - Open `db.php` and set the correct MySQL host, username, password, and database name.
5. Open the site in your browser:
   - `http://localhost/shopping_cart` (or the folder name you used under `htdocs`)

## Default pages of interest
- `index.php` — homepage and product listing
- `product.php` — product details
- `cart.php` — shopping cart
- `checkout.php` / `check_order.php` — checkout/order processing
- `admin_login.php` / `admin_dashboard.php` — admin area

## Database and admin
- Use `shoppingdb.sql` to populate the database schema and initial data.
- There is a `create_admin.php` script in the repo to create an admin user if needed; follow the file to see expected fields.
 
## Technologies Used
- PHP 7.0+
- MySQL
- HTML, CSS, JavaScript
- XAMPP (optional, for local development)

## Usage
- Register as a user to browse and add products to your cart.
- Admin can log in to manage products, categories, and orders.


## License
This project is created for educational and academic purposes as part of a university Web Application Development course. 


## Author

**Maha Sajid Khan**  
BS Computer Science Student



