<?php

// prevent direct access
defined('ABSPATH') || die('Direct access not allowed.' . PHP_EOL);

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die('Direct access not allowed.' . PHP_EOL);
}

delete_site_option('muauth_mc_lists');
delete_site_option('muauth_mc_settings');