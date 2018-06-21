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
                );
                $menu[] = array(
                    'type' => 'secionend',
                    'id'   => 'coolrunner_settings'
                );

                $settings = apply_filters('coolrunner_settings', $menu);
                return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
            }

            /**
             * Save settings
             *
             * @since 1.0
             */
            public function save() {
                crship_register_customer_shipping();
                parent::save();
            }
        }

        return new WC_Settings_CoolRunner();
    }

    add_filter('woocommerce_get_settings_pages', 'crship_add_coolrunner_settings', 15);

endif;

