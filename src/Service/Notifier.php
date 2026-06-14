<?php

declare(strict_types=1);

namespace Subscribe\Service;

defined('ABSPATH') || exit;

/**
 * Sends the optional admin notification when a new subscriber opts in.
 *
 * Notifications are best-effort: failures never interrupt the customer flow. The
 * caller is responsible for only invoking this for genuinely new subscribers, so
 * we never double-notify for a duplicate opt-in.
 */
final class Notifier
{
    public function __construct(private readonly SettingsStore $settings)
    {
    }

    /**
     * Notify the configured recipient about a new subscriber, if enabled.
     */
    public function notifyNewSubscriber(string $email, string $sourceLabel): void
    {
        if (! (bool) $this->settings->get('notify', false)) {
            return;
        }

        $email = sanitize_email($email);

        if ('' === $email || ! is_email($email)) {
            return;
        }

        $recipient = trim((string) $this->settings->get('recipient', ''));

        if ('' === $recipient || ! is_email($recipient)) {
            $recipient = (string) get_option('admin_email');
        }

        if ('' === $recipient) {
            return;
        }

        $siteName = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] New newsletter subscriber', 'subscribe'),
            $siteName,
        );

        $lines   = [];
        $lines[] = sprintf(
            /* translators: %s: site name */
            __('A new visitor opted in to your newsletter on %s.', 'subscribe'),
            $siteName,
        );
        $lines[] = '';
        $lines[] = __('Email:', 'subscribe') . ' ' . $email;
        $lines[] = __('Source:', 'subscribe') . ' ' . $sourceLabel;

        wp_mail($recipient, $subject, implode("\n", $lines));
    }
}
