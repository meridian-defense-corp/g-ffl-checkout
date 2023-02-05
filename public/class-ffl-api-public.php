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
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ffl_Api_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ffl_Api_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

//		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ffl-api-public.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ffl_Api_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ffl_Api_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ffl-api-widget.js', array( 'jquery' ), $this->version, false );
        //wp_enqueue_script($this->plugin_name, 'https://app.fflapi.com/sdk/woo-widget-v2.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name . '_init', plugin_dir_url(__FILE__) . 'js/ffl-api-public.js', array('jquery', $this->plugin_name), $this->version, false);

    }

    function ffl_woo_checkout()
    {
        $aKey = esc_attr(get_option('ffl_api_key_option'));
        $gKey = esc_attr(get_option('ffl_api_gmaps_option'));

        if ($aKey === '' || $gKey === '') {
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

        if ($fireArmCount > 0) {
            add_action(get_option('ffl_init_map_location', 'woocommerce_checkout_order_review'), array($this, 'ffl_init_map'), 10);
        }
    }

    function ffl_init_map()
    {


        $aKey = esc_attr(get_option('ffl_api_key_option'));
        $gKey = esc_attr(get_option('ffl_api_gmaps_option'));
        $wMes = get_option('ffl_checkout_message') != '' ? get_option('ffl_checkout_message') : '<b>Federal law dictates that your online firearms purchase must be delivered to a federally licensed firearms dealer (FFL) before you can take possession.</b> This process is called a Transfer. Enter your zip code, radius, and FFL name (optional), then click the Find button to get a list of FFL dealers in your area. Select the FFL dealer you want the firearm shipped to. <b><u>Before Checking Out, Contact your selected FFL dealer to confirm they are currently accepting transfers</u></b>. You can also confirm transfer costs.';

        $hok = get_option('ffl_init_map_location');
        echo '<div id="ffl_container"></div>';
        echo '
<script type="text/javascript">
    
  let aKey = "' . $aKey . '"
    let gKey = "' . $gKey . '"
    let wMes = `' . $wMes . '`
    let hok = "' . $hok . '"
    
    localStorage.removeItem("selectedFFL");

	if(!document.getElementById("ffl-zip-code")) {
        document.addEventListener("DOMContentLoaded", function() {
		    initFFLJs(aKey,gKey,wMes,hok);
        });
	}
</script>';
    }


}