<?php

/**
 * Adds a box to the main column on the Product edit screens.
 */
function auctioninc_shippingcalc_add_product_meta_box() {

	$screens = array( 'product' );

	foreach ( $screens as $screen ) {

		add_meta_box(
		'auctioninc_shippingcalc_product',
		__( 'ShippingCalc Product Settings', 'auctioninc_shippingcalc' ),
		'auctioninc_shippingcalc_product_meta_box_callback',
		$screen
		);
	}
}
add_action( 'add_meta_boxes', 'auctioninc_shippingcalc_add_product_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
*/
function auctioninc_shippingcalc_product_meta_box_callback( $post ) {

	// Default values
	$auctioninc_settings = get_option('jigoshop_options');
	
	$calc_method = get_post_meta($post->ID, 'jigoshop_shippingcalc_prod_method', true);
	$calc_method = !empty($calc_method) ? $calc_method : $auctioninc_settings['jigoshop_shippingcalc_method'];
	
	$package = get_post_meta($post->ID, 'jigoshop_shippingcalc_prod_packaging', true);
	$package = !empty($package) ? $package : $auctioninc_settings['jigoshop_shippingcalc_packaging'];
	$package = !empty($package) ? $package : 'T';
	
	$insurable = get_post_meta($post->ID, 'jigoshop_shippingcalc_prod_ins', true);
	$insurable = !empty($insurable) ? $insurable : $auctioninc_settings['jigoshop_shippingcalc_ins'];
	
	$fixed_mode = get_post_meta($post->ID, 'jigoshop_shippingcalc_prod_fixed_mode', true);
	$fixed_mode = !empty($fixed_mode) ? $fixed_mode : $auctioninc_settings['jigoshop_shippingcalc_fixed_mode'];
	
	$fixed_code = get_post_meta($post->ID, 'jigoshop_shippingcalc_prod_fixed_code', true);
	$fixed_code = !empty($fixed_code) ? $fixed_code : $auctioninc_settings['jigoshop_shippingcalc_fixed_code'];
	
	$fixed_fee1 = get_post_meta($post->ID, 'jigoshop_shippingcalc_prod_fixed_fee1', true);
	$fixed_fee1 = is_numeric($fixed_fee1) ? $fixed_fee1 : $auctioninc_settings['jigoshop_shippingcalc_fixed_fee1'];
	
	$fixed_fee2 = get_post_meta($post->ID, 'jigoshop_shippingcalc_prod_fixed_fee2', true);
	$fixed_fee2 = is_numeric($fixed_fee2) ? $fixed_fee2 : $auctioninc_settings['jigoshop_shippingcalc_fixed_fee2'];

	echo '<a href="' . SHIPPINGCALC_PLUGIN_HELP_PAGE . '" target="_blank">' . __('Guide to AuctionInc Shipping Settings', 'auctioninc_shippingcalc') . '</a>';
	
	// Add an nonce field so we can check for it later.
	wp_nonce_field('auctioninc_shippingcalc_product', 'auctioninc_shippingcalc_product_meta_box_nonce');
	
	echo '<table class="form-table meta_box">';
	
	// Calculation Methods
	$calc_methods = array(
			'' => __('-- Select -- ', 'auctioninc_shippingcalc'),
			'C' => __('Carrier Rates', 'auctioninc_shippingcalc'),
			'F' => __('Fixed Fee', 'auctioninc_shippingcalc'),
			'N' => __('Free', 'auctioninc_shippingcalc'),
			'CI' => __('Free Domestic', 'auctioninc_shippingcalc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_method">' . __('Calculation Method', 'auctioninc_shippingcalc') . '</label';
	echo '</th>';
	echo '<td>';
	echo '<select name="jigoshop_shippingcalc_prod_method" id="jigoshop_shippingcalc_prod_method">';
	
	foreach ($calc_methods as $k => $v) {
		$selected = $calc_method == $k ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Select base calculation method. Please consult the AuctionInc Help Guide for more information.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Fixed Mode
	$fixed_modes = array(
			'' => __('-- Select -- ', 'auctioninc_shippingcalc'),
			'C' => __('Code', 'auctioninc_shippingcalc'),
			'F' => __('Fee', 'auctioninc_shippingcalc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_fixed_mode">' . __('Fixed Mode', 'auctioninc_shippingcalc') . '</label';
	echo '</th>';
	echo '<td>';
	echo '<select name="jigoshop_shippingcalc_prod_fixed_mode" id="jigoshop_shippingcalc_prod_fixed_mode">';
	
	foreach ($fixed_modes as $k => $v) {
		$selected = $fixed_mode == $k ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '</td>';
	echo '</tr>';
	
	// Fixed Fee Code
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_fixed_code">' . __('Fixed Fee Code', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="jigoshop_shippingcalc_prod_fixed_code" id="jigoshop_shippingcalc_prod_fixed_code" value="' . esc_attr($fixed_code) . '">';
	echo '<p class="description">' . __('Enter your AuctionInc-configured fixed fee code.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Fixed Fee 1
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_fixed_fee1">' . __('Fixed Fee 1', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="jigoshop_shippingcalc_prod_fixed_fee1" id="jigoshop_shippingcalc_prod_fixed_fee1" value="' . esc_attr($fixed_fee1) . '" placeholder="0.00">';
	echo '<p class="description">' . __('Enter fee for first item.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Fixed Fee 2
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_fixed_fee2">' . __('Fixed Fee 2', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="jigoshop_shippingcalc_prod_fixed_fee2" id="jigoshop_shippingcalc_prod_fixed_fee2" value="' . esc_attr($fixed_fee2) . '" placeholder="0.00">';
	echo '<p class="description">' . __('Enter fee for additional items and quantities.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Package
	$pack_methods = array(
			'' => __('-- Select -- ', 'auctioninc_shippingcalc'),
			'T' => __('Together', 'auctioninc_shippingcalc'),
			'S' => __('Separately', 'auctioninc_shippingcalc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_packaging">' . __('Package', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<select name="jigoshop_shippingcalc_prod_packaging" id="jigoshop_shippingcalc_prod_packaging">';
	
	foreach ($pack_methods as $k => $v) {
		$selected = $package == $k ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Select "Together" for items that can be packed in the same box with other items from the same origin.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Insurable
	$checked = $insurable == 'yes' ? 'checked' : '';
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_ins">' . __('Insurable', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="checkbox" name="jigoshop_shippingcalc_prod_ins" id="jigoshop_shippingcalc_prod_ins" value="yes" ' . $checked . '>';
	echo __('Enable Insurance');
	echo '<p class="description">' . __('Include product value for insurance calculation based on AuctionInc settings.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Origin Code
	$origin_code = get_post_meta(get_the_ID(), 'jigoshop_shippingcalc_prod_origin_code', true);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_origin_code">' . __('Origin Code', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="jigoshop_shippingcalc_prod_origin_code" id="jigoshop_shippingcalc_prod_origin_code" value="' . esc_attr($origin_code) . '">';
	echo '<p class="description">' . __('If item is not shipped from your default AuctionInc location, enter your AuctionInc origin code here.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Supplemental Item Handling Mode
	$supp_handling_mode = get_post_meta(get_the_ID(), 'jigoshop_shippingcalc_prod_supp_handling_mode', true);
	
	$supp_handling_modes = array(
			'' => __('-- Select -- ', 'auctioninc_shippingcalc'),
			'C' => __('Code', 'auctioninc_shippingcalc'),
			'F' => __('Fee', 'auctioninc_shippingcalc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_supp_handling_mode">' . __('Supplemental Item Handling Mode', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<select name="jigoshop_shippingcalc_prod_supp_handling_mode" id="jigoshop_shippingcalc_prod_supp_handling_mode">';
	
	foreach ($supp_handling_modes as $k => $v) {
		$selected = $supp_handling_mode == $k ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Supplements your AuctionInc-configured package and order handling for this item.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Supplemental Handling Code
	$supp_handling_code = get_post_meta(get_the_ID(), 'jigoshop_shippingcalc_prod_supp_handling_code', true);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_supp_handling_code">' . __('Supplemental Item Handling Code', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="jigoshop_shippingcalc_prod_supp_handling_code" id="jigoshop_shippingcalc_prod_supp_handling_code" value="' . esc_attr($supp_handling_code) . '">';
	echo '<p class="description">' . __('Enter your AuctionInc-configured Supplemental Handling Code.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Supplemental Item Handling Fee
	$supp_handling_fee = get_post_meta(get_the_ID(), 'jigoshop_shippingcalc_prod_supp_handling_fee', true);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_supp_handling_fee">' . __('Supplemental Item Handling Fee', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="jigoshop_shippingcalc_prod_supp_handling_fee" id="jigoshop_shippingcalc_prod_supp_handling_fee" value="' . esc_attr($supp_handling_fee) . '" placeholder="0.00">';
	echo '</td>';
	echo '</tr>';
	
	// On-Demand Service Codes
	$selected_ondemand_codes = get_post_meta(get_the_ID(), 'jigoshop_shippingcalc_prod_ondemand_codes', true);
	
	$ondemand_codes = array(
			'DHLWPE' => __('DHL Worldwide Priority Express', 'auctioninc_shippingcalc'),
			'DHL9AM' => __('DHL Express 9 A.M.', 'auctioninc_shippingcalc'),
			'DHL10AM' => __('DHL Express 10:30 A.M.', 'auctioninc_shippingcalc'),
			'DHL12PM' => __('DHL Express 12 P.M.', 'auctioninc_shippingcalc'),
			'DHLES' => __('DHL Domestic Economy Select', 'auctioninc_shippingcalc'),
			'DHLEXA' => __('DHL Domestic Express 9 A.M.', 'auctioninc_shippingcalc'),
			'DHLEXM' => __('DHL Domestic Express 10:30 A.M.', 'auctioninc_shippingcalc'),
			'DHLEXP' => __('DHL Domestic Express 12 P.M.', 'auctioninc_shippingcalc'),
			'DHLDE' => __('DHL Domestic Express 6 P.M.', 'auctioninc_shippingcalc'),
			'FDX2D' => __('FedEx 2 Day', 'auctioninc_shippingcalc'),
			'FDX2DAM' => __('FedEx 2 Day AM', 'auctioninc_shippingcalc'),
			'FDXES' => __('FedEx Express Saver', 'auctioninc_shippingcalc'),
			'FDXFO' => __('FedEx First Overnight', 'auctioninc_shippingcalc'),
			'FDXPO' => __('FedEx Priority Overnight', 'auctioninc_shippingcalc'),
			'FDXPOS' => __('FedEx Priority Overnight Saturday Delivery', 'auctioninc_shippingcalc'),
			'FDXSO' => __('FedEx Standard Overnight', 'auctioninc_shippingcalc'),
			'FDXGND' => __('FedEx Ground', 'auctioninc_shippingcalc'),
			'FDXHD' => __('FedEx Home Delivery', 'auctioninc_shippingcalc'),
			'FDXIGND' => __('FedEx International Ground', 'auctioninc_shippingcalc'),
			'FDXIE' => __('FedEx International Economy', 'auctioninc_shippingcalc'),
			'FDXIF' => __('FedEx International First', 'auctioninc_shippingcalc'),
			'FDXIP' => __('FedEx International Priority', 'auctioninc_shippingcalc'),
			'UPSNDA' => __('UPS Next Day Air', 'auctioninc_shippingcalc'),
			'UPSNDE' => __('UPS Next Day Air Early AM', 'auctioninc_shippingcalc'),
			'UPSNDAS' => __('UPS Next Day Air Saturday Delivery', 'auctioninc_shippingcalc'),
			'UPSNDS' => __('UPS Next Day Air Saver', 'auctioninc_shippingcalc'),
			'UPS2DE' => __('UPS 2 Day Air AM', 'auctioninc_shippingcalc'),
			'UPS2ND' => __('UPS 2nd Day Air', 'auctioninc_shippingcalc'),
			'UPS3DS' => __('UPS 3 Day Select', 'auctioninc_shippingcalc'),
			'UPSGND' => __('UPS Ground', 'auctioninc_shippingcalc'),
			'UPSCAN' => __('UPS Standard', 'auctioninc_shippingcalc'),
			'UPSWEX' => __('UPS Worldwide Express', 'auctioninc_shippingcalc'),
			'UPSWSV' => __('UPS Worldwide Saver', 'auctioninc_shippingcalc'),
			'UPSWEP' => __('UPS Worldwide Expedited', 'auctioninc_shippingcalc'),
			'USPFC' => __('USPS First-Class Mail', 'auctioninc_shippingcalc'),
			'USPEXP' => __('USPS Priority Express', 'auctioninc_shippingcalc'),
			'USPLIB' => __('USPS Library', 'auctioninc_shippingcalc'),
			'USPMM' => __('USPS Media Mail', 'auctioninc_shippingcalc'),
			'USPPM' => __('USPS Priority', 'auctioninc_shippingcalc'),
			'USPPP' => __('USPS Standard Post', 'auctioninc_shippingcalc'),
			'USPFCI' => __('USPS First Class International', 'auctioninc_shippingcalc'),
			'USPPMI' => __('USPS Priority Mail International', 'auctioninc_shippingcalc'),
			'USPEMI' => __('USPS Priority Express Mail International', 'auctioninc_shippingcalc'),
			'USPGXG' => __('USPS Global Express Guaranteed', 'auctioninc_shippingcalc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_ondemand_codes">' . __('On-Demand Service Codes', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<select name="jigoshop_shippingcalc_prod_ondemand_codes[]" id="jigoshop_shippingcalc_prod_ondemand_codes" multiple>';
	
	foreach ($ondemand_codes as $k => $v) {
		$selected = in_array($k, $selected_ondemand_codes) ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Select any AuctionInc configured on-demand services for which this item is eligible. Hold [Ctrl] key for multiple selections.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// On-Demand Service Codes
	$selected_access_fees = get_post_meta(get_the_ID(), 'jigoshop_shippingcalc_prod_access_fees', true);
	
	$access_fees = array(
			'AddlHandling' => __('Additional Handling Charge, All Carriers', 'auctioninc_shippingcalc'),
			'AddlHandlingUPS' => __('Additional Handling Charge, UPS', 'auctioninc_shippingcalc'),
			'AddlHandlingDHL' => __('Additional Handling Charge, DHL', 'auctioninc_shippingcalc'),
			'AddlHandlingFDX' => __('Additional Handling Charge, FedEx', 'auctioninc_shippingcalc'),
			'Hazard' => __('Hazardous Charge, All Carriers', 'auctioninc_shippingcalc'),
			'HazardUPS' => __('Hazardous Charge, UPS', 'auctioninc_shippingcalc'),
			'HazardDHL' => __('Hazardous Charge, DHL', 'auctioninc_shippingcalc'),
			'HazardFDX' => __('Hazardous Charge, FedEx', 'auctioninc_shippingcalc'),
			'SignatureReq' => __('Signature Required Charge, All Carriers', 'auctioninc_shippingcalc'),
			'SignatureReqUPS' => __('Signature Required Charge, UPS', 'auctioninc_shippingcalc'),
			'SignatureReqDHL' => __('Signature Required Charge, DHL', 'auctioninc_shippingcalc'),
			'SignatureReqFDX' => __('(Indirect) Signature Required  Charge, FedEx', 'auctioninc_shippingcalc'),
			'SignatureReqUSP' => __('Signature Required Charge, USPS', 'auctioninc_shippingcalc'),
			'UPSAdultSignature' => __('Adult Signature Required Charge, UPS', 'auctioninc_shippingcalc'),
			'DHLAdultSignature' => __('Adult Signature Required Charge, DHL', 'auctioninc_shippingcalc'),
			'FDXAdultSignature' => __('Adult Signature Required Charge, FedEx', 'auctioninc_shippingcalc'),
			'DHLPrefSignature' => __('Signature Preferred Charge, DHL', 'auctioninc_shippingcalc'),
			'FDXDirectSignature' => __('(Direct) Signature Required  Charge, FedEx', 'auctioninc_shippingcalc'),
			'FDXHomeCertain' => __('Home Date Certain Charge, FedEx Home Delivery', 'auctioninc_shippingcalc'),
			'FDXHomeEvening' => __('Home Date Evening Charge, FedEx Home Delivery', 'auctioninc_shippingcalc'),
			'FDXHomeAppmnt' => __('Home Appmt. Delivery Charge, FedEx Home Delivery', 'auctioninc_shippingcalc'),
			'Pod' => __('Proof of Delivery Charge, All Carriers', 'auctioninc_shippingcalc'),
			'PodUPS' => __('Proof of Delivery Charge, UPS', 'auctioninc_shippingcalc'),
			'PodDHL' => __('Proof of Delivery Charge, DHL', 'auctioninc_shippingcalc'),
			'PodFDX' => __('Proof of Delivery Charge, FedEx', 'auctioninc_shippingcalc'),
			'PodUSP' => __('Proof of Delivery Charge, USPS', 'auctioninc_shippingcalc'),
			'UPSDelivery' => __('Delivery Confirmation Charge, UPS', 'auctioninc_shippingcalc'),
			'USPCertified' => __('Certified Delivery Charge, USPS', 'auctioninc_shippingcalc'),
			'USPRestricted' => __('Restricted Delivery Charge, USPS', 'auctioninc_shippingcalc'),
			'USPDelivery' => __('Delivery Confirmation Charge, USPS', 'auctioninc_shippingcalc'),
			'USPReturn' => __('Return Receipt Charge, USPS', 'auctioninc_shippingcalc'),
			'USPReturnMerchandise' => __('Return Receipt for Merchandise Charge, USPS', 'auctioninc_shippingcalc'),
			'USPRegistered' => __('Registered Mail Charge, USPS', 'auctioninc_shippingcalc'),
			'IrregularUSP' => __('Irregular Package Discount,USPS', 'auctioninc_shippingcalc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="jigoshop_shippingcalc_prod_access_fees">' . __('Special Accessorial Fees', 'auctioninc_shippingcalc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<select name="jigoshop_shippingcalc_prod_access_fees[]" id="jigoshop_shippingcalc_prod_access_fees" multiple>';
	
	foreach ($access_fees as $k => $v) {
		$selected = in_array($k, $selected_access_fees) ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Add preferred special carrier fees. Hold [Ctrl] key for multiple selections.', 'auctioninc_shippingcalc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	echo '</table>';	
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function auctioninc_shippingcalc_prod_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	* because the save_post action can be triggered at other times.
	*/

	// Check if our nonce is set.
	if ( ! isset( $_POST['auctioninc_shippingcalc_product_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['auctioninc_shippingcalc_product_meta_box_nonce'], 'auctioninc_shippingcalc_product' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */

	$calc_method = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_method']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_method', $calc_method);
	
	$fixed_mode = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_fixed_mode']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_fixed_mode', $fixed_mode);
	
	$fixed_code = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_fixed_code']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_fixed_code', $fixed_code);

	$fixed_fee1 = floatval($_POST['jigoshop_shippingcalc_prod_fixed_fee1']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_fixed_fee1', $fixed_fee1);
	
	$fixed_fee2 = floatval($_POST['jigoshop_shippingcalc_prod_fixed_fee2']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_fixed_fee2', $fixed_fee2);
	
	$package = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_packaging']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_packaging', $package);
	
	$insurable = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_ins']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_ins', $insurable);
	
	$origin_code = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_origin_code']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_origin_code', $origin_code);
	
	$supp_handling_mode = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_supp_handling_mode']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_supp_handling_mode', $supp_handling_mode);
	
	$supp_handling_code = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_supp_handling_code']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_supp_handling_code', $supp_handling_code);
	
	$supp_handling_fee = sanitize_text_field($_POST['jigoshop_shippingcalc_prod_supp_handling_fee']);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_supp_handling_fee', $supp_handling_fee);
	
	$ondemand_codes_dirty = $_POST['jigoshop_shippingcalc_prod_ondemand_codes'];
	$ondemand_codes = is_array($ondemand_codes_dirty) ? array_map('sanitize_text_field', $ondemand_codes_dirty) : sanitize_text_field($ondemand_codes_dirty);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_ondemand_codes', $ondemand_codes);

	$access_fees_dirty = $_POST['jigoshop_shippingcalc_prod_access_fees'];
	$access_fees = is_array($access_fees_dirty) ? array_map('sanitize_text_field', $access_fees_dirty) : sanitize_text_field($access_fees_dirty);
	update_post_meta($post_id, 'jigoshop_shippingcalc_prod_access_fees', $access_fees);
}
add_action( 'save_post', 'auctioninc_shippingcalc_prod_save_meta_box_data' );
