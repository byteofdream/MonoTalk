# MonoTalk

A modern, framework-free PHP forum with a Reddit-inspired interface. Lightweight, easy to deploy, and ready for free hosting (e.g. InfinityFree).

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Project Structure](#project-structure)
- [Usage Guide](#usage-guide)
- [API Endpoints](#api-endpoints)
- [Adding News](#adding-news)
- [Contributing](#contributing)
- [License](#license)

---

## Features

### Core
- **User system** — Registration, login, sessions (password hashing with `password_hash`)
- **Posts** — Create, view, edit; title, content, category
- **Comments** — Threaded under posts
- **Likes** — For posts and comments
- **Categories** — Games, Programming, Memes, Discussion, News (configurable via JSON)
- **Anonymous posting** — Option to post or comment without showing username

### Discovery
- **Search** — Full-text search across post titles and content
- **Sorting** — Hot, New, Popular, Discussed
- **Category filters** — Browse by topic

### User Experience
- **Profiles** — View own and other users' profiles; avatar (URL or upload)
- **Reddit-style layout** — Feed with sidebar, vote buttons, metadata
- **Responsive design** — Works on desktop and mobile
- **i18n** — Russian and English
- **News page** — Forum announcements and updates

### Other
- **Rules page** — Community guidelines
- **Settings** — Language, About section with GitHub link
- **Security** — XSS protection (`htmlspecialchars`), basic spam protection, validation

---

## Tech Stack

| Layer   | Technology               |
|---------|--------------------------|
| Backend | PHP 7.4+ (no framework)  |
| Storage | JSON files               |
| Frontend| Vanilla HTML/CSS/JS      |
| Font    | Montserrat (Google Fonts)|

---

## Requirements

- PHP 7.4 or higher
- JSON extension (enabled by default)
- Session support
- Writable `data/` and `uploads/` directories

---

## Installation

### Option 1: Local development (XAMPP, WAMP, PHP built-in server)

1. Clone or download the repository:

```bash
git clone https://github.com/your-username/MonoTalk.git
cd MonoTalk
```

2. Start PHP built-in server (if needed):

```bash
php -S localhost:8000
```

3. Open `http://localhost:8000` in your browser.

### Option 2: Web hosting (InfinityFree, 000webhost, etc.)

1. Upload all files to your hosting `htdocs` or `public_html` folder.
2. Set permissions `755` (or `775`) for `data/` and `uploads/`.
3. Create empty `uploads/avatars/` folder.
4. Open your site URL in a browser.

---

## Configuration

Edit `includes/config.php`:

```php
// Base URL — use '/' for root, '/MonoTalk/' for subfolder
define('BASE_URL', '/');

// Upload directory (usually no need to change)
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// GitHub repo URL (shown in Settings → About)
define('GITHUB_URL', 'https://github.com/your-username/MonoTalk');
```

### Subfolder installation

If the forum runs in a subfolder (e.g. `yoursite.com/forum/`):

```php
define('BASE_URL', '/forum/');
```

---

## Project Structure

```
MonoTalk/
├── index.php           # Main feed
├── post.php            # Single post view
├── create.php          # Create post
├── login.php
├── register.php
├── welcome.php         # Post-registration welcome
├── profile.php         # User profile (own or other)
├── search.php          # Topic search
├── news.php            # Forum news
├── settings.php        # Settings (General, About)
├── rules.php           # Community rules
│
├── api/                # API handlers (JSON responses)
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── create_post.php
│   ├── add_comment.php
│   ├── like.php
│   ├── update_profile.php
│   ├── set_language.php
│   └── seen_welcome.php
│
├── includes/
│   ├── config.php      # Configuration
│   ├── db.php          # JSON storage helpers
│   ├── auth.php        # Sessions, user helpers
│   ├── functions.php   # Common functions
│   ├── lang.php        # Translations (RU/EN)
│   ├── header.php
│   └── footer.php
│
├── data/               # JSON storage (must be writable)
│   ├── users.json
│   ├── posts.json
│   ├── comments.json
│   ├── likes.json
│   ├── categories.json
│   └── news.json
│
├── assets/
│   ├── style.css
│   └── script.js
│
└── uploads/            # User uploads (must be writable)
    └── avatars/
```

---

## Usage Guide

### First run

1. Open the site and click **Register**.
2. Create an account (username, password, optional email).
3. You are logged in and redirected to the welcome page.
4. Use **Create post** to add your first topic.

### Categories

- **Games** — Gaming discussions  
- **Programming** — Code, tech, dev  
- **Memes** — Humor  
- **Discussion** — General chat  
- **News** — Announcements  

Categories are defined in `data/categories.json` and can be edited.

### Profiles

- **Own profile** — Via navbar dropdown or direct link; edit avatar here.
- **Other profiles** — Click any `u/username` link in posts or comments.

### Search

Use the search bar in the header. Minimum 2 characters. Searches titles and post content.

### Language

- Click the **RU/EN** button in the header.
- Or change it in **Settings → General**.
- Preference is stored in a cookie.

---

## API Endpoints

All API endpoints expect `POST` (unless noted) and return JSON.

| Endpoint           | Purpose               |
|--------------------|-----------------------|
| `api/login.php`    | User login            |
| `api/register.php` | User registration     |
| `api/logout.php`   | Logout (GET)          |
| `api/create_post.php` | Create post        |
| `api/add_comment.php` | Add comment        |
| `api/like.php`     | Toggle like (post/comment) |
| `api/update_profile.php` | Update avatar   |
| `api/set_language.php`  | Set language (GET)    |

---

## Adding News

Edit `data/news.json` and add a new object to the array:

```json
{
  "id": 4,
  "date": "2025-03-21",
  "title_ru": "Заголовок на русском",
  "title_en": "Title in English",
  "content_ru": "Текст новости на русском.",
  "content_en": "News content in English."
}
```

Use a unique `id` and valid `date` (YYYY-MM-DD). News is displayed in reverse order (newest first).

---

## Security Notes

- Passwords are hashed with `password_hash()` (bcrypt).
- Output is escaped with `htmlspecialchars()` to reduce XSS risk.
- Basic rate limiting for create post and add comment.
- `data/.htaccess` blocks direct access to JSON files.
- Avoid exposing `data/` and `uploads/` directly.

---

## Contributing

Contributions are welcome.

1. Fork the repository.
2. Create a feature branch (`git checkout -b feature/amazing-feature`).
3. Commit your changes (`git commit -m 'Add amazing feature'`).
4. Push to the branch (`git push origin feature/amazing-feature`).
5. Open a Pull Request.

---

## License

This project is open source. Feel free to use, modify, and distribute it.

---

## Acknowledgments

- [Google Fonts — Montserrat](https://fonts.google.com/specimen/Montserrat)
- Inspired by Reddit's layout and feed design
