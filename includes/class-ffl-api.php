<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       garidium.com
 * @since      1.0.0
 *
 * @package    G_ffl_Api
 * @subpackage G_ffl_Api/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    G_ffl_Api
 * @subpackage G_ffl_Api/includes
 * @author     Big G <sales@garidium.com>
 */
class G_Ffl_Api
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      G_ffl_Api_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('G_FFL_API_VERSION')) {
            $this->version = G_FFL_API_VERSION;
        } else {
            $this->version = '1.0.1';
        }
        $this->plugin_name = 'g-ffl-api';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();

        // at wp to allow woocommerce to initialize
        add_action( 'wp', array( $this, 'define_public_hooks' ) );

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Ffl_Api_Loader. Orchestrates the hooks of the plugin.
     * - Ffl_Api_i18n. Defines internationalization functionality.
     * - Ffl_Api_Admin. Defines all hooks for the admin area.
     * - Ffl_Api_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

//        if (get_option('woocommerce_ship_to_destination') != 'shipping') {
//            update_option('woocommerce_ship_to_destination', 'shipping');
//        }

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ffl-api-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ffl-api-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ffl-api-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-ffl-api-public.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ffl-api-customizer.php';

        $this->loader = new Ffl_Api_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Ffl_Api_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new G_ffl_Api_i18n();
        add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new G_ffl_Api_Admin($this->get_plugin_name(), $this->get_version());
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles') );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts') );
        add_action( 'admin_menu', array( $plugin_admin, 'ffl_load_menu' ) );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function define_public_hooks()
    {
        /**
         * Add scripts on the public side, only if there's at least one product 
         * that requires an FFL dealer proxy - or if on a customer account page
         */
        if ( is_account_page() || is_checkout() ) {
            $plugin_public = new G_ffl_Api_Public($this->get_plugin_name(), $this->get_version());
            if ( ! is_checkout() || order_requires_ffl_selector() ) {
                // add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles' ) );
                add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts') );
                add_action( 'woocommerce_before_checkout_form', array( $plugin_public, 'ffl_woo_checkout', ), 10 );
           }
            $plugin_public->ffl_picker_shortcode();
       }
    }


    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Ffl_Api_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

}

// Set - Unset FFL Product

add_filter('bulk_actions-edit-product', 'ffl_bulk_actions');

function ffl_bulk_actions($bulk_array)
{

    $bulk_array['set_ffl'] = 'Set FFL Product';
    $bulk_array['unset_ffl'] = 'Unset FFL Product';
    return $bulk_array;

}

add_filter('handle_bulk_actions-edit-product', 'ffl_bulk_action_handler', 10, 3);

function ffl_bulk_action_handler($redirect, $doaction, $object_ids)
{

    // let's remove query args first
    $redirect = remove_query_arg(array('set_ffl_done', 'unset_ffl_done'), $redirect);

    // do something for "Set FFL Product" bulk action
    if ($doaction == 'set_ffl') {
        foreach ($object_ids as $post_id) {
            update_post_meta($post_id, '_firearm_product', 'yes');
        }

        // do not forget to add query args to URL because we will show notices later
        $redirect = add_query_arg(
            'set_ffl_done', // just a parameter for URL (we will use $_GET['set_ffl_done'] )
            count($object_ids), // parameter value - how much posts have been affected
            $redirect);

    }

    // do something for "Set price to $1000" bulk action
    if ($doaction == 'unset_ffl') {
        foreach ($object_ids as $post_id) {
            update_post_meta($post_id, '_firearm_product', 'no');
        }

        // do not forget to add query args to URL because we will show notices later
        $redirect = add_query_arg(
            'unset_ffl_done', // just a parameter for URL (we will use $_GET['unset_ffl_done'] )
            count($object_ids), // parameter value - how much posts have been affected
            $redirect);

    }

    return $redirect;

}

add_action('admin_notices', 'ffl_update_messages');

function ffl_update_messages()
{

    // first of all we have to make a message,
    // of course it could be just "Posts updated." like this:
    if (!empty($_REQUEST['set_ffl_done'])) {

        // depending on ho much posts were changed, make the message different
        printf('<div id="message" class="updated notice is-dismissible"><p>' .
            _n('%s product set as firearm.',
                '%s products set as firearm.',
                intval($_REQUEST['set_ffl_done'])
            ) . '</p></div>', intval($_REQUEST['set_ffl_done']));

    }

    // create an awesome message
    if (!empty($_REQUEST['unset_ffl_done'])) {

        // depending on ho much posts were changed, make the message different
        printf('<div id="message" class="updated notice is-dismissible"><p>' .
            _n('%s product set as unfirearm.',
                '%s products set as unfirearm.',
                intval($_REQUEST['unset_ffl_done'])
            ) . '</p></div>', intval($_REQUEST['unset_ffl_done']));

    }

}

// DONE //

// Create Sortable Firearm Column
add_filter('manage_edit-product_columns', 'firearm_product_col');

function firearm_product_col($columns)
{
    $new_columns = (is_array($columns)) ? $columns : array();
    $new_columns['FIREARM'] = 'Firearm Status';
    return $new_columns;
}

add_action('manage_product_posts_custom_column', 'firearm_product_col_data', 2);

function firearm_product_col_data($column)
{
    global $post;
    if ($post) {
        $firearm_product_ids = get_post_meta($post->ID, '_firearm_product', true);
        if ($column == 'FIREARM') {
            if (isset($firearm_product_ids) && !empty($firearm_product_ids)) {
                if ($firearm_product_ids === 'yes') echo '<strong>FFL</strong>';
                if ($firearm_product_ids === 'no') echo '<strong></strong>';
            } else {
                echo "Undefined";
            }
        }
    }
}

add_filter("manage_edit-product_sortable_columns", 'firearm_product_col_sort');

function firearm_product_col_sort($columns)
{
    $custom = array(
        'FIREARM' => 'firearmstatus'
    );
    return wp_parse_args($custom, $columns);
}
