# Contributing to WP SEO Blog Automater

First off, thank you for considering contributing to WP SEO Blog Automater! It's people like you that make this plugin better for everyone.

## 🎯 Where to Start

- **Bug Reports**: Found a bug? Open an issue with details
- **Feature Requests**: Have an idea? Share it in the issues
- **Code Contributions**: Fork, code, and submit a pull request
- **Documentation**: Help improve our docs
- **Translations**: Help translate the plugin

## 📋 Development Setup

### Prerequisites

- PHP 7.4 or higher
- WordPress 5.8 or higher
- Composer (optional, for development dependencies)
- Node.js and npm (optional, for asset compilation)

### Local Development

```bash
# Clone the repository
git clone https://github.com/codezela/wp-seo-blog-automater.git

# Create a symlink in your WordPress plugins directory
ln -s /path/to/wp-seo-blog-automater /path/to/wordpress/wp-content/plugins/

# Activate the plugin in WordPress
```

## 🔧 Coding Standards

### WordPress Coding Standards

We follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/):

- Use tabs for indentation
- Space after control structures
- Yoda conditions for comparisons
- Proper inline documentation

### PHP Standards

```php
// Good
if ( $condition ) {
    do_something();
}

// Good - Yoda conditions
if ( 'value' === $variable ) {
    do_something();
}

// Good - Documentation
/**
 * Function description.
 *
 * @param string $param Parameter description.
 * @return bool Return description.
 */
function my_function( $param ) {
    // Function code
}
```

### JavaScript Standards

- Use ES5 for compatibility
- jQuery for DOM manipulation
- Strict mode
- Proper error handling

### CSS Standards

- Use CSS custom properties (variables)
- Mobile-first responsive design
- BEM-like naming for components
- No !important unless absolutely necessary

## 🔒 Security Best Practices

### Required for All Code

- **Nonces**: Use WordPress nonces for form submissions
- **Capability Checks**: Always verify user permissions
- **Input Sanitization**: Sanitize all user input
- **Output Escaping**: Escape all output
- **Prepared Statements**: Use $wpdb->prepare() for queries

### Example

```php
// Verify nonce
check_ajax_referer( 'wp_seo_automater_nonce', 'nonce' );

// Check capabilities
if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( __( 'Permission denied.', 'wp-seo-blog-automater' ) );
}

// Sanitize input
$title = sanitize_text_field( $_POST['title'] );

// Escape output
echo '<h1>' . esc_html( $title ) . '</h1>';
```

## 📝 Commit Messages

### Format

```
type(scope): subject

body

footer
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples

```
feat(generator): add bulk content generation support

Implemented ability to generate multiple articles at once.
Users can now upload CSV with topics and keywords.

Closes #123
```

```
fix(api): handle timeout errors gracefully

Added proper error handling for API timeout scenarios.
Shows user-friendly message instead of generic error.

Fixes #456
```

## 🧪 Testing

### Manual Testing Checklist

- [ ] Test in WordPress 5.8+
- [ ] Test in PHP 7.4 and 8.0+
- [ ] Test with Yoast SEO active
- [ ] Test with Rank Math active
- [ ] Test with no SEO plugin
- [ ] Test in Chrome, Firefox, Safari
- [ ] Test mobile responsiveness
- [ ] Test with WP_DEBUG enabled

### Error Checking

```php
// Enable debugging in wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

## 📚 Documentation

### Inline Documentation

- Use PHPDoc format for all functions
- Include @since tags
- Document parameters and returns
- Add examples for complex functions

### User Documentation

- Update README.md for user-facing changes
- Add screenshots for UI changes
- Update CHANGELOG.md

## 🌐 Internationalization

### Text Domain

Always use `wp-seo-blog-automater` text domain:

```php
// Correct
__( 'Text to translate', 'wp-seo-blog-automater' );
_e( 'Text to translate', 'wp-seo-blog-automater' );
esc_html__( 'Text to translate', 'wp-seo-blog-automater' );

// Incorrect - missing text domain
__( 'Text to translate' );
```

### Translation Functions

- `__()` - Returns translated string
- `_e()` - Echoes translated string
- `esc_html__()` - Returns escaped translated string
- `esc_attr__()` - Returns attribute-escaped translated string
- `_n()` - Plural forms

## 🚀 Pull Request Process

### Before Submitting

1. Update documentation
2. Add/update tests if applicable
3. Update CHANGELOG.md
4. Ensure code follows standards
5. Test thoroughly

### PR Template

```markdown
## Description

Brief description of changes

## Type of Change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing

- [ ] Tested in WordPress 5.8+
- [ ] Tested in PHP 7.4+
- [ ] Tested with SEO plugins

## Checklist

- [ ] Code follows project standards
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] No console errors
- [ ] Mobile responsive
```

### Review Process

1. Maintainer reviews code
2. Automated checks run
3. Request changes if needed
4. Merge when approved

## 🐛 Bug Reports

### Good Bug Report Includes

- Clear title
- Steps to reproduce
- Expected behavior
- Actual behavior
- Screenshots (if applicable)
- Environment details:
  - WordPress version
  - PHP version
  - Active theme/plugins
  - Browser (for UI issues)

### Template

```markdown
**Bug Description**
Clear description of the bug

**Steps to Reproduce**

1. Go to...
2. Click on...
3. See error...

**Expected Behavior**
What should happen

**Actual Behavior**
What actually happens

**Environment**

- WordPress: 6.0
- PHP: 8.0
- Plugin Version: 1.0.4
- Browser: Chrome 120

**Screenshots**
If applicable
```

## 💡 Feature Requests

### Good Feature Request Includes

- Clear use case
- Problem it solves
- Proposed solution (optional)
- Alternative solutions considered

## 📬 Contact

- **Email**: support@codezela.com
- **Website**: https://codezela.com
- **Issues**: GitHub Issues

## 📄 License

By contributing, you agree that your contributions will be licensed under the GPL-2.0+ License.

---

## Recognition

Contributors will be recognized in:

- README.md contributors section
- CHANGELOG.md for specific contributions
- Plugin credits page (future feature)

Thank you for contributing to WP SEO Blog Automater! 🎉

**- The Codezela Technologies Team**
