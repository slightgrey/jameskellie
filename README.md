# James Kellie (React) theme

A WordPress theme for jameskellie.com.au in which the whole front end and the Theme Settings admin are React apps. It reads the same settings as the `jameskellie-theme` PHP theme, so you can switch themes without re-entering content.

## Setup (Laragon)

1. `composer install && npm install`
2. `npm run build` (or `npm run dev` while working; it needs `jameskellie.test` and Vite on port 5173)
3. Go to **Appearance → Themes** and activate **James Kellie (React)**.
4. Pretty permalinks must be `/%postname%/`.
5. Menus are stored per theme. Under **Appearance → Menus**, assign a menu to **Primary**, or leave it empty to get the automatic Services / Experience / Skills / Contact links.

## Editing content

- **James Kellie → General … Custom Code**: the React settings app. Changes save over the REST API, and the unsaved-changes dot shows anything not yet saved.
- **Pages → Book an Appointment**: the Book Appointment box on the page edit screen.
- **Export / Import** (on the General screen) uses the same JSON format as the PHP theme.

## Deploying

Run `npm run build` and upload the theme without `node_modules/` and `vendor/`. `dist/` must be uploaded. The Vite `base` in `vite.config.js` expects the folder to be named `jameskellie`.

## SEO note

Pages are rendered in the browser. The `<head>` (title, description, canonical, Open Graph, JSON-LD, GTM) is still printed by PHP on every URL, and Google renders JavaScript. A small `<noscript>` block covers visitors without JavaScript.
