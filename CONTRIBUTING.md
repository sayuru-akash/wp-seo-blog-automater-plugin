# Contributing to WP SEO Blog Automater

Thanks for considering contributing! Whether it's a bug report, feature idea, or code patch, all contributions are welcome.

## Quick Start

1. **Fork** the repository
2. **Clone** your fork locally
3. **Create a branch** for your changes: `git checkout -b fix-something`
4. **Make your changes** following the existing code style
5. **Test** your changes locally
6. **Submit a pull request** with a clear description

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/wp-seo-blog-automater-plugin.git

# Symlink to your WordPress plugins directory (or just copy)
ln -s /path/to/wp-seo-blog-automater-plugin /path/to/wordpress/wp-content/plugins/
```

That's it. No build step needed - this is plain PHP, CSS, and jQuery.

## What to Work On

- **Bug fixes** - Check the [issues](../../issues) for bugs marked `good first issue`
- **Features** - Open an issue first to discuss before coding
- **Documentation** - Typos, clarity improvements, examples

## Code Style

We follow basic WordPress conventions:

- **PHP**: Use tabs for indentation (WordPress standard)
- **PHP**: Prefix everything with `wp_seo_automater_` (functions) or `WP_SEO_AUTOMATER_` (constants)
- **PHP**: Sanitize inputs early, escape output late
- **PHP**: Use nonces and `current_user_can()` checks for AJAX handlers
- **JS**: Match the existing jQuery style in `admin/js/admin.js`
- **CSS**: Use the existing CSS variables in `admin/css/style.css`

Example:
```php
// Good - follows existing patterns
function wp_seo_automater_my_feature() {
    // Check permissions first
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Sanitize input
    $title = sanitize_text_field( $_POST['title'] );
    
    // ... your logic
    
    // Escape output
    echo '<h1>' . esc_html( $title ) . '</h1>';
}
```

## Testing Your Changes

Since this plugin has manual verification scripts (not a formal test suite), please:

1. **Test the actual feature** you changed in WordPress admin
2. **Check browser console** for JavaScript errors
3. **Run the local verification scripts** if you have API keys:
   ```bash
   php tests/run-fixture-preview.php
   php tests/run-gemini-continuation.php
   ```
4. **Test with WP_DEBUG enabled** in your `wp-config.php`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   ```

## Commit Messages

Clear, descriptive messages are appreciated. No strict format required, but something like:

```
Fix image fetch when Unsplash returns empty results

The image fetch would fail silently when Unsplash had no results.
Now it logs a clear message and continues without an image.
```

Or just `Fix typo in settings page` - that's fine too for small stuff.

## Pull Requests

**For small fixes** (typos, simple bugs):
- Just open the PR with a brief description

**For larger changes** (new features, refactors):
- Open an issue first to discuss
- Describe what you changed and why
- Mention any breaking changes

Don't worry about:
- ❌ Updating CHANGELOG.md (maintainer handles releases)
- ❌ Formal PR templates (just describe your change)
- ❌ Testing on 5 different browsers (Chrome/Firefox is enough)

## Need Help?

- Open an issue with your question
- Tag it with `question`

## License

By contributing, you agree your contributions will be licensed under GPL-2.0+ (same as WordPress).

---

Thanks for helping make this plugin better! 🎉
