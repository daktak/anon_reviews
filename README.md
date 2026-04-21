# anon_reviews
A simple PHP web application where users can submit links, view shared content, and leave anonymous comments.

## Purpose

A lightweight platform for sharing and discussing web links anonymously without requiring user accounts.

## Features

- Submit and list web links
- View individual link details
- Anonymous commenting on links
- Admin support for editing and deleting content

## Admin Access

An admin user can:
- Edit submitted links
- Delete links and comments

## Setup Instructions

1. Create the database schema:
   ```psql -d your_database -f dbcreate.sql```

2. Configure the application:

    ```cp config.php.example config.php```

3. Update config.php with:

  * Database connection details
  * Admin credentials

4. Deploy to a PHP-enabled web server

## Notes

  * No user registration or login for general users
  * All comments are anonymous
  * Designed to be minimal and easy to deploy


