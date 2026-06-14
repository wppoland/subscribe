=== Subscribe - Newsletter Opt-In for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, newsletter, opt-in, gdpr, checkout
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a GDPR-minded newsletter opt-in at checkout and via shortcode, and collect subscribers with explicit consent.

== Description ==

Subscribe adds a newsletter opt-in checkbox to your WooCommerce checkout and a
`[subscribe_form]` shortcode you can drop on any page. When a customer ticks the
box, their email is stored privately with their explicit consent, the source and
a timestamp — ready for you to review and export.

It is GDPR-minded by design: the consent checkbox is **unchecked by default**, the
label is configurable, and nothing is sent to any external service. Subscribers
are kept as a private custom post type you fully own.

= Features =

* A newsletter opt-in checkbox on the classic WooCommerce checkout.
* A `[subscribe_form]` shortcode: a standalone opt-in form for any page or widget.
* Explicit, unchecked-by-default consent with a configurable label (GDPR-minded).
* Subscribers stored as a private custom post type — email, consent, source and timestamp.
* Review subscribers under **WooCommerce → Subscribers** in wp-admin.
* One-click **Export to CSV** of every subscriber.
* Optional admin email notification on each new subscriber.
* Configurable checkout placement (after/before terms, or after billing).
* No-duplicate, idempotent recording — a repeat email is never stored or notified twice.
* No external email service required — you own your data.
* Accessible, mobile-friendly markup with dark-mode-aware styling.
* Translation ready (POT included) and clean uninstall.
* HPOS and cart/checkout blocks compatible.

= The [subscribe_form] shortcode =

Add the shortcode to any page or widget:

`[subscribe_form]`

Optional attributes:

`[subscribe_form title="Join our list" description="Monthly updates, no spam."]`

== Installation ==

1. Upload the plugin to `/wp-content/plugins/subscribe`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be installed and active.
3. Go to **WooCommerce → Subscribe** to set your checkbox label, default state, placement and notifications.
4. Optionally add the `[subscribe_form]` shortcode to a page for a standalone opt-in form.
5. Review subscribers under **WooCommerce → Subscribers** and export them to CSV.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. WooCommerce must be installed and active.

= Is the checkbox checked by default? =

No. For valid GDPR consent the checkbox is unchecked by default, so customers
have to actively opt in. You can change this in the settings if your local law
allows it.

= Where are subscribers stored? =

Each subscriber is saved as a private "Subscriber" record (a custom post type)
under the WooCommerce menu, with their email, consent, source and timestamp. You
can export them all to a CSV file.

= Does it send the subscriber to Mailchimp or another service? =

No. The free plugin keeps subscribers on your own site so you stay in control.
Integrations with email service providers are planned for the Pro version.

= Does the shortcode work without WooCommerce checkout? =

Yes. The `[subscribe_form]` shortcode is a standalone opt-in form that works on
any page, independent of the checkout checkbox.

== Screenshots ==

1. The newsletter opt-in checkbox on the WooCommerce checkout.
2. The [subscribe_form] standalone opt-in form.
3. The Subscribe settings screen under WooCommerce.
4. The private Subscribers list with CSV export.

== Changelog ==

= 0.1.0 =
* Initial release: checkout opt-in checkbox, `[subscribe_form]` shortcode, private subscriber records with consent/source/timestamp, CSV export and optional admin notification.
