<?php
// prevent direct access
defined('ABSPATH') || die('Direct access not allowed.' . PHP_EOL);
// load i18n
add_action('plugins_loaded', 'muauth_mc_textdomain');
// parse custom settings
add_action('init', 'muauth_mc_parse_settings');
// load class
add_action('muauth_mc_ready', 'muauth_mc_load_mailchimp_lib');
// front-end
add_action('muauth_mc_ready', 'muauth_mc_frontend_init', 12);
// parse field
add_action('muauth_mc_lists_ready', 'muauth_mc_register_signup_field');
// css
add_action('muauth_mc_lists_ready', 'muauth_mc_wp_head');
// parse hidden fields upon stage changes
add_action('muauth_register_form_data', 'muauth_mc_keep_request');
// add hooks to signup success
add_action('muauth_mc_lists_ready', 'muauth_mc_register_listeners');
// opt-in from usermeta upon activation
add_filter('muauth_activation_meta_handled_muauth_mc_lists', 'muauth_mc_lists_meta', 10, 3);