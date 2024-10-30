<?php
/**
 * Checkout shipping information form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;
?>

<?php if ( ( $woocommerce->cart->needs_shipping() || get_option('woocommerce_require_shipping_address') == 'yes' ) && ! $woocommerce->cart->ship_to_billing_address_only() ) : ?>

	<?php
		if ( empty( $_POST ) ) :

			$shiptobilling = (get_option('woocommerce_ship_to_same_address')=='yes') ? 1 : 0;
			$shiptobilling = apply_filters('woocommerce_shiptobilling_default', $shiptobilling);

		else :

			$shiptobilling = $checkout->get_value('shiptobilling');

		endif;
	?>

	<p class="form-row" id="shiptobilling" style="display: none;">
		<input id="shiptobilling-checkbox" class="input-checkbox" checked="checked" type="checkbox" name="shiptobilling" value="1" />
		<label for="shiptobilling-checkbox" class="checkbox"><?php _e( 'Ship to billing address?', 'woocommerce' ); ?></label>
	</p>

	<h3 style="display: none;"><?php _e( 'Shipping Address', 'woocommerce' ); ?></h3>

	<div class="shipping_address" style="display: none;">

		<?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

		<?php /*foreach ($checkout->checkout_fields['shipping'] as $key => $field) : ?>

			<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>

		<?php endforeach;*/ ?>
        

		
		
				<p class="form-row form-row-wide address-field update_totals_on_change validate-required woocommerce-validated" id="billing_country_field" style="display: none;">
						<label for="billing_country" class="">کشور <abbr class="required" title="ضروری">*</abbr></label>
						<!--<select name="billing_country" id="billing_country" class="country_to_state country_select chzn-done" style="display: none; ">
                            <option value="IR" selected="selected">ایران</option>
                        </select>-->
            </p>
		
			<p class="form-row form-row-first validate-required" id="shipping_first_name_field"><label for="shipping_first_name" class="">نام <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="shipping_first_name" id="shipping_first_name" placeholder="" value="<?php echo $checkout->get_value( 'shipping_first_name' ) ?>">
				</p>
		
			<p class="form-row form-row-last validate-required" id="shipping_last_name_field"><label for="shipping_last_name" class="">نام خانوادگی <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="shipping_last_name" id="shipping_last_name" placeholder="" value="<?php echo $checkout->get_value( 'shipping_last_name' ) ?>">
				</p><div class="clear"></div>
		
			<p class="form-row form-row-wide woocommerce-validated" id="shipping_company_field"><label for="shipping_company" class="">نام شرکت</label><input type="text" class="input-text" name="shipping_company" id="shipping_company" placeholder="" value="<?php echo $checkout->get_value( 'shipping_company' ) ?>">
				</p>
            <style>
                select{font:12px tahoma; padding: 2px 1px;}
            </style>
            
            <input type="hidden" name="shipping_state" id="shipping_state" value="<?php echo $woocommerce->customer->get_shipping_state();/*is_string($checkout->get_value('shipping_state')) ? $checkout->get_value('shipping_state') : */ ?>" />
            <input type="hidden" name="shipping_city" id="shipping_city" value="<?php echo $woocommerce->customer->get_shipping_city() ?>" />
            
		
			<p class="form-row form-row-wide validate-required" id="shipping_address_1_field"><label for="shipping_address_1" class="">آدرس <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="shipping_address_1" id="shipping_address_1" placeholder="خیابان" value="<?php echo $checkout->get_value( 'shipping_address_1' ) ?>" autocomplete="no">
				</p>
		
			<p class="form-row form-row-wide" id="shipping_address_2_field"><input type="text" class="input-text" name="shipping_address_2" id="shipping_address_2" placeholder="شماره پلاک، واحد، بلوک " value="<?php echo $checkout->get_value( 'shipping_address_2' ) ?>" autocomplete="no">
				</p>

			<p class="form-row form-row-wide validate-required" id="shipping_postcode_field" data-o_class="form-row form-row-wide address-field validate-required"><label for="shipping_postcode" class="">کدپستی <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="shipping_postcode" id="shipping_postcode" placeholder="کدپستی" value="<?php echo $checkout->get_value( 'shipping_postcode' ) ?>" autocomplete="no">
				</p>
		
			<div class="clear"></div>

		<?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>

	</div>

<?php endif; ?>

<?php do_action('woocommerce_before_order_notes', $checkout); ?>

<?php if (get_option('woocommerce_enable_order_comments')!='no') : ?>

	<?php if ($woocommerce->cart->ship_to_billing_address_only()) : ?>

		<h3><?php _e( 'Additional Information', 'woocommerce' ); ?></h3>

	<?php endif; ?>

	<?php foreach ($checkout->checkout_fields['order'] as $key => $field) : ?>

		<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>

	<?php endforeach; ?>

<?php endif; ?>

<?php do_action('woocommerce_after_order_notes', $checkout); ?>