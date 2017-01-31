<?php
// prevent direct access
defined('ABSPATH') || die('Direct access not allowed.' . PHP_EOL);
// add settings tab
add_filter('muauth_network_settings_tabs', 'muauth_mc_settings_tab');
// fetch lists
add_action('muauth_network_headers_mailchimp', 'muauth_mc_fetch_lists');
// enqueue js
add_action('muauth_network_headers_mailchimp', 'muauth_mc_admin_js');
// meta
add_filter('network_admin_plugin_action_links_' . MUAUTH_MC_BASE, 'muauth_mc_admin_plugin_links');
// alert when no lists selected
add_action('muauth_mc_settings_head', 'muauth_mc_settings_err_alert');