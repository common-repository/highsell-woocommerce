<?php

class WC_Highsellcod_Sefareshi_Method extends WC_Highsell_Base
{
    var $w_unit = "";

    //ok
    public function __construct()
    {
        $this->id = 'highsellcod_sefareshi';
        $this->method_title = __('پست سفارشی (هایسل)');
        $this->method_description = __('ارسال توسط پست سفارشی '); // Description shown in admin
        $this->init();
        $this->account_data();
    }

    //ok
    function init()
    {
        // Load the settings API
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->minimum_fee = $this->get_option('min_amount', 0);
        $this->w_unit = strtolower(get_option('woocommerce_weight_unit'));

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    //ok
    function account_data()
    {
        $this->hs_settings = get_option('hs_highsell_settings');
        $this->username = $this->hs_settings['username'];
        $this->password = $this->hs_settings['password'];
        $this->api_code = $this->hs_settings['api_code'];
        $this->websvc_url = $this->hs_settings['websvc_url'];
    }

    //ok
    function init_form_fields()
    {
        global $woocommerce;

        if ($this->minimum_fee)
            $default_requires = 'min_amount';

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('فعال کردن پست سفارشی', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Method Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('پست سفارشی', 'woocommerce'),
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
            )
        );

    }

    //ok
    public function admin_options()
    {
        ?>
        <h3><?php _e('پست سفارشی'); ?></h3>
        <table class="form-table">
            <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>
        </table>
    <?php
    }

    public function calculate_shipping($package)
    {
        global $woocommerce;
        $customer = $woocommerce->customer;

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

        $data['service_type'] = 2;

        $this->get_shipping_response($data,$package);
    }
} // end class
?>