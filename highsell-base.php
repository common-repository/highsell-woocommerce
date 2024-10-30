<?php
class WC_Highsell_Base extends WC_Shipping_Method
{
    protected function  get_card_data() {

        global $woocommerce;
        $customer = $woocommerce->customer;
        $weight = 0;
        $price = 0;
        $unit = ($this->w_unit == 'g') ? 1 : 1000;
        $data = array();

        if (sizeof($woocommerce->cart->get_cart()) > 0 && ($customer->get_shipping_city())) {
            foreach ($woocommerce->cart->get_cart() as $item_id => $values) {
                $_product = $values['data'];
                if ($_product->exists() && $values['quantity'] > 0) {
                    if (!$_product->is_virtual()) {
                        $weight += $_product->get_weight() * $unit * $values['quantity'];
                        $price += get_post_meta($values['product_id'], '_price', true);
                    }
                }
            } //end foreach

            $data['weight'] = $weight;
            $data['price'] = $price;
        }

        return $data;
    }

    protected function get_shipping_response($data = false, $package)
    {
        global $woocommerce;

        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        $rates = array();
        $customer = $woocommerce->customer;
        $debug_response = array();
        $cart_items = $woocommerce->cart->get_cart();

        foreach ($cart_items as $id => $cart_item) {
            $cart_temp[] = $id . $cart_item['quantity'];
        }

        //$total_price = (get_woocommerce_currency() == "IRT") ? $woocommerce->cart->subtotal * 10 + $service : $woocommerce->cart->subtotal + $service;

        $customer_city = $package['destination']['city'];
        $customer_city = explode('-', $customer_city);
        $customer_city = intval($customer_city[0]);

        $apiParams = array(
            'username' =>HS_USERNAME,
            'apiCode' => HS_APICODE,
            'price' => $data['price'],
            'weight' => $data['weight'],
            'cityID' => $customer_city,
            'shippingMethodCode' => $data['service_type']
        );


        $result = $this->call_GetShippingRate_method($apiParams);

        $rates = intval($result);



        $rate = (get_woocommerce_currency() == "IRT") ? (int)(intval(($rates + $service) / 10) / 100) * 100 + 100 : (int)(((int)$rates + $service) / 1000) * 1000 + 1000;

        $my_rate = array(
            'id' => $this->id,
            'label' => $this->title,
            'cost' => $rate,
        );
        $this->add_rate($my_rate);
    }


    function get_api_client(){
        $client = new nusoap_client(HS_WEBSVC, true);
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = true;

        return $client;
    }


    function call_GetShippingRate_method($apiParams)
    {
        global $woocommerce;

        $client=$this->get_api_client();

        $apiResult = $client->call("GetShippingRate",$apiParams);

        if($apiResult['GetShippingRateResult']['ErrorCode']!=0) {
            wc_add_notice('خطای هایسل: '.$apiResult['GetShippingRateResult']['ErrorDescription'] ,'error');
            return 0;
        }

        $cost = (int)$apiResult['GetShippingRateResult']['ShippingCost'] +
            (int)$apiResult['GetShippingRateResult']['ShippingVAT'] +
            (int)$apiResult['GetShippingRateResult']['ServiceCost'];

        return $cost;
    }
}