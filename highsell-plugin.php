<?php

class WC_HighsellCOD
{
    var $highsell_carrier;
    var $debug_file = "";
    var $email_handle;
    private $client = null;

    public function __construct()
    {
        add_action('woocommerce_checkout_order_processed', array($this, 'save_order'), 10, 2);
        add_filter('woocommerce_available_payment_gateways', array($this, 'get_available_payment_gateways'), 10, 1);
        add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'remove_free_text'), 10, 2);
        add_action('woocommerce_admin_css', array($this, 'add_css_file'));
        add_action('admin_enqueue_scripts', array($this, 'overriade_js_file'), 11);
        add_action('update_highsell_orders_state', array($this, 'update_highsell_orders_state'));
        add_filter('woocommerce_currencies', array($this, 'check_currency'), 20);
        add_filter('woocommerce_currency_symbol', array($this, 'check_currency_symbol'), 20, 2);
        add_filter('woocommerce_states', array($this, 'woocommerce_states'));
        add_action('woocommerce_thankyou', array($this, 'show_invoice'), 5);
        add_filter('woocommerce_default_address_fields', array($this, 'remove_country_field'), 10, 1);
        add_action('woocommerce_before_checkout_form', array($this, 'calc_shipping_after_login'));
        add_action('woocommerce_cart_collaterals', array($this, 'remove_shipping_calculator'));
        add_action('woocommerce_calculated_shipping', array($this, 'set_state_and_city_in_cart_page'));
        add_action('woocommerce_cart_collaterals', array($this, 'add_new_calculator'), 20);
        add_action('woocommerce_before_cart', array($this, 'remove_proceed_btn'));
        add_action('woocommerce_cart_totals_after_order_total', array($this, 'add_proceed_btn'));
        add_filter('woocommerce_locate_template', array($this, 'new_template'), 50, 3);


        if (!class_exists('WC_Highsellcod_Pishtaz_Method') && function_exists('highsellcod_shipping_method_init') && class_exists('WC_Shipping_Method'))
            highsellcod_shipping_method_init();

    }

    public function woocommerce_states($st)
    {
        return false;
    }

    public function show_invoice($order_id)
    {
        $factor = get_post_meta($order_id, '_highsell_tracking_code', true);

        if (empty($factor))
            return;
        $html = '<p>';
        $html .= 'کد رهگیری سفارش شما.';
        $html .= '</br>';
        $html .= 'این کد را نزد خود نگه‌دارید و با مراجعه به سایت هایسل از وضعیت سفارش خود آگاه شوید. ';
        $html .= '</br>' . $factor . '</p><div class="clear"></div>';

        echo $html;
        return;
    }

    public function get_available_payment_gateways($_available_gateways)
    {
        global $woocommerce;

        $shipping_method = $woocommerce->session->chosen_shipping_method;
        if (in_array($shipping_method, array('highsellcod_pishtaz', 'highsellcod_sefareshi'))) {
            foreach ($_available_gateways as $gateway) :
                if ($gateway->id == 'cod') $new_available_gateways[$gateway->id] = $gateway;
            endforeach;

            return $new_available_gateways;
        }
        return $_available_gateways;
    }

    public function new_template($template, $template_name, $template_path)
    {
        global $woocommerce;

        if (!$woocommerce->cart->needs_shipping())
            return $template;

        $shipping_method = $woocommerce->session->chosen_shipping_method;

        if ($template_name == 'checkout/form-billing.php' OR $template_name == 'checkout/form-shipping.php')
            return untrailingslashit(plugin_dir_path(__FILE__)) . '/' . $template_name;

        return $template;
    }

    public function save_order($id, $posted)
    {
        global $woocommerce;
        $this->email_handle = $woocommerce->mailer();
        $order = new WC_Order($id);

        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        $is_highsell = false;
        if ($chosen_shipping) {
            if (in_array($chosen_shipping, array('highsellcod_pishtaz', 'highsellcod_sefareshi'))) {
                $is_highsell = true;
                $shipping_methods = $chosen_shipping;
            }

        } else {
            $shipping_s = $order->get_shipping_methods();

            foreach ($shipping_s as $shipping) {
                if (in_array($shipping['method_id'], array('highsellcod_pishtaz', 'highsellcod_sefareshi'))) {
                    $is_highsell = true;
                    $shipping_methods = $shipping['method_id'];
                    break;
                }
            }
        }

        $this->highsell_carrier = new WC_Highsellcod_Pishtaz_Method();
        $service_type = ($shipping_methods == 'highsellcod_pishtaz') ? 1 : 2;
        $unit = ($this->highsell_carrier->w_unit == 'g') ? 1 : 1000;
        $orders = '';
        $productCode = 0;
        foreach ($order->get_items() as $item) {
            if ($item['product_id'] > 0) {
                $_product = $order->get_product_from_item($item);
                //
                $productName = str_ireplace(',', '', $_product->get_title()); // edit @ 02 14
                $productName = str_ireplace(';', '', $productName);
                $orders .= $productName . ',';
                $price = $order->get_item_total($item);
                $pricetemp = (get_woocommerce_currency() == "IRT") ? (int)$price * 10 : (int)$price;
                $orders .= (string)$pricetemp . ',';
                $weighttemp = intval($_product->weight * $unit);
                $orders .= (string)$weighttemp . ',';
                $orders .= (string)$item['qty'] . ',';
                $producttemp = $productCode++;
                $orders .= (string)$producttemp;
                $orders .= ';';
            }
        }

        $customer_city = $order->shipping_city;
        $customer_city = explode('-', $customer_city);
        $customer_city = intval($customer_city[0]);


        if ($customer_city && $customer_city > 0) {
        } else {
            if ($this->highsell_carrier->debug) {
                $this->debug_file->write('@save_order::city is not valid');
                die('city is not valid');
            }
            return false;
        }


        $params = array(
            'WebServiceUsername' => HS_USERNAME,
            'WebServiceApiCode' => HS_APICODE,
            'ShippingMethodCode' => $service_type,
            'FirstName' => $order->billing_first_name,
            'LastName' => $order->billing_last_name,
            'Mobile' => $order->billing_phone,
            'Phone' => $order->billing_phone,
            'Email' => $order->billing_email,
            'Address' => $order->billing_address_1 . ' - ' . $order->billing_address_2,
            'PostalCode' => $order->billing_postcode,
            'CityID' => $customer_city,
            'IpAddress' => $this->getIp(),
            'CustomerComment' => $order->customer_note,
            'OrderItemsString' => trim($orders, ';')
        );

        $apiResult = $this->add_order($params, $order);

        if($apiResult['ResultCode']!='0'){
            $order->update_status('failed');
            wc_add_notice($apiResult['ResultCodeDescription'] . "<br />" . 'سفارش شما در سیستم هایسل ثبت نشد', 'error');
        } else {
            $uniquecode = $apiResult['OrderUniqueCode'];
            $this->trigger($order->id, $order, true, $uniquecode);
            update_post_meta($id, '_highsell_tracking_code', $uniquecode);

            $html = '<p>';
            $html .= '<strong>سفارش شما با موفقیت ثبت شد</strong>';
            $html .= '</br>';
            $html .= 'شناسه سفارش شما:'. $uniquecode;
            $html .= '</br>';
            $html .= 'با مراجعه به سایت http://embasket.com/tracking.do می توانید از وضعیت سفارش خود مطلع شوید.';
            $html .= '</br>'  . '</p><div class="clear"></div>';
            wc_add_notice($html);
        }
    }


    public function add_order($apiParams, $order)
    {
        global $woocommerce;
        $client=new nusoap_client(HS_WEBSVC, true);
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = true;

        $apiResult = $client->call("QuickSubmitOrder", $apiParams);

        return  $apiResult['QuickSubmitOrderResult'];
    }

    public function highsellError($error, $status)
    {
        if ($status == 'sendprice')
            switch ((int)$error) {
                case 101:
                    return 'خطا در شناسایی فروشگاه';
                    break;

                case 201:
                    return 'محصول یافت نشد';
                    break;

                case 202:
                    return 'فروش محصول غیر فعال است';
                    break;

                case 203:
                    return 'خطای عمومی در افزودن کالا به سبد';
                    break;

                case 301:
                    return 'فروشگاه غیر فعال است';
                    break;

                case 102:
                    return 'پارامتر ارسالی نامعتبر است';
                    break;

                case 401:
                    return 'وضعیت سفارش نامعتبر است';
                    break;

                case 402:
                    return 'سفارش یافت نشد';
                    break;

                case 403:
                    return 'عملیات نامعتبر است';
                    break;

                case 405:
                    return 'سبد خرید خالی است';
                    break;

                case -1:
                    return 'خطای غیر منتظره';
                    break;

                default:
                    return false;
                    break;
            }

        if ($status == 'register')
            switch ((int)$error) {
                case 101:
                    return 'خطا در شناسایی فروشگاه';
                    break;

                case 201:
                    return 'محصول یافت نشد';
                    break;

                case 202:
                    return 'فروش محصول غیر فعال است';
                    break;

                case 203:
                    return 'خطای عمومی در افزودن کالا به سبد';
                    break;

                case 301:
                    return 'فروشگاه غیر فعال است';
                    break;

                case 102:
                    return 'پارامتر ارسالی نامعتبر است';
                    break;

                case 401:
                    return 'وضعیت سفارش نامعتبر است';
                    break;

                case 402:
                    return 'سفارش یافت نشد';
                    break;

                case 403:
                    return 'عملیات نامعتبر است';
                    break;

                case 405:
                    return 'سبد خرید خالی است';
                    break;

                case -1:
                    return 'خطای غیر منتظره';
                    break;

                default:
                    return false;
                    break;
            }
    }

    //ok
    function trigger($order_id, $order, $subject = false, $factor = '')
    {
        global $woocommerce;
        if (!$subject) {
            $message = $this->email_handle->wrap_message(
                'سفارش در سیستم هایسل ثبت نشد',
                sprintf('سفارش  %s در سیستم هایسل ثبت نشد، لطفن بصورت دستی اقدام به ثبت سفارش در پنل شرکت هایسل نمایید.', $order->get_order_number())
            );

            $this->email_handle->send(get_option('admin_email'), sprintf('سفارش  %s در سیستم هایسل ثبت نشد', $order->get_order_number()), $message);
        } else {
            $message = $this->email_handle->wrap_message(
                'سفارش با موفقیت در سیستم هایسل ثبت گردید',
                sprintf('سفارش  %s با موفقیت در سیستم هایسل ثبت گردید. شماره رهگیری هایسلی: %s', $order->get_order_number(), $factor)
            );

            $this->email_handle->send(get_option('admin_email'), sprintf('سفارش %s در سیستم هایسل با موفقیت ثبت گردید', $order->get_order_number()), $message);
        }
    }

    //ok
    public function calc_shipping_after_login($checkout)
    {
        global $woocommerce;

        if (!$woocommerce->cart->needs_shipping())
            return;

        $state = $woocommerce->customer->get_shipping_state();
        $city = $woocommerce->customer->get_shipping_city();

        if ($state && $city) {
            $woocommerce->customer->calculated_shipping(true);
        } else {

            wc_add_notice('پیش از وارد کردن مشخصات و آدرس، لازم است استان و شهر خود را مشخص کنید.');
            $cart_page_id = get_option('woocommerce_cart_page_id'); //wc_get_page_id( 'cart' );
            wp_redirect(get_permalink($cart_page_id));
        }

    }

    //ok
    public function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    //ok
    public function post_code_validation($posted)
    {
        $postcode = $posted['billing_postcode'];

        if (!preg_match("/([0-9]){10}/", $postcode) or strlen(trim($postcode)) != 10)
            wc_add_notice('کد پستی وارد شده معتبر نیست. کد پستی عددی است 10 رقمی.', 'error');
    }

    //ok
    public function remove_shipping_calculator()
    {
        global $woocommerce;

        if (!$woocommerce->cart->needs_shipping())
            return;

        if (get_option('woocommerce_enable_shipping_calc') != 'no')
            update_option('woocommerce_enable_shipping_calc', 'no');
    }

    //ok
    public function remove_free_text($full_label, $method)
    {
        global $woocommerce;

        $shipping_city = $woocommerce->customer->city;
        if (!in_array($method->id, array('highsellcod_pishtaz', 'highsellcod_sefareshi')))
            return $full_label;

        if (empty($shipping_city))
            return $method->label;

        return $full_label;

    }

    //ok
    public function remove_country_field($fields)
    {
        unset($fields['country']);

        return $fields;
    }

    //ok
    public function add_css_file()
    {
        global $typenow;

        if ($typenow == '' || $typenow == "product" || $typenow == "service" || $typenow == "agent") {
            wp_enqueue_style('woocommerce_admin_override', untrailingslashit(plugins_url('/', __FILE__)) . '/css/override.css', array('woocommerce_admin_styles'));
        }
    }

    //ok
    public function overriade_js_file()
    {
        global $woocommerce;

        wp_deregister_script('jquery-tiptip');
        wp_register_script('jquery-tiptip', untrailingslashit(plugins_url('/', __FILE__)) . '/js/jquery.tipTip.min.js', array('jquery'), $woocommerce->version, true);
    }

    //ok
    public function set_state_and_city_in_cart_page()
    {
        global $woocommerce;

        if (!$woocommerce->cart->needs_shipping())
            return;

        // edit @ 02 14
        $state = (woocommerce_clean($_POST['calc_shipping_state'])) ? woocommerce_clean($_POST['calc_shipping_state']) : $woocommerce->customer->get_shipping_state();
        $city = (woocommerce_clean($_POST['calc_shipping_city'])) ? woocommerce_clean($_POST['calc_shipping_city']) : $woocommerce->customer->get_shipping_city();

        if ($city && $state) {
            $woocommerce->customer->set_location('IR', $state, '', $city);
            $woocommerce->customer->set_shipping_location('IR', $state, '', $city);
        } else {
            wc_clear_notices();
            wc_add_notice('استان و شهر را انتخاب کنید. انتخاب هر دو فیلد الزامی است.', 'error');
        }
    }

    //ok
    public function add_new_calculator()
    {
        global $woocommerce;

        if (!$woocommerce->cart->needs_shipping())
            return;

        $have_city = true;
        if (!$woocommerce->customer->get_shipping_city()) {
            echo '<style> div.cart_totals{display:none!important;}
                          p.selectcitynotice {display:block;}
                    </style>';

            $have_city = false;
        }

        include('cart/shipping-calculator.php');
    }

    //ok
    public function remove_proceed_btn()
    {
        global $woocommerce;

        if (!$woocommerce->cart->needs_shipping())
            return;

        echo '<style>input.checkout-button{ display:none!important;}
                    .woocommerce .cart-collaterals .cart_totals table, .woocommerce-page .cart-collaterals .cart_totals table { border:0px; }
              </style>';
    }

    //ok
    public function add_proceed_btn()
    {
        return;
        global $woocommerce;

        if (!$woocommerce->cart->needs_shipping())
            return;

        echo '<tr style="border:0px;"><td colspan="2" style="padding:15px 0px;border:0px;">
              <input onclick="submitchform();" type="submit" style="padding:10px 15px;" class="button alt" id="temp_proceed" name="temp_proceed" value=" &rarr; اتمام خرید و وارد کردن آدرس و مشخصات" />
              </td></tr>';
    }

    public function update_highsell_orders_state()
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdab->prepare("SELECT meta.meta_value, posts.ID FROM {$wpdb->posts} AS posts
                                                              LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
                                                              LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
                                                              LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
                                                              LEFT JOIN {$wpdb->terms} AS term USING( term_id )

                                                              WHERE 	meta.meta_key 		= '_highsell_tracking_code'
                                                              AND     meta.meta_value     != ''
                                                              AND 	posts.post_type 	= 'shop_order'
                                                              AND 	posts.post_status 	= 'publish'
                                                              AND 	tax.taxonomy		= 'shop_order_status'
                                                              AND		term.slug			IN ('processing', 'on-hold', 'pending')
                                                            "));

        if ($results) {
            $tracks = array();
            foreach ($results as $result) {
                $tracks['code'][] = $result->meta_value;
                $tracks['id'][] = $result->ID;

            }
        }

        if (empty($tracks))
            return;

        if (!is_object($this->highsell_carrier))
            $this->highsell_carrier = new WC_Highsellcod_Pishtaz_Method();

        $this->highsell_carrier->client = new nusoap_client($this->highsell_carrier->wsdl_url, true);
        $this->highsell_carrier->client->soap_defencoding = 'UTF-8';
        $this->highsell_carrier->client->decode_utf8 = true;

        for ($i = 0; $i < 5; $i++) {
            $data = array(
                'UserName' => $this->highsell_carrier->username,
                'Pass' => $this->highsell_carrier->password,
                'OrderNumber' => $tracks['code'][$i]);
            $response = $this->highsell_carrier->call("GetOrderState", $data);

            if (is_array($response) && $response['error']) {
                if ($this->highsell_carrier->debug) {
                    $this->debug_file->write('@update_highsell_orders_state::' . $response['message']);
                }
                return;
            }

            mkobject($response);

            if ($this->highsell_carrier->debug) {
                ob_start();
                var_dump($response);
                $text = ob_get_contents();
                ob_end_clean();

                $this->debug_file->write('@update_highsell_orders_state::everything is Ok: ' . $text);
            }

            $res = explode(';', $response->GetOrderStateResult);

            $status = false;
            switch ($res[1]) {
                /*case '0': // سفارش جدید
                       $status = 'pending';
                       break; */
                case '1': // آماده به ارسال
                case '2': // ارسال شده
                case '3': //توزیع شده
                    /*$status = 'processing';
                    break; */
                case '4': // وصول شده
                    $status = 'completed';
                    break;
                case '5': // برگشتی اولیه
                case '6': //برگشتی نهایی
                    $status = 'refunded';
                    break;
                case '7': // انصرافی
                    $status = 'cancelled';
                    break;
            }
            if ($status) {
                $order = new WC_Order($tracks['id'][$i]);
                $order->update_status($status, 'سیستم هایسل @ ' . $res[0]);
            }


        }
        // end for

    }

    //ok
    public function check_currency($currencies)
    {
        if (empty($currencies['IRR']))
            $currencies['IRR'] = __('ریال', 'woocommerce');
        if (empty($currencies['IRT']))
            $currencies['IRT'] = __('تومان', 'woocommerce');

        return $currencies;
    }

    //ok
    public function check_currency_symbol($currency_symbol, $currency)
    {

        switch ($currency) {
            case 'IRR':
                $currency_symbol = 'ریال';
                break;
            case 'IRT':
                $currency_symbol = 'تومان';
                break;
        }

        return $currency_symbol;

    }
}


