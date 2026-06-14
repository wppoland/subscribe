<?php

declare(strict_types=1);

namespace Subscribe\Admin;

defined('ABSPATH') || exit;

use Subscribe\Contract\HasHooks;

/**
 * Admin settings page registered as a WooCommerce submenu.
 *
 * Stores everything in the `subscribe_settings` option (array): the master
 * toggle, the consent checkbox label and its default checked state. All output
 * is escaped; all input is sanitised on save.
 */
final class Settings implements HasHooks
{
    public const OPTION = 'subscribe_settings';

    private const PAGE  = 'subscribe-settings';
    private const GROUP = 'subscribe_settings_group';

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

        $settings = $this->settings();
        ?>
        <div class="wrap subscribe-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="subscribe-intro">
                <h2><?php esc_html_e('Grow your newsletter from checkout', 'subscribe'); ?></h2>
                <p>
                    <?php esc_html_e('Add a GDPR-minded newsletter opt-in to your checkout (unchecked by default) and collect subscribers with explicit consent. Every opt-in is stored privately with its source and timestamp, ready to review and export.', 'subscribe'); ?>
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
                                </th>
                                <td>
                                    <label for="subscribe_enabled">
                                        <input type="checkbox" id="subscribe_enabled"
                                            name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1"
                                            <?php checked((bool) ($settings['enabled'] ?? false), true); ?> />
                                        <?php esc_html_e('Show the newsletter opt-in to customers.', 'subscribe'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('The master switch. When off, the checkout checkbox renders nothing.', 'subscribe'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="subscribe_checkout"><?php esc_html_e('Checkout checkbox', 'subscribe'); ?></label>
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
                                </th>
                                <td>
                                    <input type="text" id="subscribe_label" class="large-text"
                                        name="<?php echo esc_attr(self::OPTION); ?>[label]"
                                        value="<?php echo esc_attr((string) ($settings['label'] ?? '')); ?>"
                                        placeholder="<?php esc_attr_e('Yes, sign me up for the newsletter.', 'subscribe'); ?>" />
                                    <p class="description">
                                        <?php esc_html_e('The consent text shown next to the checkbox. Make it clear what the customer is agreeing to. Leave blank to use the default.', 'subscribe'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Default state', 'subscribe'); ?>
                                </th>
                                <td>
                                    <label for="subscribe_default">
                                        <input type="checkbox" id="subscribe_default"
                                            name="<?php echo esc_attr(self::OPTION); ?>[default_checked]" value="1"
                                            <?php checked((bool) ($settings['default_checked'] ?? false), true); ?> />
                                        <?php esc_html_e('Pre-check the box (not recommended for GDPR).', 'subscribe'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('For valid GDPR consent, leave this off so the customer always opts in explicitly.', 'subscribe'); ?>
                                    </p>
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

        return [
            'enabled'         => ! empty($raw['enabled']),
            'checkout'        => ! empty($raw['checkout']),
            'label'           => isset($raw['label']) ? sanitize_text_field((string) $raw['label']) : '',
            'default_checked' => ! empty($raw['default_checked']),
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
