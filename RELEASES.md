# GitHub Releases & Automatic Updates

This document explains how automatic plugin updates work via GitHub releases.

## 🚀 How It Works

The plugin includes a **GitHub Updater** that:

1. **Checks GitHub** every 12 hours for new releases
2. **Compares versions** with the installed plugin version
3. **Shows "Update Available"** notification in WordPress admin
4. **Provides one-click updates** - users click "Update Now" and it downloads from GitHub

## 📦 Creating a Release (After Building)

### Prerequisites

- Plugin built: `./build.sh` creates `dist/wp-seo-blog-automater-v1.0.4.zip`
- Git committed and pushed to: `https://github.com/sayuru-akash/wp-seo-blog-automater-plugin`

### Method 1: GitHub CLI (Fastest)

```bash
# Install GitHub CLI (one-time setup)
brew install gh  # macOS
# or: sudo apt install gh  # Linux
# or: https://cli.github.com/  # Windows

# Authenticate (one-time)
gh auth login

# Create release and upload ZIP
gh release create v1.0.4 \
  --title "Version 1.0.4" \
  --notes "See CHANGELOG.md for full details" \
  dist/wp-seo-blog-automater-v1.0.4.zip
```

**Done!** WordPress sites will see the update within 12 hours (or immediately if they check for updates).

### Method 2: GitHub Web Interface

1. **Go to releases page:**

   ```
   https://github.com/sayuru-akash/wp-seo-blog-automater-plugin/releases/new
   ```

2. **Fill in the form:**
   - **Tag version:** `v1.0.4` (must match plugin version with `v` prefix)
   - **Release title:** `Version 1.0.4`
   - **Description:** Copy from CHANGELOG.md or write release notes
   - **Attach file:** Upload `dist/wp-seo-blog-automater-v1.0.4.zip`

3. **Click "Publish release"**

## 🔄 Update Flow for Users

Once you create a GitHub release:

1. **WordPress checks** (every 12 hours or on manual check)
2. **Plugin sees:** "New version v1.0.4 available on GitHub"
3. **Shows notification:** Red bubble on Plugins menu + "Update Available" badge
4. **User clicks:** "Update Now"
5. **WordPress downloads** ZIP from GitHub release
6. **Installs automatically** - settings/data preserved
7. **Plugin updated!** ✅

## 📋 Version Requirements

**CRITICAL:** Version numbers must match:

- **Plugin header:** `Version: 1.0.4` (in `wp-seo-blog-automater.php`)
- **Constant:** `WP_SEO_AUTOMATER_VERSION` = `'1.0.4'`
- **Build script:** `VERSION="1.0.4"` (in `build.sh`)
- **GitHub tag:** `v1.0.4` (note the `v` prefix)

The updater strips the `v` prefix when comparing versions.

## 🎯 Release Checklist

Before creating a release:

- [ ] Update version in `wp-seo-blog-automater.php` header
- [ ] Update `WP_SEO_AUTOMATER_VERSION` constant
- [ ] Update `VERSION` in `build.sh` and `build.bat`
- [ ] Update `CHANGELOG.md` with new version details
- [ ] Run `./build.sh` to create ZIP
- [ ] Test ZIP on local WordPress install
- [ ] Commit and push to GitHub
- [ ] Create GitHub release with tag `vX.Y.Z`
- [ ] Upload ZIP file to release
- [ ] Publish release

## 🔧 How the Updater Works (Technical)

### WordPress Update Hooks

```php
// Check for updates (runs on WordPress cron)
add_filter( 'pre_set_site_transient_update_plugins', 'check_for_update' );

// Provide plugin info for update screen
add_filter( 'plugins_api', 'plugin_info' );

// Clean up after install
add_filter( 'upgrader_post_install', 'after_install' );
```

### GitHub API

The updater queries:

```
GET https://api.github.com/repos/sayuru-akash/wp-seo-blog-automater-plugin/releases/latest
```

Returns:

```json
{
  "tag_name": "v1.0.4",
  "name": "Version 1.0.4",
  "body": "Release notes...",
  "assets": [
    {
      "name": "wp-seo-blog-automater-v1.0.4.zip",
      "browser_download_url": "https://github.com/.../releases/download/v1.0.4/..."
    }
  ]
}
```

### Caching

- Release data cached for **12 hours** in WordPress transients
- Cache cleared after successful update
- Manual update checks bypass cache

## 🐛 Troubleshooting

### "No update available" but new release exists

1. Clear transient cache:

   ```php
   delete_transient( 'wp_seo_automater_github_release' );
   ```

2. Force check: Dashboard → Updates → "Check Again"

3. Verify:
   - GitHub release is **published** (not draft)
   - Tag format is `vX.Y.Z` with lowercase `v`
   - ZIP file is attached to release
   - Version in ZIP matches tag version

### Update downloads but doesn't install

- Check plugin folder name in ZIP: must be `wp-seo-blog-automater/`
- Check main file name: must be `wp-seo-blog-automater.php`
- Check file permissions on `wp-content/plugins/`

### GitHub API rate limits

- Unauthenticated: 60 requests/hour per IP
- 12-hour caching prevents hitting limits
- For higher limits, add GitHub token (not implemented yet)

## 🚀 Advanced: Automated Releases with GitHub Actions

Create `.github/workflows/release.yml`:

```yaml
name: Create Release

on:
  push:
    tags:
      - "v*"

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Build plugin
        run: |
          chmod +x build.sh
          ./build.sh

      - name: Create Release
        uses: softprops/action-gh-release@v1
        with:
          files: dist/*.zip
          body_path: CHANGELOG.md
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

**Usage:**

```bash
git tag v1.0.5
git push origin v1.0.5
```

GitHub automatically builds and creates release! 🎉

## 📝 Best Practices

1. **Semantic Versioning:** Use `MAJOR.MINOR.PATCH` (e.g., 1.0.4)
2. **Test locally** before releasing
3. **Write clear changelog** - users see it in update screen
4. **Never delete releases** - breaks existing installations
5. **Use pre-releases** for beta/RC versions (mark as pre-release on GitHub)

## 🔐 Security Notes

- Plugin downloads via HTTPS from GitHub
- WordPress verifies ZIP integrity
- No credentials stored in plugin code
- Uses public GitHub API (no authentication needed)

## 📞 Support

For issues with automatic updates:

- Check GitHub releases: https://github.com/sayuru-akash/wp-seo-blog-automater-plugin/releases
- Verify WordPress can access GitHub (check firewall/proxy)
- Check WordPress debug log: `wp-content/debug.log`

---

**Last Updated:** January 20, 2026  
**Plugin Version:** 1.0.4  
**Repository:** https://github.com/sayuru-akash/wp-seo-blog-automater-plugin
