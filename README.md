# Subscribe - Newsletter Opt-In for WooCommerce

A GDPR-minded newsletter opt-in for WooCommerce. Subscribe adds an unchecked-by-default consent checkbox at checkout and a `[subscribe_form]` shortcode, then stores subscribers (email, consent, source and timestamp) as a private custom post type you can review and export to CSV. No external email service required.

## Features

- Newsletter opt-in checkbox on the classic WooCommerce checkout.
- `[subscribe_form]` shortcode for a standalone opt-in form.
- Explicit, unchecked-by-default consent with a configurable label.
- Subscribers stored as a private list under **WooCommerce → Subscribers**.
- One-click **Export to CSV**.
- Optional admin notification on each new subscriber.
- A repeat email is never stored or notified twice.

## Installation

1. Upload the plugin to `/wp-content/plugins/subscribe`, or install it via **Plugins → Add New**.
2. Activate it. WooCommerce must be active.
3. The consent checkbox appears at checkout automatically; add `[subscribe_form]` to any page for a standalone form.

## Frequently Asked Questions

**Does it send emails to subscribers?**
No. Subscribe collects and stores opt-ins. You can export the list to CSV and import it into your email tool of choice.

**Is consent opt-in by default?**
The checkbox is always unchecked by default, so subscribers must explicitly opt in.

Built by WPPoland — https://plogins.com

License: GPL-2.0-or-later
