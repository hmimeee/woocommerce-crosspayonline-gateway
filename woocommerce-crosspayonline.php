<?php

/**
 * Plugin Name: CrossPayOnline Gateway
 * Plugin URI: https://github.com/hmimeee/woocommerce-crosspayonline-gateway
 * Description: WooCommerce CrossPayOnline payment gateway
 * Author: Imran Hossen
 * Author URI: https://www.hmime.com
 * Version: 2.0
 * Text Domain: CrossPayOnline
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2020-2021 MTC , (hmimeee@gmail.com) and WooCommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   crosspayonline
 * @author    MTC
 * @category  Gateway
 * @copyright Copyright: (c) 2020-2021 MTC Inc, Inc. (hmimeee@gmail.com) and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This CrossPayOnline gateway.
 */

defined('ABSPATH') or exit;

// Make sure WooCommerce is active
if (! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}


/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.14
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function wc_crosspayonline_add_to_gateways($gateways)
{
    $gateways[] = 'WC_CrossPayOnline_Credit_Card_Gateway';
    $gateways[] = 'WC_CrossPayOnline_Lahza_Gateway';
    $gateways[] = 'WC_CrossPayOnline_Spaceremit_Gateway';
    $gateways[] = 'WC_CrossPayOnline_Usdt_Gateway';

    return $gateways;
}

add_filter('woocommerce_payment_gateways', 'wc_crosspayonline_add_to_gateways');


add_action('init', 'crosspayonline_init_internal');
function crosspayonline_init_internal()
{
    add_rewrite_rule('parse-crosspayonline-request.php$', 'index.php?crosspay_api=1', 'top');
}

add_filter('query_vars', 'crosspayonline_query_vars');
function crosspayonline_query_vars($query_vars)
{
    $query_vars[] = 'crosspayonline_api';
    return $query_vars;
}

add_action('parse_request', 'crosspayonline_parse_request');
function crosspayonline_parse_request(&$wp)
{
    if (array_key_exists('crosspayonline_api', $wp->query_vars)) {
        include 'parse-crosspayonline-request.php';
        exit();
    }
    return;
}


/**
 * Adds plugin page links
 * 
 * @since 1.0.14
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_crosspayonline_gateway_plugin_links($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Configure', 'crosspayonline_gateway') . '</a>'
    );

    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_crosspayonline_gateway_plugin_links');


/**
 * Offline Payment Gateway
 *
 * Provides an Offline Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_CrossPayOnline_Gateway
 * @extends		WC_Payment_Gateway
 * @version		1.0.14
 * @package		WooCommerce/Classes/Payment
 * @author 		Qedama
 */
add_action('plugins_loaded', 'wc_crosspayonline_gateway_init', 11);


function wc_crosspayonline_gateway_init()
{
    require_once plugin_dir_path(__FILE__) . 'classes/WC_CrossPayOnline_Gateway.php';
    require_once plugin_dir_path(__FILE__) . 'classes/WC_CrossPayOnline_Credit_Card_Gateway.php';
    require_once plugin_dir_path(__FILE__) . 'classes/WC_CrossPayOnline_Lahza_Gateway.php';
    require_once plugin_dir_path(__FILE__) . 'classes/WC_CrossPayOnline_Spaceremit_Gateway.php';
    require_once plugin_dir_path(__FILE__) . 'classes/WC_CrossPayOnline_Usdt_Gateway.php';
}



class MySettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'CrossPayOnline Settings',
            'CrossPayOnline Settings',
            'manage_options',
            'crosspayonline-setting-admin',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('wc_crosspayonline_options');
?>
        <div class="wrap">
            <img height="50" src="https://crosspayonline.com/images/logo-dark.png">
            <hr>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('wc_crosspayonline_option_group');
                do_settings_sections('crosspayonline-setting-admin');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'wc_crosspayonline_option_group', // Option group
            'wc_crosspayonline_options', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'mode', // ID
            'Mode: ', // Title
            array($this, 'mode_callback'), // Callback
            'crosspayonline-setting-admin', // Page
            'setting_section' // Section         
        );


        add_settings_section(
            'setting_section', // ID
            'Merchant Setting: ', // Title
            array($this, 'print_section_info'), // Callback
            'crosspayonline-setting-admin' // Page
        );

        add_settings_field(
            'api_key',
            'API key:',
            array($this, 'api_key_callback'),
            'crosspayonline-setting-admin',
            'setting_section'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['api_key']))
            $new_input['api_key'] = sanitize_text_field($input['api_key']);

        if (isset($input['mode']))
            $new_input['mode'] = sanitize_text_field($input['mode']);

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print '';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function api_key_callback()
    {
        printf(
            '<input type="text" id="api_key" name="wc_crosspayonline_options[api_key]" value="%s" />',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );
    }

    public function mode_callback()
    {

    ?>
        <select name="wc_crosspayonline_options[mode]">
            <option <?php if (esc_attr($this->options['mode']) == '' or esc_attr($this->options['mode']) == 'auto-process') print 'selected'  ?> value="auto-process">Live - Direct Realtime Payment Update</option>
            <option <?php if (esc_attr($this->options['mode']) == '' or esc_attr($this->options['mode']) == 'manual-process') print 'selected'  ?> value="manual-process">Live - Waiting Bill Approval</option>
        </select>
        <hr>
        <p>
            <b>Live - Waiting Bill Approval: </b> (best security option): Our system will inform you of any successful transactions but will never pass any update to your website without getting approval. *Approval need from 24 to 72 business hours.
        </p>
        <p>
            <b>Live - Direct Real-time Payment Update: </b> (Low-Security Option) : Our System will pass all success payments to your website without check real bank statement, it means that you get a real-time status for payment without waiting for your transaction if reviewed by Bill and Bank Staff. (Please keep in mind that any successful transaction doesn't mean that you will get money in your wallet)
        </p>

        <hr>
    <?php
    }
}

if (is_admin())
    $my_settings_page = new MySettingsPage();
