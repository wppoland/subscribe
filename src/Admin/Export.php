<?php

declare(strict_types=1);

namespace Subscribe\Admin;

use Subscribe\Contract\HasHooks;
use Subscribe\PostType\Subscriber;

defined('ABSPATH') || exit;

/**
 * Exports the subscriber list to a CSV download.
 *
 * The export is triggered from a nonce-protected admin link on the Subscribers
 * list table and gated behind the manage_woocommerce capability. Output streams
 * a CSV with email, consent, source and timestamp columns.
 */
final class Export implements HasHooks
{
    private const ACTION = 'subscribe_export';
    private const NONCE  = 'subscribe_export_csv';

    public function __construct(private readonly Subscriber $subscribers)
    {
    }

    public function registerHooks(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handle']);
        add_action('admin_notices', [$this, 'renderButton']);
    }

    /**
     * Render an Export button above the Subscribers list table.
     */
    public function renderButton(): void
    {
        $screen = get_current_screen();

        if (null === $screen || 'edit-' . Subscriber::POST_TYPE !== $screen->id) {
            return;
        }

        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=' . self::ACTION),
            self::NONCE,
            '_subscribe_nonce',
        );
        ?>
        <div class="notice notice-info subscribe-export-notice">
            <p>
                <?php esc_html_e('Export every subscriber (email, consent, source and date) to a CSV file.', 'subscribe'); ?>
                <a class="button button-primary" href="<?php echo esc_url($url); ?>">
                    <?php esc_html_e('Export to CSV', 'subscribe'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Stream the CSV download.
     */
    public function handle(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You are not allowed to export subscribers.', 'subscribe'), '', ['response' => 403]);
        }

        $nonce = isset($_GET['_subscribe_nonce'])
            ? sanitize_text_field(wp_unslash($_GET['_subscribe_nonce']))
            : '';

        if (! wp_verify_nonce($nonce, self::NONCE)) {
            wp_die(esc_html__('Security check failed. Please try again.', 'subscribe'), '', ['response' => 403]);
        }

        $rows = $this->rows();

        $lines   = [];
        $lines[] = $this->csvLine([
            __('Email', 'subscribe'),
            __('Consent', 'subscribe'),
            __('Source', 'subscribe'),
            __('Subscribed at', 'subscribe'),
        ]);

        foreach ($rows as $row) {
            $lines[] = $this->csvLine($row);
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="subscribers-' . gmdate('Y-m-d') . '.csv"');

        // Output is pre-escaped CSV text; not HTML. Echoing directly avoids the
        // PHP filesystem functions (fopen/fputcsv/fclose) that Plugin Check flags.
        echo implode("\r\n", $lines) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Build one RFC 4180 CSV line from a row of string values.
     *
     * @param array<int, string> $row
     */
    private function csvLine(array $row): string
    {
        $cells = array_map(
            static function (string $value): string {
                // Neutralize spreadsheet formula triggers (OWASP CSV-injection
                // mitigation): if a cell starts with =, +, -, @, tab or CR, a
                // subscriber-supplied value could execute as a formula when the
                // file is opened in Excel/Sheets. Prefix it with a single quote
                // before the RFC 4180 quote-wrapping below.
                if ('' !== $value && false !== strpbrk($value[0], "=+-@\t\r")) {
                    $value = "'" . $value;
                }

                // Escape double quotes and wrap every field in quotes.
                return '"' . str_replace('"', '""', $value) . '"';
            },
            $row,
        );

        return implode(',', $cells);
    }

    /**
     * Build the CSV data rows from every stored subscriber.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    private function rows(): array
    {
        $ids = get_posts(
            [
                'post_type'      => Subscriber::POST_TYPE,
                'post_status'    => ['publish', 'private'],
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ],
        );

        $rows = [];

        foreach ($ids as $id) {
            $id      = (int) $id;
            $email   = (string) get_post_meta($id, Subscriber::META_EMAIL, true);
            $consent = (bool) get_post_meta($id, Subscriber::META_CONSENT, true);
            $source  = (string) get_post_meta($id, Subscriber::META_SOURCE, true);
            $ts      = absint(get_post_meta($id, Subscriber::META_CONSENTED, true));

            $rows[] = [
                $email,
                $consent ? __('Yes', 'subscribe') : __('No', 'subscribe'),
                $this->subscribers->sourceLabel($source),
                $ts > 0 ? gmdate('Y-m-d H:i:s', $ts) : '',
            ];
        }

        return $rows;
    }
}
