# ShieldHub

ShieldHub is a web application security demonstration project that showcases common web security vulnerabilities and their mitigation techniques. This project is intended for educational purposes to help developers understand security best practices.

## Features

- User registration and authentication
- File uploads
- Dynamic content creation
- User profiles
- Intentional security vulnerabilities for educational purposes

## Prerequisites

- PHP 7.0 or higher
- MySQL/MariaDB
- Web server (Apache, Nginx, etc.)
- Basic knowledge of PHP and MySQL

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/ShieldHub.git
cd ShieldHub
```

### 2. Database Setup

You can set up the database using either direct MySQL commands or phpMyAdmin, depending on your preference.

#### Option 1: Using MySQL Command Line

1. Log in to MySQL:

```bash
mysql -u root -p
```

2. Create a new database named `shieldHub`:

```sql
CREATE DATABASE shieldHub;
```

3. Create a user for the database (or use an existing one):

```sql
CREATE USER 'username'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON shieldHub.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

4. Create the necessary tables:

```sql
USE shieldHub;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Option 2: Using phpMyAdmin

1. Open your web browser and navigate to phpMyAdmin (usually http://localhost/phpmyadmin)
2. Log in with your MySQL credentials
3. Click on "New" in the left sidebar to create a new database
4. Enter "shieldHub" as the database name and select "utf8_general_ci" as the collation
5. Click "Create"
6. Once the database is created, click on the "shieldHub" database in the left sidebar
7. Click on the "SQL" tab at the top
8. Copy and paste the following SQL code:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

9. Click "Go" to execute the SQL query and create the tables

### 3. Configure Database Connection

1. Open the file `src/config/db.php`
2. Update the database credentials:

```php
private $host = 'localhost';
private $username = 'your_username';  // Replace with your MySQL username
private $password = 'your_password';  // Replace with your MySQL password
private $dbname = 'shieldHub';
```

Note: If you're using the default XAMPP/WAMP/MAMP setup, the username is often 'root' and the password might be empty or 'root' depending on your configuration.

### 4. Set Up Web Server

#### Using PHP's Built-in Server (for development)

Navigate to the project root directory and run:

```bash
php -S localhost:8000 -t src/public
```

This will start a server at `http://localhost:8000`

#### Using Apache with XAMPP/WAMP/MAMP

1. If you're using XAMPP, WAMP, or MAMP, copy the ShieldHub folder to your web server's document root:
   - XAMPP: `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (macOS)
   - WAMP: `C:\wamp\www\`
   - MAMP: `/Applications/MAMP/htdocs/`

2. Access the application through:
   - `http://localhost/ShieldHub/src/public`

#### Using Apache with Virtual Host

1. Create a virtual host for the project:

```apache
<VirtualHost *:80>
    ServerName shieldhub.local
    DocumentRoot /path/to/ShieldHub/src/public
    
    <Directory /path/to/ShieldHub/src/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/shieldhub_error.log
    CustomLog ${APACHE_LOG_DIR}/shieldhub_access.log combined
</VirtualHost>
```

2. Add `shieldhub.local` to your hosts file:
   - On Windows: `C:\Windows\System32\drivers\etc\hosts`
   - On Linux/Mac: `/etc/hosts`

```
127.0.0.1 shieldhub.local
```

3. Restart Apache:
   - On Windows: `httpd -k restart` or restart through XAMPP/WAMP control panel
   - On Linux: `sudo systemctl restart apache2` or `sudo service apache2 restart`
   - On macOS: `sudo apachectl restart` or restart through MAMP control panel

### 5. File Permissions

Ensure the `uploads` directory inside `src/public` has the proper permissions:

```bash
mkdir -p src/public/uploads
chmod 755 src/public/uploads
```

For Windows users, you might need to set permissions through the File Explorer by right-clicking the folder, selecting Properties, then the Security tab.

## Usage

1. Open the application in your browser:
   - If using PHP's built-in server: `http://localhost:8000`
   - If using XAMPP/WAMP/MAMP: `http://localhost/ShieldHub/src/public`
   - If using Apache virtual host: `http://shieldhub.local`

2. Register a new user account
3. Log in with your credentials
4. Explore the various features and security vulnerabilities

## Troubleshooting

### Database Connection Issues
- Verify that MySQL service is running
- Check that the database credentials in `src/config/db.php` are correct
- Ensure the `shieldHub` database exists and has the required tables

### Server Configuration Issues
- Make sure your web server has PHP enabled
- Verify that PHP has the required extensions (mysqli, PDO)
- Check web server error logs for specific error messages

### File Upload Issues
- Ensure the `uploads` directory exists and has appropriate write permissions
- Check PHP settings for file upload limits (`upload_max_filesize` and `post_max_size` in php.ini)

## Security Vulnerabilities

This project intentionally contains the following security vulnerabilities for educational purposes:

1. SQL Injection
2. Cross-Site Scripting (XSS)
3. Cross-Site Request Forgery (CSRF)
4. Insecure Direct Object References (IDOR)
5. Insecure File Upload
6. Weak Password Policies
7. Missing Input Validation

## Best Practices Demonstrated

The project also includes secure implementations to demonstrate best practices:

1. Prepared statements
2. Input sanitization
3. CSRF token validation
4. File type validation
5. Secure password hashing
6. Proper error handling

## Disclaimer

This application is for educational purposes only. Do not use it in a production environment or expose it to the public internet as it contains intentional security vulnerabilities.

## License

[MIT License](LICENSE)
