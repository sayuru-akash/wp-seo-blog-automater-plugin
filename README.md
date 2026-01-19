# WP SEO Blog Automater

[![Version](https://img.shields.io/badge/version-1.0.4-blue.svg)](https://github.com/codezela/wp-seo-blog-automater)
[![WordPress](https://img.shields.io/badge/WordPress-5.8+-green.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0+-red.svg)](LICENSE)

**Enterprise-grade AI content automation for WordPress. Create high-quality, SEO-optimized blog posts in seconds.**

Developed by [**Codezela Technologies**](https://codezela.com)

---

## 🚀 Features

### Core Functionality

- **🤖 Google Gemini AI Integration** - Powered by the latest Gemini Pro models for sophisticated, human-like content generation
- **📸 Automatic Image Integration** - AI determines optimal search terms and automatically fetches royalty-free images from Unsplash
- **🎯 Complete SEO Automation** - Generates meta titles, descriptions, URL slugs, and structured data (JSON-LD Schema)
- **🔗 SEO Plugin Integration** - Native support for Yoast SEO and Rank Math with auto-detection
- **📊 Activity Logging** - Comprehensive logging system for monitoring all generation activities
- **⚙️ Customizable AI Prompts** - Full control over AI behavior with customizable master prompts

### Technical Excellence

- **Security-First Design** - WordPress nonces, capability checks, and proper data sanitization throughout
- **Production-Ready Code** - Follows WordPress coding standards with comprehensive error handling
- **Internationalization Ready** - Full i18n support for multilingual deployments
- **Clean Architecture** - Well-structured, documented, and maintainable codebase
- **Responsive Admin UI** - Modern, professional interface that works on all devices

---

## 📋 Requirements

- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher (8.0+ recommended)
- **MySQL:** 5.6 or higher (or MariaDB equivalent)
- **Google Gemini API Key:** [Get one free](https://aistudio.google.com/app/apikey)
- **Unsplash Access Key:** [Get one free](https://unsplash.com/developers) (optional, for automatic images)

---

## 📥 Installation

### Method 1: WordPress Admin (Recommended)

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Choose the downloaded ZIP file and click **Install Now**
5. Click **Activate** after installation completes

### Method 2: Manual Installation

1. Download and extract the plugin ZIP file
2. Upload the `wp-seo-blog-automater` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Method 3: WP-CLI

```bash
wp plugin install wp-seo-blog-automater.zip --activate
```

---

## ⚙️ Configuration

### Initial Setup

1. Navigate to **BP Automater → Settings** in your WordPress dashboard
2. Configure the following:

#### API Configuration

- **Gemini API Key** (Required)
  - Get your free key from [Google AI Studio](https://aistudio.google.com/app/apikey)
  - Supports all Gemini models: `gemini-pro-latest`, `gemini-1.5-pro`, `gemini-2.0-flash-exp`
- **Unsplash Access Key** (Optional)
  - Required for automatic featured image integration
  - Get your key from [Unsplash Developers](https://unsplash.com/developers)

#### SEO Plugin Integration

- **Auto Detect (Recommended)** - Automatically detects Yoast SEO or Rank Math
- **Yoast SEO** - Manually select if you use Yoast
- **Rank Math** - Manually select if you use Rank Math

#### Master Prompt

- Pre-loaded with a professional content generation prompt
- Fully customizable to match your brand voice and requirements
- Reset to default anytime with one click

---

## ✍️ Usage Guide

### Generating Content

1. **Navigate to Generator**
   - Go to **BP Automater → Generator** in your WordPress admin

2. **Enter Article Details**
   - **Article Concept/Title:** Enter your topic (e.g., "Benefits of Titanium Glasses")
   - **Target Keywords:** Enter comma-separated keywords (e.g., "lightweight frames, durability, hypoallergenic")

3. **Generate**
   - Click **Generate Article**
   - Wait 30-60 seconds for AI processing
   - The system will:
     - Generate comprehensive article content
     - Create SEO-optimized metadata
     - Find and fetch relevant images from Unsplash
     - Generate structured data (Schema.org JSON-LD)

4. **Review & Edit**
   - Review the generated content in the preview
   - Edit any fields as needed:
     - Post title (H1)
     - SEO meta title and description
     - URL slug
     - Content body
     - Featured image

5. **Publish**
   - Click **Publish to WordPress**
   - The post is created with:
     - All SEO metadata populated (Yoast/Rank Math)
     - Featured image uploaded and set
     - Schema markup injected
     - Content ready to go live
   - Click **View Post** to see it live

### Monitoring Activity

- Navigate to **BP Automater → Logs**
- View detailed logs of all generation activities
- Monitor API calls, errors, and successes
- Troubleshoot any issues

---

## 🏗️ Architecture

### File Structure

```
wp-seo-blog-automater/
├── admin/
│   ├── css/
│   │   └── style.css           # Admin UI styles
│   ├── js/
│   │   └── admin.js            # Admin JavaScript
│   └── partials/
│       ├── generator-display.php  # Generator page
│       ├── settings-display.php   # Settings page
│       └── logs-display.php       # Logs page
├── images/
│   └── logo.png                # Codezela Technologies logo
├── includes/
│   ├── class-wp-seo-automater-admin.php  # Main admin class
│   └── class-gemini-api-handler.php      # Gemini API handler
├── languages/                  # Translation files (POT/PO/MO)
├── wp-seo-blog-automater.php  # Main plugin file
├── uninstall.php              # Uninstall cleanup
├── README.md                  # Documentation
└── LICENSE                    # GPL-2.0 License
```

### Key Components

#### 1. Main Plugin File (`wp-seo-blog-automater.php`)

- Plugin initialization and hooks
- Activation/deactivation handlers
- Schema injection on frontend

#### 2. Admin Class (`class-wp-seo-automater-admin.php`)

- Admin menu and pages
- AJAX handlers for generation and publishing
- Settings management
- Activity logging

#### 3. Gemini API Handler (`class-gemini-api-handler.php`)

- API communication
- Content generation logic
- Continuation handling for long content

---

## 🔒 Security Features

- **Nonce Verification** - All AJAX requests protected with WordPress nonces
- **Capability Checks** - Proper permission verification (`manage_options`, `publish_posts`)
- **Input Sanitization** - All user inputs sanitized using WordPress functions
- **Output Escaping** - All outputs properly escaped for XSS prevention
- **SQL Injection Protection** - Uses WordPress database API exclusively
- **Secure API Key Storage** - API keys stored securely in WordPress options

---

## 🌐 Internationalization

The plugin is fully translatable and ready for localization:

- Text domain: `wp-seo-blog-automater`
- POT file location: `/languages/`
- All user-facing strings wrapped in translation functions
- RTL-ready admin interface

To translate:

1. Use [Poedit](https://poedit.net/) or [Loco Translate](https://wordpress.org/plugins/loco-translate/)
2. Create PO/MO files from the provided POT template
3. Place files in `/languages/` directory

---

## 🔧 Developer Resources

### Hooks & Filters

#### Actions

```php
// After content generation
do_action( 'wp_seo_automater_after_generation', $post_id, $content );

// After post published
do_action( 'wp_seo_automater_after_publish', $post_id );
```

#### Filters

```php
// Modify generated content before display
apply_filters( 'wp_seo_automater_generated_content', $content, $title, $keywords );

// Modify master prompt
apply_filters( 'wp_seo_automater_master_prompt', $prompt );

// Customize Gemini API parameters
apply_filters( 'wp_seo_automater_api_params', $params );
```

### Constants

```php
WP_SEO_AUTOMATER_VERSION  // Plugin version
WP_SEO_AUTOMATER_PATH     // Plugin directory path
WP_SEO_AUTOMATER_URL      // Plugin directory URL
WP_SEO_AUTOMATER_BASENAME // Plugin basename
WP_SEO_AUTOMATER_FILE     // Main plugin file path
```

---

## 🐛 Troubleshooting

### Common Issues

**Content generation fails**

- Check your Gemini API key is valid
- Verify your API quota hasn't been exceeded
- Check error logs in **BP Automater → Logs**

**Images not appearing**

- Verify Unsplash Access Key is configured
- Check WordPress media folder permissions
- Ensure `allow_url_fopen` is enabled in PHP

**SEO metadata not saving**

- Confirm Yoast SEO or Rank Math is active
- Check user has `publish_posts` capability
- Review plugin logs for specific errors

**Slow generation**

- Normal processing time is 30-60 seconds
- Long articles may take up to 2 minutes
- Check server timeout settings if needed

---

## 📊 Performance

- **Optimized Database Queries** - Minimal database impact
- **Smart Image Caching** - Prevents duplicate downloads from Unsplash
- **Efficient AJAX** - Non-blocking UI operations
- **Proper Enqueueing** - Scripts/styles loaded only on plugin pages

---

## 🤝 Support

For support, feature requests, or bug reports:

- **Email:** support@codezela.com
- **Website:** [https://codezela.com](https://codezela.com)
- **Documentation:** [Plugin Documentation](https://codezela.com/docs/wp-seo-blog-automater)

---

## 📝 Changelog

### Version 1.0.4 (2025-01-20)

- ✨ Production-ready release with enterprise features
- 🔒 Enhanced security with comprehensive nonce validation
- 🎨 Professional UI/UX with Codezela branding
- 🌐 Full internationalization support
- 📚 Comprehensive inline documentation
- ♻️ Proper uninstall cleanup
- 🔧 Activation/deactivation hooks
- 📊 Improved error handling and logging
- 🎯 Settings link on plugins page
- 📱 Responsive admin interface
- ⚡ Performance optimizations

### Version 1.0.3

- 🖼️ Added Unsplash integration
- 🔍 Improved content extraction
- 📝 Enhanced logging system

### Version 1.0.0

- 🎉 Initial release
- 🤖 Google Gemini AI integration
- 📊 Schema markup support
- 🔗 SEO plugin integration

---

## 👨‍💻 About Codezela Technologies

**Codezela Technologies** is a leading software development company specializing in WordPress solutions, AI integration, and enterprise web applications. We build professional-grade tools that empower businesses to automate and scale their digital presence.

**Our Services:**

- Custom WordPress Development
- AI/ML Integration
- Enterprise Web Applications
- Digital Marketing Automation
- Technical Consulting

**Connect With Us:**

- 🌐 Website: [https://codezela.com](https://codezela.com)
- 📧 Email: info@codezela.com
- 💼 LinkedIn: [Codezela Technologies](https://linkedin.com/company/codezela)

---

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
WP SEO Blog Automater
Copyright (C) 2026 Codezela Technologies

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

---

## 🙏 Acknowledgments

- **Google Gemini AI** - For powerful language models
- **Unsplash** - For beautiful, free images
- **WordPress Community** - For excellent documentation and support
- **All Contributors** - Thank you for making this plugin better

---

## 🔮 Roadmap

### Planned Features

- [ ] Bulk content generation
- [ ] Advanced scheduling options
- [ ] Analytics dashboard
- [ ] API endpoint for external integrations

---

<div align="center">

**Made with ❤️ by [Codezela Technologies](https://codezela.com)**

⭐ Star us on GitHub if you find this useful!

</div>
