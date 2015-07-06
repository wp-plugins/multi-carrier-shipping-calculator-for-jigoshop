jQuery(document).ready(function($) {

    toggle_methods();
    toggle_handlings();
    toggle_fixeds();

    $(document).on('change', '#jigoshop_shippingcalc_prod_method', function(e) {
        toggle_methods();
    });

    $(document).on('change', '#jigoshop_shippingcalc_prod_supp_handling_mode', function(e) {
        toggle_handlings();
    });

    $(document).on('change', '#jigoshop_shippingcalc_prod_fixed_mode', function(e) {
        toggle_fixeds();
    });

    function toggle_methods() {
        var calc_method = $('#jigoshop_shippingcalc_prod_method').val();

        if (calc_method === 'C' || calc_method === 'CI') {
            $('#jigoshop_shippingcalc_prod_fixed_mode').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_fixed_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_fixed_fee1').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_fixed_fee2').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_packaging').closest('tr').fadeIn();
            $('#jigoshop_shippingcalc_prod_ins').closest('tr').fadeIn();
            $('#jigoshop_shippingcalc_prod_origin_code').closest('tr').fadeIn();
            $('#jigoshop_shippingcalc_prod_supp_handling_mode').closest('tr').fadeIn();
            toggle_handlings();
            $('#jigoshop_shippingcalc_prod_ondemand_codes').closest('tr').fadeIn();
            $('#jigoshop_shippingcalc_prod_access_fees').closest('tr').fadeIn();
        }
        else if (calc_method === 'F') {
            $('#jigoshop_shippingcalc_prod_fixed_mode').closest('tr').fadeIn();
            toggle_fixeds();
            $('#jigoshop_shippingcalc_prod_packaging').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_ins').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_origin_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_supp_handling_mode').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_supp_handling_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_supp_handling_fee').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_ondemand_codes').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_access_fees').closest('tr').fadeOut();
        }
        else if (calc_method === 'N') {
            $('#jigoshop_shippingcalc_prod_fixed_mode').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_fixed_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_fixed_fee1').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_fixed_fee2').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_packaging').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_ins').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_origin_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_supp_handling_mode').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_supp_handling_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_supp_handling_fee').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_ondemand_codes').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_access_fees').closest('tr').fadeOut();
        }
        else {
            $('#jigoshop_shippingcalc_prod_fixed_mode').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_fixed_code').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_fixed_fee1').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_fixed_fee2').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_packaging').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_ins').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_origin_code').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_supp_handling_mode').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_supp_handling_code').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_supp_handling_fee').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_ondemand_codes').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_access_fees').closest('tr').hide();
        }
    }

    function toggle_handlings() {
        var handling_mode = $('#jigoshop_shippingcalc_prod_supp_handling_mode').val();
        if (handling_mode === 'C') {
            $('#jigoshop_shippingcalc_prod_supp_handling_code').closest('tr').fadeIn();
            $('#jigoshop_shippingcalc_prod_supp_handling_fee').closest('tr').fadeOut();
        }
        else if (handling_mode === 'F') {
            $('#jigoshop_shippingcalc_prod_supp_handling_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_prod_supp_handling_fee').closest('tr').fadeIn();
        }
        else {
            $('#jigoshop_shippingcalc_prod_supp_handling_code').closest('tr').hide();
            $('#jigoshop_shippingcalc_prod_supp_handling_fee').closest('tr').hide();
        }
    }

    function toggle_fixeds() {
        var calc_method = $('#jigoshop_shippingcalc_prod_method').val();
        var fixed_mode = $('#jigoshop_shippingcalc_prod_fixed_mode').val();
        
        if (calc_method === 'F') {
            if (fixed_mode === 'C') {
                $('#jigoshop_shippingcalc_prod_fixed_code').closest('tr').fadeIn();
                $('#jigoshop_shippingcalc_prod_fixed_fee1').closest('tr').fadeOut();
                $('#jigoshop_shippingcalc_prod_fixed_fee2').closest('tr').fadeOut();
            }
            else if (fixed_mode === 'F') {
                $('#jigoshop_shippingcalc_prod_fixed_code').closest('tr').fadeOut();
                $('#jigoshop_shippingcalc_prod_fixed_fee1').closest('tr').fadeIn();
                $('#jigoshop_shippingcalc_prod_fixed_fee2').closest('tr').fadeIn();
            }
            else {
                $('#jigoshop_shippingcalc_prod_fixed_code').closest('tr').hide();
                $('#jigoshop_shippingcalc_prod_fixed_fee1').closest('tr').hide();
                $('#jigoshop_shippingcalc_prod_fixed_fee2').closest('tr').hide();
            }
        }

    }

});