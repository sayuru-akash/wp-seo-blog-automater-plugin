# Changelog

All notable changes to WP SEO Blog Automater will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
