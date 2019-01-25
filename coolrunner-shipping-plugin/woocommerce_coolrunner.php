<?php
/**
 * Plugin Name: CoolRunner for WooCommerce
 * Plugin URI: http://coolrunner.dk/customer/integrations
 * Description: Shipping service of CoolRunner
 * Version: 1.0
 * Author: CoolRunner
 * Author URI: http://coolrunner.dk
 * Developer: Morten Harders / CoolRunner
 * Developer URI: http://coolrunner.dk
 * Text Domain: coolrunner-shipping-plugin
 * Domain Path: /languages
 *
 * WC requires at least: 3.4.2
 * WC tested up to: 3.4.2
 *
 * Copyright: © 2018- CoolRunner.dk
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('COOLRUNNER_WOOCOMMERCE_VERSION', '1.1');

//Check if woocommerce is active so it doesn't crash
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (!is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-warning">
            <p><?php _e('CoolRunner for WooCommerce requires WooCommerce to work.', 'coolrunner-shipping-plugin'); ?></p>
            <p><?php _e('You can get WooCommerce here:', 'coolrunner-shipping-plugin'); ?><?php echo sprintf('<a href="%s/wp-admin/plugin-install.php?s=WooCommerce&tab=search&type=term">Download</a>', get_site_url()) ?></p>
        </div>
        <?php
    });
    return;
} else {
    //Define plugin path
    if (!defined('COOLRUNNER_PLUGIN_DIR')) {
        define('COOLRUNNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
    }

    add_action('plugins_loaded', 'crship_coolrunner_load_textdomain');
    function crship_coolrunner_load_textdomain() {
        load_plugin_textdomain('coolrunner-shipping-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'crship_action_links');
    function crship_action_links($links) {
        $links[] = '<a href="' . admin_url('admin.php?page=wc-settings&tab=coolrunner') . '">' . __('Settings', 'coolrunner-shipping-plugin') . '</a>';
        $links[] = '<a href="https://coolrunner.dk/om-coolrunner/" target="_blank">' . __('Read more about CoolRunner', 'coolrunner-shipping-plugin') . '</a>';
        return $links;
    }

    add_action('wp_enqueue_scripts', function () {
        if (is_checkout()) {


            wp_enqueue_style('coolrunner', plugins_url('/assets/css/coolrunner.css', __FILE__), array(), COOLRUNNER_WOOCOMMERCE_VERSION);
            wp_enqueue_script('coolrunner', plugins_url('/assets/js/coolrunner.js', __FILE__), array('jquery'), COOLRUNNER_WOOCOMMERCE_VERSION, true);


            wp_localize_script('coolrunner', 'coolrunner', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'map_url'  => plugins_url('/assets/js/maps.js', __FILE__),
                'lang'     => array(
                    'droppoint_searching' => __('Searching for droppoints!', 'coolrunner-shipping-plugin')
                )
            ));
        }
    });


    add_action('admin_enqueue_scripts', function () {
        wp_enqueue_style('coolrunner', plugins_url('/assets/css/admin-coolrunner.css', __FILE__), array(), COOLRUNNER_WOOCOMMERCE_VERSION);
        wp_enqueue_script('coolrunner', plugins_url('/assets/js/admin.js', __FILE__), array('jquery'), COOLRUNNER_WOOCOMMERCE_VERSION, true);
    });


    include(COOLRUNNER_PLUGIN_DIR . 'includes/curl.php');
    include(COOLRUNNER_PLUGIN_DIR . 'includes/class-coolrunner.php');
    include(COOLRUNNER_PLUGIN_DIR . 'includes/admin/class-coolrunner-settings.php');
    include(COOLRUNNER_PLUGIN_DIR . 'includes/admin/class-coolrunner-shipping.php');
    include(COOLRUNNER_PLUGIN_DIR . 'includes/functions.php');

}