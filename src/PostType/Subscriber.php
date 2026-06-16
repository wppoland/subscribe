<?php

declare(strict_types=1);

namespace Subscribe\PostType;

use Subscribe\Contract\HasHooks;

defined('ABSPATH') || exit;

/**
 * The private custom post type that stores newsletter subscribers.
 *
 * Subscribers are never public — the CPT is registered with public => false and
 * surfaced only in wp-admin under the WooCommerce menu. Each post stores the
 * subscriber's email (also the post title), the explicit consent flag, the
 * source the opt-in came from and the timestamp, all as post meta.
 */
final class Subscriber implements HasHooks
{
    public const POST_TYPE = 'subscribe_subscriber';

    public const META_EMAIL     = '_subscribe_email';
    public const META_CONSENT   = '_subscribe_consent';
    public const META_SOURCE    = '_subscribe_source';
    public const META_CONSENTED = '_subscribe_consented_at';

    /** Recognised opt-in sources. */
    public const SOURCE_CHECKOUT = 'checkout';

    public function registerHooks(): void
    {
        $this->register();

        if (is_admin()) {
            add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'columns']);
            add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'renderColumn'], 10, 2);
            add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [$this, 'sortableColumns']);
            add_action('add_meta_boxes', [$this, 'addMetaBox']);
        }
    }

    /**
     * Register the post type. Called directly (not only via hook) so it is
     * available immediately during boot on the init action.
     */
    public function register(): void
    {
        if (post_type_exists(self::POST_TYPE)) {
            return;
        }

        register_post_type(
            self::POST_TYPE,
            [
                'labels'              => [
                    'name'               => __('Subscribers', 'subscribe'),
                    'singular_name'      => __('Subscriber', 'subscribe'),
                    'menu_name'          => __('Subscribers', 'subscribe'),
                    'all_items'          => __('Subscribers', 'subscribe'),
                    'edit_item'          => __('View Subscriber', 'subscribe'),
                    'view_item'          => __('View Subscriber', 'subscribe'),
                    'search_items'       => __('Search subscribers', 'subscribe'),
                    'not_found'          => __('No subscribers found.', 'subscribe'),
                    'not_found_in_trash' => __('No subscribers in Trash.', 'subscribe'),
                ],
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => 'woocommerce',
                'show_in_rest'        => false,
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
                'has_archive'         => false,
                'rewrite'             => false,
                'query_var'           => false,
                'hierarchical'        => false,
                'menu_icon'           => 'dashicons-email-alt',
                'supports'            => ['title'],
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
                'capabilities'        => [
                    'create_posts' => 'do_not_allow',
                ],
            ],
        );
    }

    /**
     * Whether an email is already subscribed (idempotency guard — never store a
     * duplicate). Matches on the email meta, case-insensitively.
     */
    public function exists(string $email): bool
    {
        $email = sanitize_email($email);

        if ('' === $email) {
            return false;
        }

        $found = get_posts(
            [
                'post_type'        => self::POST_TYPE,
                'post_status'      => ['publish', 'private'],
                'posts_per_page'   => 1,
                'fields'           => 'ids',
                'no_found_rows'    => true,
                'suppress_filters' => false,
                'meta_query'       => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- single targeted lookup for de-duplication.
                    [
                        'key'     => self::META_EMAIL,
                        'value'   => $email,
                        'compare' => '=',
                    ],
                ],
            ],
        );

        return [] !== $found;
    }

    /**
     * Persist a subscriber. Returns the new post ID, 0 on failure, or the
     * existing ID if the email is already subscribed (idempotent).
     */
    public function create(string $email, string $source): int
    {
        $email  = sanitize_email($email);
        $source = sanitize_key($source);

        if ('' === $email || ! is_email($email)) {
            return 0;
        }

        if ($this->exists($email)) {
            return 0;
        }

        $postId = wp_insert_post(
            [
                'post_type'   => self::POST_TYPE,
                'post_status' => 'private',
                'post_title'  => $email,
            ],
            true,
        );

        if (is_wp_error($postId) || 0 === $postId) {
            return 0;
        }

        $source = '' !== $source ? $source : self::SOURCE_CHECKOUT;

        update_post_meta($postId, self::META_EMAIL, $email);
        update_post_meta($postId, self::META_CONSENT, 1);
        update_post_meta($postId, self::META_SOURCE, $source);
        update_post_meta($postId, self::META_CONSENTED, time());

        $postId = (int) $postId;

        /**
         * Fires once when a brand-new subscriber has been recorded.
         *
         * Only fires for genuinely new subscribers — the email de-duplication
         * above guarantees this never fires for an existing subscriber. Add-ons
         * (e.g. Subscribe Pro's welcome email) hook this to react to new opt-ins.
         *
         * @param int    $postId The new subscriber post ID.
         * @param string $email  The subscriber's sanitised email address.
         * @param string $source The opt-in source key (e.g. "checkout").
         */
        do_action('subscribe/subscriber_created', $postId, $email, $source);

        return $postId;
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function columns(array $columns): array
    {
        $reordered = [];

        foreach ($columns as $key => $label) {
            if ('title' === $key) {
                $reordered['subscribe_email'] = __('Email', 'subscribe');
                continue;
            }

            if ('date' === $key) {
                $reordered['subscribe_source']   = __('Source', 'subscribe');
                $reordered['subscribe_consented'] = __('Subscribed', 'subscribe');
            }

            $reordered[$key] = $label;
        }

        return $reordered;
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function sortableColumns(array $columns): array
    {
        $columns['subscribe_consented'] = 'date';

        return $columns;
    }

    public function renderColumn(string $column, int $postId): void
    {
        switch ($column) {
            case 'subscribe_email':
                $email = (string) get_post_meta($postId, self::META_EMAIL, true);
                if ('' !== $email) {
                    printf('<a href="%1$s">%2$s</a>', esc_url('mailto:' . $email), esc_html($email));
                } else {
                    echo '&mdash;';
                }
                break;

            case 'subscribe_source':
                echo esc_html($this->sourceLabel((string) get_post_meta($postId, self::META_SOURCE, true)));
                break;

            case 'subscribe_consented':
                $ts = absint(get_post_meta($postId, self::META_CONSENTED, true));
                echo esc_html($ts > 0 ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $ts) : '&mdash;');
                break;
        }
    }

    public function addMetaBox(): void
    {
        add_meta_box(
            'subscribe_subscriber_details',
            __('Subscriber details', 'subscribe'),
            [$this, 'renderMetaBox'],
            self::POST_TYPE,
            'normal',
            'high',
        );
    }

    public function renderMetaBox(\WP_Post $post): void
    {
        $email   = (string) get_post_meta($post->ID, self::META_EMAIL, true);
        $source  = (string) get_post_meta($post->ID, self::META_SOURCE, true);
        $consent = (bool) get_post_meta($post->ID, self::META_CONSENT, true);
        $ts      = absint(get_post_meta($post->ID, self::META_CONSENTED, true));
        ?>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <th style="width:180px"><?php esc_html_e('Email', 'subscribe'); ?></th>
                    <td>
                        <?php if ('' !== $email) : ?>
                            <a href="<?php echo esc_url('mailto:' . $email); ?>"><?php echo esc_html($email); ?></a>
                        <?php else : ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Consent', 'subscribe'); ?></th>
                    <td>
                        <?php
                        echo $consent
                            ? esc_html__('Explicit opt-in recorded', 'subscribe')
                            : esc_html__('No consent recorded', 'subscribe');
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Source', 'subscribe'); ?></th>
                    <td><?php echo esc_html($this->sourceLabel($source)); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Subscribed at', 'subscribe'); ?></th>
                    <td>
                        <?php
                        echo esc_html(
                            $ts > 0
                                ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $ts)
                                : '—',
                        );
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Human-readable label for a stored source key.
     */
    public function sourceLabel(string $source): string
    {
        switch ($source) {
            case self::SOURCE_CHECKOUT:
                return __('Checkout', 'subscribe');
            case '':
                return '—';
            default:
                return ucwords(str_replace(['_', '-'], ' ', $source));
        }
    }
}
