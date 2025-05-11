# WebP Enforcer WordPress Plugin

![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)
![License](https://img.shields.io/badge/License-GPLv2-green.svg)

> Enforces WebP conversion best practices by requiring confirmation before image uploads

## Features

- ✅ Mandatory confirmation before uploading non-WebP images
- 🚫 Blocks upload if WebP conversion isn't confirmed
- 🖼️ Special SVG handling (bypasses confirmation)
- 🎨 Customizable modal dialog with WordPress styling
- 📱 Fully responsive design

## Installation

1. Download the plugin ZIP
2. Go to WordPress Admin → Plugins → Add New → Upload
3. Upload and activate

## How It Works

```php
// Core functionality in webp-enforcer.php
add_filter('wp_handle_upload_prefilter', 'enforce_webp_format');
add_filter('upload_mimes', 'enable_svg_uploads');

function enforce_webp_format($file) {
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($extension, $image_extensions)) {
        $file['error'] = __('Please convert to WebP first', 'webp-enforcer');
    }
    return $file;
}
```
## Supported Format

1. JPG/JPEG, PNG, GIF requires confirmation
2. WebP, SVG/SVGZ don't require confirmation

## Customisation

Edit these variables in webp-enforcer.php
```
// Change modal text
const ALERT_MESSAGE = "Has this image been converted to WebP?";
const CONFIRM_TEXT = "Yes, proceed with upload";
const CANCEL_TEXT = "No, cancel upload";
```
# Development

# Clone repository
git clone https://github.com/lazyZ33/webp-enforcer.git
cd webp-enforcer

# Contributing
1. Create a feature branch
2. Make your changes
3. Submit a pull request
