<?php
// prevent direct access
defined('ABSPATH') || die('Direct access not allowed.' . PHP_EOL);

function muauth_mc_textdomain() {
    load_plugin_textdomain(MUAUTH_MC_DOMAIN, FALSE, dirname(MUAUTH_MC_BASE).'/languages');
}

function muauth_mc_custom_settings() {
    global $muauth_mc_custom_settings;

    if ( isset($muauth_mc_custom_settings) )
        return $muauth_mc_custom_settings;

    $muauth_mc_custom_settings = get_site_option('muauth_mc_settings', array());

    return $muauth_mc_custom_settings;
}

function muauth_mc_parse_settings() {
    // default settings
    global $muauth_mc;
    // custom settings
    $custom = muauth_mc_custom_settings();
    // parse
    $settings = wp_parse_args( $custom, (array) $muauth_mc );
    // pluggable
    $muauth_mc = apply_filters( 'muauth_mc_settings', (object) $settings, $custom );
    // avail lists
    $muauth_mc->lists_available = get_site_option('muauth_mc_lists', array());

    if ( !empty($muauth_mc->api_key) ) {
        do_action('muauth_mc_ready', $muauth_mc);
    }
}

function muauth_mc_load_mailchimp_lib() {
    require_once MUAUTH_MC_DIR . (
        'includes/lib/mailchimp-api/src/MailChimp.php'
    );
}

function muauth_mc() {
    global $muauth_mc;

    if ( !isset($muauth_mc->api_key) )
        return;

    if ( !class_exists('\DrewM\MailChimp\MailChimp') )
        return;

    return new \DrewM\MailChimp\MailChimp($muauth_mc->api_key);
}

function muauth_mc_frontend_init($mc) {
    $mailchimp = muauth_mc();

    if ( !($mailchimp instanceof DrewM\MailChimp\MailChimp) )
        return;

    if ( empty($mc->lists) || empty($mc->lists_available) )
        return;

    $mc->lists_data = array();

    foreach ( $mc->lists as $i=>$list_id ) {
        foreach ( $mc->lists_available as $list ) {
            if ( isset($list['id']) && $list['id'] === $list_id ) {
                $mc->lists_data[$i] = $list;
            }
        }
    }

    if ( $mc->lists_data ) {
        $mc->lists_data = array_filter($mc->lists_data, 'is_array');
    }

    if ( !$mc->lists_data ) {
        unset($mc->lists_data);
        return;
    }

    do_action('muauth_mc_lists_ready', $mc);
}

function muauth_mc_optin_email($email_address, $list_id) {
    if ( !is_email( $email_address ) )
        return;

    $mailchimp = muauth_mc();

    if ( !($mailchimp instanceof DrewM\MailChimp\MailChimp) )
        return;

    $response = array();

    if ( is_array($list_id) ) {
        $list_ids = array_map('sanitize_text_field', $list_id);
    } else {
        $list_ids = array( sanitize_text_field($list_id) );
    }

    // debug
    return update_site_option('optin_'.$email_address, $list_ids);

    foreach ( $list_ids as $_list_id ) {
        $response[$_list_id] = $mailchimp->post("lists/{$_list_id}/members", array(
            'email_address' => $email_address,
            'status'        => 'subscribed',
        ));
    }

    return apply_filters('muauth_mc_optin_email', $response, $email_address, $list_id);
}

function muauth_mc_status_success($status) {
    return apply_filters('muauth_mc_status_success', (bool) ($status >= 200 && $status <= 299), $status);
}

function muauth_mc_register_signup_field() {
    add_action('muauth_register_before_submit', 'muauth_mc_parse_register_field');
}

function muauth_mc_parse_register_field() {
    global $muauth_stage;

    if ( intval($muauth_stage) > 1 ) {
        if ( !(is_user_logged_in() && 2 == intval($muauth_stage)) )
            return;
    }

    global $muauth_mc;

    call_user_func(
        apply_filters('muauth_mc_signup_field_callback', 'muauth_mc_signup_field'),
        $muauth_mc
    );
}

function muauth_mc_signup_field($mc) {
    $checked = array();

    if ( $_POST ) {
        if ( isset($_POST['muauth_mc_optin']) && is_array($_POST['muauth_mc_optin']) && $_POST['muauth_mc_optin'] ) {
            $checked = $_POST['muauth_mc_optin'];
        }
    } else {
        $checked = $mc->lists;
    }

    ?>

    <p class="form-section muauth-mc">

        <?php if ( count($mc->lists_data) > 1 ) : ?>

            <label for="muauth-mc-optin" class="mc-strong"><?php echo esc_attr($mc->label); ?></label>
            
            <?php foreach ( $mc->lists_data as $list ) : ?>
                
                <label>
                    <input type="checkbox" name="muauth_mc_optin[]" value="<?php echo esc_attr($list['id']); ?>" <?php checked(in_array($list['id'], $checked)); ?> tabindex="<?php muauth_tabindex(); ?>"/>
                    <?php echo apply_filters('muauth_mc_list_name', $list['name']); ?>
                </label>

            <?php endforeach; ?>

        <?php else : ?>

            <label for="muauth-mc-optin">
                <input type="checkbox" name="muauth_mc_optin[]" id="muauth-mc-optin" value="<?php echo esc_attr($mc->lists_data[0]['id']); ?>" <?php checked(in_array($mc->lists_data[0]['id'], $checked)); ?>  tabindex="<?php muauth_tabindex(); ?>"/>
                <?php echo esc_attr($mc->label); ?>
            </label>

        <?php endif; ?>

    </p>

    <?php
}

function muauth_mc_wp_head() {
    return add_action('wp_head', 'muauth_mc_print_css');
}

function muauth_mc_print_css() {
    print '<style type="text/css">.muauth-form .muauth-mc label{display:block;font-weight:400}.muauth-form .muauth-mc label.mc-strong{font-weight:600}</style>' . PHP_EOL;
}

function muauth_mc_validate_post_lists() {
    if ( !isset($_POST['muauth_mc_optin']) ) {
        $lists = array();
    } else if ( $_POST['muauth_mc_optin'] && is_array($_POST['muauth_mc_optin']) ) {
        global $muauth_mc;
        $lists = array();

        if ( $muauth_mc->lists_data ) {
            foreach ( $muauth_mc->lists_data as $list ) {
                if ( isset($list['id']) ) {
                    if ( in_array($list['id'], $_POST['muauth_mc_optin']) ) {
                        $lists[] = $list['id'];
                    }
                }
            }
        }
    } else {
        $lists = array();
    }

    return apply_filters('muauth_mc_validate_post_lists', $lists);
}

function muauth_mc_keep_request() {
    if ( !isset($_POST['muauth_mc_optin']) ) {
        return;
    }

    global $muauth_stage;

    if ( is_user_logged_in() ) {
        if ( 2 === intval($muauth_stage) )
            return;
    } else {
        if ( 1 === intval($muauth_stage) )
            return;  
    }

    if ( $_POST['muauth_mc_optin'] && is_array($_POST['muauth_mc_optin']) ) {
        foreach ( $_POST['muauth_mc_optin'] as $list_id ) {
            printf(
                '<input type="hidden" name="muauth_mc_optin[]" value="%s" />%s',
                $list_id,
                PHP_EOL
            );
        }
    }
}

function muauth_mc_register_listeners($mc) {
    if ( isset($mc->activation) && $mc->activation ) {
        /** upon activation, filter meta **/
        // username signup
        add_filter('muauth_validate_register_pre_signup_username_usermeta', 'muauth_mc_append_umeta_lists');
        // username and blog signup
        add_filter('muauth_validate_register_pre_signup_blog_usermeta', 'muauth_mc_append_umeta_lists');
        // logged-in user creates new blog
        add_action('muauth_post_create_blog', 'muauth_mc_post_create_blog');
    } else {
        /** upon signup complete **/
        // username signup
        add_action('muauth_post_signup_user', 'muauth_mc_post_signup_user');
        // username and blog signup
        add_action('muauth_post_signup_blog', 'muauth_mc_post_signup_blog', 10, 2);
        // logged-in user creates new blog
        add_action('muauth_post_create_blog', 'muauth_mc_post_create_blog');
    }
}

function muauth_mc_post_signup_user($user_validation) {
    $lists = muauth_mc_validate_post_lists();

    if ( !$lists )
        return;

    // opt-in
    $res = muauth_mc_optin_email($user_validation->user_email, $lists);
    /**
      * catch response
      *
      * see muauth_mc_status_success() to verify if signed up successfully
      * or not as you loop through $res
      * e.g $success = muauth_mc_status_success($res['1dd3eri2u4']['status'])
      * 
      * @param array $res returned response from signup
      * @param str $user_validation->user_email user email address
      * @param array $lists lists IDs
      */
    do_action('muauth_mc_catch_optin_response', $res, $user_validation->user_email, $lists);
}

function muauth_mc_post_signup_blog($blog_validation, $user_validation) {
    $lists = muauth_mc_validate_post_lists();
    
    if ( !$lists )
        return;

    // opt-in
    $res = muauth_mc_optin_email($user_validation->user_email, $lists);
    /**
      * catch response
      *
      * see muauth_mc_status_success() to verify if signed up successfully
      * or not as you loop through $res
      * e.g $success = muauth_mc_status_success($res['1dd3eri2u4']['status'])
      * 
      * @param array $res returned response from signup
      * @param str $user_validation->user_email user email address
      * @param array $lists lists IDs
      */
    do_action('muauth_mc_catch_optin_response', $res, $user_validation->user_email, $lists);
}

function muauth_mc_post_create_blog() {
    $lists = muauth_mc_validate_post_lists();
    
    if ( !$lists )
        return;

    global $current_user;

    // opt-in
    $res = muauth_mc_optin_email($current_user->user_email, $lists);
    /**
      * catch response
      *
      * see muauth_mc_status_success() to verify if signed up successfully
      * or not as you loop through $res
      * e.g $success = muauth_mc_status_success($res['1dd3eri2u4']['status'])
      * 
      * @param array $res returned response from signup
      * @param str $current_user->user_email user email address
      * @param array $lists lists IDs
      */
    do_action('muauth_mc_catch_optin_response', $res, $current_user->user_email, $lists);
}

function muauth_mc_append_umeta_lists($meta) {
    $lists = muauth_mc_validate_post_lists();
    
    if ( !$lists )
        return $meta;

    return array_merge($meta, array('muauth_mc_lists' => $lists));
}

function muauth_mc_lists_meta($bool, $lists, $user_id) {
    if ( $lists ) {
        global $muauth_mc;
        $user_email = apply_filters('muauth_mc_lists_meta_get_user_email', get_userdata($user_id)->user_email);

        if ( $user_email && isset($muauth_mc->lists_data) && $muauth_mc->lists_data ) {
            $avail = muauth_mc_key_list_keys( $muauth_mc->lists_data, 'id', 'strval', 'trim' );

            if ( !is_array($lists) && is_string($lists) )
                $lists = array($lists);

            foreach ( $lists as $i=>$id ) {
                if ( !in_array($id, $avail) ) {
                    unset( $lists[$i] );
                }
            }

            if ( $lists ) {
                // opt-in
                $res = muauth_mc_optin_email($user_email, $lists);
                /**
                  * catch response
                  *
                  * see muauth_mc_status_success() to verify if signed up successfully
                  * or not as you loop through $res
                  * e.g $success = muauth_mc_status_success($res['1dd3eri2u4']['status'])
                  * 
                  * @param array $res returned response from signup
                  * @param str $user_email user email address
                  * @param array $lists lists IDs
                  */
                do_action('muauth_mc_catch_optin_response', $res, $user_email, $lists);
            }
        }

    }

    return true;
}


function muauth_mc_key_list_keys( $list, $key, $map=null, $filter=null ) {
    $data = array();
    if ( $list ) {
        foreach ( (array) $list as $itm ) {
            if ( isset( $itm[$key] ) ) {
                $data[] = $itm[$key];
            }
        }
    }
    if ( $map && $data ) {
        $data = array_map($map, $data);
    }
    if ( $filter && $data ) {
        $data = array_filter($data, $filter);
    }
    return $data;
}