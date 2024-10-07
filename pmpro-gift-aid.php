<?php
/*
Plugin Name: Paid Memberships Pro - Gift Aid Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/gift-aid/
Description: Add a Checkbox to Opt In to the UK “Gift Aid” Tax-Incentive on Membership Checkout.
Version: .1.2
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
*/

/**
 * Add Gift Aid field to checkout page.
 *
 * @since TBD
 * @return void
 */
function pmproga_pmpro_checkout_after_pricing_fields() {
	//Bail if it's a free level, doesn't make sense to have gift aid.
	$pmpro_level = pmpro_getLevelAtCheckout();
	if( pmpro_isLevelFree( $pmpro_level ) ) {
		return;
	}
	// Get the gift aid value from the request or user meta. False if not set.
	$gift_aid = false;
	if( isset( $_REQUEST['gift_aid'] ) ) {
		$gift_aid = intval( $_REQUEST[ 'gift_aid' ] );
	} elseif( is_user_logged_in() ) {
		global $current_user;
		$gift_aid = $current_user->gift_aid;
	}
?>
<div class=<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card') ); ?>>
	<h3 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">Gift Aid</h3>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
		<p><?php esc_html_e( 'Gift Aid legislation allows us to reclaim 25p of tax on every £1 that you give on your 
			subscription and additional donations. It won\'t cost you any extra.', 'pmpro-gift-aid' ) ?>
		</p>
		<input type="checkbox" id="gift_aid" name="gift_aid" value="1" <?php checked( $gift_aid, 1 ) ?> />
		<label class="pmpro_normal pmpro_clickable" for="gift_aid">
			<?php esc_html_e( 'Allow Gift Aid to be collected?', 'pmpro-gift-aid' ); ?>
		</label>
	</div>
</div>
<?php
}
add_action('pmpro_checkout_after_pricing_fields', 'pmproga_pmpro_checkout_after_pricing_fields');

/**
 *  Update user meta.
 *
 * @param int $user_id The user ID.
 * @return void
 * @since TBD
 */
function pmproga_pmpro_after_checkout( $user_id ) {
	//Bail if no user ID.
	if( empty( $user_id ) ) {
		return;
	}
	
	if( isset( $_REQUEST['gift_aid']  ) ) {
		update_user_meta( $user_id, "gift_aid", intval( $_REQUEST['gift_aid'] ) );
	} elseif( isset( $_SESSION['gift_aid'] ) ) {
		update_user_meta( $user_id, "gift_aid", intval( $_SESSION['gift_aid'] ) );
		unset( $_SESSION['gift_aid'] );
	} else {
		//Remove gift aid if not set.
		update_user_meta( $user_id, "gift_aid", 0 );
	}
}
add_action("pmpro_after_checkout", "pmproga_pmpro_after_checkout");
add_action("pmpro_checkout_before_change_membership_level", "pmproga_pmpro_after_checkout");

/**
 *  Save gift aid value in session for offsite gateways.
 *  Is this deprecated? Ask David.
 *
 * @return void
 * @since TBD
 */
function pmpro_paypalexpress_session_vars() {
	if( isset($_REQUEST['gift_aid'] ) ) {
		$_SESSION['gift_aid'] = $_REQUEST['gift_aid'];				
	}
}
add_action("pmpro_paypalexpress_session_vars", "pmpro_paypalexpress_session_vars");
add_action("pmpro_before_send_to_twocheckout", "pmpro_paypalexpress_session_vars");

/**
 * Add gift aid if present  to order notes.
 *
 * @param Order $order The order object.
 * @return Order $order The order object.
 * @since TBD
 */
function pmproga_pmpro_checkout_order( $order ) {
	//Bail if no gift aid value.
	if ( empty( $_REQUEST['gift_aid'] ) ) {
		return $order;
	}
	$gift_aid = intval( $_REQUEST['gift_aid'] );
	
	if( !empty( $order ) && ( empty( $order->notes ) || strpos( $order->notes, "Gift Aid:" ) === false ) ) {
		if( $gift_aid ) {
			$order->notes .= "Gift Aid: Yes\n";
		} else {
			//doubt that this is necessary. if above is false likely we bailed already.
			$order->notes .= "Gift Aid: No\n";
		}
	}

	return $order;
}

add_filter( 'pmpro_checkout_order', 'pmproga_pmpro_checkout_order' );

/**
 *  Show Gift Aid on confirmation and invoice pages.
 *
 * @param Order $order The order object.
 */
function pmproga_pmpro_invoice_bullets_bottom( $order ) {
	if( strpos($order->notes, "Gift Aid: Yes") !== false ) {
	?>
	<li>
		<strong>
			<?php esc_html_e( 'Gift Aid: Yes', 'pmpro-gift-aid' ); ?>
		</strong>
	</li>
	<?php
		} elseif( strpos( $order->notes, "Gift Aid: No") !== false ) {
	?>
	<li><strong>
			<?php esc_html_e( 'Gift Aid: No', 'pmpro-gift-aid' ); ?>
		</strong>
	</li>
	<?php
	}
}
add_filter('pmpro_invoice_bullets_bottom', 'pmproga_pmpro_invoice_bullets_bottom');

/**
 * Show gift aid in confirmation email.
 *
 * @param Email $email The email object.
 * @return Email $email The email object.
 * @since TBD
 */
function pmproga_pmpro_email_filter( $email ) {
	global $wpdb;
	//Bail if not an admin confirmation email.
	if( strpos( $email->template, "checkout_paid_admin" ) === false ) {
		return $email;
	}

	//get the user_id from the email
	$order_id = $email->data['invoice_id'];
	if( !empty( $order_id ) ) {
		$order = new MemberOrder($order_id);
		$gift_aid = "No";
		if( strpos( $order->notes, "Gift Aid: Yes") !== false ) {
			$gift_aid = "Yes";
		}
		$body = preg_replace(
			'/(<p>\s*Order\s+#.*?<\/p>)/s',
			'$1' . "<p>Gift Aid: " . $gift_aid . "</p>",
			$email->body
		);
		$email->body = $body;
	}

	return $email;
}
add_filter( "pmpro_email_filter", "pmproga_pmpro_email_filter", 10, 2 );

/**
 * Add Gift Aid column to orders CSV export.
 *
 * @param array $columns The columns.
 * @return array $columns The columns.
 * @since TBD
 */
function pmproga_pmpro_orders_csv_extra_columns( $columns ) {
	$columns['gift_aid'] = 'pmpro_orders_csv_gift_aid_column';
	return $columns;
}
add_filter( 'pmpro_orders_csv_extra_columns', 'pmproga_pmpro_orders_csv_extra_columns' );

/**
 * Show gift aid value in orders export.
 *
 * @param Order $order The order object.
 * @return string $gift_aid The gift aid value.
 * @since TBD
 */
function pmpro_orders_csv_gift_aid_column( $order ) {
	$gift_aid = "No";
	if(strpos($order->notes, "Gift Aid: Yes") !== false) {
		$gift_aid = "Yes";
	}

	return $gift_aid;
}


/**
 * Add links to the plugin row meta.
 *
 * @param array $links The array of links.
 * @param string $file The file name.
 * @return array $links The array of links.
 * @since TBD
 */
function pmproga_plugin_row_meta( $links, $file ) {
	if( strpos( $file, 'pmpro-gift-aid.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/gift-aid/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-gift-aid' ) ) . '">' . __( 'Support', 'pmpro-gift-aid' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmproga_plugin_row_meta', 10, 2 );
