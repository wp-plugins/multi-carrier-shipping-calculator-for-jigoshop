<?php
/**
 * Plugin Name: AuctionInc ShippingCalc for Jigoshop
 * Description: Accurate multi-carrier real-time shipping rates from FedEx, USPS, UPS, and DHL. Multiple ship origins, many advanced features. Free two week trial. No carrier accounts required.
 * Plugin URI: http://auctioninc.com/
 * Author: AuctionInc
 * Author URI: http://auctioninc.com
 * Version: 1.0
 **/

define('JIGOSHOP_SHIPPINGCALC_PLUGIN_URL', 'http://www.auctioninc.com/info/page/shippingcalc_for_jigoshop');
define('SHIPPINGCALC_PLUGIN_HELP_PAGE', 'http://www.auctioninc.com/info/page/auctioninc_shipping_settings');

global $auctioninc_shippingcalc;

/**
 * Include Required AuctionInc ShippingCalc API Toolkit files
 */
if ( !class_exists('ShipRateAPI') ) {
    require_once('inc/shiprateapi/ShipRateAPI.inc');
}

/**
 * Localization
 */
load_plugin_textdomain('auctioninc_shippingcalc', false, dirname(plugin_basename(__FILE__)) . '/languages/');

/**
 * Plugin page links
 */
function auctioninc_shippingcalc_plugin_links($links) {

    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=jigoshop_settings&tab=shipping') . '">' . __('Settings', 'auctioninc_shippingcalc') . '</a>',
        '<a href="http://auctioninc.helpserve.com">' . __('Support', 'auctioninc_shippingcalc') . '</a>',
        '<a href="' . JIGOSHOP_SHIPPINGCALC_PLUGIN_URL . '">' . __('Docs', 'auctioninc_shippingcalc') . '</a>',
    );

    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'auctioninc_shippingcalc_plugin_links');

/**
 * auctioninc_shippingcalc_admin_notice function.
 *
 * @access public
 * @return void
*/
function auctioninc_shippingcalc_admin_notice() {
    // Make sure Jigoshop is Activated
    if ( !class_exists( 'jigoshop_shipping_method' ) ) {
        echo '<div class="error">
             <p>' . __('An active installation of Jigoshop is required to use AuctionInc ShippingCalc for shipping rates.', 'auctioninc_shippingcalc') . '</p>
         </div>';
        $auctioninc_shippingcalc = false;
    } else {
        // Make sure API Key has been entered
        $auctioninc_shippingcalc = true;
        $auctioninc_settings = get_option('jigoshop_options');
        
        if (empty($auctioninc_settings['jigoshop_shippingcalc_api_key'])) {
            echo '<div class="error">
                 <p>' . __('An') . ' <a href="' . JIGOSHOP_SHIPPINGCALC_PLUGIN_URL . '" target="_blank">' . __('AuctionInc', 'auctioninc_shippingcalc') . '</a> ' . __('account is required to use the ShippingCalc plugin.  Please enter your AuctionInc Account ID.', 'auctioninc_shippingcalc') . '</p>
             </div>';
        }
    }
}
add_action('admin_notices', 'auctioninc_shippingcalc_admin_notice');

/** 
 * Load AuctionInc ShippingCalc module 
 */
add_action( 'plugins_loaded', 'shippingcalc_check_jigoshop_before_module_load', 0 );

function shippingcalc_check_jigoshop_before_module_load() {
    // Do not want to cause a PHP error if Jigoshop is not Activated
    // and we try to extend the shipping method class
    if( class_exists( 'jigoshop_shipping_method' ) ) {
        // Found Jigoshop class, safe to load module
        auctioninc_shippingcalc_module_load();
    } else {
        // Instead silently do nothing and display admin notice
    }
}

function auctioninc_shippingcalc_module_load() {
    
	function add_auctioninc_shippingcalc( $methods ) {
		$methods[] = 'auctioninc_shippingcalc'; 
		return $methods;
	}
	add_filter('jigoshop_shipping_methods', 'add_auctioninc_shippingcalc' );
		
	class auctioninc_shippingcalc extends jigoshop_shipping_method {
	
	    public function __construct() {
	        parent::__construct();
	        
	        $this->id = 'auctioninc_shippingcalc';
            $this->enabled = 'yes';
            $this->chosen = true;
            $this->availability = 'all';
            $this->countries = '';
            $this->rates = array();
            
            // Pull default configuration settings
            $this->account_id = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_api_key');
            $this->delivery_type = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_dest');
            $this->calc_method = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_method');          
            $this->pack_method = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_packaging');
            $this->insurable = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_ins');
            $this->fallback_type = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_fallback_type');
            $this->fallback_fee = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_fallback_amt');
            $this->debug = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_debug');
            $this->fixed_mode = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_fixed_mode');
            $this->fixed_fee1 = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_fixed_fee1');
            $this->fixed_fee2 = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_fixed_fee2');
            $this->fixed_code = Jigoshop_Base::get_options()->get('jigoshop_shippingcalc_fixed_code');
                                    
	        add_action( 'jigoshop_settings_scripts', array( &$this, 'admin_scripts' ) );    
	    }

	    /**
	     *  Includes admin script for shipping configuration page
	     */
	    public function admin_scripts() {
	    ?>
            <script type="text/javascript">
                /*<![CDATA[*/
            <?php include('js/auctioninc-global-settings.js'); ?>
                /*]]>*/
            </script>
	    <?php
	    }
	    
	    /**
	     * Calculates the shipping rates
	     */
	    public function calculate_shipping() {
	        // Basic user data
	        $current_user = wp_get_current_user();
	        $is_admin = (!empty($current_user->roles) && in_array('administrator', $current_user->roles)) ? true : false;
	        
	        // Basic cart data
	        $total_items = 0; // total number of items in cart (including duplicates)
	        $num_items = sizeof(jigoshop_cart::$cart_contents); // number of unique items in cart
	        $total_weight = jigoshop_cart::$cart_contents_weight;
	        	        
	        $options = Jigoshop_Base::get_options();	        
	        $dim_uom = $options->get( 'jigoshop_dimension_unit' );        
	        $weight_uom = $options->get( 'jigoshop_weight_unit' );       
	        $weight_uom = ($weight_uom == "kg") ? "KGS" : "LBS";
	        $base_currency = $options->get( 'jigoshop_currency' );
	        
	        $destination_country = jigoshop_customer::get_shipping_country();
	        $destination_postcode = jigoshop_customer::get_shipping_postcode();
	        $destination_state = jigoshop_customer::get_shipping_state();
	        
	        if ($this->account_id && $num_items > 0) {
	            // Calculate if shipping fields are set
	            if (!empty($destination_country) && !empty($destination_postcode)) {
	                // Instantiate the Shipping Rate API object
	                $shipAPI = new ShipRateAPI($this->account_id);
	        
	                // SSL currently not supported
	                $shipAPI->setSecureComm(false);
	        
	                // Header reference code
	                $shipAPI->setHeaderRefCode('jigo');
	        
	                // Set base currency
	                $shipAPI->setCurrency($base_currency);
	        
	                // Set the Detail Level (1, 2 or 3) (Default = 1)
	                // DL 1:  minimum required data returned
	                // DL 2:  shipping rate components included
	                // DL 3:  package-level detail included
	                $detailLevel = 3;
	                $shipAPI->setDetailLevel($detailLevel);
	        
	                // Show table of any errors for inspection
	                $showErrors = true;
	        
	                // Set Destination Address for this API call
	                $destCountryCode = $destination_country;
	                $destPostalCode = !empty($destination_postcode) ? $destination_postcode : '';
	                $destStateCode = ($destination_country == 'US' && !empty($destination_state)) ? $destination_state : '';
	        
	                // Specify delivery destination type
	                $delivery_type = $this->delivery_type == 'Residential' ? true : false;
	        
	                $shipAPI->setDestinationAddress($destCountryCode, $destPostalCode, $destStateCode, $delivery_type);
	        
	                // Create an array of items to rate
	                $items = array();
	        
	            	// Loop through cart
    	            foreach (jigoshop_cart::$cart_contents as $item_id => $values) {
    	                $product = $values['data'];
    	                $qty = $values['quantity'];
    	                $total_items += $qty;
    	                
    	                // Only assess shipping for physical items
    	                if ($product->requires_shipping()) {

    	                    // Get AuctionInc shipping fields
    	                    $product_id = $product->id;
    	                    $sku = $product->get_sku();
    	                    
    	                    // Calculation Method
    	                    $calc_method = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_method', true);
    	                    $calc_method = !empty($calc_method) ? $calc_method : $this->calc_method;
    	                     
    	                    // Fixed Fee Mode
    	                    $fixed_mode = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_fixed_mode', true);
    	                    $fixed_mode = !empty($fixed_mode) ? $fixed_mode : $this->fixed_mode;
    	                     
    	                    // Fixed Fee Code
    	                    $fixed_code = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_fixed_code', true);
    	                    $fixed_code = !empty($fixed_code) ? $fixed_code : $this->fixed_code;
    	                     
    	                    // Fixed Fee 1
    	                    $fixed_fee1 = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_fixed_fee1', true);
    	                    $fixed_fee1 = is_numeric($fixed_fee1) ? $fixed_fee1 : $this->fixed_fee1;
    	                     
    	                    // Fixed Fee 2
    	                    $fixed_fee2 = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_fixed_fee2', true);
    	                    $fixed_fee2 = is_numeric($fixed_fee2) ? $fixed_fee2 : $this->fixed_fee2;
    	                     
    	                    // Packaging Method
    	                    $pack_method = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_packaging', true);
    	                    $pack_method = !empty($pack_method) ? $pack_method : $this->pack_method;
    	                     
    	                    // Insurable
    	                    $insurable = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_ins', true);
    	                    $insurable = !empty($insurable) ? $insurable : $this->insurable;
    	                     
    	                    // Origin Code
    	                    $origin_code = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_origin_code', true);
    	                     
    	                    // Supplemental Item Handling Mode
    	                    $supp_handling_mode = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_supp_handling_mode', true);
    	                     
    	                    // Supplemental Item Handling Code
    	                    $supp_handling_code = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_supp_handling_code', true);
    	                     
    	                    // Supplemental Item Handling Fee
    	                    $supp_handling_fee = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_supp_handling_fee', true);
    	                     
    	                    // On-Demand Service Codes
    	                    $ondemand_codes = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_ondemand_codes', true);
    	                     
    	                    // Special Accessorial Fees
    	                    $access_fees = get_post_meta($product_id, 'jigoshop_shippingcalc_prod_access_fees', true);
    	                     
    	                    $item = array();
    	                    $item["refCode"] = $product->get_title() . '-' . $sku;
    	                    $item["CalcMethod"] = $calc_method;
    	                    $item["quantity"] = $qty;
    	                    
    	                    if ($calc_method === 'C' || $calc_method === 'CI') {
    	                        $item["packMethod"] = $pack_method;
    	                    }
    	                     
    	                    // Fixed Rate Shipping
    	                    if ($calc_method === 'F') {
    	                        if (!empty($fixed_mode)) {
    	                            if ($fixed_mode === 'C' && !empty($fixed_code)) {
    	                                $item["FeeType"] = "C";
    	                                $item["fixedFeeCode"] = $fixed_code;
    	                            } elseif ($fixed_mode === 'F' && is_numeric($fixed_fee1) && (is_numeric($fixed_fee2) || empty($fixed_fee2))) {
    	                                $item["FeeType"] = "F";
    	                                $item["fixedAmt_1"] = $fixed_fee1;
                                        if(empty($fixed_fee2)) $fixed_fee2 = 0;
    	                                $item["fixedAmt_2"] = $fixed_fee2;
    	                            }
    	                        }
    	                    }
    	                     
    	                    // Insurable
    	                    if ($insurable === "yes") {
    	                        $item["value"] = $product->get_price();
    	                    }
    	                    else {
    	                        $item["value"] = 0;
    	                    }
    	                     
    	                    // Origin Code
    	                    if (!empty($origin_code)) {
    	                        $item["originCode"] = $origin_code;
    	                    }
    	                    
    	                    if ($calc_method === 'C' || $calc_method === 'CI') {
    	                        // Weight
    	                        $item["weight"] = $product->get_weight();
    	                        $item["weightUOM"] = $weight_uom;
    	                         
    	                        // Dimensions
    	                        if ($product->has_dimensions()) {
    	                            $item["length"] = $product->get_length();
    	                            $item["height"] = $product->get_height();
    	                            $item["width"] = $product->get_width();
    	                            $item["dimUOM"] = $dim_uom;
    	                        }
    	                    }       	                    
    	                    
    	                    // Supplemental Item Handling
    	                    if (!empty($supp_handling_mode)) {
    	                        if ($supp_handling_mode === 'C' && !empty($supp_handling_code)) {
    	                            // Supplemental Item Handling Code
    	                            $item["suppHandlingCode"] = $supp_handling_code;
    	                        } elseif ($supp_handling_mode === 'F' && !empty($supp_handling_fee)) {
    	                            // Supplemental Item Handling Fee
    	                            $item["suppHandlingFee"] = $supp_handling_fee;
    	                        }
    	                    }
    	                     
    	                    // On-Demand Service Codes
    	                    if (!empty($ondemand_codes)) {
    	                        $codes_str = implode(", ", $ondemand_codes);
    	                        $item["odServices"] = $codes_str;
    	                    }
    	                     
    	                    // Special Accessorial Fees
    	                    if (!empty($access_fees)) {
    	                        $codes_str = implode(", ", $access_fees);
    	                        $item["specCarrierSvcs"] = $codes_str;
    	                    }
    	                     
    	                    // Add this item to Item Array
    	                    $items[] = $item;     	                    
    	                      	         
    	                } // end product
    	                
    	            } // end product loop
        	            
    	            // Debug output
    	            if ($this->debug == "yes" && $is_admin === true) {
    	                echo 'DEBUG ITEM DATA<br>';
    	                echo '<pre>' . print_r($items, true) . '</pre>';
    	                echo 'END DEBUG ITEM DATA<br>';
    	            }

    	            // Add Item Data from Item Array to API Object
    	            foreach ($items AS $val) {
    	                 
    	                if ($val["CalcMethod"] == "C" || $val["CalcMethod"] == "CI") {
    	                    $shipAPI->addItemCalc($val["refCode"], $val["quantity"], $val["weight"], $val['weightUOM'], $val["length"], $val["width"], $val["height"], $val["dimUOM"], $val["value"], $val["packMethod"], 1, $val["CalcMethod"]);
    	                     
    	                    if (isset($val["originCode"])) {
    	                        $shipAPI->addItemOriginCode($val["originCode"]);
    	                    }
    	                    if (isset($val["odServices"])) {
    	                        $shipAPI->addItemOnDemandServices($val["odServices"]);
    	                    }
    	                    if (isset($val["suppHandlingCode"])) {
    	                        $shipAPI->addItemSuppHandlingCode($val["suppHandlingCode"]);
    	                    }
    	                    if (isset($val["suppHandlingFee"])) {
    	                        $shipAPI->addItemHandlingFee($val["suppHandlingFee"]);
    	                    }
    	                    if (isset($val["specCarrierSvcs"])) {
    	                        $shipAPI->addItemSpecialCarrierServices($val["specCarrierSvcs"]);
    	                    }
    	                } elseif ($val["CalcMethod"] == "F") {
    	                    $shipAPI->addItemFixed($val["refCode"], $val["quantity"], $val["FeeType"], $val["fixedAmt_1"], $val["fixedAmt_2"], $val["fixedFeeCode"]);
    	                } elseif ($val["CalcMethod"] == "N") {
    	                    $shipAPI->addItemFree($val["refCode"], $val["quantity"]);
    	                }
    	            }
    	             
    	            // Unique identifier for cart items & destiniation
    	            $request_identifier = serialize($items) . $destCountryCode . $destPostalCode;
    	             
    	            // Check for cached response
    	            // IMPORTANT: https://core.trac.wordpress.org/ticket/15058 - keep transient max length to 45 chars
    	            $transient = 'auctioninc_' . md5($request_identifier);
    	            $cached_response = get_transient($transient);
    	             
    	            $shipRates = array();
    	             
    	            if ($cached_response !== false) {
    	                // Cached response
    	                $shipRates = unserialize($cached_response);
    	            } else {
    	                // New API call
    	                $ok = $shipAPI->GetItemShipRateSS($shipRates);
    	                if ($ok) {
    	                    // set transients to expire after 15 minutes
    	                    set_transient($transient, serialize($shipRates), 60*15);
    	                }
    	            }
    	             
    	            if (!empty($shipRates['ShipRate'])) {
    	                 
    	                // Store response in the current user's session
    	                // Used to retrieve package level details later
    	                $_SESSION['auctioninc_response'] = $shipRates;
    	                 
    	                // Debug output
    	                if ($this->debug == "yes" && $is_admin === true) {
    	                    echo 'DEBUG API RESPONSE: SHIP RATES<br>';
    	                    echo '<pre>' . print_r($shipRates, true) . '</pre>';
    	                    echo 'END DEBUG API RESPONSE: SHIP RATES<br>';
    	                }
    	                 
    	                foreach ($shipRates['ShipRate'] as $shipRate) {    	                     
    	                    // Add Rate
    	                    $this->rates[] = array('service' => $shipRate['ServiceName'], 'price' => $shipRate['Rate'], 'tax' => 0);
    	                }
    	            } else {
    	                 
    	                if ($this->debug == "yes" && $is_admin === true) {
    	                    echo 'DEBUG API RESPONSE: SHIP RATES<br>';
    	                    echo '<pre>' . print_r($shipRates, true) . '</pre>';
    	                    echo 'END DEBUG API RESPONSE: SHIP RATES<br>';
    	                }
    	                 
    	                $use_fallback = false;
    	                 
    	                if (empty($shipRates['ErrorList'])) {
    	                    $use_fallback = true;
    	                } else {
                            var_dump($shipRates);die();
    	                    foreach ($shipRates['ErrorList'] as $error) {
    	                        // Check for proper error code
    	                        if ($error['Message'] == 'Packaging Engine unable to determine any services to be rated') {
    	                            $use_fallback = true;
    	                            break;
    	                        }
    	                    }
    	                }
    	                 
    	                // Add fallback shipping rates, if applicable
    	                if (!empty($this->fallback_type) && !empty($this->fallback_fee) && $use_fallback == true) {                  
    	                    $cost = $this->fallback_type === 'O' ? $this->fallback_fee : $total_items * $this->fallback_fee;
    	                    $this->rates[] = array('service' => "Shipping", 'price' => $cost, 'tax' => 0);
    	                } else {
    	                    $str = __('There do not seem to be any available shipping rates. Please double check your address, or contact us if you need any help.', 'auctioninc_shippingcalc');
    	                    $this->set_error_message($str);
    	                }
    	            }
    	            
    	        } // end check required shipping fields set
    	        else {
                    $str = __('There do not seem to be any available shipping rates. Please double check your address, or contact us if you need any help.', 'auctioninc_shippingcalc');
                    $this->set_error_message($str);                   
                }
	        } else {
	            //$str = __('Please enter your AuctionInc account ID in order to calculate transactions.', 'auctioninc_shippingcalc');
	            //$this->set_error_message($str);
	        } // end check api key and non-empty cart
	        
	    } // end calculate_shipping
	
	    /**
	     * Default Option settings for WordPress Settings API using an implementation of the Jigoshop_Options_Interface
	     * These should be installed on the Jigoshop_Options 'Shipping' tab
	     *
	     * @since 1.3
	     */
	    protected function get_default_options() {
            $defaults = array();
    
    		// Define the Section name for the Jigoshop_Options
    		$defaults[] = array( 'name' => __('AuctionInc ShippingCalc', 'jigoshop'), 'type' => 'title', 'desc' => __('These settings will apply globally to those products for which you haven\'t configured specific AuctionInc values.', 'jigoshop') );
    
    		// List each option in order of appearance with details
    		$defaults[] = array(
    		        'name' => __('API Key', 'jigoshop'),
    		        'desc' => '',
    		        'type' => 'text',
    		        'tip' => __('Please enter your account ID that you received when you registered at the AuctionInc site.', 'jigoshop'),
    		        'id' => 'jigoshop_shippingcalc_api_key',
    		        'std' => ''
    		);
    		$defaults[] = array(
    		    'name' => __('Delivery Destination Type', 'jigoshop'),
    		    'desc' => '',
    		    'tip' => __('Set rates to apply to either residential or commercial destination addresses.', 'jigoshop'),
    		    'id' => 'jigoshop_shippingcalc_dest',
    		    'std' => 'Residential',
    		    'type' => 'select',
    		    'choices' => array(
    		        'Residential' => __('Residential', 'jigoshop'),
    		        'Commercial' => __('Commercial', 'jigoshop')
    		    )
    		);
    		$defaults[] = array(
    		    'name' => __('Calculation Method', 'jigoshop'),
    		    'desc' => '',
    		    'tip' => __('For carrier rates, your configured product weights &amp; dimensions will be used.', 'jigoshop'),
    		    'id' => 'jigoshop_shippingcalc_method',
    		    'std' => 'C',
    		    'type' => 'select',
    		    'choices' => array(
    		        'C' => __('Carrier Rates', 'jigoshop'),
    		        'F' => __('Fixed Fee', 'jigoshop'),
    		        'N' => __('Free', 'jigoshop'),
                    'CI' => __('Free Domestic', 'jigoshop')
    		    )
    		);
    		$defaults[] = array(
    		    'name' => __('Package Items', 'jigoshop'),
    		    'desc' => '',
    		    'tip' => __('Select to pack items from the same origin into the same box or each in its own box.', 'jigoshop'),
    		    'id' => 'jigoshop_shippingcalc_packaging',
    		    'std' => 'T',
    		    'type' => 'select',
    		    'choices' => array(
    		        'T' => __('Together', 'jigoshop'),
    		        'S' => __('Separately', 'jigoshop')
    		    )
    		);
    		$defaults[] = array(
    		    'name'		=> __('Enable Insurance','jigoshop'),
    		    'desc' 		=> '',
    		    'tip' => __('If enabled your items will utilize your AuctionInc insurance settings.', 'jigoshop'),
    		    'id' 		=> 'jigoshop_shippingcalc_ins',
    		    'std' 		=> 'no',
    		    'type' 		=> 'checkbox',
    		    'choices'	=> array(
    		        'no'			=> __('No', 'jigoshop'),
    		        'yes'			=> __('Yes', 'jigoshop')
    		    )
    		);
    		$defaults[] = array(
    		    'name' => __('Fallback Rate Type', 'jigoshop'),
    		    'desc' => '',
    		    'tip' => '',
    		    'id' => 'jigoshop_shippingcalc_fallback_type',
    		    'std' => 'N',
    		    'type' => 'select',
    		    'choices' => array(
    		        'N' => __('None', 'jigoshop'),
    		        'I' => __('Per Item', 'jigoshop'),
    		        'O' => __('Per Order', 'jigoshop')
    		    )
    		);
    		$defaults[] = array(
    		    'name' => __('Fallback Rate Amount', 'jigoshop'),
    		    'desc' => '',
    		    'type' => 'text',
    		    'tip' => __('Default rate if the API cannot be reached or if no rates are found.', 'jigoshop'),
    		    'id' => 'jigoshop_shippingcalc_fallback_amt',
    		    'std' => ''
    		);
    		$defaults[] = array(
    		    'name'		=> __('Enable Debug Mode','jigoshop'),
    		    'desc' 		=> '',
    		    'tip' => __('Enable debug mode to show debugging data for ship rates in your cart. Only you, not your customers, can view this debug data.', 'jigoshop'),
    		    'id' 		=> 'jigoshop_shippingcalc_debug',
    		    'std' 		=> 'no',
    		    'type' 		=> 'checkbox',
    		    'choices'	=> array(
    		        'no'			=> __('No', 'jigoshop'),
    		        'yes'			=> __('Yes', 'jigoshop')
    		    )
    		);
    		$defaults[] = array(
    		    'name' => __('Fixed Fee Mode', 'jigoshop'),
    		    'desc' => '',
    		    'tip' => '',
    		    'id' => 'jigoshop_shippingcalc_fixed_mode',
    		    'std' => 'F',
    		    'type' => 'select',
    		    'choices' => array(
    		        'F' => __('Fee', 'jigoshop'),
    		        'C' => __('Code', 'jigoshop')
    		    )
    		);    		
    		$defaults[] = array(
    		    'name' => __('Fixed Fee 1', 'jigoshop'),
    		    'desc' => '',
    		    'type' => 'text',
    		    'tip' => __('Enter fee for first item.  Products with their own AuctionInc configured values will override this setting.', 'jigoshop'),
    		    'id' => 'jigoshop_shippingcalc_fixed_fee1',
    		    'std' => ''
    		);
    		$defaults[] = array(
    		    'name' => __('Fixed Fee 2', 'jigoshop'),
    		    'desc' => '',
    		    'type' => 'text',
    		    'tip' => __('Enter fee for additional items and quantities.  Products with their own AuctionInc configured values will override this setting.', 'jigoshop'),
    		    'id' => 'jigoshop_shippingcalc_fixed_fee2',
    		    'std' => ''
    		);
    		$defaults[] = array(
    		    'name' => __('Fixed Fee Code', 'jigoshop'),
    		    'desc' => '',
    		    'type' => 'text',
    		    'tip' => __('Enter your AuctionInc-configured fixed fee code.', 'jigoshop'),
    		    'id' => 'jigoshop_shippingcalc_fixed_code',
    		    'std' => ''
    		);
    		    		    		    		    		
            return $defaults;
	    }
	    
	} // end auctioninc_shippingcalc
	
}

/**
 * Include product settings on product pages
 */
require_once('meta/auctioninc-product-settings.php');

/**
 * Include packaging details on completed order pages
*/
require_once('meta/auctioninc-order-details.php');

/**
 * auctioninc_shippingcalc_jigoshop_scripts function.
 * Includes admin script for product pages
 *
 * @access public
 * @return void
*/
function auctioninc_shippingcalc_jigoshop_scripts() {
    $screen = get_current_screen();

    if ($screen->base == 'post' && $screen->post_type == 'product') {
        wp_enqueue_script('admin-auctioninc-product', plugins_url('js/auctioninc-product-settings.js', __FILE__), array('jquery'), null, true);
    }
}
add_action('admin_enqueue_scripts', 'auctioninc_shippingcalc_jigoshop_scripts');
