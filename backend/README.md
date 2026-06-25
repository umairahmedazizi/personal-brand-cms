# Backend

PHP backend for the Adrian Cole site. Plain PHP + PDO, no Composer, runs on
Hostinger shared hosting.

What it does:

- Contact form (stores messages, emails you, auto-reply)
- Newsletter signups (with CSV export)
- Blog CMS (write posts from the admin, they go live instantly)
- Books CMS (each book gets a card on /books and its own page)
- AI chatbot (Groq, optional, needs a free key)
- Admin panel for all of the above

The blog and books pages are rendered by PHP using the same CSS classes as the
static site, so new content matches the rest of the site with no rebuild.

## Layout

```
backend/
  config.php          your settings (db, email, admin, groq). git-ignored.
  config.sample.php   template
  router.php          local dev router for `php -S`
  schema.sql          mysql reference (tables are auto-created anyway)
  lib/                db, auth, mailer, helpers, layout, settings
  api/                contact, newsletter, chat
  admin/              the admin panel
  uploads/            uploaded images (git-ignored)
  data/               local sqlite file (git-ignored)
  deploy/.htaccess    production rewrite rules for public_html
```

## Run locally

Needs PHP 8 with pdo_sqlite, mbstring, fileinfo. From the project root:

```
npm run build
php -S localhost:8000 backend/router.php
```

Open http://localhost:8000/admin/ and log in (default `admin@adriancole.example`
/ `admin123`). The database is a sqlite file created on first load and seeded with
the current books and a sample post. Delete `backend/data/app.sqlite` to reset.

## Deploy to Hostinger

No SSH needed, done from hPanel + File Manager.

1. Create a MySQL database and user in hPanel (note the name/user/pass/host).
2. Build the static site (`npm run build`) and upload the contents of `_site/`
   into `public_html`. Upload the `backend/` folder into `public_html/backend`.
   Copy `backend/deploy/.htaccess` to `public_html/.htaccess`.
3. Edit `public_html/backend/config.php`:
   - `db.driver` = `mysql`, fill the `mysql_*` values
   - `site.url` = your domain
   - `mail.admin_to` / `mail.from`
   - `admin.email` / `admin.password`
   - `debug` = false
4. Visit `/admin/` once to create the tables and admin account. Log in and change
   the password under Settings.

If contact emails land in spam, set `mail.smtp.enabled = true` and fill the smtp
block with a mailbox created in hPanel.

## Chatbot (Groq)

Off until a Groq key is set. The browser only talks to `/api/chat.php`, which holds
the key, adds the knowledge, rate-limits per visitor, and calls Groq.

1. Get a free key at console.groq.com/keys.
2. In `config.php`, under `groq`, set `api_key` and confirm `model` against
   console.groq.com/docs/models.
3. In Admin > AI Assistant, write the persona and the two knowledge boxes (the bot
   only answers from these), then tick "Show the chatbot".

The knowledge is sent with every question, so keep it concise — the free tier has
daily token limits.

## Notes

- Passwords are bcrypt hashed. Admin panel is noindex and login-gated, forms are
  CSRF protected.
- `config.php`, `lib/`, `data/` and uploads are blocked from the web in `.htaccess`.
- Uploads are validated by mime type and can't run as PHP.
