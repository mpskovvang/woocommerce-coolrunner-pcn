<?php

class CoolRunner {
    /**
     * @param int $order_id
     *
     * @return CoolRunner_Carrier|false
     */
    public static function getCoolRunnerShippingMethod($order_id) {
        $order = new WC_Order($order_id);
        $config_name = '';
        foreach ($order->get_shipping_methods() as $shipping_method) {
            if ($shipping_method->get_method_id() === 'coolrunner') {
                $config_name = implode('_', array(
                    'woocommerce',
                    'coolrunner',
                    $shipping_method->get_instance_id(),
                    'settings'
                ));
            }
        }

        return $config_name ? new CoolRunner_Carrier(get_option($config_name)) : false;
    }

    /**
     * Get all carriers
     *
     * @return array
     */
    public static function getCarriers($key = null) {
        $carriers = array(
            'dao'        => 'DAO',
            'pdk'        => 'Postnord',
            'gls'        => 'GLS',
            'coolrunner' => 'CoolRunner',
            'posti'      => 'Posti',
            'dhl'        => 'DHL',
            'helthjem'   => 'Helt Hjem'
        );

        return !is_null($key) ? (isset($carriers[$key]) ? $carriers[$key] : null) : $carriers;
    }

    public static function getVersion() {
        return get_plugin_data(__DIR__ . '/../woocommerce_coolrunner.php')['Version'];
    }
}

class CoolRunner_Carrier {
    protected $_carrier,
        $_product,
        $_service,
        $_title;

    public function __construct($arr) {

        $product = explode('_', $arr['product'], 3);

        $this->_carrier = $product[0];
        $this->_product = $product[1];
        $this->_service = $product[2];
        $this->_title = $arr['title'];
    }

    /**
     * @return mixed
     */
    public function getProduct() {
        return $this->_product;
    }

    /**
     * @return mixed
     */
    public function getCarrier() {
        return $this->_carrier;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->_title;
    }

    /**
     * @return mixed
     */
    public function getService() {
        return $this->_service;
    }
}