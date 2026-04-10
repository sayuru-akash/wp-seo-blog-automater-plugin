# AGENTS.md

## Overview

WP SEO Blog Automater is a WordPress plugin that generates long-form SEO posts in the admin area, enriches them with metadata and optional Unsplash images, and publishes the result into WordPress. The repository also includes its own GitHub-based update path and version-aware release packaging flow.

## Folder Structure

- `wp-seo-blog-automater.php`: plugin bootstrap, constants, lifecycle hooks, textdomain loading, updater registration, schema injection.
- `includes/`: runtime PHP classes.
- `includes/class-wp-seo-automater-admin.php`: main admin controller for menus, settings handling, AJAX endpoints, content parsing, publishing, and activity logging.
- `includes/class-gemini-api-handler.php`: Gemini request construction, continuation loop handling, and response extraction.
- `includes/class-github-updater.php`: GitHub release lookup, WordPress plugin update integration, and post-install cleanup.
- `admin/partials/`: admin page templates; these are display-oriented and rely on the admin class for data and actions.
- `admin/js/admin.js`: jQuery-driven admin interactions for generate, publish, discard, and update-check flows.
- `admin/css/style.css`: plugin admin styling.
- `languages/`: translation template.
- `scripts/`: release/version utilities used by local validation and GitHub Actions.
- `.github/workflows/`: version-aware build and release workflow.
- `.githooks/`: local `pre-push` hook that only runs release validation when the plugin version changes.
- `tests/`: manual verification harness and debug scripts. Includes fixture-driven preview checks, Gemini continuation simulation, and an env-driven live preview script.
- `dist/`: packaged release output.
- `.agents/skills/`: local Codex skill material; useful context, but not part of the distributable plugin.

## Core Behaviors & Patterns

- Bootstrap is centralized in `wp-seo-blog-automater.php`. That file defines all plugin constants, loads includes, registers activation/deactivation hooks, and wires the admin class and GitHub updater into WordPress hooks.
- The main content flow is admin AJAX driven. `admin/js/admin.js` posts user input to `wp_ajax_wp_seo_generate_post`, `WP_SEO_Automater_Admin::ajax_generate_post()` sanitizes input and loads the saved master prompt, `Gemini_API_Handler::generate_article()` calls Gemini and stitches continuation chunks together, then the admin class parses slug/meta/schema/image fields from the generated text and returns a structured JSON payload that the JS uses to populate the editor state.
- Publishing is a second AJAX path. The browser submits the edited fields, the admin class validates and sanitizes them, creates the post, writes SEO/schema-related metadata, handles optional image sideloading, and returns the published post URL for the UI success state.
- Logging is first-class and used across generation, image fetches, continuation loops, and update checks. New behavior should keep the existing pattern of recording meaningful steps with `WP_SEO_Automater_Admin::log_activity()` instead of failing silently.
- The updater path is independent from wordpress.org. `WP_SEO_Automater_GitHub_Updater` hooks `pre_set_site_transient_update_plugins` and `plugins_api`, reads the latest GitHub release, compares tag version to `WP_SEO_AUTOMATER_VERSION`, and supplies a downloadable ZIP package. Post-install logic clears both plugin-specific and WordPress update caches so stale update notices do not persist.
- Release packaging is version-driven from the plugin header file. `scripts/get-version.php` reads the plugin header and version constant, `build.sh` and `build.bat` derive artifact names from that value, and `scripts/release-build-check.sh` performs syntax checks, builds the ZIP, and validates archive contents. The GitHub workflow only publishes a release when the pushed range actually changes the plugin version.
- Local verification now has a dedicated CLI path in `tests/`. `tests/run-fixture-preview.php` validates the preview payload against saved raw responses, `tests/run-gemini-continuation.php` validates chunk stitching behavior, and `tests/run-live-preview.php` runs a live Gemini request and writes the exact box payload to `tests/output/live-preview.json`.

## Conventions

- Treat `wp-seo-blog-automater.php` as the source of truth for releases. When bumping a version, update both the `Version:` header and `WP_SEO_AUTOMATER_VERSION` in that file. Do not add version literals back into build scripts.
- Keep WordPress naming prefixes consistent:
  - constants: `WP_SEO_AUTOMATER_*`
  - options/meta keys/actions: `wp_seo_automater_*`
  - main classes: `WP_SEO_Automater_*`
- Use WordPress guard patterns at file entry points: `if ( ! defined( 'ABSPATH' ) ) { ... }` or equivalent uninstall checks.
- Keep templates in `admin/partials/` focused on rendering. Move request handling, persistence, parsing, and cross-cutting logic into `WP_SEO_Automater_Admin`.
- Follow existing WordPress sanitization/escaping style. Sanitize request input as early as possible, escape on output in partials, and use `WP_Error` for recoverable failures that need to surface to AJAX responses.
- Preserve existing response shapes consumed by `admin/js/admin.js`. Frontend behavior expects fields such as `content`, `slug`, `schema`, `meta_title`, `meta_desc`, `image_url`, `image_credit`, and `debug_info`.
- The JavaScript layer is jQuery-based and imperative. Match the existing style when making small edits rather than partially rewriting flows into a different frontend pattern.
- `tests/` is still a manual/env-driven verification harness, not a reliable automated test suite. Describe it that way unless a real automated runner is added.

## Working Agreements

- Prefer minimal, targeted changes. This plugin has a few dense controller files, so avoid broad refactors unless the task explicitly requires them.
- When changing generation or publishing behavior, trace the full flow across PHP handler, parsed response shape, and `admin/js/admin.js` before editing. Most regressions here are contract mismatches between server and admin UI.
- When touching generation parsing or continuation behavior, run `php tests/run-fixture-preview.php` and `php tests/run-gemini-continuation.php`. If API keys are available, also run `php tests/run-live-preview.php` to inspect the exact payload that will populate the editor box.
- When changing updater behavior, reason about both caches: the plugin transient (`wp_seo_automater_github_release`) and WordPress plugin update cache (`update_plugins`).
- When preparing a release-related change, run `./scripts/release-build-check.sh` locally. For package-only output, run `./build.sh`.
- Keep distribution boundaries intact. Files under `.github/`, `.githooks/`, `.agents/`, `tests/`, and build helper docs should not end up in the packaged plugin ZIP.
- Preserve option names, AJAX action names, and post meta keys unless the task includes a migration plan. Existing installs depend on those identifiers remaining stable.
- If a change touches admin copy or user-facing strings, keep textdomain usage intact so `languages/wp-seo-blog-automater.pot` can stay authoritative.
