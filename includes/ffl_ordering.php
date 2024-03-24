<?php
// no outside access
if (!defined('WPINC')) die('No access outside of wordpress.');

add_filter('woocommerce_checkout_fields', 'ffl_checkout_fields');
function ffl_checkout_fields($fields)
{

    // check to see if the candr override exists
    if (isset($_COOKIE["g_ffl_checkout_candr_override"])) {
        $candr_license_value = isset($_COOKIE["candr_license"]) ? $_COOKIE["candr_license"] : '';
        $fields['billing']['candr_license'] = array(
            'type'          => 'text',
            'required'      => true, 
            'readonly'      => true, 
            'label'         => 'C&R License'
        );
        return $fields;
    }

    if ( all_items_are_firearms() ) {

        // If the *entire order* is going to an FFL dealer, we will use the dealer's
        // address as the basis for determining the appropriate shipping zone.
        // Retain the WC shipping address fields, but set as hidden so we can 
        // populate them with the FFL dealer's address (and not confuse the customer).

        foreach( $fields['shipping'] as $key => $field ) {
            $fields['shipping'][$key]['required'] = false;
            $fields['shipping'][$key]['type'] = 'hidden';
        }
    }

    if (order_requires_ffl_selector()){
        return ffl_customize_checkout_fields($fields);   
    }

    return $fields;
}








/**
 * Persist the FFL order metadata after the order has been placed.
 */
function ffl_checkout_update_order_meta($order_id)
{
    // if (isset($_COOKIE["g_ffl_checkout_candr_override"])) {
    //     if (isset($_COOKIE["candr_license"])){
        //         update_post_meta($order_id, '_candr_license', $_COOKIE["candr_license"]);
    //         // Set the cookie to expire in the past (i.e., immediately expire)
    //         setcookie('g_ffl_checkout_candr_override', '', time() - 3600, '/'); // Set the expiration time to a past timestamp
    //         setcookie('candr_license', '', time() - 3600, '/'); // Set the expiration time to a past timestamp
    
    //         // Unset the cookie from the $_COOKIE superglobal (optional but recommended for immediate effect)
    //         unset($_COOKIE['g_ffl_checkout_candr_override']);
    //         unset($_COOKIE['candr_license']);
    //     }
    // }
    
    if (order_requires_ffl_selector()) {
        update_post_meta($order_id, '_shipping_fflno', $_POST['_shipping_fflno']);
        update_post_meta($order_id, '_shipping_fflexp', $_POST['_shipping_fflexp']);
        update_post_meta($order_id, '_shipping_ffl_onfile', $_POST['_shipping_ffl_onfile']);
        update_post_meta($order_id, '_shipping_fflcompany', $_POST['_shipping_fflcompany']);
        update_post_meta($order_id, '_shipping_fflstreet', $_POST['_shipping_fflstreet']);
        update_post_meta($order_id, '_shipping_fflcity', $_POST['_shipping_fflcity']);
        update_post_meta($order_id, '_shipping_fflstate', $_POST['_shipping_fflstate']);
        update_post_meta($order_id, '_shipping_fflzip', $_POST['_shipping_fflzip']);
        update_post_meta($order_id, '_shipping_fflphone', $_POST['_shipping_fflphone']);
        update_post_meta($order_id, '_shipping_ffl_cust_firstname', $_POST['_shipping_ffl_cust_firstname']);
        update_post_meta($order_id, '_shipping_ffl_cust_lastname', $_POST['_shipping_ffl_cust_lastname']);
    } 
}
add_action('woocommerce_checkout_update_order_meta', 'ffl_checkout_update_order_meta');


/**
 * Add custom fields, to receive dealer information on FFL dealer selection.
 * 
 * Associating with 'billing' has no particular purpose, other than ensuring 
 * that the fields will always appear on the checkout page. The alternatives are 
 * 'shipping' and 'order' fieldsets, but unlike billing, neither of these are 
 * guaranteed to be available.
 */
function ffl_customize_checkout_fields( $fields )
{
    $fields['billing']['_shipping_fflcompany'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_fflstreet'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_fflcity'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_fflstate'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_fflzip'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_fflphone'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_fflemail'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_fflno'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_fflexp'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_ffl_onfile'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_ffl_cust_firstname'] = array(
        'type' => 'hidden',
    );
    $fields['billing']['_shipping_ffl_cust_lastname'] = array(
        'type' => 'hidden',
    );

    return $fields;
}

// Hook to add order metadata after checkout validation
add_action('woocommerce_checkout_create_order', 'add_custom_order_metadata', 10, 2);
function add_custom_order_metadata($order, $data) {
    if (isset($_COOKIE["g_ffl_checkout_candr_override"])) {
        if (isset($_COOKIE["candr_license"])){
            $order->update_meta_data('_candr_license', $_COOKIE["candr_license"]);
            // Set the cookie to expire in the past (i.e., immediately expire)
            setcookie('g_ffl_checkout_candr_override', '', time() - 3600, '/'); // Set the expiration time to a past timestamp
            setcookie('candr_license', '', time() - 3600, '/'); // Set the expiration time to a past timestamp

            // Unset the cookie from the $_COOKIE superglobal (optional but recommended for immediate effect)
            unset($_COOKIE['g_ffl_checkout_candr_override']);
            unset($_COOKIE['candr_license']);
        }
    }
}

add_action('woocommerce_after_checkout_validation', 'ffl_checkout_validation', 10, 2);
function ffl_checkout_validation($data, $errors)
{

    // check to see if the candr override exists
    if (isset($_COOKIE["g_ffl_checkout_candr_override"])) {
        if (empty($data['candr_license'])) {
            $errors->add('validation', "C&R wasn't set, please close your browser and retry.");
            return;
        }
        return;
    }

    if (order_requires_ffl_selector()) {

        if (empty($data['_shipping_fflno'])) {
            $errors->add('validation', '<strong>An FFL dealer</strong> must be selected.');
            return;
        }else{
            // set the favorite FFL cookie for this customer
            setcookie('g_ffl_checkout_favorite_ffl', $data['_shipping_fflno']);     
        }

        if (empty($data['_shipping_fflexp'])) {
            $errors->add('validation', "FFL Expiration Data Required.");
            return;
        }
    }
}

add_action('add_meta_boxes', 'ffl_order_meta_box');
function ffl_order_meta_box()
{
    $screen = wc_get_container()->get( Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
    ? wc_get_page_screen_id( 'shop-order' )
    : 'shop_order';

    add_meta_box(
        'ffl-order-meta-box',
        __('FFL Information'),
        'ffl_order_meta_box_html',
        $screen,
        'normal',
        'high'
    );
}

/**
 * Triggered on backend order update, Change FFL action
 * 
 * Note: works on wp admin backend, on Change FFL action it successfully updates
 * the ffl meta data on the order.
 */
function update_order_ffl()
{
    // Get the order object
    $new_ffl = $_POST['new_ffl'];
    $order_id = $_POST['order_id'];

    $order = wc_get_order($order_id);
    $aKey = get_option('ffl_api_key_option');
    
 
    // Prepare the headers for the POST request
    $headers = array(
        'origin' => get_site_url(),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'x-api-key' => esc_attr($aKey)
    );

    // Call the web service with a POST request
    $api_url = 'https://ffl-api.garidium.com';
    $body = '{"action": "get_ffl_list", "data": {"license_number": "'.$new_ffl.'"}}';

    $response = wp_safe_remote_post($api_url, array(
        'headers' => $headers,
        'body' => $body
    ));
    
    $ffl = wp_remote_retrieve_body($response);
    $ffl = json_decode($ffl, true)[0];
    
    if (strlen($ffl['license_number']) == 20){
        // Update meta_data
        // $new_meta_data = array(
        //     array('key' => '_shipping_email', 'value' => $ffl['email']),
        //     array('key' => '_shipping_fflno', 'value' => $ffl['license_number']),
        //     array('key' => '_shipping_fflexp', 'value' => $ffl['expiration_date']),
        //     array('key' => '_shipping_ffl_onfile', 'value' => $ffl['ffl_on_file']?"Yes":"No"),
        //     array('key' => 'is_vat_exempt', 'value' => 'no'),
        // );
        // Update the order's meta_data and shipping fields
        $order->update_meta_data('_shipping_fflemail', $ffl['email']);
        $order->update_meta_data('_shipping_fflno', $ffl['license_number']);
        $order->update_meta_data('_shipping_fflexp', $ffl['expiration_date']);
        $order->update_meta_data('_shipping_ffl_onfile', $ffl['ffl_on_file']?"Yes":"No");
        // $order->update_meta_data('is_vat_exempt', 'no'); // this may interfere with non-FFL shipping; disabled
        // FFL shipping is a special destination, apart from the customer's res/bus shipping address
        $order->update_meta_data('_shipping_fflcompany', $ffl['list_name']);
        $order->update_meta_data('_shipping_fflphone', $ffl['voice_phone']);
        $order->update_meta_data('_shipping_fflstreet', $ffl['premise_street']);
        $order->update_meta_data('_shipping_fflcity', $ffl['premise_city']);
        $order->update_meta_data('_shipping_fflstate', $ffl['premise_state']);
        $order->update_meta_data('_shipping_fflzip', $ffl['premise_zip_code']);
        $order->update_meta_data('_shipping_ffl_cust_firstname', $order->get_shipping_first_name());
        $order->update_meta_data('_shipping_ffl_cust_lastname', $order->get_shipping_last_name());
    
        // Update shipping fields
        // $shipping_address = array(
            // 'first_name' => $order->get_shipping_first_name(),
            // 'last_name'  => $order->get_shipping_last_name(),
            // 'company'    => $ffl['list_name'],
            // 'address_1'  => $ffl['premise_street'],
            // 'address_2'  => '',
            // 'city'       => $ffl['premise_city'],
            // 'state'      => $ffl['premise_state'],
            // 'postcode'   => $ffl['premise_zip_code'],
            // 'country'    => 'US',
            // 'phone'      => $ffl['voice_phone']
        // );
        
        // $order->set_shipping_address($shipping_address);
    
        // Save the changes
        $order->save();
        echo 'success';
    }else{
        echo 'The FFL License Number provided did not match a record in our ATF database. Please try again. If the error persists please contact support@garidium.com';
    }
    wp_die(); 
}
add_action( 'wp_ajax_update_order_ffl', 'update_order_ffl' );


/**
 * FFL Order Meta Box on the backend order page
 */
function ffl_order_meta_box_html($post_or_order_object)
{
    $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
    $order_id = $order->get_id();
    $aKey = get_option('ffl_api_key_option');

    // Get the "_candr_license" metadata for the order
    $candr_license = $order->get_meta('_candr_license', true);

    // Check if the metadata exists and is not empty
    if (!empty($candr_license)) {
        echo 'C&R License: <a style="cursor:pointer;" id="download_candr">' . $candr_license .'</a><br>
        <script>
        document.getElementById("download_candr").addEventListener("click", function(){
            fetch("https://ffl-api.garidium.com/download", {
                method: "POST",
                headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "x-api-key": "',esc_attr($aKey),'",
                },
                body: JSON.stringify({"candr": "',esc_attr($candr_license),'"})
            })
            .then(response=>response.json())
            .then(data=>{ 
                window.open(data, "_blank", "location=yes, scrollbars=yes,status=yes");         
            });
        });
        </script>
        ';

        return;
    }

    $ffl_company = $order->get_meta('_shipping_fflcompany', true);
    $ffl_onfile = $order->get_meta('_shipping_ffl_onfile', true ) == 'Yes';
    $ffl_license = $order->get_meta('_shipping_fflno', true );
    $ffl_phone = $order->get_meta('_shipping_fflphone', true );
    $ffl_short = str_replace('-','',$ffl_license);  
    $ffl_short = substr($ffl_short, 0, 3) . substr($ffl_short, -5);
    $ffl_expiration = $order->get_meta('_shipping_fflexp', true);
    $ffl_email = $order->get_meta('_shipping_fflemail', true);
    $ffl_customer = $order->get_meta('_shipping_ffl_cust_firstname', true) . ' ' . $order->get_meta('_shipping_ffl_cust_lastname', true);
    $ffl_street = $order->get_meta('_shipping_fflstreet', true);
    $ffl_city = $order->get_meta('_shipping_fflcity', true);
    $ffl_state = $order->get_meta('_shipping_fflstate', true);
    $ffl_zip = $order->get_meta('_shipping_fflzip', true);
    
    $status = $order->get_status();
    
    if ($ffl_license == ""){
        if ($status == "auto-draft") {
            echo 'You must create the order before adding an FFL';
        } else {
            echo '
            <table>
                <tr>
                    <td>
                        <div><a id="change_ffl" class="button alt">Add FFL to Order</a></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <div id="change_ffl_form" style="display:none;">
                            <br>License Number (X-XX-XXX-XX-XX-XXXXX):<br><input style="width:200px;" maxlength=20 type="text" id="new_ffl">
                            <a id="save_new_ffl" class="button alt">Update</a>
                            <a id="cancel_new_ffl" class="button alt">Cancel</a>
                        </div>
                    </td>
                </tr>
            </table>';
        }
    } else {
        $address = '';
        if (isset($ffl_street) && !empty($ffl_street)) {
            $address .= esc_attr($ffl_street) . ', ';
        }
        if (isset($ffl_city) && !empty($ffl_city)) {
            $address .= esc_attr($ffl_city) . ', ';
        }
        if (isset($ffl_state) && !empty($ffl_state)) {
            $address .= esc_attr($ffl_state) . ' ';
        }
        if (isset($ffl_zip) && !empty($ffl_zip)) {
            $address .= esc_attr($ffl_zip);
        }
        echo '
            <p>
            <strong>Company Name:</strong> ' . esc_attr($ffl_company) . '<br>
            <strong>Address:</strong> ' . esc_attr($address) . '<br>
            <strong>License Number:</strong> ' . esc_attr($ffl_license) . '<br>
            <strong>Expiration Date:</strong> ' . esc_attr($ffl_expiration) . '<br>
            ';
        
        if ($ffl_email!=""){
            echo '<strong>Email:</strong> ' . esc_attr($ffl_email) . '<br>';
        }
        echo '<strong>Phone:</strong> ' . esc_attr($ffl_phone) . '<br>';

        echo '<strong>Shipment For:</strong> ' . esc_attr($ffl_customer) . '</p>
            <table>
                <tr>
                    <td>
                        <div><a id="change_ffl" class="button alt">Change FFL</a></div>
                    </td>
                    <td>&nbsp;</td>
                    <td><div><a id="atf_ezcheck" class="button alt">ATF ezCheck</a></div></td>
                    <td>&nbsp;</td>
                    <td><div id="ffl_upload_download"></div></td>
                </tr>
                <tr>
                    <td colspan="4">
                        <div id="change_ffl_form" style="display:none;">
                            <br>License Number (X-XX-XXX-XX-XX-XXXXX):<br><input style="width:200px;" maxlength=20 type="text" id="new_ffl">
                            <a id="save_new_ffl" class="button alt">Update</a>
                            <a id="cancel_new_ffl" class="button alt">Cancel</a>
                        </div>
                    </td>
                </tr>
            </table>';
            $ezCheckLink = "https://fflezcheck.atf.gov/FFLEzCheck/fflSearch?licsRegn=" . substr($ffl_license,0,1) . "&licsDis=" . substr($ffl_license,2,2) . "&licsSeq=" . substr($ffl_license,-5,5);   
            echo '<script>
                    document.getElementById("atf_ezcheck").addEventListener("click", function(){
                        window.open("',esc_url_raw($ezCheckLink),'", "_blank", "location=yes, scrollbars=yes,status=yes"); 
                    });
                 </script>';
    }
    echo '<script>
            document.getElementById("change_ffl").addEventListener("click", function(){
                document.getElementById("change_ffl_form").style.display=""; 
            });

            document.getElementById("cancel_new_ffl").addEventListener("click", function(){
                document.getElementById("change_ffl_form").style.display="none"; 
            });
            document.getElementById("save_new_ffl").addEventListener("click", function(){
                var new_ffl_input = document.getElementById("new_ffl").value;
                if (new_ffl_input.length!=20 || new_ffl_input.indexOf("-") < 0){
                    alert("The FFL must be properly formatted!"); 
                    return;
                }else{
                    document.getElementById("save_new_ffl").disabled = true;
                    document.getElementById("change_ffl_form").innerHTML = "<br><span style=\"font-weight:bold;color:red;font-style:italic;\">Updating FFL Please wait...</span>";
                    jQuery.ajax({
                        type: "POST",
                        url: "',admin_url('admin-ajax.php'),'",
                        data:{action:"update_order_ffl", order_id: "',esc_attr($order_id),'" , new_ffl: new_ffl_input},
                        success:function(response) {
                            if (response!="success"){alert(response);}
                            window.location.reload();
                        }
                    });
                }    
            });
    
            function sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            async function reload() {
                await sleep(10000);
                load_ffl();
            }
            
            function load_ffl(){
                fetch("https://ffl-api.garidium.com", {
                    method: "POST",
                    headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "x-api-key": "',esc_attr($aKey),'",
                    },
                    body: JSON.stringify({"action": "get_ffl_list", "data": {"license_number": "',esc_attr($ffl_license),'"}})
                })
                .then(response=>response.json())
                .then(data=>{ 
                    var onFile = data[0].ffl_on_file;  
                    if (onFile){
                        document.getElementById("ffl_upload_download").innerHTML = "<a id=\"download_ffl\" class=\"button alt\" data-marker-id=\"" + data[0].short_lic_nodash + "\">Download FFL</a>";
                        document.getElementById("download_ffl").addEventListener("click", function(){
                            if (window.confirm("It is your responsibility to ensure the receiving FFL is valid (using ezCHeck) and is willing and able to accept transfers. Do not assume that is the case because this FFL is on-file. If you have an issue with a transfer and the FFL should be removed, please contact us at sales@garidium.com with the FFL number to remove. If the download is not working, try again, check popup-blockers.")){
                                fetch("https://ffl-api.garidium.com/download", {
                                    method: "POST",
                                    headers: {
                                    "Accept": "application/json",
                                    "Content-Type": "application/json",
                                    "x-api-key": "',esc_attr($aKey),'",
                                    },
                                    body: JSON.stringify({"fflno": "',esc_attr($ffl_short),'"})
                                })
                                .then(response=>response.json())
                                .then(data=>{ 
                                    window.open(data, "_blank", "location=yes, scrollbars=yes,status=yes");         
                                });
                            }
                        });

                    }else{
                        document.getElementById("ffl_upload_download").innerHTML = "<strong>Upload a FFL to the g-FFL eFile System:</strong><input type=\"file\" id=\"ffl_upload_filename\"><a id=\"upload_ffl\" class=\"button alt\">Upload FFL</a>";
                        // Select your input type file and store it in a variable
                        const input = document.getElementById("ffl_upload_filename");
                        // This will upload the file after having read it
                        const upload = (file) => {
                            console.log("Uploading File Name = " + file.name);
                            var ext = file.name.split(".").pop();
                            var newFileName = "',esc_attr($ffl_short),'" + "." + ext;
                            fetch("https://ffl-api.garidium.com/garidium-ffls/uploads%2F" + newFileName, { 
                                method: "PUT",
                                headers: {
                                    "x-api-key": "',esc_attr($aKey),'",
                                },
                                body: file
                            })
                            .then(
                                success => {
                                    alert("Upload Successful, we will process the FFL and make it available for the next order shipping to this FFL. Thank you for your contribution!");
                                    document.getElementById("ffl_upload_download").innerHTML = "";
                                    reload();
                                } 
                            ).catch(
                                error => {alert("There was an Error uploading the FFL, please try again.");console.log(error);}
                            );
                        };    
                        // Event handler executed when a file is selected
                        const onSelectFile = () => upload(input.files[0]);

                        // Add a listener on your input
                        // It will be triggered when a file will be selected
                        document.getElementById("upload_ffl").addEventListener("click", onSelectFile, false);
                    }    
                });
            }
            load_ffl();
            </script>';
}

/**
 * Check if the order contains at least one firearm. 
 * 
 * This is determined by the Requires FFL Shipment checkbox, in the product data 
 * config in the backend under the General tab.
 */
function order_requires_ffl_selector()
{
    static $contain_firearms = null;
    if ( ! is_null( $contain_firearms ) ) {
        return $contain_firearms;
    }
    $contain_firearms = false;
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = wc_get_product($cart_item['data']->get_id());
        if (isset($product->get_data()['parent_id']) && $product->get_data()['parent_id'] != 0) {
            $_parent_product = wc_get_product($product->get_data()['parent_id']);
            $firearm = $_parent_product->get_meta('_firearm_product');
        } else {
            $firearm = $product->get_meta('_firearm_product');
        }
        if (isset($firearm)) {
            if ($firearm === 'yes') {
                $contain_firearms = true;
                break;
            }
        }
    }
    return $contain_firearms;
}

/**
 * Check if all items in the order are firearms.
 * @return bool true if all items are firearms, false otherwise.
 */
function all_items_are_firearms( $order_id = null )
{
    $all_firearms = true;
    if ( empty( $order_id ) ) {
        $cart = WC()->cart->get_cart();
        foreach ( $cart as $cart_item ) {
            $product = wc_get_product( $cart_item['data']->get_id() );
            // check both simple and variable products for the _firearm_product meta
            if ( isset( $product->get_data()['parent_id']) && $product->get_data()['parent_id'] != 0 ) {
                $_parent_product = wc_get_product( $product->get_data()['parent_id'] );
                $firearm = $_parent_product->get_meta('_firearm_product');
            } else {
                $firearm = $product->get_meta('_firearm_product');
            }
            if (isset($firearm)) {
                if ($firearm === 'no') {
                    $all_firearms = false;
                    break;
                }
            }
        }
        return $all_firearms;
    } else {
        $order = wc_get_order( $order_id );
        $items = $order->get_items();
        foreach ( $items as $item ) {
            $product = $item->get_product();
            // check both simple and variable products for the _firearm_product meta
            if ( isset( $product->get_data()['parent_id']) && $product->get_data()['parent_id'] != 0 ) {
                $_parent_product = wc_get_product( $product->get_data()['parent_id'] );
                $firearm = $_parent_product->get_meta('_firearm_product');
            } else {
                $firearm = $product->get_meta('_firearm_product');
            }
            if (isset($firearm)) {
                if ($firearm === 'no') {
                    $all_firearms = false;
                    break;
                }
            }
        }
        return $all_firearms;
    }
}