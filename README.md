# WP Plugin Boilerplate
A modern, object-oriented WordPress plugin boilerplate following WordPress coding standards. Features a generic structure where you only need to change ONE FILE to create a completely new plugin.

## 🚀 Features
One-File Setup (True Dynamic Naming): Change only the main plugin file's header to rename the plugin, `slug`, `version`, menu items, and file handles across the entire codebase.

**Object-Oriented Architecture**: Clean, maintainable code structure.

**WordPress Standards**: Follows WordPress coding standards and best practices.

**Security Ready**: Built-in security measures and data sanitization.

**Internationalization**: Ready for translations with proper text domains.

**Admin & Public Separation**: Organized separation of backend and frontend functionality.

**Hook Management**: Centralized WordPress hook system.

**Asset Management**: Proper CSS/JS enqueuing for admin and public.

## 📁 Plugin Structure

```
my-wp-plugin/
├── 📄 my-wp-plugin.php                 # Main plugin file (ONLY FILE TO EDIT HEADER)
├── 📁 includes/                        # Core PHP classes
│   ├── 📄 class-main.php               # Plugin orchestrator
│   ├── 📄 class-loader.php             # Hook manager
│   ├── 📄 class-activator.php          # Activation handler
│   └── 📄 class-deactivator.php        # Deactivation handler
├── 📁 admin/                           # Backend functionality ONLY
│   ├── 📄 class-admin.php              # Admin controller
│   └── 📁 partials/                    # Admin HTML templates
├── 📁 public/                          # Frontend functionality ONLY
│   └── 📄 class-public.php             # Frontend controller
├── 📁 assets/                          # ALL static files
│   ├── 📁 css/
│   │   ├── 📄 admin.css
│   │   └── 📄 public.css
│   ├── 📁 js/
│   │   ├── 📄 admin.js
│   │   └── 📄 public.js
│   └── 📁 images/
├── 📁 languages/                       # Translation files (.po/.mo)
└── 📄 uninstall.php                    # Cleanup when deleted
```

## 🎯 Quick Start: Where to Change Plugin Names 🎯
The plugin's Name, Slug, and Version are pulled directly from the file header of the main plugin file (my-wp-plugin.php). No other file needs manual editing for renaming.

## Step 1: Change ONLY the Main File Header
Open my-wp-plugin.php and update the following lines in the plugin header. This is the ONLY PLACE you need to edit the names.

```
<?php
/**
 * Plugin Name: My Awesome Widget        // 1. Changes DISPLAY NAME (Menu Title, Plugin List)
 * Plugin URI:  https://example.com/widget
 * Description: A brief description of my new plugin.
 * Version:     1.0.1                    // 2. Changes ASSET VERSIONING (Cache Buster)
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: awesome-widget-slug      // 3. Changes UNIQUE SLUG (Handles, Options, Menu Slug)
 * Domain Path: /languages
 */
// ... rest of the file is fully dynamic
```

## Step 2: Rename Files (Optional but Recommended)
For consistency, you should rename the main plugin file and the containing folder to match your new Text Domain (Slug).
```
my-wp-plugin/ → your-plugin-name/
my-wp-plugin.php → your-plugin-name.php
```

## Step 3: Activate & Build!
Upload to /wp-content/plugins/ and activate in WordPress admin.

## 🌐 Internationalization
The boilerplate uses the Text Domain defined in your main file's header for all internationalization. This value is accessible throughout the code via the MY_PLUGIN_SLUG constant.

Adding Translations:
Use translation functions in your code:
```
echo esc_html__( 'Hello World', MY_PLUGIN_SLUG );
_e( 'Hello World', MY_PLUGIN_SLUG );
```
Generate .pot file using WP-CLI (replace your-plugin-slug with your Text Domain):
```
wp i18n make-pot . languages/your-plugin-slug.pot
```
Translation Files Structure:

```
languages/
├── your-plugin-slug.pot          # Template for translators
├── your-plugin-slug-en_US.po     # English translations (source)
├── your-plugin-slug-en_US.mo     # Compiled English
└── your-plugin-slug-es_ES.mo     # Compiled Spanish
```

## ⚙️ Customization Guide
Adding Admin Settings:
Edit admin/class-admin.php to add settings pages, menus, and options.

Adding Frontend Features:
Edit public/class-public.php to add shortcodes, frontend functionality.

Database Operations:
Edit includes/class-activator.php to create custom tables and set default options.

Adding New Hooks:
Use the loader class in any file:

```
$this->loader->add_action('wp_head', $this, 'your_method');
$this->loader->add_filter('the_content', $this, 'filter_content');
```

## 🔧 Development

File Purpose Overview:

`my-wp-plugin.php` - Main loader, reads header data, defines constants, activation hooks

`includes/class-main.php` - Core plugin orchestrator, receives name/slug/version

`includes/class-loader.php` - Manages WordPress actions and filters

`includes/class-activator.php` - Handles plugin activation (DB setup, options)

`includes/class-deactivator.php` - Handles plugin deactivation

`admin/class-admin.php` - All admin-facing functionality, uses dynamic name/slug

`public/class-public.php` - All public-facing functionality, uses dynamic slug/version

`uninstall.php` - Cleanup when plugin is deleted

Constants Available (Defined dynamically in `my-wp-plugin.php`):

`MY_PLUGIN_NAME` - Plugin display name (e.g., "My Awesome Widget")

`MY_PLUGIN_SLUG` - Plugin unique identifier/text domain (e.g., "awesome-widget-slug")

`MY_PLUGIN_VERSION` - Plugin version (e.g., "1.0.1")

`MY_PLUGIN_PATH` - Plugin directory path

`MY_PLUGIN_URL` - Plugin directory URL

## 🛡️ Security Features
Direct file access protection

Data sanitization and validation

WordPress nonce verification

Prepared database statements

Proper capability checks

Escaped output

## 📦 Installation
Download or clone this boilerplate.

Open the main plugin file (my-wp-plugin.php) and update the header fields (Plugin Name, Version, Text Domain) to your desired values.

Rename the main plugin folder (e.g., my-wp-plugin/ to your-plugin-slug/).

Rename the main plugin file inside the folder (e.g., my-wp-plugin.php to your-plugin-slug.php).

Upload the entire customized plugin folder to your WordPress installation's /wp-content/plugins/ directory.

Activate the plugin from the WordPress admin dashboard under "Plugins".

Begin developing your custom functionality!

## 🔄 Creating Multiple Plugins
To create another plugin:

Duplicate the boilerplate folder.

In the new plugin's main file, update the header fields with your new plugin's details.

All class names, constants, and prefixes will automatically adjust to your new plugin details.

## 📝 License

This project is licensed under the GPL-2.0+ License - see the GPL v2 license for details.
# ai-chat-agent
