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
2. ✅ Copies all necessary plugin files
3. ✅ Excludes development files:
   - `tests/` directory
   - All dot files (`.git`, `.gitignore`, etc.)
   - All dot folders (`.vscode`, `.idea`, etc.)
   - `node_modules/`, `vendor/`
   - `*.log`, `*.map` files
   - OS files (`.DS_Store`, `Thumbs.db`)
4. ✅ Creates optimized ZIP: `wp-seo-blog-automater-v1.0.4.zip`
5. ✅ Places ZIP in `dist/` folder
6. ✅ Cleans up temporary files

## Output

After running, you'll find:

```
dist/
  └── wp-seo-blog-automater-v1.0.4.zip
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
zip -r ../wp-seo-blog-automater-v1.0.4.zip wp-seo-blog-automater
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
6. Rename to: `wp-seo-blog-automater-v1.0.4.zip`

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
3. Update version in `build.sh` (line 15)
4. Update version in `build.bat` (line 12)
5. Update `README.md` version badge
6. Update `CHANGELOG.md`
7. Update `languages/wp-seo-blog-automater.pot`

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

For CI/CD integration, the build script can be automated:

```bash
# GitHub Actions example
- name: Build Plugin
  run: |
    chmod +x build.sh
    ./build.sh

- name: Upload Artifact
  uses: actions/upload-artifact@v2
  with:
    name: plugin-package
    path: dist/*.zip
```

---

**Need help?** Contact info@codezela.com
