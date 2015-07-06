<?php

/**
 * Adds a box to the main column on the Order screens.
 */
function auctioninc_shippingcalc_add_packaging_meta_box() {

	$screens = array( 'shop_order' );

	foreach ( $screens as $screen ) {

		add_meta_box(
		'auctioninc_shippingcalc_packaging',
		__( 'ShippingCalc Packaging Details', 'auctioninc_shippingcalc' ),
		'auctioninc_shippingcalc_packaging_meta_box_callback',
		$screen
		);
	}
}
add_action( 'add_meta_boxes', 'auctioninc_shippingcalc_add_packaging_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
*/
function auctioninc_shippingcalc_packaging_meta_box_callback( $post ) {
    $shipping_meta = get_post_meta($post->ID, "auctioninc_shipping_meta", true);
    if(!empty($shipping_meta)) {
        echo $shipping_meta;
    } else {
        $idx = (int)$_SESSION['auctioninc_method'];
        if($idx < count($_SESSION['auctioninc_response']['ShipRate'])) {
            $shipping_meta = $_SESSION['auctioninc_response']['ShipRate'][$idx];
        } else {
            $shipping_meta = "";
        }
    
        $data='';
        if (!empty($shipping_meta['PackageDetail'])) {
            $i = 1;
            foreach ($shipping_meta['PackageDetail'] as $package) :
                $flat_rate_code = !empty($package['FlatRateCode']) ? $package['FlatRateCode'] : __('NONE', 'auctioninc_shippingcalc');
                ?>
                <?php $data .= "<strong>" . __('Package', 'auctioninc_shippingcalc') . "# $i" . "</strong><br>"; ?>
                <?php
                $data .= __('Flat Rate Code', 'auctioninc_shippingcalc') . ": $flat_rate_code<br>";
                $data .= __('Quantity', 'auctioninc_shippingcalc') . ": {$package['Quantity']}<br>";
                $data .= __('Pack Method', 'auctioninc_shippingcalc') . ": {$package['PackMethod']}<br>";
                $data .= __('Origin', 'auctioninc_shippingcalc') . ": {$package['Origin']}<br>";
                $data .= __('Declared Value', 'auctioninc_shippingcalc') . ": {$package['DeclaredValue']}<br>";
                $data .= __('Weight', 'auctioninc_shippingcalc') . ": {$package['Weight']}<br>";
                $data .= __('Length', 'auctioninc_shippingcalc') . ": {$package['Length']}<br>";
                $data .= __('Width', 'auctioninc_shippingcalc') . ": {$package['Width']}<br>";
                $data .= __('Height', 'auctioninc_shippingcalc') . ": {$package['Height']}<br>";
                $data .= __('Oversize Code', 'auctioninc_shippingcalc') . ": {$package['OversizeCode']}<br>";
                $data .= __('Carrier Rate', 'auctioninc_shippingcalc') . ": ".number_format($package['CarrierRate'],2)."<br>";
                $data .= __('Fixed Rate', 'auctioninc_shippingcalc') . ": ".number_format($package['FixedRate'],2)."<br>";
                $data .= __('Surcharge', 'auctioninc_shippingcalc') . ": ".number_format($package['Surcharge'],2)."<br>";
                $data .= __('Fuel Surcharge', 'auctioninc_shippingcalc') . ": ".number_format($package['FuelSurcharge'],2)."<br>";
                $data .= __('Insurance', 'auctioninc_shippingcalc') . ": ".number_format($package['Insurance'],2)."<br>";
                $data .= __('Handling', 'auctioninc_shippingcalc') . ": ".number_format($package['Handling'],2)."<br>";
                $data .= __('Total Rate', 'auctioninc_shippingcalc') . ": ".number_format($package['ShipRate'],2)."<br>";
    
                $j = 1;
                $data .= '<br>';
                foreach ($package['PkgItem'] as $pkg_item) :
                    ?>
                    <?php "<strong>" . $data .= __('Item', 'auctioninc_shippingcalc') . "# $j" . "</strong><br>"; ?>
                    <?php
                    $data .= __('Ref Code', 'auctioninc_shippingcalc') . ": {$pkg_item['RefCode']}<br>";
                    $data .= __('Quantity', 'auctioninc_shippingcalc') . ": {$pkg_item['Qty']}<br>";
                    $data .= __('Weight', 'auctioninc_shippingcalc') . ": {$pkg_item['Weight']}<br>";
                    $j++;
                endforeach;
                $data .= '<br><br>';
                $i++;
            endforeach;
    
        } else {
            $data = "No data available for this order";
        } 
        
        // Save result
        update_post_meta($post->ID, "auctioninc_shipping_meta", $data);
        echo $data;
        
        // Delete session data
        unset($_SESSION['auctioninc_response']);
        unset($_SESSION['auctioninc_method']);
    
    }   
}

function auctioninc_shippingcalc_transfer_session() {
    if (isset( jigoshop_session::instance()->order_awaiting_payment ) && jigoshop_session::instance()->order_awaiting_payment > 0) {
        $order = new jigoshop_order( jigoshop_session::instance()->order_awaiting_payment );
        
        // Store the selected shipping option for this order
        $idx = jigoshop_session::instance()->selected_rate_id;
        $_SESSION['auctioninc_method'] = $idx;

    }
        
}
add_action('save_post', 'auctioninc_shippingcalc_transfer_session');
