@echo off
REM WP SEO Blog Automater - Build Script for Windows
REM Creates a production-ready ZIP file
REM
REM Usage: Double-click this file or run: build.bat
REM
REM @package    WP_SEO_Blog_Automater
REM @author     Codezela Technologies
REM @version    1.0.4

setlocal EnableDelayedExpansion

set PLUGIN_SLUG=wp-seo-blog-automater
set VERSION=1.1.0
set BUILD_DIR=build
set DIST_DIR=dist
set ZIP_NAME=%PLUGIN_SLUG%-v%VERSION%.zip

echo.
echo ========================================================
echo    WP SEO Blog Automater - Build Script v%VERSION%
echo          Codezela Technologies
echo ========================================================
echo.

REM Clean previous builds
if exist "%BUILD_DIR%" rd /s /q "%BUILD_DIR%"

REM Create directories (if doesn't exist)
if not exist "%BUILD_DIR%\%PLUGIN_SLUG%" mkdir "%BUILD_DIR%\%PLUGIN_SLUG%"
if not exist "%DIST_DIR%" mkdir "%DIST_DIR%"

REM Clean only ZIP files from dist (preserve other files)
del /q "%DIST_DIR%\*.zip" 2>nul

echo [+] Copying plugin files...

REM Copy main files
copy wp-seo-blog-automater.php "%BUILD_DIR%\%PLUGIN_SLUG%\" >nul
copy uninstall.php "%BUILD_DIR%\%PLUGIN_SLUG%\" >nul
copy README.md "%BUILD_DIR%\%PLUGIN_SLUG%\" >nul
copy LICENSE "%BUILD_DIR%\%PLUGIN_SLUG%\" >nul
if exist CHANGELOG.md copy CHANGELOG.md "%BUILD_DIR%\%PLUGIN_SLUG%\" >nul
if exist CONTRIBUTING.md copy CONTRIBUTING.md "%BUILD_DIR%\%PLUGIN_SLUG%\" >nul

REM Copy directories
xcopy /E /I /Q admin "%BUILD_DIR%\%PLUGIN_SLUG%\admin" >nul
xcopy /E /I /Q includes "%BUILD_DIR%\%PLUGIN_SLUG%\includes" >nul
xcopy /E /I /Q images "%BUILD_DIR%\%PLUGIN_SLUG%\images" >nul
xcopy /E /I /Q languages "%BUILD_DIR%\%PLUGIN_SLUG%\languages" >nul

echo [+] Cleaning build...

REM Remove tests directory
if exist "%BUILD_DIR%\%PLUGIN_SLUG%\tests" rd /s /q "%BUILD_DIR%\%PLUGIN_SLUG%\tests"

REM Remove common development files
del /s /q "%BUILD_DIR%\%PLUGIN_SLUG%\*.log" 2>nul
del /s /q "%BUILD_DIR%\%PLUGIN_SLUG%\*.map" 2>nul
del /s /q "%BUILD_DIR%\%PLUGIN_SLUG%\.DS_Store" 2>nul
del /s /q "%BUILD_DIR%\%PLUGIN_SLUG%\Thumbs.db" 2>nul

REM Remove build-related files
if exist "%BUILD_DIR%\%PLUGIN_SLUG%\build.sh" del /q "%BUILD_DIR%\%PLUGIN_SLUG%\build.sh" 2>nul
if exist "%BUILD_DIR%\%PLUGIN_SLUG%\build.bat" del /q "%BUILD_DIR%\%PLUGIN_SLUG%\build.bat" 2>nul
if exist "%BUILD_DIR%\%PLUGIN_SLUG%\BUILD.md" del /q "%BUILD_DIR%\%PLUGIN_SLUG%\BUILD.md" 2>nul
if exist "%BUILD_DIR%\%PLUGIN_SLUG%\dist" rd /s /q "%BUILD_DIR%\%PLUGIN_SLUG%\dist" 2>nul

echo [+] Creating ZIP archive...

REM Check if PowerShell is available (Windows 7+)
where powershell >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    powershell -Command "Compress-Archive -Path '%BUILD_DIR%\%PLUGIN_SLUG%' -DestinationPath '%DIST_DIR%\%ZIP_NAME%' -Force"
) else (
    echo ERROR: PowerShell not found. Please install PowerShell or use 7-Zip manually.
    pause
    exit /b 1
)

REM Cleanup
rd /s /q "%BUILD_DIR%"

echo.
echo ========================================================
echo           Build Completed Successfully!
echo ========================================================
echo.
echo Package:  %ZIP_NAME%
echo Location: %CD%\%DIST_DIR%\%ZIP_NAME%
echo.
echo Next steps:
echo   1. Upload to WordPress: Plugins -^> Add New -^> Upload
echo   2. Test functionality
echo   3. Deploy to production
echo.
echo Happy deploying! 🚀
echo.

pause
