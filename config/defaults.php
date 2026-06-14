<?php
/**
 * Default settings, merged under the option key `subscribe_settings`.
 *
 * The plugin ships enabled with the checkout checkbox on and unticked by default
 * for explicit, GDPR-minded consent. No email service is integrated in the free
 * plugin — subscribers are stored privately for you to review and export.
 *
 * @package Subscribe
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    // Master switch for the checkout checkbox and the [subscribe_form] shortcode.
    'enabled' => true,

    // Show the opt-in checkbox on the classic checkout.
    'checkout' => true,

    // Consent label. Empty = translated default ("Yes, sign me up for the newsletter.").
    'label' => '',

    // Pre-check the box. Off by default for valid GDPR consent.
    'default_checked' => false,

    // Classic-checkout placement: 'after_terms', 'before_terms' or 'after_billing'.
    'placement' => 'after_terms',

    // Email the admin on each new subscriber.
    'notify' => false,

    // Notification recipient. Empty = site admin email.
    'recipient' => '',
];
