<?php

declare(strict_types=1);

namespace Subscribe\Service;

use Subscribe\Contract\HasHooks;
use Subscribe\PostType\Subscriber;

defined('ABSPATH') || exit;

/**
 * Adds the newsletter opt-in checkbox to the classic WooCommerce checkout and
 * records the subscriber when the order is placed and the box was ticked.
 *
 * The checkbox is unticked by default (configurable) for explicit GDPR consent.
 * Recording is idempotent — the Subscriber CPT de-duplicates by email, so a
 * repeat customer never creates a duplicate record.
 */
final class Checkout implements HasHooks
{
    private const FIELD = 'subscribe_optin';

    public function __construct(
        private readonly SettingsStore $settings,
        private readonly Subscriber $subscribers,
    ) {
    }

    public function registerHooks(): void
    {
        if (! $this->settings->isEnabled() || ! (bool) $this->settings->get('checkout', true)) {
            return;
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('woocommerce_checkout_after_terms_and_conditions', [$this, 'renderCheckbox']);

        // Persist the opt-in once the order is created. order_processed runs after
        // a successful checkout and gives us the posted fields safely.
        add_action('woocommerce_checkout_order_processed', [$this, 'capture'], 10, 2);
    }

    /**
     * Enqueue the opt-in row styles and the presentation-only postmark script,
     * only on the checkout where the field renders.
     */
    public function enqueueAssets(): void
    {
        if (! function_exists('is_checkout') || ! is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'subscribe-checkout',
            SUBSCRIBE_URL . 'assets/css/checkout.css',
            [],
            \Subscribe\VERSION,
        );

        wp_enqueue_script(
            'subscribe-checkout',
            SUBSCRIBE_URL . 'assets/js/checkout.js',
            [],
            \Subscribe\VERSION,
            true,
        );
    }

    /**
     * Render the consent checkbox. Output is fully escaped.
     */
    public function renderCheckbox(): void
    {
        $checked = (bool) $this->settings->get('default_checked', false);
        ?>
        <p class="form-row subscribe-optin" id="subscribe_optin_field">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox subscribe-optin__label">
                <input
                    type="checkbox"
                    class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox subscribe-optin__input"
                    name="<?php echo esc_attr(self::FIELD); ?>"
                    id="<?php echo esc_attr(self::FIELD); ?>"
                    value="1"
                    <?php checked($checked, true); ?>
                />
                <span class="subscribe-optin__text"><?php echo esc_html($this->settings->label()); ?></span>
                <span class="subscribe-optin__mark" aria-hidden="true"><?php echo esc_html__('Subscribed', 'subscribe'); ?></span>
            </label>
        </p>
        <?php
    }

    /**
     * Record the subscriber when the box was ticked.
     *
     * @param mixed $order Order object passed by WooCommerce (unused; we read POST).
     */
    public function capture(int $orderId, mixed $order = null): void
    {
        unset($order);

        // Nonce: WooCommerce verifies the checkout nonce itself before this fires.
        // We only read our own checkbox from the already-validated submission.
        $optedIn = isset($_POST[self::FIELD]) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            && '1' === sanitize_text_field(wp_unslash($_POST[self::FIELD])); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if (! $optedIn) {
            return;
        }

        $email = $this->orderEmail($orderId);

        if ('' === $email) {
            return;
        }

        // Idempotency: skip if already subscribed.
        if ($this->subscribers->exists($email)) {
            return;
        }

        $this->subscribers->create($email, Subscriber::SOURCE_CHECKOUT);
    }

    /**
     * Resolve the customer's email from the order, falling back to the posted
     * billing email if the order object is unavailable.
     */
    private function orderEmail(int $orderId): string
    {
        $order = function_exists('wc_get_order') ? wc_get_order($orderId) : null;

        if ($order instanceof \WC_Order) {
            $email = sanitize_email((string) $order->get_billing_email());
            if ('' !== $email && is_email($email)) {
                return $email;
            }
        }

        $posted = isset($_POST['billing_email']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            ? sanitize_email(wp_unslash($_POST['billing_email'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        return is_email($posted) ? $posted : '';
    }
}
