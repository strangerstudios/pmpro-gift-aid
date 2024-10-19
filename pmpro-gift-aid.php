<?php
/*
Plugin Name: Paid Memberships Pro - Gift Aid Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/gift-aid/
Description: Add a Checkbox to Opt In to the UK “Gift Aid” Tax-Incentive on Membership Checkout.
Version: .1.2
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-gift-aid
Domain Path: /languages
*/

/**
 * Render the Gift Aid checkbox on the checkout page.
 *
 * @since TBD
 * @return void
 */
function pmproga_pmpro_checkout_boxes() {
	// Get the level at checkout.
	$pmpro_level = pmpro_getLevelAtCheckout();

	// Assume we are showing the gift aid checkbox.
	$show_gift_aid = true;

	// Hide the gift aid checkbox if the level is free.
	if ( pmpro_isLevelFree( $pmpro_level ) ) {
		$show_gift_aid = false;
	}

	// Compatibiliy with Donations Add On.
	if ( defined( 'PMPRODON_DIR' ) ) {
		// Does this level have donation pricing?
		$donfields = get_option( 'pmprodon_' . $pmpro_level->id );

		// If there are donation fields, show the gift aid checkbox.
		if ( ! empty( $donfields['donations'] ) ) {
			$show_gift_aid = true;
		}
	}

	/**
	 * Filter to show or hide the gift aid checkbox.
	 *
	 * @since TBD
	 * @param bool $show_gift_aid True to show the gift aid checkbox, false to hide it.
	 * @param object $pmpro_level The level object.
	 *
	 */
	$show_gift_aid = apply_filters( 'pmproga_show_gift_aid_at_checkout', $show_gift_aid, $pmpro_level );

	// Return early if we are not showing the gift aid checkbox.
	if ( ! $show_gift_aid ) {
		return;
	}

	// Get the gift aid value from the request or user meta. False if not set.
	$gift_aid = 0;
	if ( isset( $_REQUEST['gift_aid'] ) ) {
		$gift_aid = intval( $_REQUEST['gift_aid'] );
	} elseif( is_user_logged_in() ) {
		global $current_user;
		$gift_aid = $current_user->gift_aid;
	}
	?>
	<fieldset id="pmpro_gift_aid" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_gift_aid' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
				<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Gift Aid', 'pmpro-gift-aid' ); ?></h2>
				</legend>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
					<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-description' ) ); ?>"><?php esc_html_e( "Gift Aid legislation allows us to reclaim 25p of tax on every £1 that you give on your subscription and additional donations. It won't cost you any extra.", 'pmpro-gift-aid' ) ?></p>
					<div id="gift_aid-div" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox' ) ); ?>">
						<label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ); ?>" for="gift_aid">
							<input name="gift_aid" type="checkbox" value="1" id="gift_aid" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox', 'gift_aid' ) ); ?>" <?php checked( $gift_aid, 1 ) ?> />
							<?php esc_html_e( 'Allow Gift Aid to be collected?', 'pmpro-gift-aid' ); ?>
						</label>
					</div>
				</div> <!-- end pmpro_form_fields -->
			</div> <!-- end pmpro_card_content -->
		</div> <!-- end pmpro_card -->
	</fieldset> <!-- end pmpro_gift_aid -->
	<?php
}
add_action('pmpro_checkout_boxes', 'pmproga_pmpro_checkout_boxes');

/**
 * Update gift aid value in user meta after checkout.
 *
 * @since TBD
 *
 * @param int $user_id of user who checked out.
 */
function pmproga_pmpro_after_checkout( $user_id ) {
	// Return if user_id is empty.
	if ( empty( $user_id ) ) {
		return;
	}

	// Update gift aid value in user meta.
	if ( isset( $_REQUEST['gift_aid']  ) ) {
		update_user_meta( $user_id, 'gift_aid', intval( $_REQUEST['gift_aid'] ) );
	} elseif ( isset( $_SESSION['gift_aid'] ) ) {
		update_user_meta( $user_id, 'gift_aid', intval( $_SESSION['gift_aid'] ) );
		unset( $_SESSION['gift_aid'] );
	} else {
		// Set gift aid to 0 if not set.
		update_user_meta( $user_id, 'gift_aid', 0 );
	}
}
add_action( 'pmpro_after_checkout', 'pmproga_pmpro_after_checkout' );
add_action( 'pmpro_checkout_before_change_membership_level', 'pmproga_pmpro_after_checkout' );

/*
	Save gift aid value in session for offsite gateways.
*/
function pmpro_paypalexpress_session_vars() {
	if(isset($_REQUEST['gift_aid'])) {
		$_SESSION['gift_aid'] = $_REQUEST['gift_aid'];				
	}
}
add_action("pmpro_paypalexpress_session_vars", "pmpro_paypalexpress_session_vars");
add_action("pmpro_before_send_to_twocheckout", "pmpro_paypalexpress_session_vars");

/*
	Update order notes at checkout.
*/
function pmproga_pmpro_checkout_order($order)
{
	if(!empty($_REQUEST['gift_aid']))
		$gift_aid = intval($_REQUEST['gift_aid']);
	else
		return $order;
	
	if(!empty($order) && (empty($order->notes) || strpos($order->notes, "Gift Aid:") === false))
	{
		if ( ! isset( $order->notes ) || null === $order->notes ) {
			$order->notes = '';
		}

		if($gift_aid)
			$order->notes .= "Gift Aid: Yes\n";
		else
			$order->notes .= "Gift Aid: No\n";
	}

	return $order;
}
add_filter('pmpro_checkout_order', 'pmproga_pmpro_checkout_order');

/*
	Show Gift Aid on confirmation and invoice pages.
*/
function pmproga_pmpro_invoice_bullets_bottom($order)
{
	if(strpos($order->notes, "Gift Aid: Yes") !== false)
	{
	?>
	<li><strong><?php esc_html_e('Gift Aid', 'pmpro-gift-aid');?>:</strong> Yes</li>
	<?php
	}
	elseif(strpos($order->notes, "Gift Aid: No") !== false)
	{
	?>
	<li><strong><?php esc_html_e('Gift Aid', 'pmpro-gift-aid');?>:</strong> No</li>
	<?php
	}
}
add_filter('pmpro_invoice_bullets_bottom', 'pmproga_pmpro_invoice_bullets_bottom');

/**
 * Show gift aid in confirmation email.
 *
 * @since TBD
 *
 * @param object $email Email object.
 */
function pmproga_pmpro_email_filter( $email ) {
	global $wpdb;
 	
	// Return early if this is not an admin confirmation email.
	if ( strpos( $email->template, 'checkout_paid_admin' ) === false ) {
		return $email;
	}

	// Get the order ID from the email.
	$order_id = $email->data['invoice_id'];

	// Return early if the order ID is empty.
	if( empty( $order_id ) ) {
		return $email;
	}

	// Get the order object.
	$order = new MemberOrder( $order_id );

	// Set the gift aid value.
	$gift_aid = __( 'Gift Aid: No', 'pmpro-gift-aid' );
	if ( isset( $order->notes ) && strpos( $order->notes, __( 'Gift Aid: Yes', 'pmpro-gift-aid' ) ) !== false ) {
		$gift_aid = __( 'Gift Aid: Yes', 'pmpro-gift-aid' );
	}

	// Add the gift aid value to the email body.
	$email->body = preg_replace(
		'/(<p>\s*Order\s+#.*?<\/p>)/s',
		'$1' . "<p>" . $gift_aid . "</p>",
		$email->body
	);

	return $email;
}
add_filter( "pmpro_email_filter", "pmproga_pmpro_email_filter", 10, 2 );

/*
	Show gift aid value in orders export.
*/
function pmproga_pmpro_orders_csv_extra_columns($columns)
{
	$columns['gift_aid'] = 'pmpro_orders_csv_gift_aid_column';
	return $columns;
}
add_filter('pmpro_orders_csv_extra_columns', 'pmproga_pmpro_orders_csv_extra_columns');

function pmpro_orders_csv_gift_aid_column($order)
{
	if(strpos($order->notes, "Gift Aid: Yes") !== false)
	{
		$gift_aid = "Yes";
	}
	else
		$gift_aid = "No";

	return $gift_aid;
}


/*
Function to add links to the plugin row meta
*/
function pmproga_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-gift-aid.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/gift-aid/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-gift-aid' ) ) . '">' . __( 'Support', 'pmpro-gift-aid' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmproga_plugin_row_meta', 10, 2);
