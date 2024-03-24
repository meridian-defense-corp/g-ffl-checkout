<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Customer account section management
 */

// function ffl_add_ffl_pickup_query_var( $vars ) {
//     $vars[] = 'ffl-pickup';
//     return $vars;
// }
// add_filter( 'query_vars', 'ffl_add_ffl_pickup_query_var', 0 );

// // Register new endpoint to use inside My Account page
// function ffl_add_ffl_pickup_endpoint() {
//     add_rewrite_endpoint( 'ffl-pickup', EP_ROOT | EP_PAGES );
// }
// add_action( 'init', 'ffl_add_ffl_pickup_endpoint' );

// // Add new menu item for FFL Pickup in My Account
// function ffl_add_ffl_pickup_link_my_account( $items ) {
//     // Insert 'ffl-pickup' before 'customer-logout'
//     $logout = $items['customer-logout'];
//     unset( $items['customer-logout'] );

//     $items['ffl-pickup'] = 'FFL Pickup';
//     $items['customer-logout'] = $logout;

//     return $items;
// }
// add_filter( 'woocommerce_account_menu_items', 'ffl_add_ffl_pickup_link_my_account' );

// // Add content to the new endpoint
// function ffl_pickup_content() {
//     // Add your content here
//     echo '<h3>FFL Pickup</h3><p>Custom content for FFL Pickup.</p>';
// }
// add_action( 'woocommerce_account_ffl-pickup_endpoint', 'ffl_pickup_content' );

/**
 * Add an ajax endpoint for submitting an FFL dealer change request
 */
add_action( 'wp_ajax_md_ffl_change_request', 'md_ffl_change_request' );

function md_ffl_change_request() {
    
    // Attempt to get the hubspot customer ID to associate the ticket with
    $user_id = get_current_user_id();
    $user = get_user_by( 'id', $user_id );
    $email = $user->user_email;

    // hubspot functionality is on a dependent plugin, 'meridian-defense-corp'
    if ( class_exists( '\Mdc\Hubspot' ) ) {
        $contact = \Mdc\Hubspot::search_contact_by_email( 'mike@studioefx.com' );
    }
    $hs_contact_id = 0;
    if ( ! empty( $contact ) && ! is_wp_error( $contact ) ) {
        $hs_contact_id = $contact->getId();
    }

    // this information comes via ajax from/ffl_wc_account.php
    $details = array();
    $details['subject'] = 'FFL Dealer change request';
    $details['content'] = sprintf(
        'FFL dealer change request from %s %s (%s). Please change my FFL dealer to the following: %s, %s, %s, %s %s %s  Email: %s FFL Number: %s Expiration: %s, FFL On file: %s',
        $user->first_name,
        $user->last_name,
        $email,
        sanitize_text_field( $_POST['_shipping_fflcompany'] ),
        sanitize_text_field( $_POST['_shipping_fflstreet'] ),
        sanitize_text_field( $_POST['_shipping_fflcity'] ),
        sanitize_text_field( $_POST['_shipping_fflstate'] ),
        sanitize_text_field( $_POST['_shipping_fflzip'] ),
        sanitize_text_field( $_POST['_shipping_fflphone'] ),
        sanitize_text_field( $_POST['_shipping_fflemail'] ),
        sanitize_text_field( $_POST['_shipping_fflno'] ),
        sanitize_text_field( $_POST['_shipping_fflexp'] ),
        sanitize_text_field( $_POST['_shipping_ffl_onfile'] )
    );
    // submit the ticket
    $ticket = \Mdc\Hubspot::generate_ticket( $details, $hs_contact_id );
}



add_action( 'woocommerce_view_order', function( $order_id ) {

    if ( all_items_are_firearms( $order_id ) ) {
        add_filter( 'woocommerce_order_needs_shipping_address', '__return_true', 10, 1 );
    }

}, 10, 1 );



add_action( 'woocommerce_order_details_after_customer_details', 'ffl_order_details_after_customer_details', 10, 1 );

// add_action('woocommerce_order_details_after_order_table', 'ffl_order_details_after_order_table', 10, 1);

function ffl_order_details_after_customer_details( $order ) {

    ?>

    <div style="margin-top:25px;" class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2">
        <h2 class="woocommerce-column__title"><?php esc_html_e( 'FFL Dealer', 'woocommerce' ); ?></h2>
        <div>
            <p>Your firearms are scheduled for delivery to your preferred FFL dealer. Bring your government-issued photo ID to collect your order. </p><?php

$ffl_company = $order->get_meta('_shipping_fflcompany', true);
$ffl_street = $order->get_meta('_shipping_fflstreet', true);
$ffl_city = $order->get_meta('_shipping_fflcity', true);
$ffl_state = $order->get_meta('_shipping_fflstate', true);
$ffl_zip = $order->get_meta('_shipping_fflzip', true);

echo '<table>';

// Each row contains a name in bold and its value
echo '<tr><th>Company:</th><td>' . esc_html($ffl_company) . '</td></tr>';
echo '<tr><th>Street:</th><td>' . esc_html($ffl_street) . '</td></tr>';
echo '<tr><th>City:</th><td>' . esc_html($ffl_city) . '</td></tr>';
echo '<tr><th>State:</th><td>' . esc_html($ffl_state) . '</td></tr>';
echo '<tr><th>ZIP:</th><td>' . esc_html($ffl_zip) . '</td></tr>';

// End the table
echo '</table>';

    // if not in the account area, we can leave here
    if ( ! is_account_page() ) {
        return;
    }
?>
        <a href="#" class="trigger-ffl-dropdown" href="#"><strong>REQUEST AN FFL DEALER CHANGE</strong></a> 
</div>
    </div><!-- /.col-2 -->
    <div id="ffl-dealer-change-dropdown" style="display:none;">
        <div class="wrapper" style="padding:30px 0;">
            <p>If your order has not yet been shipped, you may request a change to your selected FFL dealer. Please note that this may delay your order. If your order has already been shipped, you will need to contact the carrier to request a change to your delivery address.</p>
            <?php echo do_shortcode( '[checkout-ffl-selector]' ); ?>
            <button id="place_order" disabled class="button " name="submit" value="Submit request">Submit Request</button>
        </div>
        <form id="ffl-updater">
            <!-- These are replicated from checkout. The ffl widget requires these to function properly -->
            <input type="hidden" value="" name="_shipping_ffl_onfile">
            <input type="hidden" value="" name="_shipping_fflexp">
            <input type="hidden" value="" name="_shipping_fflno">
            <input type="hidden" value="" name="_shipping_fflemail">
            <input type="hidden" value="" name="_shipping_fflphone">
            <input type="hidden" value="" name="_shipping_fflzip">
            <input type="hidden" value="" name="_shipping_fflstate">
            <input type="hidden" value="" name="_shipping_fflcity">
            <input type="hidden" value="" name="_shipping_fflstreet">
            <input type="hidden" value="" name="_shipping_fflcompany">
        </form>
    </div>
    <script>
    (function($) {
        'use strict';
        $('.trigger-ffl-dropdown').click(function(e) {
            e.preventDefault();
            $('#ffl-dealer-change-dropdown').slideToggle(400);
        });
        $('#place_order').click(function(e) {
            e.preventDefault();
            var form = $('#ffl-updater');
            var formData = form.serialize();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData + '&action=md_ffl_change_request',
                dataType: 'json'
            })
            .done(function(response) {
                $('#ffl-dealer-change-dropdown').slideUp(400, function() {
                    $('#ffl-updater').remove();
                    $('.trigger-ffl-dropdown').replaceWith('<p><strong>Your request has been submitted.</strong><br /><br />Please allow us 24 to 48 hours to coordinate with the new FFL dealer.</p>');
                });
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
            });
        });
    })(jQuery);
    </script>
<?php
}