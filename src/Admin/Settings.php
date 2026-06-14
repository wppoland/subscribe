<?php

declare(strict_types=1);

namespace Subscribe\Admin;

defined('ABSPATH') || exit;

use Subscribe\Contract\HasHooks;

/**
 * Admin settings page registered as a WooCommerce submenu.
 *
 * Stores everything in the `subscribe_settings` option (array): the master
 * toggle, the consent checkbox label, its default checked state, the checkout
 * placement and whether to email the admin on each new subscriber. All output
 * is escaped; all input is sanitised on save.
 */
final class Settings implements HasHooks
{
    public const OPTION = 'subscribe_settings';

    private const PAGE  = 'subscribe-settings';
    private const GROUP = 'subscribe_settings_group';

    /** Recognised checkout placements (classic checkout hooks). */
    private const PLACEMENTS = ['after_terms', 'before_terms', 'after_billing'];

    /** Incremented to give each inline-help control a unique id/anchor. */
    private int $helpSeq = 0;

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if ('woocommerce_page_' . self::PAGE !== $hook) {
            return;
        }

        wp_enqueue_style(
            'subscribe-admin',
            SUBSCRIBE_URL . 'assets/css/admin.css',
            [],
            \Subscribe\VERSION,
        );
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Subscribe — Newsletter Opt-In', 'subscribe'),
            __('Subscribe', 'subscribe'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::GROUP,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        add_filter(
            'option_page_capability_' . self::GROUP,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings  = $this->settings();
        $placement = (string) ($settings['placement'] ?? 'after_terms');
        ?>
        <div class="wrap subscribe-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="subscribe-intro">
                <h2><?php esc_html_e('Grow your newsletter from checkout', 'subscribe'); ?></h2>
                <p>
                    <?php esc_html_e('Add a GDPR-minded newsletter opt-in to your checkout (unchecked by default) and collect subscribers with explicit consent. Every opt-in is stored privately with its source and timestamp, ready to review and export.', 'subscribe'); ?>
                </p>
                <p>
                    <?php
                    printf(
                        /* translators: %s: shortcode wrapped in <code>. */
                        esc_html__('Prefer a standalone form? Add the %s shortcode to any page or widget.', 'subscribe'),
                        '<code>[subscribe_form]</code>',
                    );
                    ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::GROUP); ?>

                <div class="subscribe-card">
                    <h2><?php esc_html_e('Opt-in', 'subscribe'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Enable opt-in', 'subscribe'); ?>
                                    <?php $this->help(__('The master switch. When off, the checkout checkbox and the [subscribe_form] shortcode render nothing.', 'subscribe')); ?>
                                </th>
                                <td>
                                    <label for="subscribe_enabled">
                                        <input type="checkbox" id="subscribe_enabled"
                                            name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1"
                                            <?php checked((bool) ($settings['enabled'] ?? false), true); ?> />
                                        <?php esc_html_e('Show the newsletter opt-in to customers.', 'subscribe'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="subscribe_checkout"><?php esc_html_e('Checkout checkbox', 'subscribe'); ?></label>
                                    <?php $this->help(__('Show the opt-in checkbox on the classic checkout. The [subscribe_form] shortcode works regardless of this setting.', 'subscribe')); ?>
                                </th>
                                <td>
                                    <label for="subscribe_checkout">
                                        <input type="checkbox" id="subscribe_checkout"
                                            name="<?php echo esc_attr(self::OPTION); ?>[checkout]" value="1"
                                            <?php checked((bool) ($settings['checkout'] ?? true), true); ?> />
                                        <?php esc_html_e('Add the opt-in checkbox at checkout.', 'subscribe'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="subscribe_label"><?php esc_html_e('Checkbox label', 'subscribe'); ?></label>
                                    <?php $this->help(__('The consent text shown next to the checkbox. Make it clear what the customer is agreeing to. Leave blank to use the default.', 'subscribe')); ?>
                                </th>
                                <td>
                                    <input type="text" id="subscribe_label" class="large-text"
                                        name="<?php echo esc_attr(self::OPTION); ?>[label]"
                                        value="<?php echo esc_attr((string) ($settings['label'] ?? '')); ?>"
                                        placeholder="<?php esc_attr_e('Yes, sign me up for the newsletter.', 'subscribe'); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Default state', 'subscribe'); ?>
                                    <?php $this->help(__('For valid GDPR consent the checkbox must be unticked by default — the customer has to actively opt in. Only enable pre-checking if your local law allows it.', 'subscribe')); ?>
                                </th>
                                <td>
                                    <label for="subscribe_default">
                                        <input type="checkbox" id="subscribe_default"
                                            name="<?php echo esc_attr(self::OPTION); ?>[default_checked]" value="1"
                                            <?php checked((bool) ($settings['default_checked'] ?? false), true); ?> />
                                        <?php esc_html_e('Pre-check the box (not recommended for GDPR).', 'subscribe'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Recommended: leave this off so consent is always explicit.', 'subscribe'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="subscribe_placement"><?php esc_html_e('Checkout placement', 'subscribe'); ?></label>
                                    <?php $this->help(__('Where the checkbox appears on the classic checkout. The blocks checkout is not affected by this setting.', 'subscribe')); ?>
                                </th>
                                <td>
                                    <select id="subscribe_placement" name="<?php echo esc_attr(self::OPTION); ?>[placement]">
                                        <option value="after_terms" <?php selected($placement, 'after_terms'); ?>>
                                            <?php esc_html_e('After the terms & conditions', 'subscribe'); ?>
                                        </option>
                                        <option value="before_terms" <?php selected($placement, 'before_terms'); ?>>
                                            <?php esc_html_e('Before the terms & conditions', 'subscribe'); ?>
                                        </option>
                                        <option value="after_billing" <?php selected($placement, 'after_billing'); ?>>
                                            <?php esc_html_e('After the billing details', 'subscribe'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="subscribe-card">
                    <h2><?php esc_html_e('Notifications', 'subscribe'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Admin notification', 'subscribe'); ?>
                                    <?php $this->help(__('Email the recipient below whenever a new subscriber opts in. Leave the recipient blank to use the site admin email.', 'subscribe')); ?>
                                </th>
                                <td>
                                    <label for="subscribe_notify">
                                        <input type="checkbox" id="subscribe_notify"
                                            name="<?php echo esc_attr(self::OPTION); ?>[notify]" value="1"
                                            <?php checked((bool) ($settings['notify'] ?? false), true); ?> />
                                        <?php esc_html_e('Email me when someone subscribes.', 'subscribe'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="subscribe_recipient"><?php esc_html_e('Recipient email', 'subscribe'); ?></label>
                                    <?php $this->help(__('Where new-subscriber notifications are sent. Leave blank to use the site admin email.', 'subscribe')); ?>
                                </th>
                                <td>
                                    <input type="email" id="subscribe_recipient" class="regular-text"
                                        name="<?php echo esc_attr(self::OPTION); ?>[recipient]"
                                        value="<?php echo esc_attr((string) ($settings['recipient'] ?? '')); ?>"
                                        placeholder="<?php echo esc_attr((string) get_option('admin_email')); ?>" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render an accessible inline-help affordance using the native Popover API.
     */
    private function help(string $text): void
    {
        $id = 'subscribe-help-' . (++$this->helpSeq);
        ?>
        <button type="button" class="subscribe-help"
            aria-label="<?php esc_attr_e('More information', 'subscribe'); ?>"
            aria-describedby="<?php echo esc_attr($id); ?>"
            popovertarget="<?php echo esc_attr($id); ?>">?</button>
        <div id="<?php echo esc_attr($id); ?>" class="subscribe-tip" role="tooltip" popover hidden>
            <?php echo esc_html($text); ?>
        </div>
        <?php
    }

    /**
     * Sanitise the submitted settings before save.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $placement = isset($raw['placement']) ? sanitize_key((string) $raw['placement']) : 'after_terms';

        if (! in_array($placement, self::PLACEMENTS, true)) {
            $placement = 'after_terms';
        }

        $recipient = isset($raw['recipient']) ? sanitize_email((string) $raw['recipient']) : '';

        return [
            'enabled'         => ! empty($raw['enabled']),
            'checkout'        => ! empty($raw['checkout']),
            'label'           => isset($raw['label']) ? sanitize_text_field((string) $raw['label']) : '',
            'default_checked' => ! empty($raw['default_checked']),
            'placement'       => $placement,
            'notify'          => ! empty($raw['notify']),
            'recipient'       => is_email($recipient) ? $recipient : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require SUBSCRIBE_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }
}
