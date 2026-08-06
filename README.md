# HostSW – Static Website Hosting Platform

A modern, lightweight static website hosting platform built with PHP. Upload a ZIP file and your HTML website is instantly published under a unique URL — similar to GitHub Pages or Netlify, but self-hosted.

![Dashboard](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)
![XAMPP](https://img.shields.io/badge/Runs%20on-XAMPP-F37623?logo=apache&logoColor=white)

---

## ✨ Features

- 🚀 **Instant Deploy** – Upload a ZIP → site goes live immediately
- 🔒 **Secure Login** – Session-based auth with bcrypt passwords
- 📊 **Dashboard Stats** – Total sites, storage used, last deployment
- 🔍 **Search & Filter** – Find sites by name, sort by date/size/status
- 🔄 **Replace Website** – Update content without changing the URL
- ✏️ **Rename Website** – Rename with automatic URL update
- ⬇️ **Download ZIP** – Re-download any hosted site as a ZIP
- 🗑️ **Delete** – One-click removal with confirmation dialog
- 🌙 **Dark / Light Mode** – Persistent theme toggle
- 📱 **Responsive** – Works on desktop and mobile
- 🛡️ **Security** – Directory traversal prevention, file type whitelist, `.htaccess` guards

---

## 📦 Tech Stack

| Layer    | Technology            |
|----------|-----------------------|
| Backend  | PHP 8.0+ (no Composer needed) |
| Frontend | Vanilla HTML/CSS/JS   |
| Server   | Apache (XAMPP)        |
| Storage  | Filesystem (no database) |
| ZIP      | PHP `ZipArchive` extension |

---

## 🚀 Installation

### Requirements
- XAMPP (Apache + PHP 8.0+)
- PHP `zip` extension enabled

### 1. Clone the repository

```bash
git clone https://github.com/raseloriginal/Host-Static-Website.git
cd Host-Static-Website
```

Place the folder inside your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\HostSW\
```

### 2. Enable the ZIP extension

Open `C:\xampp\php\php.ini` and uncomment:
```ini
extension=zip
```

Then restart Apache from the XAMPP Control Panel.

### 3. Configure

Edit `config/config.php`:

```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('your-password', PASSWORD_BCRYPT));
define('BASE_URL', 'http://localhost/HostSW');  // Change to your domain
define('MAX_UPLOAD_BYTES', 500 * 1024 * 1024); // 500 MB limit
```

### 4. Open in browser

```
http://localhost/HostSW/
```

Default login: **`admin`** / **`admin123`**

> ⚠️ **Change the default password before deploying to production!**

---

## 📁 Project Structure

```
HostSW/
├── index.php               ← Login page
├── dashboard.php           ← Main dashboard (protected)
├── .htaccess               ← Security rules
├── config/
│   └── config.php          ← Credentials & settings
├── api/
│   ├── auth_check.php      ← Session guard + helpers
│   ├── deploy_helper.php   ← ZIP validation & extraction engine
│   ├── login.php           ← POST: authenticate
│   ├── logout.php          ← Destroy session
│   ├── list.php            ← GET: all websites + stats
│   ├── upload.php          ← POST: deploy new site
│   ├── replace.php         ← POST: replace existing site
│   ├── rename.php          ← POST: rename site
│   ├── delete.php          ← POST: delete site
│   └── download.php        ← GET: download site as ZIP
├── assets/
│   ├── css/app.css         ← Design system (dark/light)
│   └── js/app.js           ← Dashboard logic
├── websites/               ← Deployed sites (git-ignored)
└── temp/                   ← Upload temp dir (git-ignored)
```

---

## 🌐 How It Works

1. Log in to the dashboard
2. Click **➕ Add Website**
3. Fill in the website name (e.g. `my-portfolio`)
4. Upload a ZIP containing your HTML site (must include `index.html`)
5. Click **Deploy**
6. Your site is live at:
   ```
   http://localhost/HostSW/websites/my-portfolio/
   ```

---

## 🔒 Supported File Types

Only static assets are allowed inside ZIPs:

`html` `htm` `css` `js` `json` `xml` `txt` `md` `png` `jpg` `jpeg` `gif` `webp` `avif` `ico` `svg` `woff` `woff2` `ttf` `eot` `otf` `mp4` `webm` `mp3` `ogg` `wav` `pdf` `csv`

PHP, ASP, Python, and all server-side scripts are **rejected**.

---

## 🛡️ Security

- Bcrypt password hashing
- Session regeneration on login
- Directory traversal prevention in ZIP extraction
- File type whitelist (blocks `.php`, `.asp`, `.py`, etc.)
- `config/` and `temp/` directories blocked via `.htaccess`
- Hidden metadata files (`.hostsw_meta.json`) blocked from public access

---

## 📄 License

MIT License – feel free to use, modify, and distribute.
