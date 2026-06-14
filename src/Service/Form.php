<?php

declare(strict_types=1);

namespace Subscribe\Service;

use Subscribe\Contract\HasHooks;
use Subscribe\PostType\Subscriber;

defined('ABSPATH') || exit;

/**
 * The [subscribe_form] shortcode: a standalone newsletter opt-in form with an
 * email field and an explicit consent checkbox (unchecked by default).
 *
 * Submissions are nonce-verified and handled inline. The form renders graceful
 * states for success, an already-subscribed email and validation errors, and
 * never emits a fatal if data is missing. All output is escaped; all input is
 * sanitised.
 */
final class Form implements HasHooks
{
    private const NONCE  = 'subscribe_form_submit';
    private const ACTION = 'subscribe_form';

    public function __construct(
        private readonly SettingsStore $settings,
        private readonly Subscriber $subscribers,
        private readonly Notifier $notifier,
    ) {
    }

    public function registerHooks(): void
    {
        add_shortcode('subscribe_form', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
    }

    public function registerAssets(): void
    {
        wp_register_style(
            'subscribe-form',
            SUBSCRIBE_URL . 'assets/css/form.css',
            [],
            \Subscribe\VERSION,
        );
    }

    /**
     * Render the shortcode. Returns escaped HTML, or an empty string when the
     * opt-in is disabled.
     *
     * @param array<string, mixed>|string $atts
     */
    public function render(array|string $atts = []): string
    {
        if (! $this->settings->isEnabled()) {
            return '';
        }

        $atts = shortcode_atts(
            [
                'title'       => __('Subscribe to our newsletter', 'subscribe'),
                'description' => '',
            ],
            is_array($atts) ? $atts : [],
            'subscribe_form',
        );

        wp_enqueue_style('subscribe-form');

        [$status, $message] = $this->handleSubmission();

        ob_start();
        echo '<div class="subscribe-form-wrap">';

        if ('success' === $status) {
            $this->renderNotice('success', $message);
            echo '</div>';
            return (string) ob_get_clean();
        }

        $title       = (string) $atts['title'];
        $description = (string) $atts['description'];
        ?>
        <form class="subscribe-form" method="post" novalidate>
            <?php if ('' !== $title) : ?>
                <h2 class="subscribe-form__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <?php if ('' !== $description) : ?>
                <p class="subscribe-form__desc"><?php echo esc_html($description); ?></p>
            <?php endif; ?>

            <?php if ('error' === $status) : ?>
                <?php $this->renderNotice('error', $message); ?>
            <?php endif; ?>

            <?php wp_nonce_field(self::NONCE, 'subscribe_nonce'); ?>
            <input type="hidden" name="subscribe_action" value="<?php echo esc_attr(self::ACTION); ?>" />

            <p class="subscribe-form__field">
                <label for="subscribe-email"><?php esc_html_e('Email address', 'subscribe'); ?>
                    <span class="subscribe-form__req" aria-hidden="true">*</span></label>
                <input type="email" id="subscribe-email" name="subscribe_email" required
                    autocomplete="email"
                    value="<?php echo esc_attr($this->lastEmail()); ?>" />
            </p>

            <p class="subscribe-form__field subscribe-form__consent">
                <label for="subscribe-consent">
                    <input type="checkbox" id="subscribe-consent" name="subscribe_consent" value="1" required />
                    <span><?php echo esc_html($this->settings->label()); ?></span>
                </label>
            </p>

            <p class="subscribe-form__submit">
                <button type="submit" class="subscribe-form__button">
                    <?php esc_html_e('Subscribe', 'subscribe'); ?>
                </button>
            </p>
        </form>
        <?php

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Process a posted form. Returns [status, message] where status is one of
     * '', 'success' or 'error'.
     *
     * @return array{0: string, 1: string}
     */
    private function handleSubmission(): array
    {
        if (! isset($_POST['subscribe_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return ['', ''];
        }

        $action = sanitize_key(wp_unslash($_POST['subscribe_action'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if (self::ACTION !== $action) {
            return ['', ''];
        }

        $nonce = isset($_POST['subscribe_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['subscribe_nonce']))
            : '';

        if (! wp_verify_nonce($nonce, self::NONCE)) {
            return ['error', __('Your session expired. Please try again.', 'subscribe')];
        }

        $consent = isset($_POST['subscribe_consent'])
            && '1' === sanitize_text_field(wp_unslash($_POST['subscribe_consent']));

        if (! $consent) {
            return ['error', __('Please tick the box to give your consent.', 'subscribe')];
        }

        $email = isset($_POST['subscribe_email'])
            ? sanitize_email(wp_unslash($_POST['subscribe_email']))
            : '';

        if ('' === $email || ! is_email($email)) {
            return ['error', __('Please enter a valid email address.', 'subscribe')];
        }

        if ($this->subscribers->exists($email)) {
            // Idempotent + privacy-friendly: report success without re-storing.
            return ['success', __('You are already subscribed — thank you!', 'subscribe')];
        }

        $postId = $this->subscribers->create($email, Subscriber::SOURCE_SHORTCODE);

        if ($postId <= 0) {
            return ['error', __('Something went wrong. Please try again.', 'subscribe')];
        }

        $this->notifier->notifyNewSubscriber($email, $this->subscribers->sourceLabel(Subscriber::SOURCE_SHORTCODE));

        return ['success', __('Thanks for subscribing! Please check your inbox.', 'subscribe')];
    }

    private function renderNotice(string $type, string $message): void
    {
        if ('' === $message) {
            return;
        }

        $role = 'success' === $type ? 'status' : 'alert';

        printf(
            '<div class="subscribe-form__notice subscribe-form__notice--%1$s" role="%2$s">%3$s</div>',
            esc_attr($type),
            esc_attr($role),
            esc_html($message),
        );
    }

    /**
     * Repopulate the email field after a validation error (never after success).
     */
    private function lastEmail(): string
    {
        if (! isset($_POST['subscribe_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return '';
        }

        $nonce = isset($_POST['subscribe_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['subscribe_nonce']))
            : '';

        if (! wp_verify_nonce($nonce, self::NONCE)) {
            return '';
        }

        return isset($_POST['subscribe_email'])
            ? sanitize_email(wp_unslash($_POST['subscribe_email']))
            : '';
    }
}
