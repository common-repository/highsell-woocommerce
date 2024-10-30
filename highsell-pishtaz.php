<?php

class WC_Highsellcod_Pishtaz_Method extends WC_Highsell_Base
{
    var $debug = 1;
    var $w_unit = "";
    var $debug_file = "";
    var $client = null;

    public function __construct()
    {
        $this->id = 'highsellcod_pishtaz';
        $this->method_title = __('پست پیشتاز (هایسل)');
        $this->method_description = __('ارسال توسط پست پیشتاز'); // Description shown in admin

        $this->init();
    }

    function init()
    {
        $this->init_form_fields(); //  [91] This is part of the settings API. Override the method to add your own settings
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->minimum_fee = $this->get_option('min_amount', 0);
        $this->w_unit = strtolower(get_option('woocommerce_weight_unit'));

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    function init_form_fields()
    {
        global $woocommerce;

        if ($this->minimum_fee)
            $default_requires = 'min_amount';

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('فعال کردن پست پیشتاز', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Method Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('پست پیشتاز - هایسل', 'woocommerce'),
                'desc_tip' => true,
            ),
            'min_amount' => array(
                'title' => __('Minimum Order Amount', 'woocommerce'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0'
                ),
                'description' => __('کمترین میزان خرید برای فعال شدن این روش ارسال.', 'woocommerce'),
                'default' => '0',
                'desc_tip' => true,
                'placeholder' => '0.00'
            ),
        );
    }


    public function admin_options()
    {
        ?>
        <h3><?php _e('پست پیشتاز'); ?></h3>
        <table class="form-table">
            <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>
        </table>
    <?php
    }

    function is_available($package)
    {
        return true;
        global $woocommerce;

        if ($this->enabled == "no")
            return false;
        if (!in_array(get_woocommerce_currency(), array('IRR', 'IRT')))
            return false;
        if ($this->w_unit != 'g' && $this->w_unit != 'kg')
            return false;

        // Enabled logic
        $has_met_min_amount = false;

        if (isset($woocommerce->cart->cart_contents_total)) {
            if ($woocommerce->cart->prices_include_tax)
                $total = $woocommerce->cart->cart_contents_total + array_sum($woocommerce->cart->taxes);
            else
                $total = $woocommerce->cart->cart_contents_total;

            if ($total >= $this->minimum_fee)
                $has_met_min_amount = true;
        }

        if ($has_met_min_amount)
            $is_available = true;

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available);
    }

    public function calculate_shipping($package)
    {
        global $woocommerce;

        if (empty($package['destination']['city'])) {
            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => 0
            );
            $this->add_rate($rate);
        }

        $this->shipping_total = 0;

        $data=$this->get_card_data();

        $data['service_type'] = 1; // pishtaz

        $this->get_shipping_response($data, $package);
    }


    function highsell_service()
    {
        // webservice dont responsible!
        return 0;
        global $woocommerce;
        $cache_data = get_transient('highsell_cod_service_price');

        if ($cache_data) {
            if (time() - (int)$cache_data['date'] < 86400) {
                if ($this->debug) {
                    $this->debug_file->write('@highsell_service::Everything is Ok --> return from cache');
                }
                return $cache_data['price'];
            }

        }

        $this->client = new nusoap_client($this->wsdl_url, true);
        $this->client->soap_defencoding = 'UTF-8';
        $this->client->decode_utf8 = true;
        $response = $this->call("GetServiceCost", array());

        if (is_array($response) && $response['error']) {
            if ($this->debug) {
                $this->debug_file->write('@highsell_service::' . $response['message']);
                wc_clear_notices();
                wc_add_notice('<p>Highsell Error:</p> <p>' . $response['message'] . '</p>');
            }
            return 7000; // estimated
        }

        $service = intval($response);
        $cache_data['date'] = time();
        $cache_data['price'] = $service;
        set_transient('highsell_cod_service_price', $cache_data, 60 * 60 * 24);

        if ($this->debug) {
            $this->debug_file->write('@highsell_service::Everything is Ok');
        }
        return $service;
    }

    //call method is ok
    public function call($method, $params)
    {
        $result = $this->client->call($method, $params);

        if ($this->client->fault || ((bool)$this->client->getError())) {
            return array('error' => true, 'fault' => true, 'message' => $this->client->getError());
        }
        return $result;
    }

    public function handleError($error, $status)
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
} // end class
?>