# Copilot instructions for WP SEO Blog Automater

## Project context
- This repository is a WordPress plugin (`wp-seo-blog-automater.php`) that generates SEO blog content with Gemini and optional Unsplash images.
- Core logic lives in:
  - `/includes/class-wp-seo-automater-admin.php`
  - `/includes/class-gemini-api-handler.php`
  - `/includes/class-github-updater.php`
- Admin UI assets and templates live in:
  - `/admin/partials/`
  - `/admin/js/admin.js`
  - `/admin/css/style.css`

## Coding expectations
- Follow WordPress coding standards:
  - Use tabs for indentation in PHP.
  - Use Yoda conditions where comparisons are used.
  - Use proper sanitization/escaping and nonce/capability checks for admin/AJAX flows.
- Keep changes minimal and scoped to the requested task.
- Do not rename plugin options, hooks, AJAX actions, or text domain (`wp-seo-blog-automater`) unless explicitly requested.

## Validation commands
- Use the existing build script to validate packaging changes:
  - `./build.sh` (Linux/macOS)
- There is no established automated unit test suite in this repository at the moment; rely on focused build/manual verification for changes.

## Documentation and release hygiene
- For user-facing changes, update `README.md` and `CHANGELOG.md` when appropriate.
- Keep release/version references consistent with:
  - `wp-seo-blog-automater.php`
  - `build.sh`
  - `build.bat`
