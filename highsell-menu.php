<?php
function highsell_settings()
{
    add_menu_page('خرید پستی هایسل', 'هایسل', 'manage_options', 'highsell_settings', 'highsell_settings_page', plugins_url('woocommerce-highsell/img/menu-icon.png'), 59);
}

add_action('admin_menu', 'highsell_settings');

function highsell_settings_page()
{
    $message = '';
    $arr = array(
        'websvc' => 'http://api.highsell.ir/ShippingServices.asmx?WSDL',
        'username' => '',
        'password' => '',
        'apicode' => ''
    );

    if (wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        $hs_settings = shortcode_atts($arr, $_POST);

        if (get_option('hs_highsell_settings') === false)
            add_option('hs_highsell_settings', $hs_settings, null, 'no');
        else
            update_option('hs_highsell_settings', $hs_settings);

        $message = 'تنظیمات ذخیره شد.';
    }

    if (get_option('hs_highsell_settings'))
        $hs_settings = shortcode_atts($arr, get_option('hs_highsell_settings'));
    else
        $hs_settings = $arr;
    ?>
    <div class="wrap">
        <h2>تنظیمات افزونه هایسل</h2>

        <p>
            لطفا از نصب افزونه ووکامرس و ووکامرس پارسی اطمینان حاصل نمایید
        </p>

        <?php if (!empty($message)) : ?>
            <div id="message" class="updated"><p><?php echo $message; ?></p></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__)); ?>">
            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row"><label for="wsdl">نشانی وب سرویس</label></th>
                    <td><input name="websvc" type="text" id="websvc" dir="ltr"
                               value="<?php esc_attr_e($hs_settings['websvc']); ?>" class="regular-text"
                               style="direction:ltr;width:30em;"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="username">نام کاربری شما در هایسل</label></th>
                    <td><input name="username" type="text" id="username"
                               value="<?php esc_attr_e($hs_settings['username']); ?>" style="direction:ltr;" class="">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="password">کلمه عبور شما در هایسل</label></th>
                    <td><input name="password" type="password" id="password"
                               value="<?php esc_attr_e($hs_settings['password']); ?>" style="direction:ltr;" class="">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="sms">API Code</label></th>
                    <td><input name="apicode" type="text" id="apicode"
                               value="<?php esc_attr_e($hs_settings['apicode']); ?>" style="direction:ltr;width:30em;"
                               class="regular-text"></td>
                </tr>

                </tbody>
            </table>
            <p>
                <input type="submit" value="ذخیره تنظیمات" id="submit" class="button-primary" name="submit">
            </p>
        </form>
    </div>
<?php
}

?>