=== Subscribe - Newsletter Opt-In for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, newsletter, opt-in, gdpr, checkout
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a newsletter opt-in checkbox to the WooCommerce checkout and records each subscriber's email, consent and signup date.

== Description ==

Subscribe puts a newsletter opt-in checkbox on your WooCommerce checkout. When a
customer ticks it and places the order, their email is saved on your own site
along with the consent flag, where the opt-in came from, and the date. You review
the list under WooCommerce and export it whenever you need it.

The checkbox is unticked by default, which is what most GDPR setups need: the
customer has to make an active choice to opt in. You can edit the label text, and
nothing is ever sent to an outside service, so the email addresses stay in your
database and nowhere else.

The plugin is built for the source to be easy to read and fork. If you hit a bug
or want to suggest a change, the code and issue tracker live at
https://github.com/wppoland/subscribe.

= What it does =

* Adds a newsletter opt-in checkbox to the classic WooCommerce checkout.
* Records the opt-in only when the customer ticks the box; it is unticked by default and the label is editable.
* Saves each subscriber as a private custom post type record with email, consent, source and signup date.
* Lists subscribers under WooCommerce > Subscribers in wp-admin.
* Exports the whole list to a CSV file with one click (CSV-injection safe).
* Skips duplicates, so the same email is never recorded twice.
* Keeps everything in your own database. No third-party email service is involved.
* Ships a translation template (.pot) and removes its data on uninstall.
* Declares HPOS compatibility and works with WooCommerce 8.0 and up.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/subscribe`, or install it from Plugins > Add New.
2. Activate it. WooCommerce must be installed and active first.
3. Open WooCommerce > Subscribe to turn the opt-in on, set the checkbox label, and choose whether it starts ticked.
4. Find the people who opted in under WooCommerce > Subscribers, and use the Export to CSV button there to download them.

== Frequently Asked Questions ==

= Does it need WooCommerce? =

Yes. WooCommerce has to be installed and active, otherwise the plugin shows a notice and does nothing.

= Is the checkbox ticked by default? =

No. It starts unticked so the customer opts in on purpose, which is what GDPR consent generally requires. There is a setting to pre-tick it if your local rules permit, but that is off out of the box.

= Where do the subscribers end up? =

In your WordPress database, as private "Subscriber" records under the WooCommerce menu. Each one holds the email, the consent flag, the source and the signup date. You can export the lot to CSV.

= Does it push subscribers to Mailchimp or anything like that? =

No. There is no integration with any external email platform. The addresses stay on your site and go where you decide to take them.

= Does the checkbox show on the block-based checkout? =

The opt-in renders on the classic (shortcode) checkout. The plugin declares compatibility with the cart and checkout blocks so it does not trigger a warning, but the checkbox itself currently appears on the classic checkout.

== Screenshots ==

1. The newsletter opt-in checkbox on the WooCommerce checkout.
2. The Subscribers list with the Export to CSV button.

== External Services ==

Subscribe connects to no external services. The opt-in checkbox, the consent records and the CSV export all run on your own site, and no email addresses or order data are sent anywhere off it. Each subscriber is stored in your WordPress database as a private "subscribe_subscriber" custom post type record holding the email, consent flag, source and signup timestamp; its settings live in the "subscribe_settings" option. The plugin does not send email and is not tied to Mailchimp or any other mailing platform, so what you do with the exported list is entirely up to you.

== Changelog ==

= 0.1.0 =
* First release: checkout opt-in checkbox, private subscriber records storing consent, source and date, and CSV export.
</content>
</invoke>
