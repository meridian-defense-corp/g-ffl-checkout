<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       garidium.com
 * @since      1.0.0
 *
 * @package    G_ffl_Api
 * @subpackage G_ffl_Api/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    G_ffl_Api
 * @subpackage G_ffl_Api/public
 * @author     Big G <sales@garidium.com>
 */
class G_ffl_Api_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ffl-api-widget.js', array( 'jquery' ), rand(0, 99999), false );
        wp_enqueue_script($this->plugin_name . '_init', plugin_dir_url(__FILE__) . 'js/ffl-api-public.js', array('jquery', $this->plugin_name), rand(0, 99999), false);
        wp_localize_script($this->plugin_name . '_init', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    function ffl_woo_checkout()
    {
        $aKey = esc_attr(get_option('ffl_api_key_option'));
        
        if ($aKey === '') {
            return false;
        }

        global $woocommerce;
        $items = $woocommerce->cart->get_cart();

        $fireArmCount = 0;
        foreach ($items as $item => $values) {
            $_product = wc_get_product($values['data']->get_id());

            if (isset($_product->get_data()['parent_id']) && $_product->get_data()['parent_id'] != 0) {
                $_parent_product = wc_get_product($_product->get_data()['parent_id']);
                $firearm = $_parent_product->get_meta('_firearm_product');
            } else {
                $firearm = $_product->get_meta('_firearm_product');
            }

            if (isset($firearm)) {
                if ($firearm === 'yes') {
                    $fireArmCount++;
                }
            }
        }
        //$fireArmCount = 0;
        // check to see if the candr override exists
        if (isset($_COOKIE["g_ffl_checkout_candr_override"])) {
            $fireArmCount = 0;
        }
        if ($fireArmCount > 0) {
      //      add_action(get_option('ffl_init_map_location', 'woocommerce_checkout_order_review'), array($this, 'ffl_init_map'), 10);
        }else{
            add_action('woocommerce_checkout_shipping', array( $this, 'handle_no_ffl_items' ), 10);
        }
    }

    function handle_no_ffl_items(){
        $notes = '';
        if (isset($_COOKIE["candr_license"])) {
            $notes = $_COOKIE['candr_license'];
        }
        echo '
            <script>
                jQuery("#candr_license").val("'.$notes.'");
                jQuery("#candr_license").prop("readonly", true);
                // if (document.getElementById("ship-to-different-address-checkbox") != null){
                //     document.getElementById("ship-to-different-address-checkbox").checked = false;
                // }
                // document.getElementById("shipping_first_name").value = "";
                // document.getElementById("shipping_last_name").value = "";
                // document.getElementById("shipping_company").value = "US";
                // document.getElementById("shipping_address_1").value = "";
                // document.getElementById("shipping_address_2").value = "";
                // document.getElementById("shipping_city").value = "";
                // document.getElementById("shipping_postcode").value = "";
                // document.getElementById("shipping_state").value = "";
            </script>';
    }

    function ffl_picker_shortcode()
    {
        add_shortcode( 'checkout-ffl-selector', array( $this, 'ffl_init_map' ) );
    }

    /**
     * Shortcode callback
     * 
     * Inject data consumed by ffl-api-widget.js
     */
    function ffl_init_map()
    {
        // currently only two areas the ffl picker must be displayed: checkout and account pages
        // @todo may need to add to admin area
        if ( ! is_account_page() && ( is_checkout() && ! order_requires_ffl_selector() ) ) {
            return '';
        }
        $cartAllFirearms = all_items_are_firearms();
        $aKey = get_option('ffl_api_key_option');
        $wMes = '';
        $hok = get_option('ffl_init_map_location');
        $fflLocalPickup = get_option('ffl_local_pickup');
        $candrOverride = get_option('ffl_candr_override');
        $fflIncludeMap = get_option('ffl_include_map') == "No"?false:true;
        $customerFavoriteFFL = '';
        if(isset($_COOKIE['g_ffl_checkout_favorite_ffl'])) {
            $customerFavoriteFFL = $_COOKIE['g_ffl_checkout_favorite_ffl'];
        }
        ob_start();
        echo '<div id="ffl_container"></div>';
        echo '
                <script type="text/javascript">
                    const fflAllFirearms = ' . absint($cartAllFirearms) . ';
                    let g_ffl_plugin_directory = "' . esc_attr(plugin_dir_url(__FILE__)) . '";    
                    let aKey = "' . esc_attr($aKey) . '";
                    let wMes = `' . wp_kses_post($wMes) . '`;
                    let hok = "' . esc_attr($hok) . '";
                    let fflLocalPickup = "' . esc_attr($fflLocalPickup) . '";
                    let candrOverride = "' . esc_attr($candrOverride) . '";
                    let fflIncludeMap = "' . esc_attr($fflIncludeMap) . '";
                    let licenseSearchValue = "";
                    let customerFavoriteFFL = "' . esc_attr($customerFavoriteFFL) . '";
                    localStorage.removeItem("selectedFFL");
                    if(!document.getElementById("ffl-zip-code")) {
                        document.addEventListener("DOMContentLoaded", function() {
                            initFFLJs(aKey,wMes,hok);
                        });
                    }
                </script>';
        return ob_get_clean();
    }
}

