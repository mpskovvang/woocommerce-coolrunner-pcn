<?php

define('COOLRUNNER_NAME', get_plugin_data(__DIR__ . '/../woocommerce_coolrunner.php')['Name']);
define('COOLRUNNER_VERSION', get_plugin_data(__DIR__ . '/../woocommerce_coolrunner.php')['Version']);

function crship_register_customer_shipping($force = false) {

    $last_run = get_option('coolrunner_last_sync', 0);

    // Piggy back on any request once per hour to update product lists
    if ($last_run < time() - 3600 || $force) {
        update_option('coolrunner_last_sync', time());
        $username = get_option('coolrunner_settings_username');
        $token = get_option('coolrunner_settings_token');
        $destination = "v2/freight_rates/" . substr(get_option('coolrunner_settings_sender_country'), 0, 2);
        $curldata = "";

        $curl = new CR_Curl();
        $response = $curl->sendCurl($destination, $username, $token, $curldata, $header_enabled = false, $json = true);
        if ((int)!$response) {
            update_option('coolrunner_last_sync', time() - 10000);
            add_action('admin_notices', function () {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('CoolRunner could not retrieve information about your account.', 'coolrunner-shipping-plugin'); ?></p>
                    <p>
                        <?php _e('Please check your username and/or token:', 'coolrunner-shipping-plugin'); ?>
                        <?php echo sprintf('<a href="%s/wp-admin/admin.php?page=wc-settings&tab=coolrunner">CoolRunner Settings</a>', get_site_url()) ?>
                    </p>
                </div>
                <?php
            });
            return false;
        }

        if ($response->status == "ok") {
            $data = json_decode(json_encode($response->result), true);
        }

        CoolRunner::showDebugNotice('Fetching CoolRunner product information: <pre>' . print_r($data, true) . '</pre>');

        if ($data) {
            update_option('coolrunner_wc_curl_data', $data);
        }

        if (!empty($data)) {
            return $data;
        }
        return false;
    }

    return get_option('coolrunner_wc_curl_data');
}

crship_register_customer_shipping();


function crship_add_coolrunner_pickup_to_checkout() {
    ?>
    <div class="coolrunner_select_shop" data-carrier="" name="coolrunner_select_shop">
        <h3><?php echo __('Choose package shop', 'coolrunner-shipping-plugin'); ?></h3>
        <p><?php echo __('Choose where you want your package to be dropped off', 'coolrunner-shipping-plugin'); ?></p>

        <input type="hidden" name="coolrunner_carrier" id="coolrunner_carrier">
        <label for="coolrunner_zip_code_search" class=""><?php echo __('Input Zip Code', 'coolrunner-shipping-plugin'); ?></label>
        <div class="zip-row">
            <div>
                <input class="input-text" type="text" id="coolrunner_zip_code_search" name="coolrunner_zip_code_search">
            </div>
            <div>
                <button style="width: 100%;" type="button" id="coolrunner_search_droppoints"
                        name="coolrunner_search_droppoints">
                    <?php echo __('Search for package shop', 'coolrunner-shipping-plugin'); ?>
                </button>
            </div>
        </div>
        <div class="clear"></div>
        <div class="coolrunner-droppoints">

        </div>

    </div>
    <?php
}

add_action('woocommerce_review_order_before_payment', 'crship_add_coolrunner_pickup_to_checkout');

add_action('wp_ajax_nopriv_coolrunner_droppoint_search', 'crship_coolrunner_droppoint_search');
add_action('wp_ajax_coolrunner_droppoint_search', 'crship_coolrunner_droppoint_search');

function crship_coolrunner_droppoint_search() {

    global $woocommerce;

    $curl = new CR_Curl();

    $curldata = array(
        "carrier"              => $_POST['carrier'],
        "country_code"         => $_POST['country'],//get_option('coolrunner_settings_sender_country'),
        "zipcode"              => $_POST['zip_code'],
        "city"                 => isset($_POST['city']) ? $_POST['city'] : null,
        "street"               => isset($_POST['street']) ? $_POST['street'] : null,
        "number_of_droppoints" => get_option('coolrunner_settings_number_droppoint')
    );

    $destination = "v2/droppoints/";

    $response = $curl->sendCurl($destination, get_option('coolrunner_settings_username'), get_option('coolrunner_settings_token'), $curldata, $header_enabled = false, $json = false);
    $response = json_decode(json_encode($response), true);

    $radios = array();

    if ($response['status'] == "ok" && !empty($response['result'])) {
        $list = $response['result'];
        $list = array_splice($list, 0, get_option('coolrunner_settings_number_droppoint'));

        foreach ($list as $entry) {
            ob_start();

            $props = array(
                'id'      => $entry['droppoint_id'],
                'name'    => $entry['name'],
                'address' => $entry['address']
            );

            ?>
            <label>
                <input required type="radio" name="coolrunner_droppoint" value='<?php echo base64_encode(json_encode($props)) ?>'>
                <table style="margin: 0;">
                    <colgroup>
                        <col width="1">
                        <col>
                    </colgroup>
                    <tr>
                        <td>
                            <div class="cr-check"></div>
                        </td>
                        <td>
                            <b><?php echo $entry['name'] ?></b>
                            <div>
                                <?php printf('%s, %s-%s %s', $entry['address']['street'], $entry['address']['country_code'], $entry['address']['postal_code'], $entry['address']['city']) ?>
                            </div>
                            <?php if ($curldata['city'] && $curldata['street']) : ?>
                                <div>
                                    <?php echo __('Distance', 'coolrunner-shipping-plugin') ?>: <?php echo number_format(intval($entry['distance']) / 1000, 2) ?>km
                                </div>
                            <?php endif; ?>
                            <!--                            <div class="cr-open-hours">-->
                            <!--                                <table cellspacing="0" cellpadding="5">-->
                            <!--                                    <colgroup>-->
                            <!--                                        <col width="1">-->
                            <!--                                        <col>-->
                            <!--                                    </colgroup>-->
                            <!--                                    --><?php //foreach ($entry['opening_hours'] as $data) : ?>
                            <!--                                        <tr>-->
                            <!--                                            <td>--><?php //echo $data['weekday'] ?><!--</td>-->
                            <!--                                            <td>--><?php //echo $data['from'] ?><!-- - --><?php //echo $data['to'] ?><!--</td>-->
                            <!--                                        </tr>-->
                            <!--                                    --><?php //endforeach; ?>
                            <!--                                </table>-->
                            <!--                            </div>-->
                        </td>
                    </tr>
                </table>
            </label>
            <?php
            $radios[] = ob_get_clean();
        }

        echo implode($radios);
    } else {
        echo print_r($response, true);
        echo "No Droppoints were found";
    }
    exit();
}

//add_action( 'wp_ajax_coolrunner_save_droppoint', 'coolrunner_save_droppoint' );

add_action('woocommerce_checkout_update_order_meta', 'crship_add_order_meta', 10, 2);
function crship_add_order_meta($order_id, $posted) {
    if (isset($_POST['coolrunner_droppoint'])) {
        update_post_meta($order_id, '_coolrunner_droppoint', json_decode(base64_decode($_POST['coolrunner_droppoint']), true));
    }
}

add_action('woocommerce_admin_order_data_after_shipping_address', 'crship_package_information', 10, 1);
function crship_package_information($order = null) {
    /** @var WC_Order $order */

    if ($shipping_method = CoolRunner::getCoolRunnerShippingMethod($order->get_id())) {
        echo '<div id="coolrunner-shipment-information">';
        $order_droppoint = get_post_meta($order->get_id(), '_coolrunner_droppoint', true);
        if ($shipping_method->getService() === 'droppoint' && $order_droppoint) { ?>
            <h3><?php echo __('Servicepoint', 'coolrunner-shipping-plugin') ?></h3>
            <?php
            printf(
                '<address><p><div><b>%s</b></div><div>%s</div><div>%s-%s %s</div></p></address>',
                $order_droppoint['name'],
                $order_droppoint['address']['street'],
                $order_droppoint['address']['country_code'],
                $order_droppoint['address']['postal_code'],
                $order_droppoint['address']['city']
            );
        }
        if ($pkg_num = get_post_meta($order->get_id(), '_coolrunner_package_number', true)) : ?>
            <h3><?php echo __('Package Information', 'coolrunner-shipping-plugin') ?></h3>
            <p>
            <table cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                    <td><?php echo __('Package Number', 'coolrunner-shipping-plugin') ?>:</td>
                    <td style="padding: 0 5px;"><?php echo $pkg_num ?></td>
                </tr>
                <?php if ($pkg_id = get_post_meta($order->get_id(), '_coolrunner_pcn_pack_id', true)) : ?>
                    <tr>
                        <td><?php echo __('PCN Pack ID', 'coolrunner-shipping-plugin') ?>:</td>
                        <td style="padding: 0 5px;"><?php echo $pkg_id ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </p>
        <?php endif; ?>
        <h3><?php _e('Carrier', 'coolrunner-shipping-plugin') ?></h3>
        <p><?php echo $shipping_method->getTitle() ?></p>
        <p>
            <a class="coolrunner button button-secondary" id="coolrunner_ajax_resend_call<?php echo $order->get_id() ?>" name="coolrunner_ajax_resend_call" data-order-id="<?php echo $order->get_id() ?>">
                <?php if (!get_post_meta($order->get_id(), '_coolrunner_package_number', true)) : ?>
                    <span><?php echo __('Send to PCN', 'coolrunner-shipping-plugin') ?></span>
                <?php else : ?>
                    <span><?php echo __('Re-send tracking', 'coolrunner-shipping-plugin') ?></span>
                <?php endif; ?>
                <img style="vertical-align: middle; height: 12px" src="<?php bloginfo('wpurl') ?>/wp-content/plugins/coolrunner-shipping-plugin/assets/images/coolrunner-create.png"/>
            </a>
        </p>
        <?php
        echo '</div>';
    }
}


function crship_admin_order_action_filter($order) {
    $shipping_methods = $order->get_items('shipping');
    foreach ($shipping_methods as $shipping_method) {
        $shipping_method = $shipping_method;
    }
    if (isset($shipping_method)) {
        $shipping_method = $shipping_method['method_id'];

        //	$order = new WC_Order($_POST['id']);

        if (strpos($shipping_method, 'coolrunner_') == 'coolrunner_') {

            ?>

            <a class="coolrunner" id="coolrunner_ajax_resend_call<?php echo $order->get_id() ?>" name="coolrunner_ajax_resend_call" data-order-id="<?php echo $order->get_id() ?>" style="height:2em; cursor: pointer;">
                <img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/coolrunner-shipping-plugin/assets/images/coolrunner-create.png"/>
            </a>

            <?php
        }
    }
}

add_filter('woocommerce_admin_order_actions_end', 'crship_admin_order_action_filter', 5, 2);

function coolrunner_ajax_resend_pdf_script() {

    ?>
    <script type="text/javascript">
        jQuery(function ($) {
            $(document).on('click', '[name="coolrunner_ajax_resend_call"]', function () {
                var id = jQuery(this).data('order-id');

                $.ajax({
                    method: "POST",
                    url: ajaxurl,
                    data: {
                        'action': 'coolrunner_resend_label_notification',
                        'id': id
                    }
                })
                    .done(function (data) {
                        if (data.errors.length !== 0) {
                            alert(data.errors.join(' | '));
                            return;
                        } else if (data.sent && data.created) {
                            alert('Shipment sent to PCN and notification sent');
                        } else if (data.sent) {
                            alert('Notification sent');
                        } else if (data.created) {
                            alert('Shipment sent to PCN');
                        }
                        $('#coolrunner-shipment-information').replaceWith(data.new_content);
                        // location.reload(true);
                    })
                    .fail(function (data) {
                        jQuery('.coolrunner-notification').remove();
                        jQuery("#wpbody-content .wrap").prepend('<div id="cr-notif" class="coolrunner-notification notice notice-error is-dismissible"><p> Error, could not display order</p></div>');
                    });

            })
        })

    </script><?php
}

add_action('admin_footer', 'coolrunner_ajax_resend_pdf_script');


add_action('wp_ajax_coolrunner_resend_label_notification', 'coolrunner_resend_label_notification');
function coolrunner_resend_label_notification($post_id = null) {

    if (!empty($_POST['id']) || !is_null($post_id)) {

        $order_id = $post_id ? $post_id : $_POST['id'];

        $order = new WC_Order($order_id);

        $destination = "pcn/order/create";

        $errors = [];

        if (!get_post_meta($order->get_id(), '_coolrunner_pcn_pack_id', true)) {
            $curldata = create_shipment_array($order);

            if (!$curldata) {
                return;
            }

            $curl = new CR_Curl();

            $response = $curl->sendCurl($destination, get_option('coolrunner_settings_username'), get_option('coolrunner_settings_token'), $curldata, $recieve_responsecode = false, $json = true);

            if ($response->status == 'ok') {
//                update_post_meta($order_id, '_coolrunner_auto_status', get_option('coolrunner_settings_auto_status'));
                update_post_meta($order_id, '_coolrunner_package_number', $response->package_number);
                update_post_meta($order_id, '_coolrunner_pcn_pack_id', $response->pcn_pack_id);
            } else {
                $errors = $response->errors;
            }
        }

        //	$response = $curl->sendCurl($destination, $username, $token, $curldata, $header_enabled = false, $json = true);
        //	print_r($curldata);
        $success = 0;
        CoolRunner::showDebugNotice("Sending Order {$order->get_id()} to PCN");
        if (isset($response) || get_post_meta($order->get_id(), '_coolrunner_pcn_pack_id', true)) {
            CoolRunner::showDebugNotice("Order {$order->get_id()} has been sent to PCN");
            $success = 1;
        }
        //update pdf link

        //Send email
        $get_email = get_post_meta($order_id, '_coolrunner_printed', true);

        $sent = false;
        if (get_option('coolrunner_settings_send_email') == 'yes' && count($errors) === 0) {
            CoolRunner::showDebugNotice("Sending Order {$order->get_id()} tracking email");
            //	$tracking_array = coolrunner_get_tracking_data($post_id);
            //	$package_no = $tracking_array->package_number;
            $package_no = get_post_meta($order_id, '_coolrunner_package_number', true);

            $customer = new WC_Customer($order->get_customer_id());

            $placeholders = array(
                '{first_name}'     => $customer->get_first_name(),
                '{last_name}'      => $customer->get_last_name(),
                '{email}'          => $customer->get_email(),
                '{order_no}'       => $order->get_id(),
                '{package_number}' => $package_no
            );

            $text = get_option('coolrunner_settings_tracking_email');

            foreach ($placeholders as $placeholder => $value) {
                $text = str_replace($placeholder, $value, $text);
            }

            ob_start();
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>CoolRunner Tracking</title>
            </head>
            <body style="margin: 0; padding: 0;">
            <?php echo $text ?>
            </body>
            </html>
            <?php
            $message = ob_get_clean();

            $to = $order->get_billing_email();
            $subject = implode(' | ', array(get_bloginfo('name'), 'CoolRunner Tracking', "Order no. #$order_id"));;
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                sprintf('From: %s <%s>', get_bloginfo('name'), get_option('woocommerce_store_email'))
            );

            $sent = wp_mail($to, $subject, $message, $headers, $attachments = "");
            update_post_meta($order_id, '_coolrunner_email_sent', (int)$sent);
        }

        update_post_meta($order_id, '_coolrunner_printed', $success);

        ob_start();
        crship_package_information(new WC_Order($order_id));
        $content = ob_get_clean();

        $return = array(
            'sent' => $sent,
            'created' => isset($response) && $response->status === 'ok',
            'new_content' => $content,
            'errors' => $errors,
            'curldata' => $curldata,
            'response' => $response
        );
        //	$data = $tracking_array;
        //	echo $data->package_number;
        if (!$post_id) {
            header('Content-Type: application/json');
            echo json_encode($return);
            exit();
        } else {
            return $return;
        }

        //Returner korrekt værdi udfra respons kode
        //echo $response['http_code'];


    } else {
        echo "ID was not found";
    }

    exit; // just to be safe
}


function coolrunner_get_tracking_data($order_id) {


    if (!empty($order_id)) {

        //	$destination = get_post_meta($order_id,'coolrunner_pdf_link', true );

        $package_number = get_post_meta($order_id, '_coolrunner_package_number', true);
        if ($package_number) {
            $destination = "v1/tracking/" . $package_number;

            $curldata = array();
            $curl = new CR_Curl();

            $response = $curl->sendCurl($destination, get_option('coolrunner_settings_username'), get_option('coolrunner_settings_token'), $curldata, $recieve_responsecode = false, $json = true);

            return $response;
        } else {
            return null;
        }
    }

}

add_filter('woocommerce_general_settings', function ($arr) {
    $offset = 0;
    foreach ($arr as $i => $setting) {
        if ($setting['id'] === 'woocommerce_store_postcode') {
            $offset = $i + 1;
            break;
        }
    }
    $chopped = array_splice($arr, $offset);
    $arr[] = array(
        'title'    => __('Phone', 'coolrunner-shipping-plugin'),
        'desc'     => __('The phone number for your business location.', 'coolrunner-shipping-plugin'),
        'id'       => 'woocommerce_store_phone',
        'default'  => '',
        'type'     => 'text',
        'desc_tip' => true,
    );
    $arr[] = array(
        'title'    => __('Email', 'coolrunner-shipping-plugin'),
        'desc'     => __('The email address for your business location.', 'coolrunner-shipping-plugin'),
        'id'       => 'woocommerce_store_email',
        'default'  => '',
        'type'     => 'text',
        'desc_tip' => true,
    );
    foreach ($chopped as $setting) {
        $arr[] = $setting;
    }
    return $arr;
});

/**
 * @param WC_Order $order
 *
 * @return array
 */
function create_shipment_array($order) {

    //$dp = ( isset( $filter['dp'] ) ? intval( $filter['dp'] ) : 2 );
    $order_post = get_post($order->get_id());


    //$chosen_methods = WC()->customer->get( 'chosen_shipping_methods' );
    //$chosen_methods = $order->get_items( 'shipping' );
    //$chosen_shipping = $chosen_methods[0];
    $shipping_items = $order->get_items('shipping');
    $key = array_keys($shipping_items);

    $chosen_shipping = $shipping_items[$key[0]]['method_id'];
    $matches = explode('_', $chosen_shipping);

    $user_info = get_userdata(1);
    $add_order_note = get_post_meta($order->get_id(), 'add_order_note', true);

    $droppoint = get_post_meta($order->get_id(), '_coolrunner_droppoint', true);

    $shipping_method = CoolRunner::getCoolRunnerShippingMethod($order->get_id());

    if ($shipping_method) {
        if ($droppoint) {
            $drop_id = $droppoint['id'];
            $drop_name = $droppoint['name'];
            $drop_street = $droppoint['address']['street'];
            $drop_zip = $droppoint['address']['postal_code'];
            $drop_city = $droppoint['address']['city'];
            $drop_country = $droppoint['address']['country_code'];
        } else {
            $drop_id = 0;
            $drop_name = '';
            $drop_street = '';
            $drop_zip = '';
            $drop_city = '';
            $drop_country = '';
        }

        $name = $order->get_shipping_company() ?: ($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());

        $array = array(
            'order_number'          => $order->get_order_number(),
            'receiver_name'         => $name,
            "receiver_attention"    => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            'receiver_street1'      => $order->get_shipping_address_1(),
            'receiver_street2'      => $order->get_shipping_address_2(),
            'receiver_zipcode'      => $order->get_shipping_postcode(),
            'receiver_city'         => $order->get_shipping_city(),
            'receiver_country'      => $order->get_shipping_country(),
            'receiver_phone'        => $order->get_billing_phone(),
            'receiver_email'        => $order->get_billing_email(),
            "receiver_notify"       => true,
            "receiver_notify_sms"   => $order->get_billing_phone(),
            "receiver_notify_email" => $order->get_billing_email(),
            //            "sender_name"           => $user_info->first_name . " " . $user_info->last_name,
            //            'sender_attention'      => "",
            //            "sender_street1"        => WC()->countries->get_base_address(),
            //            'sender_street2'        => WC()->countries->get_base_address_2(),
            //            "sender_zipcode"        => WC()->countries->get_base_postcode(),
            //            "sender_city"           => WC()->countries->get_base_city(),
            //            "sender_country"        => "DK",
            //            "sender_phone"          => get_option('woocommerce_store_phone'),
            //            "sender_email"          => get_option('woocommerce_store_email'),
            "carrier"               => $shipping_method->getCarrier(),
            "carrier_product"       => $shipping_method->getProduct(),
            "carrier_service"       => $shipping_method->getService(),
            "reference"             => "Order no: " . $order->get_id(),
            "label_format"          => 'A4',
            'description'           => $add_order_note,
            'comment'               => "",
            'droppoint_id'          => $drop_id,
            'droppoint_name'        => $drop_name,
            'droppoint_street1'     => $drop_street,
            'droppoint_zipcode'     => $drop_zip,
            'droppoint_city'        => $drop_city,
            'droppoint_country'     => $drop_country,
            'order_lines'           => array()
        );

        foreach ($order->get_items() as $item) {
            $prod = new WC_Order_Item_Product($item->get_id());
            if (!$prod->get_product()->is_virtual()) {
                $array['order_lines'][] = array(
                    'item_number' => $prod->get_product()->get_sku(),
                    'qty'         => $item->get_quantity()
                );
            }
        }

        return $array;
    }

    return false;
}

// Hook in
add_filter('woocommerce_default_address_fields', 'coolrunner_override_default_address_fields');

// Our hooked in function - $address_fields is passed via the filter!
function coolrunner_override_default_address_fields($address_fields) {
    return $address_fields;
}

// Add order new column in administration
add_filter('manage_edit-shop_order_columns', 'woo_order_weight_column', 20);
function woo_order_weight_column($columns) {
    $offset = 8;
    foreach (array_keys($columns) as $key => $value) {
        if ($value === 'order_total') {
            $offset = $key;
            break;
        }
    }
    $updated_columns = array_slice($columns, 0, $offset, true) +
                       array(
                           'order_pcn_sent' => esc_html__('Shipping Status', 'coolrunner-shipping-plugin'),
                       ) +
                       array_slice($columns, $offset, null, true);
    return $updated_columns;
}

// Populate weight column
add_action('manage_shop_order_posts_custom_column', 'woo_custom_order_weight_column', 2);
function woo_custom_order_weight_column($column) {
    global $post;
    if ($column == 'order_pcn_sent') {
        $status = array();
        if (get_post_meta($post->ID, '_coolrunner_package_number')) {
            $status[] = __('Sent to PCN', 'coolrunner-shipping-plugin');
        }
        if (get_post_meta($post->ID, '_coolrunner_email_sent')) {
            $status[] = __('Tracking sent', 'coolrunner-shipping-plugin');
        }

        if (empty($status)) {
            $status[] = 'No status available';
        }

        foreach ($status as $value) {
            echo "<p>$value</p>";
        }

    }
}

add_action('admin_menu', 'remove_post_custom_fields');
function remove_post_custom_fields() {
//    remove_meta_box('postcustom', 'shop_order', 'normal');
}

// Adding Meta container admin shop_order history
add_action('add_meta_boxes', function () {
    add_meta_box('crship_history_fields', __('Tracking History', 'coolrunner-shipping-plugin'), function () {
        global $post;
        $order = new WC_Order($post->ID);
        $config_name = '';
        foreach ($order->get_shipping_methods() as $shippingMethod) {
            if ($shippingMethod->get_method_id() === 'coolrunner') {
                $config_name = implode('_', array(
                    'woocommerce',
                    'coolrunner',
                    $shippingMethod->get_instance_id(),
                    'settings'
                ));
            }
        }
        if ($config_name) {
            $order_id = $post->ID;
            $tracking_array = coolrunner_get_tracking_data($order_id);
            if ($tracking_array &&
                isset($tracking_array->tracking->history) &&
                count($tracking_array->tracking->history) > 0) {
                echo "<p><strong>Status : </strong>" . $tracking_array->tracking->status->header . "</p>";
                $history_array = $tracking_array->tracking->history;
                echo "<ul>";
                foreach ($history_array as $value) {
                    echo '<li>';
                    echo "<div><strong>Time : </strong>$value->time</div>";
                    echo "<div><strong>Message : </strong>$value->message</div>";
                    echo '</li>';
                }
                echo "</ul>";
            } else {
                ?>
                <p><?php echo __('No tracking data available for order no.', 'coolrunner-shipping-plugin') ?><?php echo $post->ID ?></p>
                <?php
            }
        }
    }, 'shop_order', 'side', 'core');
});


//checkout validation

add_action('woocommerce_checkout_process', 'crship_is_droppoint');
if (!function_exists('crship_is_droppoint')) {
    function crship_is_droppoint() {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        $matches = array();
        $matches = explode('_', $chosen_shipping);


        if ($matches[0] == "coolrunner" && end($matches) == "droppoint") {
            $droppoint_selection = isset($_POST['coolrunner_droppoint']) ? str_replace('\"', '"', base64_decode($_POST['coolrunner_droppoint'])) : false;
            $droppoint_selection = json_decode($droppoint_selection, true);
            if (!is_array($droppoint_selection)) {
                wc_add_notice(__('Please select your package shop.', 'coolrunner-shipping-plugin'), 'error');
            }
        }
    }
}

// bulk action
add_action('admin_footer-edit.php', 'crship_custom_bulk_admin_footer');

function crship_custom_bulk_admin_footer() {
    return;
    global $post_type;

    if ($post_type == 'shop_order') {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function () {
                jQuery('<option>').val('coolrunner-pdf').text('<?php _e('Send to PCN / Re-send tracking')?>').appendTo("select[name='action']");
            });
        </script>
        <?php
    }
}


add_action('load-edit.php', 'crship_custom_bulk_action');
function crship_custom_bulk_action() {
    return;
    // Make sure that we on "Woocomerce orders list" page
    if (!isset($_GET['post_type']) || $_GET['post_type'] != 'shop_order') {
        return;
    }
    if (isset($_GET['action']) && $_GET['post_type'] == 'shop_order') {
        // Check Nonce


        if (!check_admin_referer("bulk-posts")) {
            return;
        }

        if ($_GET['action'] == 'coolrunner-pdf') {
            // Remove 'set-' from action
            //    $new_status =  substr( $_GET['action'], 4 );
            $posts = $_GET['post'];

            foreach ($posts as $post_id) {
                $notif = '';
                $class = 'success';

                //	$order = new WC_Order( (int)$post_id );
                $result = coolrunner_resend_label_notification($post_id);
                if ($result['created'] || $result['sent']) {
                    if ($result['created']) {
                        $notif .= "<p>Order no. $post_id sent to PCN</p>";
                    }
                    if ($result['sent']) {
                        $notif .= "<p>Tracking email for order no. $post_id sent to customer</p>";
                    }
                }

                if (!$result['created'] && !$result['sent']) {
                    $notif = __('Unable to create or re-send email for order: ', 'coolrunner-shipping-plugin') . " $post_id";
                    $class = 'error';
                }
            }
        }

    }

}

//add_action('admin_footer-edit.php', 'coolrunner_resend_label_bulk_action');
function coolrunner_resend_label_bulk_action($post_id) {

    if (!empty($post_id)) {

        $order = new WC_Order($post_id);
        $destination = "pcn/order/create";
        $curldata = create_shipment_array($order);

        if ($curldata) {
            $curl = new CR_Curl();
            $response = $curl->sendCurl($destination, get_option('coolrunner_settings_username'), get_option('coolrunner_settings_token'), $curldata, $recieve_responsecode = false, $json = true);
            //	$response = $curl->sendCurl($destination, $username, $token, $curldata, $header_enabled = false, $json = true);
            //	print_r($curldata);

            $success = 0;
            if ($response->status == 'ok') {
                $success = 1;
                //update pdf link
//            update_post_meta($_POST['id'], '_coolrunner_auto_status', get_option('coolrunner_settings_auto_status'));
                update_post_meta($_POST['id'], '_coolrunner_package_number', $response->package_number);
                update_post_meta($_POST['id'], '_coolrunner_pcn_pack_id', $response->pcn_pack_id);

                //Send email
                $get_email = get_post_meta($_POST['id'], 'coolrunner_printed', true);
                if (get_option('coolrunner_settings_send_email') == 'yes' && $get_email == 0 || true) {
                    //	$tracking_array = coolrunner_get_tracking_data($post_id);
                    //	$package_no = $tracking_array->package_number;
                    $package_no = get_post_meta($post_id, 'coolrunner_package_number', true);
                    $message = "Hej, 
					Vedrørende din pakke #" . $order->get_id() . ", kan du finde den via dette link https://coolrunner.dk/ Klik på fanen spor en pakke og indsæt dette track and trace nummer: $package_no
					 ";

                    $to = $order->billing_email;
                    $subject = get_the_title() . " CoolRunner Tracking";
                    wp_mail($to, $subject, $message, $headers = "", $attachments = "");
                }
            }
            update_post_meta($post_id, '_coolrunner_printed', $success);


            //Returner korrekt værdi udfra respons kode
            //echo $response['http_code'];
        }

    } else {
        echo "ID was not found";
    }

}


// bulk action
add_action('admin_footer', 'crship_custom_js_admin_footer');

function crship_custom_js_admin_footer() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            //	  jQuery('#coolrunner_package_name').on("change",function(){
            jQuery(document).on('change', '#coolrunner_package_name', function () {
                var length = jQuery("#coolrunner_package_name option:selected").data('length');
                var width = jQuery("#coolrunner_package_name option:selected").data('width');
                var height = jQuery("#coolrunner_package_name option:selected").data('height');
                var weight = jQuery("#coolrunner_package_name option:selected").data('weight');


                jQuery('#coolrunner_length').val(length);
                jQuery('#coolrunner_width').val(width);
                jQuery('#coolrunner_height').val(height);
                jQuery('#coolrunner_weight').val(weight);
                jQuery('#coolrunner_weight').trigger("chosen:updated");
            });
        });
    </script>
    <?php
}

if (get_option('coolrunner_settings_auto_send_to_pcn') === 'yes') {
    add_action(get_option('coolrunner_settings_auto_send_to_pcn_when'), function ($order_id) {
        coolrunner_resend_label_notification($order_id);
    });
}



