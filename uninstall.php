<?php
/**
 * Uninstall cleanup for Subscribe.
 *
 * Runs when the plugin is deleted from wp-admin. Removes the plugin's options.
 * Stored subscribers (the subscribe_subscriber custom post type) are intentionally
 * left in place: they are merchant data with recorded consent that should survive
 * a reinstall and can be removed manually if desired.
 *
 * @package Subscribe
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('subscribe_settings');
delete_option('subscribe_db_version');
