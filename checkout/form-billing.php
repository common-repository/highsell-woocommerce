<?php
/**
 * Checkout billing information form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;
?>
<?php if ( $woocommerce->cart->ship_to_billing_address_only() && $woocommerce->cart->needs_shipping() ) : ?>

	<h3><?php _e( 'Billing &amp; Shipping', 'woocommerce' ); ?></h3>

<?php else : ?>

	<h3><?php _e( 'Billing Address', 'woocommerce' ); ?></h3>

<?php endif; ?>

<?php do_action('woocommerce_before_checkout_billing_form', $checkout ); ?>

<?php /*foreach ($checkout->checkout_fields['billing'] as $key => $field) : ?>

	<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>

<?php endforeach;*/
/**
 * 
 * */?>
	<p class="form-row form-row-wide address-field update_totals_on_change validate-required woocommerce-validated" id="billing_country_field" style="display: none;">
						<label for="billing_country" class="">کشور <abbr class="required" title="ضروری">*</abbr></label>
						<!--<select name="billing_country" id="billing_country" class="country_to_state country_select chzn-done" style="display: none; ">
                            <option value="IR" selected="selected">ایران</option>
                        </select>-->
    </p>

	<p class="form-row form-row-first validate-required" id="billing_first_name_field"><label for="billing_first_name" class="">نام <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="billing_first_name" id="billing_first_name" placeholder="" value="<?php echo $checkout->get_value( 'billing_first_name' ) ?>"/>
				</p>

	<p class="form-row form-row-last validate-required woocommerce-invalid woocommerce-invalid-required-field" id="billing_last_name_field"><label for="billing_last_name" class="">نام خانوادگی <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="billing_last_name" id="billing_last_name" placeholder="" value="<?php echo $checkout->get_value( 'billing_last_name' ) ?>">
				</p><div class="clear"></div>

	<p class="form-row form-row-wide" id="billing_company_field"><label for="billing_company" class="">نام شرکت</label><input type="text" class="input-text" name="billing_company" id="billing_company" placeholder="" value="<?php echo $checkout->get_value( 'billing_company' ) ?>">
				</p>
    
    <style>
    select{font:12px tahoma; padding: 2px 1px;}
    </style>

    <input type="hidden" name="billing_state" id="billing_state" value="<?php echo $woocommerce->customer->get_shipping_state(); ?>" />
    <input type="hidden" name="billing_city" id="billing_city" value="<?php echo $woocommerce->customer->get_shipping_city() ?>" />
    

	<p class="form-row form-row-wide validate-required" id="billing_address_1_field"><label for="billing_address_1" class="">آدرس <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="billing_address_1" id="billing_address_1" placeholder="خیابان" value="<?php echo $checkout->get_value( 'billing_address_1' ) ?>" autocomplete="no">
				</p>

	<p class="form-row form-row-wide" id="billing_address_2_field"><input type="text" class="input-text" name="billing_address_2" id="billing_address_2" placeholder="شماره پلاک، واحد، بلوک " value="<?php echo $checkout->get_value( 'billing_address_2' ) ?>" autocomplete="no">
				</p>

	

	
    
    <p class="form-row form-row-wide validate-required" id="billing_postcode_field" data-o_class="form-row form-row-wide address-field validate-required"><label for="billing_postcode" class="">کد پستی <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="billing_postcode" id="billing_postcode" placeholder="کدپستی" value="<?php echo $checkout->get_value( 'billing_postcode' ) ?>" autocomplete="no">
				</p>

	<div class="clear"></div>

	<p class="form-row form-row-first validate-required validate-email" id="billing_email_field"><label for="billing_email" class="">آدرس ایمیل <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="billing_email" id="billing_email" placeholder="" value="<?php echo $checkout->get_value( 'billing_email' ) ?>">
				</p>

	<p class="form-row form-row-last validate-required" id="billing_phone_field"><label for="billing_phone" class="">تلفن <abbr class="required" title="ضروری">*</abbr></label><input type="text" class="input-text" name="billing_phone" id="billing_phone" placeholder="" value="<?php echo $checkout->get_value( 'billing_phone' ) ?>">
				</p><div class="clear"></div>
 
 <?php
 /**
  * 
  * */
 ?>


<?php do_action('woocommerce_after_checkout_billing_form', $checkout ); ?>



<?php if ( ! is_user_logged_in() && $checkout->enable_signup ) : ?>

	<?php if ( $checkout->enable_guest_checkout ) : ?>

		<p class="form-row form-row-wide">
			<input class="input-checkbox" id="createaccount" <?php checked($checkout->get_value('createaccount'), true) ?> type="checkbox" name="createaccount" value="1" /> <label for="createaccount" class="checkbox"><?php _e( 'Create an account?', 'woocommerce' ); ?></label>
		</p>

	<?php endif; ?>

	<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

	<div class="create-account">

		<p><?php _e( 'Create an account by entering the information below. If you are a returning customer please login at the top of the page.', 'woocommerce' ); ?></p>

		<?php foreach ($checkout->checkout_fields['account'] as $key => $field) : ?>

			<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>

		<?php endforeach; ?>

		<div class="clear"></div>

	</div>

	<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>

<?php endif; ?>