<?php
/**
 * Plugin Name: ووکامرس - هایسل
 * Plugin URI: http://highsell.net/plugins/woocommerce
 * Description: افزونه اتصال ووکامرس به سرویس خرید پستی هایسل
 * Version: 2.1.0
 * Author: هایسل
 * Text Domain: highsell.net
 * Domain Path: /lang/
 * Author URI: http://highsell.net
 * Requires at least: 4.0
 * Tested up to: 4.4
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 **/

$hs_settings=get_option('hs_highsell_settings');

if($hs_settings!==false)
{
	define('HS_USERNAME',$hs_settings['username']);
	define('HS_PASSWORD',$hs_settings['password']);
	define('HS_APICODE',$hs_settings['apicode']);
	define('HS_WEBSVC',$hs_settings['websvc']);
}

require_once('highsell-menu.php');


function activate_WC_HighsellCOD_plugin()
{
    wp_schedule_event(time(), 'hourly', 'update_highsell_orders_state');//1137
}

register_activation_hook(__FILE__, 'activate_WC_HighsellCOD_plugin');//21


function deactivate_WC_HighsellCOD_plugin()
{
    wp_clear_scheduled_hook('update_highsell_orders_state');//1137
}

register_deactivation_hook(__FILE__, 'deactivate_WC_HighsellCOD_plugin');//28



// Check if WooCommerce is active
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    require_once('highsell-plugin.php');

    function highsellcod_shipping_method_init()
    {
        if (!class_exists('nusoap_client')) { // edit @ 02 14
            include_once(plugin_dir_path(__FILE__) . 'lib/nusoap/nusoap.php');
        }

        //
        date_default_timezone_set('Asia/Tehran');
        ini_set('default_socket_timeout', 160);

        if (!class_exists('WC_Highsell_Base')) {
            require_once('highsell-base.php');
        }

        // Define Pishtaz method
        if (!class_exists('WC_Highsellcod_Pishtaz_Method')) {
            require_once('highsell-pishtaz.php');
        }

        if (!class_exists('WC_Highsellcod_Sefareshi_Method')) {
            require_once('highsell-sefareshi.php');
        }
    } // end function
    add_action('woocommerce_shipping_init', 'highsellcod_shipping_method_init');

    function add_highsellcod_shipping_method($methods)
    {
        $methods[] = 'WC_Highsellcod_Pishtaz_Method';
        $methods[] = 'WC_Highsellcod_Sefareshi_Method';
        return $methods;
    }

    add_filter('woocommerce_shipping_methods', 'add_highsellcod_shipping_method');

    $GLOBALS['HighsellCOD'] = new WC_HighsellCOD();

    function mkobject(&$data)
    {
        $numeric = false;
        foreach ($data as $p => &$d) {
            if (is_array($d))
                mkobject($d);
            if (is_int($p))
                $numeric = true;
        }
        if (!$numeric)
            settype($data, 'object');
    }
}