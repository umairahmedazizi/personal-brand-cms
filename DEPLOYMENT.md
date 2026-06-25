# Deploy to Hostinger — Easy Steps

A short, do-this-then-that guide. Everything is done from **hPanel** and the
**File Manager** in your browser. No SSH, no command line on the server.

> **Plan:** any Hostinger plan with **PHP 8+ and MySQL** works (Single, Premium,
> Business, Cloud). The standard/shared plan is fine.

The site has two parts that both live inside `public_html`:
- **Static pages** (home, about, contact) — built on your computer into `_site/`.
- **PHP backend** (blog, books, admin, contact form, chatbot) — the `backend/` folder.
- A `.htaccess` file connects them.

---

## Step 1 — Build the static site (on your computer)

Open a terminal in the project folder and run:

```bash
npm install     # first time only
npm run build
```

This creates a **`_site/`** folder. That folder's contents go live.

---

## Step 2 — Create a MySQL database (in hPanel)

hPanel → **Databases → MySQL Databases**:

1. Create a database — note the **name** (e.g. `u123456789_mk`).
2. Create a **user** + **password**, and add the user to the database with **All Privileges**.
3. The **host** is usually `localhost`.

Write down these 4 values — you need them in Step 4:
**host, database name, user, password.**

> You do NOT need to import any SQL. The app builds its own tables on first visit.

---

## Step 3 — Upload the files (File Manager)

hPanel → **Files → File Manager** → open `public_html`. You want it to end up like this:

```
public_html/
  index.html, about/, contact/, css/, js/, assets/ ...   ← contents of _site/
  backend/                                                ← the backend folder
  .htaccess                                               ← copy of backend/deploy/.htaccess
```

Do these three uploads:

1. **Static site:** upload everything **inside** `_site/` into `public_html`
   (the files, not the `_site` folder itself).
2. **Backend:** upload the whole `backend/` folder so you get `public_html/backend/`.
3. **.htaccess:** copy `backend/deploy/.htaccess` to `public_html/.htaccess`
   (top level). If File Manager won't let you make a dotfile, create a new file named
   `.htaccess` in `public_html` and paste the contents of `backend/deploy/.htaccess` into it.

> **Faster:** zip `_site` and `backend` on your computer, upload the two zips, then
> use File Manager's **Extract**.

---

## Step 4 — Set up config.php

In `public_html/backend/`:

1. Copy `config.sample.php` and rename the copy to **`config.php`**.
2. Open `config.php` and change these:

```php
'db' => [
    'driver'     => 'mysql',                 // change from 'sqlite'
    'mysql_host' => 'localhost',             // from Step 2
    'mysql_name' => 'u123456789_mk',         // from Step 2
    'mysql_user' => 'u123456789_mk',         // from Step 2
    'mysql_pass' => 'your-db-password',      // from Step 2
    'mysql_charset' => 'utf8mb4',
],

'site' => [
    'url' => 'https://yourdomain.com',       // your domain, no trailing slash
],

'admin' => [
    'email'    => 'you@yourdomain.com',      // your admin login
    'password' => 'a-strong-password',
],

'debug' => false,                            // keep false when live
```

3. Save.

---

## Step 5 — Turn on HTTPS + check PHP version

- hPanel → **Security → SSL** → install the free SSL certificate and wait until it
  shows **Active**. (The `.htaccess` then forces HTTPS automatically.)
- hPanel → **Advanced → PHP Configuration** → set **PHP 8.1 or higher**.

---

## Step 6 — First visit (creates tables + admin login)

1. Go to **`https://yourdomain.com/admin/`** once. This builds the database tables
   and seeds the starter content.
2. Log in with the `admin.email` / `admin.password` from Step 4.
3. Go to **Settings** and change your password.

If `/admin/` shows a 500 error, it's almost always a wrong database value in
`config.php` (see Troubleshooting).

---

## Step 7 — Chatbot (optional)

1. Get a free key at <https://console.groq.com/keys>.
2. In `config.php` → `groq`, paste it into `api_key` and set `enabled => true`.
3. In **Admin → AI Assistant**, fill the persona + the two knowledge boxes (the bot
   only answers from these), then tick **"Show the chatbot"**.

> Keep the knowledge text concise — the free tier has daily limits.

---

## Quick go-live check

- [ ] Home, About, Contact, Blog, Books all open over **https** (padlock shows).
- [ ] Contact form sends and the message appears in **Admin → Contacts**.
- [ ] Newsletter signup shows up in **Admin → Subscribers**.
- [ ] `/admin/` asks for login; you can publish a blog post and see it on `/blog`.
- [ ] Chatbot replies (if enabled).

---

## Updating later

- **Blog posts, books, settings:** edit live in **/admin/** — no re-upload needed.
- **Design / pages / CSS:** edit `src/` on your computer → `npm run build` →
  re-upload the changed files from `_site/` to `public_html` (overwrite). Don't touch
  `backend/` or `config.php` for design-only changes.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| `/admin/` or `/blog` → **500 error** | Wrong DB values in `config.php`, or `driver` still `sqlite`. Recheck Step 2 values. Set `'debug' => true` to see the message, then set it back to `false`. |
| `/blog`, `/books`, `/api/*` give **404** | `.htaccess` is missing or in the wrong place. It must be `public_html/.htaccess` with the contents of `backend/deploy/.htaccess`. |
| **Chatbot** says "trouble answering" | Check the Groq `api_key` and `model` in `config.php`. On a fresh server, also confirm SSL/CA is available (Hostinger has this by default). |
| Contact emails go to **spam / never arrive** | In `config.php` enable SMTP: set `smtp.enabled => true` with a mailbox you create in hPanel → **Emails** (`smtp.hostinger.com`, port `465`, `ssl`). |
| **CSS / images missing** | You uploaded the `_site` folder instead of its contents. Files must sit at `public_html/` top level, not `public_html/_site/`. |
| Image upload in admin **fails** | Set `public_html/backend/uploads/` permissions to `755` (File Manager → right-click → Permissions). |
| Changes **don't show** | Hard refresh the browser (Ctrl+F5). |

---

## Already handled for you (security)

- Admin passwords are hashed; the admin panel is login-gated and `noindex`.
- `config.php`, `lib/`, `data/`, and database files are blocked from the web.
- Uploads are checked by file type and can't run as code.
- Keep `'debug' => false` in production.
