<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Subscribe\Contract\HasHooks.
 *
 * @package Subscribe
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Subscribe\Admin\Export;
use Subscribe\Admin\Settings;
use Subscribe\PostType\Subscriber;
use Subscribe\Service\Checkout;
use Subscribe\Service\Form;

defined('ABSPATH') || exit;

return is_admin()
    ? [
        Subscriber::class,
        Checkout::class,
        Form::class,
        Settings::class,
        Export::class,
    ]
    : [
        Subscriber::class,
        Checkout::class,
        Form::class,
    ];
