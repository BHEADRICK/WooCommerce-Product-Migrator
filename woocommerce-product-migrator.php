<?php
/*
Plugin Name: WooCommerce Product Migrator
Plugin URI:
Description:
Version: 1.0.0
Author: Author Name
Author URI: https://authorurl.com
 License: GNU General Public License v3.0
 License URI: http://www.gnu.org/licenses/gpl-3.0.html

*/


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once 'boots/api.php';
require __DIR__ . '/vendor/autoload.php';
class WooCommerceProductMigrator {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'WooCommerce Product Migrator';
	const slug = 'woocommerce-product-migrator';
	const version = '1.0';
	private $prefix;
	private $Settings;

	/**
	 * Constructor
	 */
	function __construct() {
		//register an activation hook for the plugin
		$this->prefix = str_replace('-', '_', self::slug);
		register_activation_hook( __FILE__, array( $this, 'install_woocommerce_product_migrator' ) );
		$args = array(
		'ABSPATH' => __FILE__,
    'APP_ID' => $this->prefix,
    'APP_NICK' => self::name,
    'APP_VERSION' => self::version,
    'APP_MODE' => 'dev');

		$this->Settings = new WCPMOptions($args);
		add_action('admin_menu', array($this->Settings, 'admin'));
		//Hook up to the init action
		add_action( 'init', array( $this, 'init_woocommerce_product_migrator' ) );
	}

	/**
	 * Runs when the plugin is activated
	 */
	function install_woocommerce_product_migrator() {
		// do not generate any output here
	}

	/**
	 * Runs when the plugin is initialized
	 */
	function init_woocommerce_product_migrator() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();

		// Register the shortcode [my_shortcode]
		add_shortcode( 'my_shortcode', array( $this, 'render_shortcode' ) );

		if ( is_admin() ) {
			//this will run when in the WordPress admin
		} else {
			//this will run when on the frontend
		}

		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'your_action_here', array( $this, 'action_callback_method_name' ) );
		add_filter( 'your_filter_here', array( $this, 'filter_callback_method_name' ) );
	}

	function action_callback_method_name() {
		// TODO define your action method here
	}

	function filter_callback_method_name() {
		// TODO define your filter method here
	}

	function get_products(){
		$key = get_option($this->prefix.'_wc_key');
		$secret = get_option($this->prefix.'_wc_secret');
		$site_url = get_option($this->prefix.'_wc_site_url');
		$options = array(
			'debug'=>true,
			'ssl_verify'=>false,
			'validate_url'=>true
		);
		if($key && $secret && $site_url) {

			$client = new WC_API_Client($site_url, $key, $secret, $options);

			$products = $client->products->get();

			echo json_enconde($products);
		}
		wp_die();
	}
	function render_shortcode($atts) {
		// Extract the attributes
		extract(shortcode_atts(array(
			'attr1' => 'foo', //foo is a default value
			'attr2' => 'bar'
			), $atts));
		// you can now access the attribute values using $attr1 and $attr2
	}

	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {
			$this->load_file( self::slug . '-admin-script', '/js/admin.js', true );
			$this->load_file( self::slug . '-admin-style', '/css/admin.css' );
		} else {
			$this->load_file( self::slug . '-script', '/js/script.js', true );
			$this->load_file( self::slug . '-style', '/css/style.css' );
		} // end if/else
	} // end register_scripts_and_styles

	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {

				wp_enqueue_script($name, $url, array('jquery'), false, true ); //depends on jquery
			} else {

				wp_enqueue_style( $name, $url );
			} // end if
		} // end if

	} // end load_file

} // end class


if(!class_exists('WCPMOptions')) :

class WCPMOptions
{
	private $Boots;
	private $Settings;
	private $prefix;

	public function __construct($Args)
	{
		$this->Boots = new Boots('plugin', $Args);
		$this->Settings = $Args;
		$this->prefix = $Args['APP_ID'];
		$this->Boots->Admin;
	}

	public function admin()
	{


		$this->Boots->Admin
			->menu('mop_admin')
			->add('textbox', array(
				'title' => 'WC API Key',
				'name'  => $this->prefix.'_wc_key',
				'help'  => 'API Key'
			))->add('textbox', array(
				'title' => 'WC API Secret',
				'name'  => $this->prefix.'_wc_secret',
				'help'  => 'API SECRET'
			))->add('textbox', array(
				'title' => 'WC Remote site url',
				'name'  => $this->prefix.'_wc_site_url',
				'help'  => 'http://my-example-site.com'
			))
			->add('_', array($this, 'my_custom_html'))

			->done();
	}

	public function my_custom_html(){
		echo 'hello world';
		$key = get_option($this->prefix.'_wc_key');
		$secret = get_option($this->prefix.'_wc_secret');
		$site_url = get_option($this->prefix.'_wc_site_url');
		if($key && $secret && $site_url){

		echo 'hello world';
		}

	}
}
endif;


new WooCommerceProductMigrator();