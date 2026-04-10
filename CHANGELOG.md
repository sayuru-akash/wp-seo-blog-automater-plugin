# Changelog

All notable changes to WP SEO Blog Automater will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.8] - 2026-04-10

### 🌐 Search Submission Release

Patch release focused on adding official search-engine submission helpers for published posts and pages.

### Added

- 📬 **Posts & Pages Bulk Actions**: Added `Submit to IndexNow`, `Resubmit Sitemap to Google`, and `Check Google Index Status` to both the Posts and Pages admin list screens
- 🔑 **IndexNow Key Management**: Added Settings support for saving or generating an IndexNow key and serving the required verification file directly from the plugin at `/<key>.txt`
- 🔐 **Google Search Console Credentials**: Added Settings fields for a Search Console property, service account JSON, and optional sitemap URL overrides

### Improved

- 🪵 **Bulk Action Reporting**: Added per-run admin notices plus Activity Log summaries for IndexNow submissions, sitemap resubmissions, and Google index inspections
- 🧹 **Uninstall Cleanup**: Added cleanup for the new search-engine submission options on uninstall
- 📘 **Documentation**: Documented the new search-engine submission setup and usage flow in the repo docs

### Technical

- Updated plugin version constants and release metadata to `1.3.8`
- Added service-account JWT authentication for Google Search Console API calls
- Added official-safe Google support boundaries: sitemap submission and URL inspection only, not a fake generic "index now" action for ordinary content

### Verification

- `php -l includes/class-wp-seo-automater-admin.php`
- `php -l admin/partials/settings-display.php`
- `php -l admin/partials/system-info-display.php`
- `php -l uninstall.php`
- `./scripts/release-build-check.sh`

## [1.3.7] - 2026-04-10

### 🖼️ Image Refresh Release

Patch release focused on making featured image selection more controllable and less repetitive inside the generator UI.

### Added

- 🔁 **Refresh Image Button**: Added a small `Refresh Image` action next to the Unsplash preview so users can request a new featured image without regenerating the article
- 🧠 **Three-Stage Image Query Flow**:
  - Stage 1 uses the article's primary `Image Search Keywords`
  - Stage 2 asks Gemini for dedicated Unsplash-friendly visual search phrases based on the generated content
  - Stage 3 falls back to generic title/meta-derived rescue queries only if the first two stages fail

### Improved

- 🚫 **Repeat Image Avoidance**: Image refresh now tracks previously returned Unsplash photo IDs and skips them on subsequent refresh attempts
- 🔍 **Richer Unsplash Search**: Queries now inspect multiple Unsplash results per request instead of only the first returned image
- 🪵 **Debug Visibility**: The image query source and query attempts remain visible in the preview payload for troubleshooting

### Technical

- Updated plugin version constants and release metadata to `1.3.7`
- Added a new AJAX endpoint for image-only refreshes from the generator preview

### Verification

- `php -l includes/class-wp-seo-automater-admin.php`
- `php -l includes/class-gemini-api-handler.php`
- `./scripts/release-build-check.sh`

## [1.3.6] - 2026-04-10

### ⚡ Manual Update Check Release

Patch release focused on making the in-plugin update check reflect the latest GitHub release immediately.

### Fixed

- 🔄 **Synchronous Manual Update Check**: The "Check Updates Now" button now forces a real GitHub fetch instead of only scheduling a background check
- 📣 **Immediate Plugins Page Refresh**: After a manual check, the plugin now rebuilds the WordPress `update_plugins` transient so the Installed Plugins screen can show the update right away

### Technical

- Updated plugin version constants and release metadata to `1.3.6`
- Kept the updater source on GitHub `releases/latest`; latest published release remains the only release channel

### Verification

- `php -l includes/class-wp-seo-automater-admin.php`
- `./scripts/release-build-check.sh`

## [1.3.5] - 2026-04-10

### 🔧 Generation Reliability Release

Patch release focused on stabilizing long-form generation output, preserving the admin preview payload contract, and improving image lookup recovery.

### Fixed

- 🧵 **Continuation Loop Handling**: Fixed Gemini continuation logic so the plugin stops requesting extra chunks once the latest chunk no longer contains the pause marker
- 🧹 **Trailing Assistant Chatter Cleanup**: Strips recap text, "new topic" offers, and CMS insertion instructions that were sometimes appended after the finished article/schema
- 📦 **Shared Preview Payload Path**: The admin AJAX generator and local verification scripts now use the same parsing path, reducing contract drift for the editor box

### Improved

- 🖼️ **Unsplash Fallback Search**: Added broader fallback queries and orientation retries when the first image search returns zero results
- 🧪 **Local Verification Harness**: Added fixture-driven preview checks, continuation simulation, and env-driven live preview verification scripts
- 📝 **Documentation**: Added `.env.example` and documented the local verification flow in the repo docs

### Technical

- Updated plugin version constants and release metadata to `1.3.5`
- Added debug visibility for image query attempts in the preview payload
- Preserved the existing master prompt depth while adding stricter output boundary handling

### Verification

- `php tests/run-fixture-preview.php`
- `php tests/run-gemini-continuation.php`
- `php tests/run-live-preview.php`
- `./scripts/release-build-check.sh`

## [1.1.0] - 2026-01-20

### 🎨 UI/UX Improvements Release

Minor version bump with critical bug fixes and visual improvements.

### Fixed

- 🐛 **Critical AJAX Fix**: Resolved "Network error" when clicking "Check for Updates Now" button
  - Changed `get_github_release()` from private to public (was causing fatal PHP error)
  - Fixed return type from `false` to `WP_Error` for proper error handling
  - Fixed response handling from array syntax to object property access (`$release['tag_name']` → `$release->tag_name`)
  - Added proper error handling in `check_for_update()` method
- 🎨 **Button Hover Fix**: Fixed white secondary buttons where text became invisible on hover
  - Changed hover state to maintain white background with better contrast
  - Text color now uses `var(--primary-dark)` instead of `var(--primary-color)` on hover
  - Enhanced shadow on hover for better visual feedback
  - Fixed ::before pseudo-element gradient for smoother transitions

### Changed

- 🔧 Enhanced error debugging in JavaScript with detailed console logging
- 📝 Improved error messages to include browser console reference

### Added

- ✅ **Test Suite**: Created `tests/test-github-api.php` for API connectivity verification
  - Standalone PHP script to test GitHub API endpoint
  - Validates response structure and asset availability
  - Helps diagnose update system issues

### Technical

- Updated all version numbers from 1.0.8 to 1.1.0
- Enhanced AJAX error callback with xhr, status, and error details
- Improved WP_Error handling throughout the update chain

### Why This Version Bump

Moving to 1.1.0 (minor version) because:

- Contains important bug fixes that affect core functionality
- Includes backward-compatible improvements
- Provides better testing and debugging capabilities
- Follows semantic versioning: MAJOR.MINOR.PATCH

## [1.0.8] - 2026-01-20

### ⚡ Instant Update Check Release

Added immediate update checking functionality for better user control and transparency.

### Added

- ✨ **Instant Update Check**: "Check for Updates Now" button on System Info page
  - Immediate GitHub API check without waiting for 12-hour automatic cycle
  - Clear transient cache and fetch fresh release data
  - Real-time version comparison and display update
  - Loading state with spinning icon for visual feedback
  - Success/error messages with auto-fade after 5 seconds
  - Updates both display and WordPress update system
- 🔄 **Dynamic Version Display**: Version numbers update in real-time after check
- 📝 **Activity Logging**: All manual update checks logged for tracking
- 🎨 **Enhanced UI**: Animated dashicons during loading state

### Changed

- 🔧 System Info page now has interactive update checking
- 🎯 Update status notices refresh dynamically without page reload
- 💫 Better user feedback with immediate visual response

### Technical

- New AJAX handler: `ajax_check_updates_now()` in admin class
- Clears both `wp_seo_automater_github_release` and `update_plugins` transients
- JavaScript handler with error handling and timeout protection
- Added `admin_url` to localized script data for proper URL construction
- CSS animation for spinning dashicons
- Permission check: requires `update_plugins` capability
- Network error handling with user-friendly messages

### Why This Matters

Users wanted immediate control over update checking instead of waiting up to 12 hours for the automatic system. This gives power users the ability to:

- Check for updates on-demand after seeing a GitHub release notification
- Verify update availability immediately after releasing a new version
- Confirm the update system is working correctly
- Get instant feedback without waiting for WordPress cron

### Security

- Nonce verification on AJAX requests
- Capability check for `update_plugins` permission
- Sanitized output and escaped data throughout

## [1.0.7] - 2026-01-20

### 📋 System Info Page Release

Added dedicated System Info page for better update visibility and system monitoring.

### Added

- ✨ **System Info Page**: New menu item showing plugin and system status
  - Current vs. Latest version display
  - Update availability notification with direct link to update
  - System requirements check (PHP, WordPress, API keys)
  - Complete "How Updates Work" guide built-in
  - Where to find updates in WordPress (Dashboard, Plugins page, Toolbar)
  - Update schedule explanation (12-hour checks)
  - Direct links to GitHub releases and WordPress updates page
  - Plugin details display (Name, Author, URI, Text Domain)
- 🎨 **New UI Components**: Info grids, notice boxes, status badges
- 📖 **In-App Documentation**: Users can now see update status without leaving the plugin

### Why This Matters

Users were confused about where to find updates since the GitHub updater integrates with WordPress's native system (not a separate page in the plugin menu). The new System Info page provides clear visibility into:

- Whether an update is available
- Where to go to update (Dashboard → Updates or Plugins page)
- How the automatic update system works
- System compatibility status

### Technical

- New file: `admin/partials/system-info-display.php`
- New menu item: Blog Automater → System Info
- Added CSS for info grids and notice boxes
- Translation-ready with 30+ new strings

## [1.0.6] - 2026-01-20

### 📊 UX Improvements Release

Enhanced logs management and user experience improvements.

### Added

- ✨ **Logs Pagination**: Professional pagination system for activity logs
  - Shows 20 logs per page
  - Previous/Next navigation
  - Page counter (e.g., "Page 1 of 5")
  - Shows log count (e.g., "Showing 1-20 of 87 logs")
  - Newest logs appear first (reversed order)
- 📖 **Update Guide**: Complete UPDATE-GUIDE.md with step-by-step instructions
  - How to manually update from older versions
  - Troubleshooting common update issues
  - FTP/SSH quick update commands

### Changed

- 🎨 Logs table improved with fixed column widths for better readability
- 📊 Empty logs state now centered with better spacing
- 🔄 Logs display newest entries first (auto-reversed)

### Fixed

- 🐛 Logs page now follows WordPress pagination best practices
- 🐛 Fixed infinite scroll issue with large log files
- 🐛 Better performance with hundreds of log entries

### Technical

- Updated translation strings for pagination (Previous, Next, Page X of Y)
- Added pagination CSS styling matching modern design system
- Improved table column width consistency

## [1.0.5] - 2026-01-20

### 🚀 Automatic Updates Release

Major enhancement adding GitHub-based automatic updates and UI improvements.

### Added

- ✨ **GitHub Automatic Updates**: Plugin now checks GitHub releases for updates
  - One-click updates directly from WordPress admin
  - Update notifications appear in WordPress dashboard
  - 12-hour caching to prevent API rate limits
  - Complete documentation in RELEASES.md
- 🎯 **Improved Admin Menu Icon**: Switched from logo image to clean Dashicon (`dashicons-chart-area`)
  - Professional, consistent with WordPress UI
  - No more logo display issues in admin sidebar
- 🛡️ **Function Guards**: Added `function_exists()` checks to prevent redeclaration errors
  - Safer plugin updates
  - Prevents conflicts with old versions
- 📦 **Enhanced Build System**:
  - Only cleans ZIP files from dist/ (preserves other files)
  - Automated GitHub release instructions after build
  - Excludes build artifacts (build.sh, build.bat, BUILD.md, dist/)
- 📖 **Complete Translation Template**: Fully functional POT file with 75+ translatable strings
  - All admin UI strings mapped
  - Proper msgctxt for disambiguation
  - Ready for translation to any language

### Changed

- 🎨 Admin left menu now uses `dashicons-chart-area` instead of logo image
- 🔧 Build script preserves non-ZIP files in dist/ folder
- 📝 Build output now includes GitHub release instructions

### Fixed

- 🐛 Function redeclaration errors when multiple plugin versions exist
- 🐛 Build script no longer removes entire dist/ folder
- 🐛 Logo display issues in WordPress admin sidebar

### Technical

- New file: `includes/class-github-updater.php` - Handles automatic updates
- New file: `RELEASES.md` - Complete release workflow documentation
- Updated: All version references to 1.0.5
- Updated: POT file with complete string extraction

## [1.0.4] - 2026-01-20

### 🎉 Production-Ready Release

This release transforms the plugin from MVP to enterprise-grade, production-ready software with professional polish, security enhancements, and comprehensive documentation.

### Added

- ✨ Complete Codezela Technologies branding throughout
- 🎨 Professional admin UI with responsive design
- 🖼️ Logo integration in admin pages and menu
- 🔒 Comprehensive security hardening:
  - Enhanced nonce validation
  - Improved capability checks
  - Better input sanitization
  - Output escaping throughout
- 🌐 Full internationalization (i18n) support
  - All strings wrapped in translation functions
  - POT file template included
  - Translation-ready structure
- 📚 Professional documentation:
  - Comprehensive README.md
  - CONTRIBUTING.md for developers
  - CHANGELOG.md
  - Inline PHPDoc comments
- ♻️ Proper WordPress lifecycle hooks:
  - Activation hook with default options
  - Deactivation hook with logging
  - Uninstall.php for clean removal
- 🔗 Settings link on plugins page
- 📊 Enhanced activity logging with status types
- ⚡ Performance optimizations
- 🎯 Better error handling and user feedback
- 📱 Mobile-responsive admin interface
- 🎨 Improved CSS with better organization
- 🚀 Enhanced JavaScript with better error handling

### Changed

- 🔄 Updated all function documentation to PHPDoc standard
- 🔄 Improved menu structure with translated strings
- 🔄 Enhanced settings page with better organization
- 🔄 Better admin notices and user feedback
- 🔄 Improved AJAX handlers with timeouts
- 🔄 Updated version to 1.0.4
- 🔄 Plugin headers updated to WordPress standards

### Fixed

- 🐛 Fixed schema injection validation
- 🐛 Improved error messages
- 🐛 Better handling of empty/missing data
- 🐛 Fixed CSS variable usage
- 🐛 Improved responsive design issues

### Security

- 🔒 All AJAX endpoints properly secured
- 🔒 All user inputs sanitized
- 🔒 All outputs escaped
- 🔒 Nonce verification on all actions
- 🔒 Capability checks throughout

## [1.0.3] - 2026-01-XX

### Added

- 🖼️ Unsplash API integration for automatic image fetching
- 📸 Auto-sideloading of images to WordPress media library
- 🎯 Image deduplication to prevent duplicate uploads
- 🏷️ Automatic SEO-optimized alt text for images
- 🔍 Enhanced content extraction logic

### Changed

- 🔄 Improved metadata extraction from AI content
- 🔄 Better handling of long-form content
- 🔄 Enhanced logging system with more details

### Fixed

- 🐛 Fixed metadata extraction bugs
- 🐛 Improved schema JSON validation
- 🐛 Better handling of malformed AI responses

## [1.0.2] - 2026-01-XX

### Added

- 🔗 Rank Math SEO plugin integration
- 🎯 Auto-detection of active SEO plugin
- 📝 Enhanced prompt system

### Changed

- 🔄 Improved admin UI styling
- 🔄 Better error messages

### Fixed

- 🐛 Schema markup issues
- 🐛 Content formatting bugs

## [1.0.1] - 2026-01-XX

### Added

- 📊 Activity logging system
- 🔍 Debug information display

### Changed

- 🔄 Improved API error handling
- 🔄 Better content parsing

### Fixed

- 🐛 Various bug fixes
- 🐛 Improved stability

## [1.0.0] - 2025-12-XX

### 🎉 Initial Release

### Added

- 🤖 Google Gemini AI integration
- ✍️ Automated blog post generation
- 🎯 SEO metadata generation
- 🔗 Yoast SEO integration
- 📊 JSON-LD schema markup
- ⚙️ Customizable master prompt
- 🎨 Clean admin interface
- 📝 Content preview and editing
- 🚀 One-click publishing

---

## Version History Summary

- **1.0.4** - Production-ready release (Current)
- **1.0.3** - Unsplash integration
- **1.0.2** - Rank Math support
- **1.0.1** - Logging system
- **1.0.0** - Initial release

---

**Note**: This changelog follows [semantic versioning](https://semver.org/). For any questions about changes, please contact info@codezela.com.

[1.0.4]: https://github.com/codezela/wp-seo-blog-automater/releases/tag/1.0.4
[1.0.3]: https://github.com/codezela/wp-seo-blog-automater/releases/tag/1.0.3
[1.0.2]: https://github.com/codezela/wp-seo-blog-automater/releases/tag/1.0.2
[1.0.1]: https://github.com/codezela/wp-seo-blog-automater/releases/tag/1.0.1
[1.0.0]: https://github.com/codezela/wp-seo-blog-automater/releases/tag/1.0.0
