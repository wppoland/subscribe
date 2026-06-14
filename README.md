# Subscribe - Newsletter Opt-In for WooCommerce

A GDPR-minded newsletter opt-in for WooCommerce. Adds an unchecked-by-default
consent checkbox at checkout and a `[subscribe_form]` shortcode, and stores
subscribers (email + consent + source + timestamp) as a private custom post type
you can review and export to CSV. No external email service required.

## Features

- Newsletter opt-in checkbox on the classic WooCommerce checkout.
- `[subscribe_form]` shortcode for a standalone opt-in form.
- Explicit, unchecked-by-default consent with a configurable label.
- Subscribers stored as a private CPT under **WooCommerce → Subscribers**.
- One-click **Export to CSV**.
- Optional admin notification on each new subscriber.
- Idempotent recording — a repeat email is never stored or notified twice.
- Accessible, dark-mode-aware, translation-ready, clean uninstall.
- HPOS and cart/checkout blocks compatible.

## Requirements

- WordPress 6.5+
- WooCommerce 8.0+
- PHP 8.1+

## Development

```bash
composer install
composer cs        # PHP_CodeSniffer (WordPress security ruleset)
composer analyse   # PHPStan level 6
```

The plugin is self-contained — it has no runtime Composer dependencies. The
`autoload.php` PSR-4 fallback loads `src/` when `vendor/` is absent (the wp.org
build excludes `vendor/`).

## Extension point

Pro add-ons boot via the `subscribe/booted` action and can react to new
subscribers via `subscribe/subscriber_created` (`$postId`, `$email`, `$source`).

## License

GPL-2.0-or-later.
