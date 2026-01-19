# How to Enable Automatic Updates

## Issue: Can't See Updates in WordPress Admin

If you have an older version of the plugin installed (v1.0.4 or earlier), it **doesn't have the GitHub updater yet**. You need to manually update to v1.0.5 first, then future updates will be automatic.

## Solution: Manual Update to v1.0.5

### Step 1: Deactivate Current Plugin
1. Go to: **Plugins → Installed Plugins**
2. Find "WP SEO Blog Automater"
3. Click **Deactivate**

### Step 2: Delete Old Version
1. After deactivating, click **Delete**
2. Confirm deletion
3. Your settings are safe (stored in database, not deleted)

### Step 3: Install v1.0.5
1. Download: https://github.com/sayuru-akash/wp-seo-blog-automater-plugin/releases/download/v1.0.5/wp-seo-blog-automater-v1.0.5.zip
2. Go to: **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file
4. Click **Install Now**
5. Click **Activate**

### Step 4: Verify Auto-Updates Work
1. Go to: **Dashboard → Updates**
2. Click **Check Again**
3. The plugin will now check GitHub every 12 hours

## From Now On

Once v1.0.5 is installed:
- ✅ WordPress automatically checks GitHub for new versions
- ✅ "Update Available" notification appears when new version is released
- ✅ One-click "Update Now" button
- ✅ Settings and data preserved during updates

## Quick Method (Via FTP/SSH)

If you have server access:

```bash
# Navigate to plugins directory
cd wp-content/plugins/

# Remove old version
rm -rf wp-seo-blog-automater/

# Download and extract new version
wget https://github.com/sayuru-akash/wp-seo-blog-automater-plugin/releases/download/v1.0.5/wp-seo-blog-automater-v1.0.5.zip
unzip wp-seo-blog-automater-v1.0.5.zip
rm wp-seo-blog-automater-v1.0.5.zip

# Set proper permissions
chown -R www-data:www-data wp-seo-blog-automater/
```

Then activate via WordPress admin.

## Troubleshooting

### "Update Available" doesn't appear after 12 hours

Force check:
```php
// Add to functions.php temporarily, visit site, then remove
delete_transient('wp_seo_automater_github_release');
```

Or just go to **Dashboard → Updates → Check Again**

### Plugin appears as new installation

This means the folder name changed. Make sure:
- Folder is named: `wp-seo-blog-automater`
- Main file is: `wp-seo-blog-automater.php`
- Not nested in extra folders

### Settings lost after update

Settings are stored in `wp_options` table:
- `wp_seo_automater_gemini_key`
- `wp_seo_automater_gemini_model`
- `wp_seo_automater_unsplash_key`
- `wp_seo_automater_seo_plugin`
- `wp_seo_automater_master_prompt`
- `wp_seo_automater_logs`

They persist unless you run `uninstall.php` or manually delete them.
