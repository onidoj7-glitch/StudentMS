# StudentMS

Student Management System is a web application built with PHP and MySQL for managing student records. It provides a login system, a dashboard, and tools for adding, viewing, editing, deleting, and searching student information.

## Features

- Login and logout functionality
- Session-based authentication
- Dashboard displaying student statistics
- Add new student records
- View and manage student information
- Edit existing student records
- Delete student records
- Search students by ID, name, or course
- Filter and paginate student records
- Form validation for required fields and email addresses
- CSRF protection for supported form submissions
- Light and dark mode
- Green-themed user interface

## Technologies Used

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- XAMPP

## Requirements

- PHP
- MySQL or MariaDB
- Apache web server
- XAMPP or another compatible PHP/MySQL environment

## Project Structure

```text
student_management_system/
├── index.php
├── students.php
├── database.php
├── login.php
├── logout.php
└── assets/
    ├── css/
    │   └── style.css
    └── js/
        └── script.js
```

*Note: This structure lists the main files discussed in the project. Include any additional files present in your repository.*

## How to Run Locally

1. Install and open XAMPP.
2. Copy the project folder into the `htdocs` directory.
3. Start Apache and MySQL from the XAMPP Control Panel.
4. Open phpMyAdmin at `http://localhost/phpmyadmin`.
5. Create the database named `student_management_db`, or use the database name specified in your configuration.
6. Import your project's SQL database backup to create the required tables and records.
7. Check `database.php` and update the database connection settings if needed.
8. Open the application in your browser:

   `http://localhost/student_management_system/`

9. Log in using your configured administrator account.

## Database

The application uses MySQL to store user accounts and student records.

Student records include information such as:

- Student ID
- First name
- Last name
- Email address
- Course
- Year level

The `users` table stores login information. Passwords should be stored as secure hashes rather than plain text.

## Security

- Session-based login protection
- Server-side input validation
- Prepared database statements
- CSRF protection for supported actions
- Password hashing and verification

## Project Purpose

This project demonstrates how PHP and MySQL can be used to build a basic student record management system with authentication, CRUD operations, search, filtering, and validation.

## Developer

**Jerome I. Oñido**

BSIT Student | Web Development

