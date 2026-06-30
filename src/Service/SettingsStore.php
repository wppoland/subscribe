<?php

declare(strict_types=1);

namespace Subscribe\Service;

use Subscribe\Admin\Settings;

defined('ABSPATH') || exit;

/**
 * Read-only access to the merged plugin settings (defaults + stored option).
 *
 * A single source of truth so the storefront and the admin both resolve the same
 * values without each re-implementing the merge.
 */
final class SettingsStore
{
    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        $stored = get_option(Settings::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require SUBSCRIBE_DIR . 'config/defaults.php';

        return $this->cache = array_merge($defaults, $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get('enabled', false);
    }

    /**
     * The consent label, falling back to the translated default when unset.
     */
    public function label(): string
    {
        $label = trim((string) $this->get('label', ''));

        return '' !== $label
            ? $label
            : __('Yes, sign me up for the newsletter.', 'plogins-subscribe');
    }
}
