# Build Instructions

## Quick Start

### macOS / Linux / Unix

```bash
./build.sh
```

### Windows

Double-click `build.bat` or run in Command Prompt:

```cmd
build.bat
```

## What It Does

The build script automatically:

1. ✅ Creates a clean build directory
2. ✅ Detects the release version directly from `wp-seo-blog-automater.php`
3. ✅ Copies all necessary plugin files
4. ✅ Excludes development files:
   - `tests/` directory
   - All dot files (`.git`, `.gitignore`, etc.)
   - All dot folders (`.vscode`, `.idea`, etc.)
   - `node_modules/`, `vendor/`
   - `*.log`, `*.map` files
   - OS files (`.DS_Store`, `Thumbs.db`)
5. ✅ Creates optimized ZIP: `wp-seo-blog-automater-v<version>.zip`
6. ✅ Places ZIP in `dist/` folder
7. ✅ Cleans up temporary files

## Output

After running, you'll find:

```
dist/
  └── wp-seo-blog-automater-v<version>.zip
```

This ZIP file is ready for:

- WordPress plugin upload
- Distribution
- Deployment to production sites

## Manual Build (If Scripts Don't Work)

### Using ZIP command (macOS/Linux):

```bash
# Create temporary directory
mkdir -p temp-build/wp-seo-blog-automater

# Copy files (excluding tests and dot files)
rsync -av --exclude='tests' --exclude='.*' --exclude='build.sh' --exclude='build.bat' \
  --exclude='BUILD.md' --exclude='dist' --exclude='build' \
  . temp-build/wp-seo-blog-automater/

# Create ZIP
cd temp-build
zip -r ../wp-seo-blog-automater-v<version>.zip wp-seo-blog-automater
cd ..

# Cleanup
rm -rf temp-build
```

### Using Windows Explorer:

1. Copy entire plugin folder
2. Delete `tests/` folder
3. Delete `.git`, `.gitignore`, and other dot files
4. Delete `build.sh`, `build.bat`, `BUILD.md`, `dist/`, `build/`
5. Right-click folder → Send to → Compressed (zipped) folder
6. Rename to: `wp-seo-blog-automater-v<version>.zip`

## Verification

Before distribution, verify the ZIP contains:

- ✅ `wp-seo-blog-automater.php` (main file)
- ✅ `uninstall.php`
- ✅ `README.md`
- ✅ `LICENSE`
- ✅ `admin/` directory
- ✅ `includes/` directory
- ✅ `images/` directory (with logo.png)
- ✅ `languages/` directory
- ❌ NO `tests/` directory
- ❌ NO `.git` or other dot files
- ❌ NO `build.sh` or `build.bat`

## Updating Version

When releasing a new version:

1. Update version in `wp-seo-blog-automater.php` header
2. Update `WP_SEO_AUTOMATER_VERSION` constant
3. Update `README.md` version badge
4. Update `CHANGELOG.md`
5. Update `languages/wp-seo-blog-automater.pot`

## Version-Aware Validation

The repository now includes a single validation command used by both local Git hooks and GitHub Actions:

```bash
./scripts/release-build-check.sh
```

It will:

1. Lint all PHP files
2. Validate that the plugin header version and `WP_SEO_AUTOMATER_VERSION` match
3. Run `./build.sh`
4. Verify the generated ZIP contents and version

## Local Pre-Push Hook

To enable automatic local validation before pushes that change the plugin version, configure Git once in this repository:

```bash
git config core.hooksPath .githooks
```

After that, normal pushes are left alone, but pushes that change the plugin version will run the release validation locally before Git sends them upstream.

## Troubleshooting

### "Permission denied" on macOS/Linux

```bash
chmod +x build.sh
./build.sh
```

### "zip command not found" on Linux

```bash
sudo apt-get install zip  # Debian/Ubuntu
sudo yum install zip      # CentOS/RHEL
```

### Build script hangs on Windows

- Ensure PowerShell is installed (comes with Windows 7+)
- Run as Administrator if needed
- Check antivirus isn't blocking the script

## Automated Builds

GitHub Actions is configured in `.github/workflows/version-build.yml`.

The workflow:

1. Triggers on pushes that touch the version/build logic
2. Checks whether the pushed range actually changed the plugin version
3. Skips normal pushes with no version bump
4. Runs the same `./scripts/release-build-check.sh` validation used locally
5. Uploads the built ZIP as a workflow artifact when validation passes
6. Creates or updates a GitHub Release tagged as `v<version>` and attaches the ZIP

If you already pushed the version bump before this workflow existed, run the workflow manually from the Actions tab with `force_release=true` to publish the release for the current version without changing the version again.

---

**Need help?** Contact info@codezela.com
