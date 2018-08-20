<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('WC_Settings_CoolRunner')) :

    function crship_add_coolrunner_settings() {
        /**
         * Settings class
         *
         * @since 1.0.0
         */
        class WC_Settings_CoolRunner
            extends WC_Settings_Page {

            public function default_email() {
                ob_start(); ?>
                <table style='width:100%;height: 100vh'>
                    <tr>
                        <td align='center' style='background-color:#f7f7f7'>
                            <table style='width:600px'>
                                <tr>
                                    <td style='background-color:#2B97D6;padding: 15px'>
                                        <h1 style="color:#ffffff;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left">CoolRunner</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='background-color:#ffffff;padding: 15px'>
                                        Hej {first_name}
                                        <hr>
                                        Vedrørende din ordre med nr. #{order_no} fra CoolRunner, så kan du følge den via dette link <a href="https://coolrunner.dk/">https://coolrunner.dk/</a><br>
                                        Klik på fanen spor en pakke og indsæt dette
                                        track and trace nummer:
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="background-color:#ffffff;padding: 15px;border-top: 2px solid #2B97D6;border-bottom: 2px solid #2B97D6">
                                        <h3>{package_number}</h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="background-color: #ffffff">
                                        <small>
                                            OBS! Denne e-mail kan ikke besvares!
                                        </small>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <?php
                return ob_get_clean();
            }

            /**
             * Setup settings class
             *
             * @since  1.0
             */
            public function __construct() {

                $this->id = 'coolrunner';
                $this->label = __('CoolRunner', 'coolrunner-shipping-plugin');

                add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20);
                add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
                add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
                add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections'));
                add_action('woocommerce_get_settings_for_' . $this->id, array($this, 'get_option'));
            }


            /**
             * Get sections
             *
             * @return array
             */
            public function get_sections() {

                $sections = array(
                    'General' => __('General', 'coolrunner-shipping-plugin'),
                );

                return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
            }

            /**
             * Get settings array
             *
             * @since 1.0.0
             *
             * @param string $current_section Optional. Defaults to empty string.
             *
             * @return array Array of settings
             */
            public function get_settings($current_section = '') {

                $menu = array(
                    array(
                        'name' => __('CoolRunner General', 'coolrunner-shipping-plugin'),
                        'type' => 'title',
                        'desc' => '',
                        'id'   => 'coolrunner_settings',
                    ),
                    array(
                        'name'     => __('CoolRunner Username', 'coolrunner-shipping-plugin'),
                        'type'     => 'text',
                        'id'       => 'coolrunner_settings_username',
                        'desc_tip' => true,
                        'desc'     => __('Input your CoolRunner username', 'coolrunner-shipping-plugin'),
                    ),
                    array(
                        'name'     => __('CoolRunner Token', 'coolrunner-shipping-plugin'),
                        'type'     => 'text',
                        'id'       => 'coolrunner_settings_token',
                        'desc_tip' => true,
                        'desc'     => __('Input your CoolRunner token', 'coolrunner-shipping-plugin'),
                    ),
                    array(
                        'name'     => __('Sender country', 'coolrunner-shipping-plugin'),
                        'type'     => 'text',
                        'id'       => 'coolrunner_settings_sender_country',
                        'desc_tip' => true,
                        'desc'     => __('Input your Sender country, remember to only use 2 characters!', 'coolrunner-shipping-plugin'),
                    ),
                    array(
                        'name'     => __('Number of droppoint', 'coolrunner-shipping-plugin'),
                        'type'     => 'select',
                        'id'       => 'coolrunner_settings_number_droppoint',
                        'desc_tip' => true,
                        'desc'     => __('Choose which number of droppoint to show at checkout', 'coolrunner-shipping-plugin'),
                        'default'  => '5',
                        'options'  => array(
                            '5'  => __('5', 'coolrunner-shipping-plugin'),
                            '6'  => __('6', 'coolrunner-shipping-plugin'),
                            '7'  => __('7', 'coolrunner-shipping-plugin'),
                            '8'  => __('8', 'coolrunner-shipping-plugin'),
                            '9'  => __('9', 'coolrunner-shipping-plugin'),
                            '10' => __('10', 'coolrunner-shipping-plugin'),
                            '11' => __('11', 'coolrunner-shipping-plugin'),
                            '12' => __('12', 'coolrunner-shipping-plugin'),
                            '13' => __('13', 'coolrunner-shipping-plugin'),
                            '14' => __('14', 'coolrunner-shipping-plugin'),
                            '15' => __('15', 'coolrunner-shipping-plugin'),
                            '16' => __('16', 'coolrunner-shipping-plugin'),
                            '17' => __('17', 'coolrunner-shipping-plugin'),
                            '18' => __('18', 'coolrunner-shipping-plugin'),
                            '19' => __('19', 'coolrunner-shipping-plugin'),
                            '20' => __('20', 'coolrunner-shipping-plugin'),
                        ),
                    ),
                    array(
                        'name'     => __('Send To PCN Automatically', 'coolrunner-shipping-plugin'),
                        'type'     => 'checkbox',
                        'id'       => 'coolrunner_settings_auto_send_to_pcn',
                        'desc_tip' => true,
                        'desc'     => __('Send the order to PCN automatically', 'coolrunner-shipping-plugin')
                    ),
                    'pcn-when' => array(
                        'name'     => __('Send To PCN when', 'coolrunner-shipping-plugin'),
                        'type'     => 'select',
                        'id'       => 'coolrunner_settings_auto_send_to_pcn_when',
                        'desc_tip' => true,
                        'desc'     => __('Send the order to PCN automatically when order status changes to', 'coolrunner-shipping-plugin'),
                        'default'  => 'woocommerce_order_status_completed',
                        'options'  => array(),
                    ),
                    array(
                        'name'     => __('Enable Automatic Tracking Email', 'coolrunner-shipping-plugin'),
                        'type'     => 'checkbox',
                        'id'       => 'coolrunner_settings_send_email',
                        'desc_tip' => true,
                        'desc'     => __('Enable automatic tracking email for the customer - This e-mail is sent at the time of the order going through and NOT when it is actually shipped', 'coolrunner-shipping-plugin')
                    ),
                    array(
                        'name'     => __('Tracking Email', 'coolrunner-shipping-plugin'),
                        'type'     => 'textarea',
                        'default'  => $this->default_email(),
                        'desc_tip' => false,
                        'desc'     => 'Placeholders: {first_name}, {last_name}, {email}, {order_no}, {package_number}',
                        'id'       => 'coolrunner_settings_tracking_email',
                    ),
                    array(
                        'name'     => 'Debug Mode',
                        'type'     => 'checkbox',
                        'id'       => 'coolrunner_settings_debug_mode',
                        'desc_tip' => true,
                        'desc'     => 'Enable debug mode - This is meant for debugging purposes only and may cause issues on your site\'s frontend so use with care<br>'
                    ),
                    array(
                        'type' => 'secionend',
                        'id'   => 'coolrunner_settings'
                    ),
                );

                foreach (wc_get_order_statuses() as $key => $value) {
                    $menu['pcn-when']['options']['woocommerce_order_status_' . str_replace('wc-', '', $key)] = implode(' ', array(__('Order', 'woocommerce'), $value));
                }

                $settings = apply_filters('coolrunner_settings', $menu);
                return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
            }

            /**
             * Save settings
             *
             * @since 1.0
             */
            public function save() {
                crship_register_customer_shipping(true);
                parent::save();
            }
        }

        return new WC_Settings_CoolRunner();
    }

    add_filter('woocommerce_get_settings_pages', 'crship_add_coolrunner_settings', 15);

endif;

