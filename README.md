# Personal Brand Website with a Lightweight PHP CMS

A fast, statically generated website for an author and speaker, paired with a small self-contained
PHP backend that lets the owner edit the site without touching code. The front end is built with
Eleventy; the admin side is plain PHP with no framework, so it runs comfortably on ordinary shared
hosting.

> This is a sanitised demo build. It started life as a real client project, but every name, bio,
> book, testimonial, image, contact detail, and API key has been replaced with fictional
> placeholders. The persona here ("Adrian Cole") is invented. The point of the repo is to show the
> structure and engineering, not the original site.

**Live demo:** https://umairahmedazizi.github.io/personal-brand-cms/ — the static front end, hosted
on GitHub Pages. The PHP admin backend isn't running there (Pages can't run PHP); it needs a normal
PHP host.

## What it does

**Front end (static)**
- Built with Eleventy (11ty) and Nunjucks templates, shipped as plain HTML/CSS/JS.
- Home, About, Books, a dedicated book landing page, Blog, and Contact.
- SEO-minded: per-page meta, Open Graph, JSON-LD structured data, sitemap, and an Atom feed.
- Lightweight vanilla-JS interactions (reveal-on-scroll, parallax, mobile menu).

**Back end (the page editor)**
- A small custom PHP admin panel behind a login.
- Manage blog posts, books, contact submissions, and newsletter subscribers.
- Image uploads, a settings screen, and an optional AI chat endpoint (Groq) for the site.
- Works with SQLite locally and MySQL in production, selected in one config file.

## Tech

Eleventy, Nunjucks, vanilla JavaScript, and PHP 8 with SQLite or MySQL.

## Project structure

```
personal-brand-cms/
  src/            Eleventy source: pages, templates, data, CSS, JS
  assets/img/     images (placeholder graphics in this demo)
  backend/        the PHP admin panel, API endpoints, and library code
  backend/schema.sql   database schema
  .eleventy.js    Eleventy config
```

## Running it locally

Front end:

```bash
npm install
npx @11ty/eleventy --serve
```

Back end (optional, for the editor):

```bash
cd backend
cp config.sample.php config.php   # then fill in your own values
php -S localhost:8000
```

## Notes on configuration and secrets

Nothing sensitive is committed. `backend/config.php` (database credentials, mail, and API keys),
the local database under `backend/data/`, and uploaded files are all git-ignored. Use
`backend/config.sample.php` as the template — every value in it is a placeholder.
