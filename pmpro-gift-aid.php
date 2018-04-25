<?php
/*
Plugin Name: Paid Memberships Pro - Gift Aid Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-gift-aid/
Description: Add a Checkbox to Opt In to the UK Gift Aid Tax-Incentive on Membership Checkout.
Version: .1.2
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
*/

/*
	Add checkbox to checkout
*/
function pmproga_pmpro_checkout_after_level_cost()
{
	if(isset($_REQUEST['gift_aid']))
		$gift_aid = intval($_REQUEST['gift_aid']);
	elseif(is_user_logged_in())
	{
		global $current_user;
		$gift_aid = $current_user->gift_aid;
	}
	else
		$gift_aid = false;

	global $pmpro_level;

	if ( !pmpro_isLevelFree( $pmpro_level ) ) {
?>
	<hr />
	<h3>Gift Aid</h3>
	<p>Gift Aid legislation allows us to reclaim 25p of tax on every £1 that you give on your subscription and additional donations. It won't cost you any extra.</p>
	<input type="checkbox" id="gift_aid" name="gift_aid" value="1" <?php if($gift_aid) echo 'checked="checked"';?> />
	<label class="pmpro_normal pmpro_clickable" for="gift_aid">Allow Gift Aid to be collected?</label>
	<hr />
<?php
	} // if for free level.
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
	<li><strong><?php _e('Gift Aid', 'pmpro');?>:</strong> Yes</li>
	<?php
	}
	elseif(strpos($order->notes, "Gift Aid: No") !== false)
	{
	?>
	<li><strong><?php _e('Gift Aid', 'pmpro');?>:</strong> No</li>
	<?php
	}
}
add_filter('pmpro_invoice_bullets_bottom', 'pmproga_pmpro_invoice_bullets_bottom');

/*
	Show gift aid in confirmation email.
*/
function pmproga_pmpro_email_filter($email)
{
	global $wpdb;
 	
	//only update admin confirmation emails
	if(strpos($email->template, "checkout") !== false)
	{
		//get the user_id from the email
		$order_id = $email->data['invoice_id'];
		if(!empty($order_id))
		{
			$order = new MemberOrder($order_id);
				
			if(strpos($order->notes, "Gift Aid: Yes") !== false)
			{
				$gift_aid = "Yes";
			}
			elseif(strpos($order->notes, "Gift Aid: No") !== false)
			{
				$gift_aid = "No";
			}
			else
				$gift_aid = "No";

			//add to bottom of email
			$email->body = preg_replace("/\<p\>\s*Invoice/", "<p>Gift Aid: " . $gift_aid . "</p><p>Invoice", $email->body);	
		}
	}
		
	return $email;
}
add_filter("pmpro_email_filter", "pmproga_pmpro_email_filter", 10, 2);

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
