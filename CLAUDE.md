# CLAUDE.md: James Kellie (React) theme

React version of the `jameskellie-theme` PHP theme. It uses **the same data** (`jk_*` options, `_jk_appointment` post meta, `config/fields.php` and `config/defaults.php`) and the **same design**, so you can switch between the two themes and the content carries over.

## Architecture

- **Front end is a React SPA** (`src/main.jsx` → `src/App.jsx`, React Router). PHP renders no body markup.
  - `index.php` is the only template. It prints `wp_head()` (SEO meta, JSON-LD, fonts, Custom Code), `<div id="jk-root">`, the boot JSON (`JK\Boot::print_data()`) and a `<noscript>` fallback.
  - `templates/page-appointment.php` exists only so "Book Appointment" shows up in the page template list. It includes `index.php`.
  - `JK\Boot` builds `{ site, route }` from the real main query: route types are `home`, `appointment`, `page`, `posts` and `404`. **Never put secrets (Turnstile secret) or Code-tab snippets in it.**
  - SPA navigation fetches the real URL with `X-JK-Spa: 1`. `JK\Spa` returns `{ route, title, head, bodyClass }` as JSON, so redirects and 404s still work. The client syncs title and SEO tags from `head` (`src/lib/navigation.js`).
  - Sections: `src/sections/*` (Home), `src/pages/*` (route types), `src/components/*` (interactive pieces), `src/layout/*` (header, footer, back-to-top).
- **Theme Settings admin is React** (`src/admin/main.jsx` → `AdminApp.jsx`, `fields.jsx`). `JK\Admin\Menu` registers the menu and prints the mount point, and `Menu::config()` passes the schema and nav to the app as `window.JKSettings`.
  - REST: `jk/v1/admin/settings/{section}` (GET/POST), `…/{section}/reset`, `jk/v1/admin/export`, `jk/v1/admin/import`, all `manage_options`. Saving goes through `update_option`, so the registered `Sanitizer::option` still runs.
  - The class names match the old FieldRenderer markup, so `src/admin/admin.css` (Tailwind, `tw:` prefix) styles both.
- **Captcha** (James Kellie → Captcha, option `jk_captcha`): provider `none`, `turnstile` or `recaptcha` (v2/v3). `JK\Captcha` verifies server-side; `src/lib/captcha.js` (`useCaptcha`) loads the widget on first focus of the form. The browser only gets `{provider, siteKey, version}`. `JK_CAPTCHA_DISABLED` in wp-config is the kill switch. Old Turnstile keys from `jk_general` are moved once by `Admin\Seeder::migrate_captcha()`. Fields can use `show_if: { key: value }` in `config/fields.php` (React admin only).
- **Live preview** (every Home section's settings screen): `src/admin/Preview.jsx` iframes `/?jk_preview={key}` (administrators only, noindex, no admin bar), where `src/preview/PreviewApp.jsx` renders that one section with the real component. Unsaved values go to `POST jk/v1/admin/preview/{key}` → `Boot::preview_data()` (sanitised, never saved) and are posted into the frame. Links and form submits are blocked inside the preview. Sections are listed in `Boot::PREVIEW_SECTIONS`.
- **Still PHP:** the Book Appointment meta box (`AppointmentMetaBox` + `FieldRenderer` + `src/admin/metabox.js`), Enquiries CPT, contact endpoint `jk/v1/contact`, Performance, Custom Code, SEO. WordPress cannot load a theme without PHP.

## Rules

- PHP 8.1+, namespace `JK\`, WPCS: escape output, sanitise input, and add capability checks on every write.
- React 18 JSX, Tailwind v4. No UI kits, jQuery or plugins (no ACF).
- **Tailwind class scanning:** keep utility classes as whole words in string literals. `` `object-bottom${x}` `` is NOT detected. Write `'a b' + (x ? ' c' : '')` or a ternary of full strings.
- Settings schema changes go in `config/fields.php` (and defaults in `config/defaults.php`). Keep both themes in sync if both stay in use.
- Git lives in this folder. Work on `feature/react-theme`, never commit to `main`, and add no AI attribution.

## Commands

```bash
composer install && npm install
npm run dev     # Vite :5173, writes /hot so PHP enqueues from it
npm run build   # dist/ with manifest
composer lint   # PHPCS (WPCS)
npm run version:patch|minor|major   # bumps style.css, JK_VERSION and package.json together
npm run bundle  # build + release/jameskellie-vX.Y.Z.zip (refuses with hot present or versions out of step)
```
