<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_shipping_init', function () {

    class WC_CoolRunner_Shipping_Method
        extends WC_Shipping_Method {
        protected $package_sizes = array();
        protected $methods       = array();

        protected $_countries = array();
        protected $_carriers  = array();

        public function __construct($instance_id = 0) {
            parent::__construct($instance_id);

            $this->init_settings();
            $this->carrier = $this->get_option('carrier');

            $this->init_form_fields();

            $this->id = 'coolrunner';

            $this->supports = array(
                'shipping-zones',
                'instance-settings',
                'instance-settings-modal',
            );

            $this->method_title =
                implode(' -> ',
                        array_filter(
                            array(
                                'CoolRunner/PCN',
                                $this->get_option('carrier', null) ?
                                    CoolRunner::getCarriers($this->get_option('carrier', null)) : '',
                                $this->get_option('product', null) ?
                                    $this->_getCarrierProducts(true)[$this->get_option('product', null)] : ''
                            )
                        )
                );
            $this->method_description = 'Ship using CoolRunner and PCN';
            $this->title = $this->get_option('title', $this->method_title);
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields() {
            asort($this->_carriers);
            $this->instance_form_fields = array(
                'title'                => array(
                    'title'             => __('Method Title', 'woocommerce'),
                    'type'              => 'text',
                    'description'       => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default'           => $this->method_title,
                    'custom_attributes' => array('autocomplete' => 'off', 'required' => true)
                ),
                'carrier'              => array(
                    'title'             => __('Carrier', 'coolrunner-shipping-plugin'),
                    'type'              => 'select',
                    'description'       => __('This is the carrier used', 'coolrunner-shipping-plugin'),
                    'default'           => '',
                    'options'           => array_merge(array('' => __('Select Carrier', 'coolrunner-shipping-plugin')), CoolRunner::getCarriers()),
                    'custom_attributes' => array('required' => true)
                ),
                'product'              => array(
                    'title'             => __('Product', 'coolrunner-shipping-plugin'),
                    'type'              => 'select',
                    'description'       => __('This is the product used', 'coolrunner-shipping-plugin'),
                    'default'           => '',
                    'options'           => array_merge(array('' => __('Select Product', 'coolrunner-shipping-plugin')), $this->_getCarrierProducts(true)),
                    'custom_attributes' => array('required' => true)
                ),
                'free_shipping'        => array(
                    'title'       => __('Free Shipping', 'woocommerce'),
                    'type'        => 'checkbox',
                    'description' => __('Enable Free Shipping', 'coolrunner-shipping-plugin'),
                    'default'     => false
                ),
                'free_shipping_clause' => array(
                    'title'             => __('Free Shipping Clause', 'coolrunner-shipping-plugin'),
                    'type'              => 'select',
                    'description'       => __('Free Shipping On', 'coolrunner-shipping-plugin'),
                    'default'           => '',
                    'options'           => array(
                        ''             => __('Select Clause', 'coolrunner-shipping-plugin'),
                        'weight_above' => __('Weight Above', 'coolrunner-shipping-plugin'),
                        'weight_below' => __('Weight Below', 'coolrunner-shipping-plugin'),
                        'price_above'  => __('Price Above', 'coolrunner-shipping-plugin'),
                        'price_below'  => __('Price Below', 'coolrunner-shipping-plugin'),
                    ),
                    'custom_attributes' => array('required' => true)
                ),
                'free_shipping_input'  => array(
                    'title'             => __('Free Shipping Value', 'coolrunner-shipping-plugin'),
                    'type'              => 'number',
                    'description'       => __('This controls when at the above clause free shipping is used.', 'woocommerce'),
                    'default'           => 0,
                    'custom_attributes' => array('autocomplete' => 'off', 'required' => true, 'step' => '0.01')
                ),
            );

            foreach ($this->_getCarrierProducts() as $key => $product) {
                if (isset($product['title']) && $product['title']) {
                    $countries = array();
                    foreach ($product['zone'] as $cc) {
                        $countries[] = isset(WC()->countries->get_countries()[$cc]) ? WC()->countries->get_countries()[$cc] : $cc;
                    }
                    $this->instance_form_fields["service_$key"] = array(
                        'title'             => "Cost | {$product['title']}",
                        'type'              => 'number',
                        'description'       => sprintf(__("Set cost for %s | Set to -1 to disable this freight method", 'coolrunner-shipping-plugin'), $product['title']),
                        'default'           => '-1',
                        'custom_attributes' => array(
                            'placeholder'    => '49,00',
                            'data-countries' => implode('|', $countries), 'step' => '0.01',
                            'data-service'   => $product['service']
                        )
                    );
                }
            }

        }

        protected function _getCarrierProducts($for_select = false) {
            $shipping_methods = get_option('coolrunner_wc_curl_data');
            if (!$for_select) {
                $products = array();
            } else {
                $products = array('' => 'Select Product');
            }
            if ($shipping_methods) {
                foreach ($shipping_methods as $country => $services) {
                    foreach ($services as $service) {
                        $service_name = implode('_', array_filter(
                            array(
                                $service['carrier'],
                                $service['carrier_product'],
                                $service['carrier_service']
                            )
                        ));
                        $size = array(
                            'weight_from'    => $service['weight_from'],
                            'weight_to'      => $service['weight_to'],
                            'title'          => $service['title'],
                            'price_excl_tax' => $service['price_excl_tax'],
                            'price_incl_tax' => $service['price_incl_tax'],
                            'zone'           => array($service['zone_to']),
                            'service'        => $service_name
                        );
                        $title = substr($size['title'], 0, strpos($size['title'], '('));
                        if ($for_select) {
                            if (!isset($products[$service_name]) && $title) {
                                $products[$service_name] = $title;
                            }
                        } else {
                            if (is_array($size)) {
                                $key = "{$service_name}_{$size['weight_from']}_{$size['weight_to']}";
                                if (!isset($products[$key])) {
                                    $products[$key] = $size;
                                } else {
                                    if (isset($products[$key])) {
                                        $products[$key]['zone'][] = $size['zone'][0];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $products;
        }

        public function calculate_shipping($package = array()) {
            $weight = 0;
            $price = 0;

            foreach ($package['contents'] as $product) {
                /** @var WC_Product $prod_data */
                $prod_data = $product['data'];

                $weight += floatval($prod_data->get_weight()) * $product['quantity'];
                $price += floatval($product['line_total']);
            }

            $weight *= 1000;
            $product = $this->get_option('product', false);
            $services = array();
            foreach ($this->instance_settings as $key => $setting) {
                if (strpos($key, $product) !== false) {
                    $services[str_replace("service_{$product}_", '', $key)] = $setting;
                }
            }

            $chosen_service = null;
            foreach ($services as $size => $service) {
                if (floor(floatval($service)) !== -1) {
                    $size = array_map('intval', explode('_', $size, 2));
                    $size[1] = $size[1] - 1;

                    if ($size[0] <= $weight && $size[1] >= $weight) {
                        $chosen_service = floatval($service);
                        break;
                    }
                }
            }

            $free_shipping = false;
            if ($this->get_option('free_shipping') === 'yes') {
                $clause = $this->get_option('free_shipping_clause');
                $value = floatval($this->get_option('free_shipping_input'));
                if (
                    ($clause === 'weight_above' && $weight >= $value) ||
                    ($clause === 'weight_below' && $weight <= $value) ||
                    ($clause === 'price_above' && $price >= $value) ||
                    ($clause === 'price_below' && $price <= $value)
                ) {
                    $chosen_service = 0;
                    $free_shipping = true;

                }
            }

            if (isset($package['applied_coupons'])) {
                foreach ($package['applied_coupons'] as $code) {
                    if ((new WC_Coupon($code))->get_free_shipping()) {
                        $free_shipping = true;
                        $chosen_service = 0;
                    }
                }
            }

            if (($chosen_service != -1 && $chosen_service != null) || $free_shipping) {
                $this->add_rate(
                    array(
                        'id'        => "coolrunner_$product",
                        'label'     => $this->title,
                        'cost'      => $chosen_service,
                        'meta_data' => array(
                            'carrier' => explode('_', $product)[0],
                            'product' => explode('_', $product)[1],
                            'service' => explode('_', $product)[2]
                        )
                    )
                );
            }
        }
    }
});


add_filter('woocommerce_shipping_methods', function ($methods) {

    $methods['coolrunner'] = new WC_CoolRunner_Shipping_Method();

    return $methods;
});