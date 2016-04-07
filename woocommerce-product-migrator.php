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
	private $key;
	private $secret;
    private $local_key;
    private $local_secret;
	private $url;



	private  $client;

	/*
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

     $this->get_options();
		$this->Settings = new WCPMOptions($args);
		add_action('admin_menu', array($this->Settings, 'admin'));
		//Hook up to the init action
		add_action( 'init', array( $this, 'init_woocommerce_product_migrator' ) );
	}

    function get_options(){
        $this->key = get_option($this->prefix.'_wc_key');
        $this->secret = get_option($this->prefix.'_wc_secret');
        $this->url = get_option($this->prefix.'_wc_site_url');
        $this->local_key = get_option($this->prefix.'_local_wc_key');
        $this->local_secret = get_option($this->prefix.'_local_wc_secret');

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
		add_action ('wp_ajax_get_products_count', array($this, 'get_products_count'));
		add_action ('wp_ajax_get_products', array($this, 'get_products'));
	}

    function get_client(){
        return new WC_API_Client($this->url, $this->key, $this->secret, array(

            'ssl_verify'=>false,
            'validate_url'=>true
        ));

    }

	function set_client(){
		if($this->key && $this->url && $this->secret){
			$this->client = new WC_API_Client($this->url, $this->key, $this->secret, array(

				'ssl_verify'=>false,
				'validate_url'=>true
			));

		}
	}

	function action_callback_method_name() {
		// TODO define your action method here
	}

	function filter_callback_method_name() {
		// TODO define your filter method here
	}

	function get_products_page($page=1){

        $client = $this->get_client();

		return $client->products->get(null, array('page'=>$page));
	}

	function get_products_count(){

	$client = $this->get_client();

		echo json_encode( $client->products->get_count());
		wp_die();
	}

	function get_products(){



			$page = filter_input(INPUT_POST, 'page');

			$products = $this->get_products_page($page);

            $this->process_products($products);


			echo json_encode($products);

		wp_die();
	}

    function get_products_by_sku($sku){
        $posts = get_posts(
            array(
                'post_type'=>array('product', 'product_variation'),
                'meta_key'=>'_sku',
                'meta_value'=>$sku,
                'post_status'=>'any'
            )
        );
        if(count($posts)>0){
            return $posts;
        }else{
            return null;
        }
    }

    function update_variation_skus(&$product){
        foreach($product->variations as $ix=>$variation){
            if($variation->sku != '' && $product->sku != '' ){
                $product->variations[$ix]->sku = $product->sku .'_'.$variation->sku;
            }
        }
    }

    function update_variation_sku($variation){
        $variation_sku = get_post_meta($variation->ID, '_sku', true);
        $product_sku = get_post_meta($variation->post_parent, '_sku', true);
		if($product_sku !='')
        update_post_meta($variation->id, '_sku', $product_sku . '_' .$variation_sku);
    }

	function update_skus(&$product, $find_product)
	{
		$id = null;


		if ($product->sku != "") {

			if ($find_product != null) {

				$id = $find_product->ID;
			}

			$prods = $this->get_products_by_sku($product->sku);
			$prodcount = $this->get_dup_count($prods, $id);
error_log($prodcount . ' dupes fround for ' . $product->title);
			if ($prodcount > 0)
				$this->increment_product_sku($product, $id);
		}

		if ($product->type == 'variable') {
			foreach ($product->variations as $ix => $variation) {
				if ($variation->sku != null && $variation->sku != '') {
					$prods = $this->get_products_by_sku($variation->sku);

					$prodcount = $this->get_dup_count($prods, $id);

					if ($prodcount > 0) {
						$this->increment_variation_sku($product, $ix, $id);
					}
				}

			}
		}

	}


	function increment_variation_sku(&$product, $var_num=0, $id=null, $number =null){
		$sku = $product->variations[$var_num]->sku;
		error_log('incrementing variation sku: '. $sku);
		if($number ==null) $number =1;

		$product->variations[$var_num]->sku = $this->get_unique_sku($sku, $number);
}
	function get_dup_count($prods, $id){
		if(is_array($prods)){
			$prodcount = count($prods);
			foreach($prods as $prod){
				if($prod->ID == $id || $prod->post_parent == $id){
					$prodcount--;
				}
			}
			return $prodcount;
		}else{
			error_log('no prods array');
			return 0;
		}

	}

	function get_unique_sku($sku, $number=null, $id=null){
		$prods = $this->get_products_by_sku($sku.'_PW'. $number);

			$prodcount = $this->get_dup_count($prods, $id);

		if($prodcount>0){
			$number++;
			$this->get_unique_sku($sku, $number, $id);
		}else{
			return $sku  .'_PW'.$number;
		}
	}

	function increment_product_sku( &$product, $id=null, $number=null){

		$sku = $product->sku;
			error_log('incrementing product sku: '. $sku);
		if($number == null) $number = 1;

		$product->sku= $this->get_unique_sku($sku, $number);

	}

    function process_products(&$products){

        foreach($products->products as $ix=> $product){

         
            $find_product = get_page_by_title(trim($product->title), OBJECT, 'product');

            $product->slug = substr($product->permalink, strpos($product->permalink, '/shop/')+6);

            unset($product->permalink);

			$this->update_skus($product, $find_product);


            if($find_product != null){

				$products->products[$ix]->found = true;
				$products->products[$ix]->foundid = $find_product->ID;
                if($product->updated_at > $find_product->post_modified){
                    $products->products[$ix]->updating = true;
                    $this->update_product($find_product, $product);
                }else{
                    $products->products[$ix]->updating = false;
                    error_log('Product is up to date!');
                }
            }else{
                if($product->type =='variable'){
                    $this->update_variation_skus($product);
                }

				$products->products[$ix]->found = false;
              $result =  $this->create_product($product);
                error_log(print_r($result, true));

                if($product->type =='variable'){

                    foreach($product->variations as $variation){



                    }
                }
            }


        }


    }

    function create_product($product){
        error_log('adding product ' . $product->title);

        unset($product->id);
        if($product->type == 'variable'){
            foreach($product->variations as $ix=>$variation){
                unset($product->variations[$ix]->id);
            }
        }
        $localclient = $this->get_local_client();

       $this->remove_placeholder_image($product);
        try{
            return   $localclient->products->create(array('product'=>$product));
        }catch(WC_API_Client_HTTP_Exception $ex){
            error_log($ex);

            error_log(print_r($product, true));

        }




    }

    function remove_placeholder_image(&$product){
        $images = $product->images;
        foreach($images as $ix=> $image){
            if(strpos($image->src, '/wp-content/plugins/woocommerce/assets/images/placeholder.png')!==false){
                unset($product->images[$ix]);
            }
        }

    }

    function update_product($old, $new){
        error_log('updating product ' . $new->title);

        $this->remove_placeholder_image($new);
        $localclient = $this->get_local_client();
        try{
            $result =   $localclient->products->update($old->ID, array('product'=>$new));

        }catch(WC_API_Client_HTTP_Exception $ex){
            error_log($ex);
            error_log(print_r($new, true));
        }



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

	}

    private function get_local_client()
    {



        return new WC_API_Client(get_site_url(), $this->local_key,
        	$this->local_secret, array(
            'debug' => true,
            'ssl_verify' => false,
            'validate_url' => true
        ));
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
			))->add('textbox', array(
                'title' => 'Local WC API Key',
                'name'  => $this->prefix.'_local_wc_key',
                'help'  => 'API Key'
            ))->add('textbox', array(
                'title' => 'Local WC API Secret',
                'name'  => $this->prefix.'_local_wc_secret',
                'help'  => 'API Key'
            ))
			->add('_', array($this, 'my_custom_html'))

			->done();
	}

	public function my_custom_html(){

		$key = get_option($this->prefix.'_wc_key');
		$secret = get_option($this->prefix.'_wc_secret');
		$site_url = get_option($this->prefix.'_wc_site_url');
		if($key && $secret && $site_url){

		echo '<div id="productsResults"><div class="status"></div><span class="pageof"></span><ul></ul><span class="pageof"></span></div>';
            echo '<div id="buttonRow"><a href="#" class="start button button-primary">Start</a> <a href="#" class="stop button button-secondary">Stop</a></div>';
		}

	}
}
endif;


new WooCommerceProductMigrator();