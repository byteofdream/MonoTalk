# MonoTalk

A modern, framework-free PHP forum with a Reddit-inspired interface. JSON-file storage (no database), vanilla frontend, and a long list of features: subreddits, nested comment replies, mentions, notifications, gamification, a full admin panel with a reports queue, and a maintenance mode.

Lightweight, easy to deploy, and ready for free hosting (e.g. InfinityFree).

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Project Structure](#project-structure)
- [Data Storage](#data-storage)
- [Usage Guide](#usage-guide)
- [Admin Panel](#admin-panel)
- [Moderation & Trust](#moderation--trust)
- [Levels & XP](#levels--xp)
- [Maintenance Mode](#maintenance-mode)
- [API Endpoints](#api-endpoints)
- [Adding News](#adding-news)
- [Security Notes](#security-notes)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Acknowledgments](#acknowledgments)

---

## Features

### Content
- **Posts** — create and edit; title, text, subreddit, optional image upload (deletion is admin-only)
- **Images** — in posts *and* comments (JPG/PNG/GIF/WEBP, up to 5 MB)
- **Comments** — flat and **threaded replies** (`parent_id`), rendered as a nested tree server-side
- **Likes** — for posts and comments, with live counters
- **Favorites** — save posts to your personal list
- **Anonymous posting** — post or comment without showing your username
- **Text formatting** — `**bold**` and `*italic*` markers, preserved line breaks, auto-linked `@mentions`

### Communities
- **Subreddits (r/)** — browse, create, subscribe; default set: Games, Programming, Memes, Discussion, News
- **Subscriber counts** — shown on every community
- **Community feed** — filter the main feed by subreddit (you must be subscribed to post there)

### Social
- **Mentions** — type `@username` in posts/comments; the name becomes a clickable link and the user gets a notification
- **Notifications** — bell in the navbar with an unread counter: likes, comments, replies, mentions, subscriptions, report outcomes
- **User status** — online / recently seen presence badges
- **Profiles** — avatar, about text, social links (Telegram, Discord, GitHub, etc.), level & trust badges, post list, subscriptions

### Discovery
- **Search** — posts, users, and a combined "search everything" endpoint
- **Sorting** — Hot, New, Popular, Discussed
- **Subreddit search** — find communities by name/description

### Gamification
- **Levels & XP** — earn XP for activity, progress bar on your profile ([details](#levels--xp))
- **Trust system** — Suspicious / Neutral / Trusted score that affects moderation ([details](#moderation--trust))
- **Verified badge** — the "галочка", toggled per account from the admin panel; grants full access during maintenance

### Moderation & Admin
- **Admin panel** — Stats, Users, Posts, Comments, Subs, Reports, Logs
- **Reports queue** — report posts/comments, track `open` → `resolved`, act directly from the queue
- **Strikes** — automatic strikes for filtered words; mutes and bans at thresholds
- **Bad-word filter** — configurable groups in `data/bad_words.json`
- **Action logs** — every moderation action is appended to `data/moderation_logs.json`
- **Maintenance mode** — one switch closes the whole site to regular visitors

### Experience
- **i18n** — Russian and English, switchable from the header or Settings
- **Themes** — light / dark (cookie-based, no flash on load)
- **Compact mode** and **hide images** options
- **News page**, **Rules page**, **Welcome flow**
- **Custom error pages** — 403 / 404 / 500 wired through `.htaccess`
- **Responsive** — works on desktop and mobile
- **Speed notice** — dismissible cache hint on the main feed

---

## Tech Stack

| Layer    | Technology                        |
|----------|-----------------------------------|
| Backend  | PHP 7.4+ (no framework)           |
| Storage  | JSON files (flat files, no DB)    |
| Frontend | Vanilla HTML/CSS/JS               |
| Icons    | Inline SVG set (`assets/icons/`)  |
| Font     | Montserrat (Google Fonts)         |

The codebase runs fine on modern PHP (tested on PHP 8.5).

---

## Requirements

- PHP 7.4 or higher (8.x recommended)
- `json` and `session` support (enabled by default)
- `mbstring` optional — fallbacks are defined in `includes/config.php`
- Writable `data/` and `uploads/` directories

---

## Installation

### Option 1: Local development

Clone, then start PHP's built-in server:

```bash
git clone https://github.com/your-username/MonoTalk.git
cd MonoTalk
php -S localhost:8000
```

Or just double-click `localhost.bat` (starts `php -S localhost:8000`).

Open `http://localhost:8000` in your browser.

### Option 2: Web hosting (InfinityFree, 000webhost, etc.)

1. Upload all files to your hosting `htdocs` or `public_html` folder.
2. Set permissions `755` (or `775`) for `data/` and `uploads/`.
3. Make sure `uploads/avatars`, `uploads/posts`, and `uploads/comments` exist (they are created automatically on first upload).
4. Open your site URL in a browser.

> `data/.htaccess` and the root `.htaccess` are included — they block direct access to JSON files and register the custom error pages. Keep them if your host uses Apache.

---

## Configuration

Edit `includes/config.php`:

```php
// Base URL — use '/' for root, '/MonoTalk/' for a subfolder
define('BASE_URL', '/');

// Upload directory (usually no need to change)
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// GitHub repo URL (shown in Settings → About)
define('GITHUB_URL', 'https://github.com/your-username/MonoTalk');

// Moderation thresholds
define('MODERATION_MUTE_HOURS', 24);       // mute duration
define('MODERATION_HIGH_STRIKE_THRESHOLD', 3);  // escalate
define('MODERATION_BAN_STRIKE_THRESHOLD', 5);   // auto-ban
```

### Subfolder installation

If the forum runs in a subfolder (e.g. `yoursite.com/forum/`):

```php
define('BASE_URL', '/forum/');
```

On Apache with `mod_rewrite`, add `RewriteBase /forum/` if links break.

---

## Project Structure

```
MonoTalk/
├── index.php             # Main feed (sorting + subreddit filter)
├── post.php              # Single post + comment tree
├── create.php            # Create post (image upload)
├── create_subreddit.php  # Create a community
├── edit_profile.php      # Edit profile / avatar
├── profile.php           # User profile (own or other)
├── search.php            # Search results
├── login.php
├── register.php
├── welcome.php           # Post-registration welcome
├── settings.php          # Settings: General / Appearance / About
├── news.php              # Forum announcements
├── rules.php             # Community rules
├── admin.php             # Admin panel (7 tabs)
├── maintenance.php       # Maintenance screen
├── 403.php  404.php  500.php
├── localhost.bat         # Local dev server launcher
├── favicon.svg
│
├── api/                  # JSON API handlers (32 endpoints)
│   ├── login.php  register.php  logout.php  me.php
│   ├── create_post.php  edit_post.php  like.php
│   ├── toggle_favorite.php  get_posts.php  get_post_info.php
│   ├── add_comment.php  edit_comment.php  get_comments.php
│   ├── create_subreddit.php  get_subreddits.php  toggle_subscription.php
│   ├── search_all.php  search_users.php
│   ├── update_profile.php  get_user_info_from_id.php
│   ├── get_user_posts.php  get_user_subscriptions.php
│   ├── read_notifications.php  activity_ping.php  user_status.php
│   ├── set_language.php  set_theme.php  set_option.php  set_maintenance.php
│   ├── admin_toggle.php  report.php
│   └── seen_welcome.php
│
├── includes/
│   ├── config.php        # Constants, session, theme, mbstring fallbacks
│   ├── db.php            # JSON storage helpers (readData/writeData/getNextId)
│   ├── auth.php          # Sessions, login/logout, current user
│   ├── functions.php     # Shared helpers (posts, comments, notifications…)
│   ├── lang.php          # Translations (RU/EN) + t()
│   ├── moderation.php    # Bad words, strikes, logging, posting restrictions
│   ├── trust.php         # Trust score + status badges
│   ├── leveling.php      # XP, levels, progress
│   ├── maintenance.php   # Maintenance mode state + enforcement
│   ├── header.php        # <head>, navbar, notifications bell
│   └── footer.php
│
├── data/                 # JSON storage (must be writable, blocked by .htaccess)
│   ├── users.json  posts.json  comments.json  likes.json
│   ├── subreddits.json  categories.json  favorites.json
│   ├── notifications.json  reports.json  moderation_logs.json
│   ├── levels.json  bad_words.json  news.json
│   ├── rate_limits.json  maintenance.json
│   └── .htaccess
│
├── assets/
│   ├── style.css         # All styles, light/dark themes via [data-theme]
│   ├── script.js         # Frontend behavior
│   ├── trust-system.js   # Trust badge UI
│   ├── level-system.js   # Level progress UI
│   └── icons/            # SVG icon set
│
└── uploads/              # User uploads (must be writable)
    ├── avatars/
    ├── posts/
    └── comments/
```

---

## Data Storage

Everything lives in `data/*.json`. Helpers are in `includes/db.php`:

| Helper | Purpose |
|--------|---------|
| `readData($file)` | Read a JSON file into an array |
| `writeData($file, $array)` | Write an array back to JSON (pretty-printed) |
| `getNextId($file)` | Next free numeric `id` |

| File | Holds |
|------|-------|
| `users.json` | Accounts: password hash, verified flag, role, XP, trust, strikes, status, subscriptions |
| `posts.json` | Posts: author, `category` (subreddit id), title, content, image, counters, timestamps |
| `comments.json` | Comments: `parent_id` (threading), content, image, likes, anonymous flag |
| `likes.json` | Who liked what (`type` = post/comment) |
| `favorites.json` | Saved posts per user |
| `subreddits.json` | Communities: name (RU/EN), emoji, description, creator |
| `notifications.json` | Per-user notifications: type, text, link, `read` |
| `reports.json` | Reports: target, reason, reporter, `status` (`open`/`resolved`) |
| `moderation_logs.json` | Append-only log of moderation actions |
| `bad_words.json` | Filter word groups + severities |
| `levels.json` | Level thresholds and titles (editable) |
| `news.json` | Forum announcements (bilingual fields) |
| `rate_limits.json` | Rate-limit counters |
| `maintenance.json` | `{"enabled": true|false}` |

> There is no database and no concurrency control — this design is intended for small communities and shared/free hosting.

---

## Usage Guide

### First run

1. Open the site and click **Register** (username, password, optional email).
2. You are logged in and redirected to the welcome page.
3. Subscribe to a subreddit — posting inside a community requires a subscription.
4. Use **Create post** to add your first topic — you can attach an image.
5. Filter the feed by subreddit, sort by Hot/New/Popular/Discussed.

### Comments and replies

- Leave a top-level comment under a post.
- Click **Ответить / Reply** under any comment to open the inline reply form.
- Replies render nested under their parent (up to arbitrary depth).
- If a parent comment is deleted, its replies are moved up to the root.

### Mentions

- Write `@username` in a post or comment — it renders as a highlighted, clickable link.
- The mentioned user gets a notification linking back to the content.
- Rules: 3–32 characters (`[a-zA-Z0-9_]`), ignored inside emails/URLs, duplicates deduplicated, no notifications for anonymous actors or yourself.

### Favorites & profiles

- **★** on a post saves it to your favorites list.
- Click any `u/username` to open a profile — avatar, about, social links, level, trust badge, posts, subscriptions.
- Your own bio, avatar and social links are edited on `edit_profile.php`, opened via the edit button on your own profile page.

### Search

- Use the search bar in the header (minimum 2 characters).
- It searches post titles/content; dedicated user and combined search are also available.

### Settings

`settings.php?tab=…`:

- **General** — interface language, compact mode, hide post images
- **Appearance** — light / dark theme with previews
- **About** — a short blurb about MonoTalk and the project's GitHub link

### Language & theme

- **RU / EN** button in the header.
- Theme switch in the header or Settings → Appearance; stored in a cookie.
- Both preferences apply instantly without a rebuild.

---

## Admin Panel

`admin.php` (available to verified/admin accounts). Tabs:

| Tab | Contents |
|-----|----------|
| 📊 **Stats** | User/post/comment/subreddit counts, banned & verified totals, **maintenance toggle** |
| 👥 **Users** | Search, toggle the verified badge, cycle role (`user` → `mod` → `admin`), ban/unban |
| 📝 **Posts** | Search, delete |
| 💬 **Comments** | Search, delete |
| 📂 **Subs** | Delete communities |
| 🚩 **Reports** | Queue with an open-count badge — resolve, delete content, issue a strike |
| 📋 **Logs** | Moderation action history |

Actions are dispatched from `api/admin_toggle.php`:

```
verify, ban, role, delete_post, delete_comment, delete_subreddit,
report_resolve, report_delete_post, report_delete_comment, report_strike
```

Every action is written to `data/moderation_logs.json`, and the reporter is notified when their report is handled.

### Reporting content

`api/report.php` — report a post or a comment:

- requires login
- **10-second rate limit** per user
- rejects duplicates while a report for the same target is still `open`
- you cannot report your own content
- the reporter is notified once an admin acts on the report (closed / content deleted / strike issued)

---

## Moderation & Trust

### Bad words & strikes

- `data/bad_words.json` groups filtered words with severities.
- Submitted text is checked by `moderateForumText()`; violations can block the post, mute the user, or add a strike.
- Strikes escalate automatically:

| Strikes | Result |
|---------|--------|
| `≥ 3` (`MODERATION_HIGH_STRIKE_THRESHOLD`) | Escalated / high-severity flag |
| `≥ 5` (`MODERATION_BAN_STRIKE_THRESHOLD`) | Ban |

- Mute duration: `MODERATION_MUTE_HOURS` (default **24**).
- Posting restrictions are resolved per user by `getPostingRestriction()`.

### Trust score

A 0–100 score stored on each user (`includes/trust.php`):

| Range | Status | Meaning |
|-------|--------|---------|
| 0–30 | 🔴 **Suspicious** | Needs moderation on every post |
| 31–70 | 🟡 **Neutral** | No extra restrictions |
| 71–100 | 🟢 **Trusted** | Trusted contributor |

The badge is rendered next to usernames through `assets/trust-system.js`.

---

## Levels & XP

`includes/leveling.php`:

| Action | XP |
|--------|----|
| Create a post | **+40** (`XP_REWARD_POST`) |
| Add a comment | **+15** (`XP_REWARD_COMMENT`) |

- Levels are defined in `data/levels.json` (thresholds + titles) — edit freely.
- Progress to the next level is shown on profiles via `assets/level-system.js`.
- Helpers: `addXPToUser()`, `calculateLevel()`, `getLevelProgressData()`.

---

## Maintenance Mode

Closes the site to regular visitors while verified users keep full access.

**How to use it:** `admin.php` → **Stats** → *Maintenance mode* switch. The switch calls `api/set_maintenance.php` (verified accounts only) and writes `data/maintenance.json`.

**Behavior:**

| Visitor | Mode ON | Mode OFF |
|---------|---------|----------|
| Guest on any page | 302 → `maintenance.php` | normal |
| Logged-in, not verified | 302 → `maintenance.php` | normal |
| Verified (badge) | full access | normal |
| `login.php` / `register.php` | always open | open |
| `api/set_maintenance.php` | 403 for non-verified | 403 for non-verified |

**Implementation:**

- `includes/maintenance.php` — `isMaintenanceEnabled()`, `setMaintenanceMode()`, `isMaintenanceExempt()`, `enforceMaintenance()`
- `includes/header.php` calls `enforceMaintenance()` before any HTML output — this covers every page
- `maintenance.php` — the maintenance screen itself («Мы подкручиваем винтики», RU/EN)
- Exempt scripts: `maintenance.php`, `login.php`, `register.php` (+ their APIs)

---

## API Endpoints

Every endpoint returns JSON, except the redirect-style preference helpers (`set_language`, `set_theme`, `set_option`, `admin_toggle`), which redirect back to the calling page. The accepted HTTP method is listed for each endpoint.

### Auth & session
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/login.php` | POST | Log in |
| `api/register.php` | POST | Register |
| `api/logout.php` | GET | Log out |
| `api/me.php` | GET | Current user info |
| `api/seen_welcome.php` | POST | Dismiss welcome screen |
| `api/set_language.php` | GET/POST | Set `ru`/`en`, redirect back |
| `api/set_theme.php` | GET/POST | Set `light`/`dark` |
| `api/set_option.php` | GET | Compact mode / hide images (cookie) |
| `api/set_maintenance.php` | POST | Toggle maintenance (verified only) |

### Posts
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/create_post.php` | POST | Create post (multipart, image ≤ 5 MB) |
| `api/edit_post.php` | POST | Edit own post |
| `api/like.php` | POST | Toggle like on post/comment |
| `api/toggle_favorite.php` | POST | Save/unsave a post |
| `api/get_posts.php` | GET | Feed with sorting/filter |
| `api/get_post_info.php` | GET | Post metadata |

### Comments
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/add_comment.php` | POST | Add comment; `parent_id` for replies; optional image |
| `api/edit_comment.php` | POST | Edit own comment |
| `api/get_comments.php` | GET | Comments for a post |

### Communities
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/create_subreddit.php` | POST | Create a subreddit |
| `api/get_subreddits.php` | GET | List subreddits |
| `api/toggle_subscription.php` | POST | Subscribe/unsubscribe (notifies the owner) |

### Profiles & search
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/update_profile.php` | GET/POST | Read profile / update it (multipart for avatar) |
| `api/get_user_info_from_id.php` | GET | Public user info |
| `api/get_user_posts.php` | GET | Posts by user |
| `api/get_user_subscriptions.php` | GET | Subscriptions by user |
| `api/search_all.php` | GET | Combined search |
| `api/search_users.php` | GET | User search |

### Social & realtime
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/read_notifications.php` | POST | Mark notifications as read |
| `api/activity_ping.php` | POST | Presence heartbeat |
| `api/user_status.php` | GET | Online/recent status |

### Moderation & admin
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/report.php` | POST | Report a post/comment (10 s rate limit) |
| `api/admin_toggle.php` | GET | Admin actions (see [Admin Panel](#admin-panel)) |

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

Use a unique `id` and a valid `date` (`YYYY-MM-DD`). News is displayed newest-first.

---

## Security Notes

- Passwords hashed with `password_hash()` (bcrypt).
- Output escaped with `htmlspecialchars()` via the `e()` helper to reduce XSS risk.
- Rate limiting for posts, comments, and reports (`checkSpamProtection()`, `data/rate_limits.json`).
- Image uploads restricted by extension (`jpg`, `jpeg`, `png`, `gif`, `webp`), 5 MB cap, and random filenames.
- Report abuse guards: duplicate check, self-reporting blocked, 10 s cooldown.
- `data/.htaccess` denies direct access to JSON; the root `.htaccess` hides dotfiles and registers error pages.
- Avoid exposing `data/` and `uploads/` directly on hosts without Apache rules (add equivalent nginx rules).

> This is a hobby-grade project with flat-file storage and no CSRF tokens — do not deploy it as-is for a large or high-trust audience without hardening.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Blank page / warnings | Make sure `data/` and `uploads/` are writable (`755` or `775`) |
| 404 on all links | Set `BASE_URL` to the real path (`/forum/`, etc.) |
| JSON read errors | Check that `data/.htaccess` still exists and files are valid UTF-8 JSON |
| Uploads not appearing | Verify `uploads/` is writable and PHP allows uploads (`upload_max_filesize` ≥ 5M) |
| Stale styles after update | Hard-refresh with `Ctrl + Shift + R` |
| Everyone sees the maintenance page | Turn it off in **Admin → Stats → Maintenance mode** |

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

This project is open source. See [LICENSE](LICENSE) for details. Feel free to use, modify, and distribute it.

---

## Acknowledgments

- [Google Fonts — Montserrat](https://fonts.google.com/specimen/Montserrat)
- Inspired by Reddit's layout and feed design
