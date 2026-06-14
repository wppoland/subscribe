<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Services are thin and self-contained — this plugin has no external
 * runtime dependencies.
 *
 * @package Subscribe
 */

declare(strict_types=1);

use Subscribe\Admin\Export;
use Subscribe\Admin\Settings;
use Subscribe\Container;
use Subscribe\Migrator;
use Subscribe\PostType\Subscriber;
use Subscribe\Service\Checkout;
use Subscribe\Service\SettingsStore;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    // Merged settings (defaults + stored option), shared everywhere.
    $c->singleton(SettingsStore::class, static fn (): SettingsStore => new SettingsStore());

    // The private custom post type that stores subscribers.
    $c->singleton(Subscriber::class, static fn (): Subscriber => new Subscriber());

    // Storefront: checkout opt-in checkbox + capture.
    $c->singleton(Checkout::class, static fn (Container $c): Checkout => new Checkout(
        $c->get(SettingsStore::class),
        $c->get(Subscriber::class),
    ));

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(Settings::class, static fn (): Settings => new Settings());
        $c->singleton(Export::class, static fn (Container $c): Export => new Export(
            $c->get(Subscriber::class),
        ));
    }
};
