<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping
 * WordPress or running the main plugin file.
 *
 * @package Subscribe
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('SUBSCRIBE_DIR')) {
        define('SUBSCRIBE_DIR', '/tmp/subscribe/');
    }
    if (! defined('SUBSCRIBE_URL')) {
        define('SUBSCRIBE_URL', 'https://example.test/wp-content/plugins/subscribe/');
    }
}

namespace Subscribe {
    if (! defined('Subscribe\\VERSION')) {
        define('Subscribe\\VERSION', '0.1.0');
    }
    if (! defined('Subscribe\\PLUGIN_FILE')) {
        define('Subscribe\\PLUGIN_FILE', '/tmp/subscribe/subscribe.php');
    }
}
