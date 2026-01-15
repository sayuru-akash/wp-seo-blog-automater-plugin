# WP SEO Blog Automater

**Automate high-quality, SEO-optimised blog content creation using Google Gemini AI.**

This WordPress plugin transforms a simple Title and Keyword input into a comprehensive, medically-accurate (or niche-specific), and fully optimized blog post. It automates 90% of the workflow while giving you full control over the final output.

## 🚀 Key Features

- **Google Gemini AI Integration**: Powered by the latest Gemini Pro models for nuanced, human-like writing.
- **📸 Visual Intelligence (New)**:
  - **Auto-Image Search**: The AI determines the perfect visual search terms.
  - **Unsplash Integration**: Automatically searches for high-quality, royalty-free images.
  - **Auto-Sideloading**: Downloads the image, uploads it to your Media Library, sets it as the **Featured Image**, and adds SEO-optimized **Alt Text**—all in one click.
- **One-Click SEO**: Automatically generates optimized:
  - **H1 Title**: Catchy and keyword-rich.
  - **URL Slug**: Clean and SEO-friendly.
  - **Meta Title & Description**: Pre-filled for high CTR.
  - **JSON-LD Schema**: Automatically injects structured data (e.g., FAQPage) into the head.
- **Deep Integration**:
  - **Yoast SEO Support**: Auto-fills Yoast metadata (Title, Desc, OpenGraph, Twitter).
  - **Rank Math Support**: Auto-fills Rank Math metadata.
  - **Auto-Detect**: Smartly identifies your active SEO plugin.
- **Robust Extraction Engine**:
  - **Surgical Parsing**: Separates Metadata from Content to ensure zero data loss.
  - **Smart Cleaning**: Removes AI artifacts ("Phase 1", internal instructions) automatically.
- **Customizable Brain**: Comes with a powerful "Master Prompt" that you can tweak to match any brand voice or industry.

## 📥 Installation

1.  Download the repository as a ZIP file.
2.  Go to your WordPress Admin -> **Plugins** -> **Add New** -> **Upload Plugin**.
3.  Upload the ZIP and click **Activate**.

## ⚙️ Configuration

Go to **BP Automater** -> **Settings** in your WordPress dashboard.

1.  **Gemini API Key**: Get your free key from [Google AI Studio](https://aistudio.google.com/).
2.  **Unsplash Access Key**: Get your free key from [Unsplash Developers](https://unsplash.com/developers).
    - Required for the "Visual Intelligence" feature.
3.  **Active SEO Plugin**: Set to **Auto Detect** (recommended). It will find Yoast or Rank Math automatically.
4.  **Master System Prompt**: A rigorous prompt is pre-loaded. You can edit this to change the persona (e.g., "Medical Copywriter" vs "Tech Blogger").

## ✍️ Usage Guide

1.  Navigate to **BP Automater** -> **Generator**.
2.  **Enter Title**: E.g., "The Benefits of Titanium Glasses".
3.  **Enter Keywords**: E.g., "lightweight frames, durability, hypoallergenic".
4.  Click **Generate Article**.
    - _Wait 30-60 seconds for the AI to research, write, and find an image._
5.  **Review Preview**:
    - **Image**: Check the fetched Unsplash image.
    - **SEO**: Review the extracted H1, Slug, and Meta fields.
    - **Content**: Scan the article body.
6.  Click **Publish to WordPress**.
    - The post is created instantly with the image attached and all SEO data saved.
    - Check the "View Post" link to see it live.

## 🔍 Technical Details

- **Security**: Uses WordPress nonces and capability checks (`manage_options`) for all actions.
- **Validation**: Validates JSON Schema before saving to prevent site errors.
- **Logs**: Built-in activity logger (under **Logs** tab) to troubleshoot API errors or generation issues.

## 📄 License

GPL-2.0+
Developed by Sayuru Amarasinghe
