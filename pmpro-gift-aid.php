<?php
/*
Plugin Name: Paid Memberships Pro - Gift Aid Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/gift-aid/
Description: Add a Checkbox to Opt In to the UK “Gift Aid” Tax-Incentive on Membership Checkout.
Version: .1.2
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
*/

/*
	Add checkbox to checkout
*/
function pmproga_pmpro_checkout_after_level_cost() {
	//Check PMPro is active 
	if ( ! function_exists( 'pmpro_getLevelAtCheckout' ) ) {
		return;
	}
	$level = pmpro_getLevelAtCheckout();
	//Bail is level is free, no need to show gift aid.
	if( pmpro_isLevelFree( $level ) ) {
		return;
	}

	if( isset( $_REQUEST['gift_aid'] ) ) {
		$gift_aid = intval($_REQUEST['gift_aid']);
	} elseif( is_user_logged_in() ) {
		global $current_user;
		$gift_aid = $current_user->gift_aid;
	} else {
		$gift_aid = false;
	}
?>
	<hr />
	<h3>Gift Aid</h3>
	<p>Gift Aid legislation allows us to reclaim 25p of tax on every £1 that you give on your subscription and additional donations. It won't cost you any extra.</p>
	<input type="checkbox" id="gift_aid" name="gift_aid" value="1" <?php checked( $gift_aid, 1 ) ?> />
	<label class="pmpro_normal pmpro_clickable" for="gift_aid">Allow Gift Aid to be collected?</label>
	<hr />
<?php
}
add_action('pmpro_checkout_after_level_cost', 'pmproga_pmpro_checkout_after_level_cost');

/*
	Update user meta.
*/
function pmproga_pmpro_after_checkout($user_id)
{
	if(empty($user_id))
		return;
	
	if(isset($_REQUEST['gift_aid'])) {
		update_user_meta($user_id, "gift_aid", intval($_REQUEST['gift_aid']));
	} elseif(isset($_SESSION['gift_aid'])) {
		update_user_meta($user_id, "gift_aid", intval($_SESSION['gift_aid']));
		unset($_SESSION['gift_aid']);
	}
}
add_action("pmpro_after_checkout", "pmproga_pmpro_after_checkout");
add_action("pmpro_checkout_before_change_membership_level", "pmproga_pmpro_after_checkout");

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


/**
 * Show Gift Aid on confirmation and invoice pages.
 *
 * @param MemberOrder $order The order object.
 * @return void
 * @since TBD
 */
function pmproga_pmpro_invoice_bullets_bottom( $order ) {
	$gift_aid = pmproga_yes_or_no( $order );
	?>
	<li><strong><?php _e('Gift Aid: ', 'pmpro');?></strong><?  echo $gift_aid ?> </li>
	<?php
}
add_filter('pmpro_invoice_bullets_bottom', 'pmproga_pmpro_invoice_bullets_bottom');

/**
 * Show gift aid in confirmation email.
 *
 * @param object $email The email object.
 * @return object $email The email object.
 * @since TBD
 */
function pmproga_pmpro_email_filter( $email ) {
	global $wpdb;

	//Bail if it's not a checkout email
	if( strpos($email->template, "checkout") === false ) {
		return $email;
	}

	//Bail if it's a free level and there's no invoice
	if( empty($email->data['invoice_id']) ) {
		return $email;
	}


	//get the user_id from the email
	$order_id = $email->data['invoice_id'];
	if( !empty( $order_id ) ) {
		$order = new MemberOrder($order_id);
		$gift_aid = pmproga_yes_or_no( $order );
		//add to bottom of email
		$email->body = preg_replace("/\<p\>\s*Invoice/", "<p>Gift Aid: " . $gift_aid . "</p><p>Invoice", $email->body);	
	}
	
		
	return $email;
}
add_filter("pmpro_email_filter", "pmproga_pmpro_email_filter", 10, 2);

/**
 * 	Show gift aid value in orders export.
 *
 * @param array $columns Array of columns of the CSV.
 * @return array $columns Array of columns to be added to the CSV.
 * @since TBD
 */
function pmproga_pmpro_orders_csv_extra_columns( $columns ) {
	$columns['gift_aid'] = 'pmpro_orders_csv_gift_aid_column';
	return $columns;
}

add_filter('pmpro_orders_csv_extra_columns', 'pmproga_pmpro_orders_csv_extra_columns');


/**
 * Add gift aid to the order meta.
 *
 * @param MemberOrder $order The order object.
 * @return void
 * @since TBD
 */
function pmpro_orders_csv_gift_aid_column( $order ) {
 return pmproga_yes_or_no( $order );
	
}


/*
Function to add links to the plugin row meta
*/
function pmproga_plugin_row_meta( $links, $file ) {
	if( strpos( $file, 'pmpro-gift-aid.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/gift-aid/' )  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url( 'http://paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-gift-aid' ) ) . '">' . __( 'Support', 'pmpro-gift-aid' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}

add_filter( 'plugin_row_meta', 'pmproga_plugin_row_meta', 10, 2 );


/**
 * Add gift aid value to order meta.
 *
 * @param object The order object.
 * @return void
 * @since TBD
 */
function pmproga_store_gift_aid_in_order_meta( $order ) {
	if ( isset( $_REQUEST['gift_aid'] ) ) {
		update_pmpro_membership_order_meta( $order->id, 'gift_aid', sanitize_text_field( $_REQUEST['gift_aid'] ) );
	}
}

add_action( 'pmpro_added_order','pmproga_store_gift_aid_in_order_meta', 10, 1 );

/**
 * Add gift aid field to the orders page.
 *
 * @param MemberOrder $order The order object.
 * @return void
 * @since TBD
 */
function pmproga_add_gift_aid_field_to_orders_page( $order ) {

	//get gift aid value
	$gift_aid = get_pmpro_membership_order_meta( $order->id, 'gift_aid', true );
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label for="gift_aid"><?php _e( 'Gift aid', 'pmpro-ga' ); ?>:</label></th>
				<td>
					<input type="checkbox" id="gift_aid" name="gift_aid" <?php checked( $gift_aid, 1 ); ?> />
				</td>
			</tr>
		</tbody>
	</table>
	<?php

}

add_action( 'pmpro_after_order_settings', 'pmproga_add_gift_aid_field_to_orders_page'  );

/**
 * Return yes or no based on the value of the gift aid. Add legacy order notes backwards compatibility.
 * 
 * @param MemberOrder $order The order object.
 * @return string Yes or No based on the value of the gift aid.
 * @since TBD
 */
function pmproga_yes_or_no( $order ) {
	$gift_aid = get_pmpro_membership_order_meta( $order->id, 'gift_aid', true );
	// Return either yes or no based on value above
	if ( ! empty( $gift_aid ) ) {
		$gift_aid = 'Yes';
	} else {
		if( strpos( $order->notes, "Gift Aid: Yes" ) !== false ) {
			$gift_aid = "Yes";
		} else {
			$gift_aid = "No";
		}
	}
	return $gift_aid;
}