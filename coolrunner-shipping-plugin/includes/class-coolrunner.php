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
                break;
            }
        }

        return $config_name ? new CoolRunner_Carrier(get_option($config_name), $shipping_method) : false;
    }

    /**
     * Get all carriers
     *
     * @return array
     */
    public static function getCarriers($key = null) {
        $carriers = array(
            'dao'        => 'DAO',
            'bring'      => 'Bring',
            'coolrunner' => 'CoolRunner',
            'dhl'        => 'DHL',
            'gls'        => 'GLS',
            'pdk'        => 'Postnord',
            'posti'      => 'Posti',
            'helthjem'   => 'Helt Hjem',
            'dfm'        => 'Danske Fragtmænd'
        );

        return !is_null($key) ? (isset($carriers[$key]) ? $carriers[$key] : null) : $carriers;
    }

    public static function getVersion() {
        return get_plugin_data(__DIR__ . '/../woocommerce_coolrunner.php')['Version'];
    }

    public static function showDebugNotice($contents) {
        if (self::debugEnabled()) {
            add_action('admin_notices', function () use ($contents) {
                ?>
                <div class="notice notice-warning">
                    <h4 style="margin: .5em 0;">CoolRunner Debug Notice:</h4>
                    <p>
                        <?php echo $contents ?>
                    </p>
                    <p>
                        Disable CoolRunner Debug Notices:
                        <?php echo sprintf('<a href="%s/wp-admin/admin.php?page=wc-settings&tab=coolrunner">CoolRunner Settings</a>', get_site_url()) ?>
                    </p>
                </div>
                <?php
            });
        }
    }

    public static function showNotice($contents, $type = 'error') {
        add_action('admin_notices', function () use ($contents, $type) {
            ?>
            <div class="notice notice-<?php echo $type ?>">
                <p>
                    <?php echo $contents ?>
                </p>
                <p>
                    <?php echo sprintf('<a href="%s/wp-admin/admin.php?page=wc-settings&tab=coolrunner">CoolRunner Settings</a>', get_site_url()) ?>
                </p>
            </div>
            <?php
        });
    }

    public static function debugEnabled() {
        return get_option('coolrunner_settings_debug_mode') === 'yes';
    }
}

class CoolRunner_Carrier {
    protected $_carrier,
        $_product,
        $_service,
        $_title;

    /**
     * CoolRunner_Carrier constructor.
     *
     * @param $arr
     * @param $order_item WC_Order_Item_Shipping
     */
    public function __construct($arr, $order_item) {

        $product = explode('_', $arr['product'], 3);

        $this->_carrier = $order_item->get_meta('carrier');
        $this->_product = $order_item->get_meta('product');
        $this->_service = $order_item->get_meta('service');
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