# MSC: Post Last Updated Date

![Version](https://img.shields.io/badge/version-1.6.1-blue)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![WordPress](https://img.shields.io/badge/WordPress-5.9%2B-blue)
![Tested up to](https://img.shields.io/badge/tested%20up%20to-7.1-blue)

Display and control the post last-updated date in flexible positions.

**All features are free. There is no premium version.**

## Index

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Developer Reference](#developer-reference)
- [Development](#development)
- [Changelog](#changelog)
- [License](#license)

## Features

- **Automatic Placement** — Show the last-updated date before content, after content, both, or set Manual for full theme control
- **Flexible Label** — Customise the label with a template; use `%s` as the date placeholder, or omit it for a static label with no date
- **Three Date Formats** — The WordPress site date format, a custom PHP date format string, or a relative date ("3 days ago")
- **Unchanged-Post Suppression** — Optionally hide the label when the modified date matches the publish date
- **Post Type Control** — Include selected post types, or exclude selected types from an all-types baseline
- **Semantic Markup** — Clean HTML5 `<time>` output with an ISO 8601 `datetime` attribute for SEO and accessibility
- **Template Tags** — `msclup_the_last_updated()` and `msclup_get_last_updated()` for manual placement
- **Shortcode** — `[msclu_last_updated]` places the date anywhere shortcodes run
- **Developer-Friendly** — Four filter hooks for visibility, label text, CSS classes, and final HTML output
- **12 Languages** — German (DE/CH), Spanish (ES/MX), French (FR/CA), Italian, Japanese, Dutch (NL/BE), Portuguese (BR/PT)

## Installation

### From WordPress Admin

1. Download the plugin zip file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Click **Activate**

### Manual Installation

1. Upload the `micro-site-care-post-last-updated-date` folder to `/wp-content/plugins/`
2. Activate via the **Plugins** menu in WordPress

### Post-Activation

1. Go to **Settings → MSC Post Last Updated Date**
2. Choose the placement, label template, and date format
3. Select the post types the label should appear on

## Configuration

Navigate to **Settings → MSC Post Last Updated Date**.

| Option | Description | Default |
|--------|-------------|---------|
| Enable output | Master toggle for the last-updated label | Enabled |
| Automatic placement | Where to inject the label: After content, Before content, Before and after, or Manual only | After content |
| Label template | Text to display. Use `%s` where the formatted date should appear; omit it for a static label | `Updated %s` |
| Date format source | Use the WordPress site date format, a custom PHP date format string, or a relative date | Site format |
| Custom date format | PHP date format string (e.g. `d/m/Y`). Used only when Date format source is Custom | `F j, Y` |
| Visibility condition | When ticked, the label is suppressed on posts whose modified date equals their publish date | Ticked |
| Post type mode | Include only selected types, or exclude selected types from all public post types | Include selected |
| Post types | The post types to target, based on the mode above | `post`, `page` |

## Usage

### Template Tags

Use these in theme templates when **Automatic placement** is set to **Manual only**.

#### `msclup_the_last_updated( $post_id = 0, $context = array() )`

Echoes the rendered HTML directly. Use inside The Loop, or pass a post ID.

```php
// Inside The Loop — uses the current post.
msclup_the_last_updated();

// Outside The Loop — pass a specific post ID.
msclup_the_last_updated( 42 );
```

Expected output:

```html
<p class="msclu-last-updated">
    <time datetime="2026-03-28T14:30:00+00:00">Updated March 28, 2026</time>
</p>
```

#### `msclup_get_last_updated( $post_id = 0, $context = array() )`

Returns the rendered HTML string instead of echoing it.

```php
$html = msclup_get_last_updated( get_the_ID() );
echo wp_kses_post( $html );
```

### Shortcode

`[msclu_last_updated]` renders the same markup as the template tags, anywhere shortcodes run.

| Attribute | Description | Default |
|-----------|-------------|---------|
| `post_id` | Target post ID | Current post |
| `relative` | `true` forces a relative date ("3 days ago"); `false` forces the site date format. Omit to follow the Date format source setting | Follows setting |

```
[msclu_last_updated]
[msclu_last_updated post_id="42"]
[msclu_last_updated relative="true"]
```

Registration is skipped when a callback on the `msclu_pro_active` filter returns `true`.

## Developer Reference

### Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `MSCLUD_PLUGIN_VERSION` | `'1.6.1'` | Current plugin version |
| `MSCLUD_PLUGIN_FILE` | `__FILE__` | Absolute path to main plugin file |
| `MSCLUD_PLUGIN_DIR` | Plugin directory path | Absolute path to plugin directory |
| `MSCLUD_PLUGIN_URL` | Plugin directory URL | URL to plugin directory |

### Plugin Options

All options are stored as a single serialised array under the `msclu_options` key. Access via:

```php
$options = get_option( 'msclu_options' );
```

Or via the plugin API:

```php
$plugin = MSCLUD\Plugin::instance();
$value  = $plugin->get_option( 'position', 'after' );
```

| Option Key | Type | Default | Description |
|------------|------|---------|-------------|
| `module_enabled` | `int` | `1` | Enable/disable the label (1/0) |
| `post_types` | `array` | `['post','page']` | Post types to target |
| `post_type_mode` | `string` | `'include'` | `include` or `exclude` |
| `position` | `string` | `'after'` | `before`, `after`, `both`, or `manual` |
| `label_text` | `string` | `'Updated %s'` | Label template; `%s` is the formatted date |
| `date_mode` | `string` | `'site'` | `site`, `custom`, or `relative` |
| `custom_format` | `string` | `'F j, Y'` | PHP date format used when `date_mode` is `custom` |
| `modified_only` | `int` | `1` | Suppress the label when the post has never been modified (1/0) |

The activation timestamp is stored under `msclu_activated_time`, and the review-notice state under `msclu_review_dismissed`.

### Plugin Filters

| Filter | Parameters | Description |
|--------|------------|-------------|
| `msclu_should_display` | `bool $display`, `WP_Post $post`, `int $modified`, `int $published`, `array $context` | Return `false` to prevent the label appearing on a specific post |
| `msclu_label_text` | `string $label`, `WP_Post $post`, `string $formatted_date`, `int $modified`, `int $published`, `array $context` | Override the rendered label string before it is wrapped in the `<time>` element |
| `msclu_wrapper_classes` | `array $classes`, `WP_Post $post`, `array $context` | Add or remove CSS classes on the `<p>` wrapper |
| `msclu_output_html` | `string $html`, `WP_Post $post`, `string $label`, `int $modified`, `array $context` | Filter the complete final HTML before it is injected or returned |
| `msclu_pro_active` | `bool $active` | Legacy extension gate. Returning `true` suppresses registration of the built-in shortcode so an add-on can own it |
| `msclu_settings_sanitized_options` | `array $options`, `array $sanitized_post` | Filter the sanitised settings array before it is saved |

### Custom Actions

| Action | Parameters | Description |
|--------|------------|-------------|
| `msclu_settings_sections` | — | Fires inside the settings form. Use to add custom fields |

**Example — hide the label on a specific post:**

```php
add_filter( 'msclu_should_display', function( $display, $post ) {
    return ( 99 === $post->ID ) ? false : $display;
}, 10, 2 );
```

**Example — add a custom wrapper class:**

```php
add_filter( 'msclu_wrapper_classes', function( $classes, $post ) {
    $classes[] = 'my-custom-class';
    return $classes;
}, 10, 2 );
```

### Uninstall Behaviour

On plugin deletion (not deactivation), the plugin removes `msclu_options`, `msclu_activated_time`, and `msclu_review_dismissed` from `wp_options`. The plugin creates no custom tables, post meta, or cron events.

## Development

### Requirements

- PHP 7.4+
- Composer
- MySQL/MariaDB (for tests)
- WP-CLI (for .pot generation)

### Setup

```bash
cd micro-site-care-post-last-updated-date
composer install
```

### Linting

```bash
# Check coding standards (WordPress-Core, WordPress-Docs, WordPress-Extra)
composer lint

# Auto-fix fixable issues
composer lint-fix
```

### Testing

The plugin includes 25 PHPUnit tests covering the plugin core, output module, and settings.

```bash
# Run all tests
composer test

# Run with readable output
vendor/bin/phpunit --testdox

# Run a specific test file
vendor/bin/phpunit tests/test-module.php

# Generate coverage report
composer run test:coverage
```

**Test files:**

| File | Tests | Covers |
|------|-------|--------|
| `test-core.php` | 10 | Singleton, activation, options, defaults |
| `test-module.php` | 8 | Placement, formatting, `<time>` output, shortcode |
| `test-settings.php` | 7 | Sanitisation, validation, saves |

### Translations

The plugin ships with 12 translations.

- **Text domain:** `micro-site-care-post-last-updated-date`
- **Domain path:** `/languages`
- **POT template:** `languages/micro-site-care-post-last-updated-date.pot`

```bash
# Regenerate the .pot template from source PHP files (requires WP-CLI)
composer i18n:pot

# Compile all .po files in the languages directory
wp i18n make-mo languages/
```

To contribute a locale, copy the POT to `languages/micro-site-care-post-last-updated-date-{locale}.po`, translate it with [Poedit](https://poedit.net/) or any `.po` editor, compile it with the command above, and submit both files.

**Supported locales:** de_DE, de_CH, es_ES, es_MX, fr_FR, fr_CA, it_IT, ja, nl_NL, nl_BE, pt_BR, pt_PT

### Composer Scripts

| Script | Command | Description |
|--------|---------|-------------|
| `lint` | `composer lint` | Run PHP_CodeSniffer |
| `lint-fix` | `composer lint-fix` | Auto-fix coding standard issues |
| `i18n:pot` | `composer i18n:pot` | Regenerate .pot file from source |
| `test` | `composer test` | Run PHPUnit tests |
| `test:coverage` | `composer run test:coverage` | Run tests with HTML coverage report |

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

GPL-2.0+ — see [LICENSE](LICENSE) for details.
