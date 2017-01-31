<?php
// prevent direct access
defined('ABSPATH') || die('Direct access not allowed.' . PHP_EOL);

function muauth_mc_settings_tab($tabs) {
    return array_merge($tabs, array(
        'mailchimp' => array(
            'contentCallback' => 'muauth_mc_settings',
            'updateCallbak' => 'muauth_mc_update_settings',
            'title' => _x('MailChimp', 'settings title', MUAUTH_MC_DOMAIN)
        )
    ));
}

function muauth_mc_settings() {
    global $muauth_mc;

    do_action('muauth_mc_settings_head', $muauth_mc);
    ?>
    <form method="post">

        <div class="section" id="mc">

            <p><strong><?php _e('API credentials:', MUAUTH_MC_DOMAIN); ?></strong></p>

            <p>
                <label><?php _e('Enter your <a href="http://kb.mailchimp.com/integrations/api-integrations/about-api-keys">MailChimp API key</a>:', MUAUTH_MC_DOMAIN); ?><br/>
                <input type="text" name="api_key" value="<?php echo esc_attr($muauth_mc->api_key); ?>" size="50" />
                </label>
            </p>

            <p><strong><?php _e('Fetch and select lists:', MUAUTH_MC_DOMAIN); ?></strong></p>

            <?php if ( $muauth_mc->api_key ) : ?>
                <label class="button" for="mc-fetch" data-loading="<?php esc_attr_e('Loading .. (this may take couple seconds)', MUAUTH_MC_DOMAIN); ?>"><?php _E('Fetch Lists', MUAUTH_MC_DOMAIN); ?></label>

                <?php if ( isset($muauth_mc->lists_available) && $muauth_mc->lists_available ) : ?>

                    <p><em><?php printf(__('You have %s!', MUAUTH_MC_DOMAIN), sprintf(_n( '%d list', '%d lists', count($muauth_mc->lists_available), MUAUTH_MC_DOMAIN), count($muauth_mc->lists_available))); ?></em></p>

                    <table class="form-table widefat striped">
                        <thead>
                            <tr>
                                <th style="padding-left:10px"><input type="checkbox" class="toggle_select_lists" /></th>
                                <th style="padding-left:10px"><?php _e('ID', MUAUTH_MC_DOMAIN); ?></th>
                                <th style="padding-left:10px"><?php _e('Name', MUAUTH_MC_DOMAIN); ?></th>
                                <th style="padding-left:10px"><?php _e('Subscriber count', MUAUTH_MC_DOMAIN); ?></th>
                            </tr>
                        </thead>

                        <?php foreach ( $muauth_mc->lists_available as $list ) : ?>
                            <tr>
                                <td style="padding-left:1.3em">
                                    <input type="checkbox" name="lists[]" value="<?php echo esc_attr($list['id']); ?>" <?php checked(in_array($list['id'], $muauth_mc->lists)); ?> />
                                </td>

                                <td>
                                    <?php echo $list['id']; ?>
                                </td>

                                <td>
                                    <?php echo apply_filters('muauth_mc_list_name', $list['name']); ?>
                                </td>

                                <td>
                                    <?php echo isset($list['subscriber_count']) ? $list['subscriber_count'] : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <p><em><?php _e('Select 1 or more lists to opt signups in', MUAUTH_MC_DOMAIN); ?></em></p>

                <?php else : ?>
                    <p><em><?php _e('You don\'t have any lists yet.', MUAUTH_MC_DOMAIN); ?></em></p>
                <?php endif; ?>

            <?php else : ?>
                <p><?php _e('You must enter an API key first!', MUAUTH_MC_DOMAIN); ?></p>
            <?php endif; ?>

            <p><strong><?php _e('Label:', MUAUTH_MC_DOMAIN); ?></strong></p>
            <input type="text" name="label" value="<?php echo esc_attr($muauth_mc->label); ?>" size="50" />

            <p><strong><?php _e('Other settings:', MUAUTH_MC_DOMAIN); ?></strong></p>

            <label>
                <input type="checkbox" name="check_by_default" <?php checked($muauth_mc->check_by_default); ?> />
                <?php _e('Check by default', MUAUTH_MC_DOMAIN); ?>
            </label><br/>

            <label>
                <input type="checkbox" name="activation" <?php checked($muauth_mc->activation); ?> />
                <?php _e('Opt-in users only after account activation', MUAUTH_MC_DOMAIN); ?>
            </label><br/>

            <?php if ( count($muauth_mc->lists_available) > 1 ) : ?>
                <label>
                    <input type="checkbox" name="select_freedom" <?php checked($muauth_mc->select_freedom); ?> />
                    <?php _e('Allow user to select which lists to opt-in', MUAUTH_MC_DOMAIN); ?>
                </label>
            <?php endif; ?>

        </div>

        <?php wp_nonce_field( 'muauth_nonce', 'muauth_nonce' ); ?>
        <?php submit_button(); ?>

    </form>

    <form style="display:none" method="post">
        <?php wp_nonce_field( 'muauth_nonce', 'muauth_nonce' ); ?>
        <input type="submit" name="fetch" id="mc-fetch" style="display:none" />
    </form>
    
    <?php
}

function muauth_mc_update_settings() {
    global $muauth_mc;
    $custom_settings = muauth_mc_custom_settings();

    if ( isset($_POST['api_key']) && trim($_POST['api_key']) ) {
        $custom_settings['api_key'] = sanitize_text_field($_POST['api_key']);
    } else {
        $custom_settings['api_key'] = null;
    }

    if ( isset($_POST['label']) && trim($_POST['label']) ) {
        $custom_settings['label'] = esc_attr(wp_unslash($_POST['label']));
    } else {
        unset($custom_settings['label']);
        $muauth_mc->label = __('Sign me up for the newsletter!', MUAUTH_MC_DOMAIN);
    }

    $custom_settings['check_by_default'] = isset($_POST['check_by_default']);
    $custom_settings['activation'] = isset($_POST['activation']);
    $custom_settings['select_freedom'] = isset($_POST['select_freedom']);
    $custom_settings['lists'] = array();

    if ( isset($_POST['lists']) && $_POST['lists'] && is_array($_POST['lists']) && $muauth_mc->lists_available ) {
        foreach ( $_POST['lists'] as $list_id ) {
            foreach ( $muauth_mc->lists_available as $list ) {
                if ( isset($list['id']) && $list['id'] === $list_id ) {
                    $custom_settings['lists'][] = $list_id;
                }
            }
        }
    }

    $muauth_mc->lists = $custom_settings['lists'];

    if ( empty($custom_settings['lists']) ) {
        unset($custom_settings['lists']);
        $muauth_mc->lists_data = array();
    }

    update_site_option('muauth_mc_settings', $custom_settings);

    // flush settings
    unset( $GLOBALS['muauth_mc_custom_settings'] );
    muauth_mc_parse_settings();
}

function muauth_mc_admin_plugin_links($links) {
    return array_merge(array(
        'Settings' => sprintf(
            '<a href="%s">' . __('Settings', MUAUTH_MC_DOMAIN) . '</a>',
            network_admin_url('settings.php?page=mu-auth&tab=mailchimp')
        )
    ), $links);
}

function muauth_mc_fetch_lists() {
    if ( !class_exists('\MUAUTH\MUAUTH') )
        return;

    if ( !method_exists('\MUAUTH\MUAUTH', 'verifyNonce') )
        return;
    
    if ( !class_exists('\MUAUTH\Includes\Admin\Admin') )
        return;

    if ( !method_exists('\MUAUTH\Includes\Admin\Admin', 'feedback') )
        return;

    $Admin = new \MUAUTH\Includes\Admin\Admin;

    if ( isset($_POST['fetch']) && \MUAUTH\MUAUTH::verifyNonce() ) {
        global $muauth_mc;
        if (!isset($muauth_mc->api_key) || !trim($muauth_mc->api_key)) {
            return $Admin::feedback(array(
                'success' => false,
                'message' => __('Error, you need to provide an API key first', MUAUTH_MC_DOMAIN)
            ));
        }

        $lists = muauth_mc_update_lists();
        // update
        $muauth_mc->lists_available = $lists;

        if ( is_array($lists) ) {
            return $Admin::feedback(array(
                'success' => true,
                'message' => __('Your mailing lists were fetched successfully!', MUAUTH_MC_DOMAIN)
            ));
        } else {
            return $Admin::feedback(array(
                'success' => false,
                'message' => __('Error occured while fetching your lists. Make sure the API key is valid, and try again or later.', MUAUTH_MC_DOMAIN)
            ));
        }
    }
}

function muauth_mc_update_lists() {
    $mc = muauth_mc();

    if ( !($mc instanceof DrewM\MailChimp\MailChimp) )
        return;

    $lists = $mc->get('lists');
    $lists = isset($lists['lists']) ? $lists['lists'] : array();
    $data = array();

    if ( $lists ) {
        foreach ( $lists as $_list ) {
            $list = array();
            
            if ( empty($_list['id']) ) {
                continue;
            }

            $list['id'] = $_list['id'];
            $list['name'] = isset($_list['name']) ? esc_attr($_list['name']) : null;

            if ( isset($_list['stats']) && isset($_list['stats']['member_count']) ) {
                $list['subscriber_count'] = intval($_list['stats']['member_count']);
            }

            $data[] = $list;
        } 
    }

    if ( $data ) {
        update_site_option('muauth_mc_lists', $data);
    } else {
        delete_site_option('muauth_mc_lists');
    }

    return $data;
}

function muauth_mc_admin_js() {
    return wp_enqueue_script('muauth-mc', plugin_dir_url(MUAUTH_MC_FILE) . 'assets/js/admin.js', array('jquery'));
}

function muauth_mc_settings_err_alert($mc) {
    if ( !class_exists('\MUAUTH\Includes\Admin\Admin') )
        return;

    if ( !method_exists('\MUAUTH\Includes\Admin\Admin', 'feedback') )
        return;

    if ( !method_exists('\MUAUTH\Includes\Admin\Admin', 'uiFeedback') )
        return;

    if ( !isset($mc->lists_data) || !$mc->lists_data ) {
        \MUAUTH\Includes\Admin\Admin::feedback(array(
            'success' => false,
            'message' => __('You have no lists selected yet, please verify your API key and select at least 1 list in order to opt users in.', MUAUTH_MC_DOMAIN)
        ))->uiFeedback();
    }
}