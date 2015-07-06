jQuery(document).ready(function($) {

    toggle_methods();
    toggle_fixeds();

    $(document).on('change', '#jigoshop_shippingcalc_method', function(e) {
        toggle_methods();
    });

    $(document).on('change', '#jigoshop_shippingcalc_fixed_mode', function(e) {
        toggle_fixeds();
    });

    function toggle_methods() {
        var calc_method = $('#jigoshop_shippingcalc_method').val();

        if (calc_method === 'C' || calc_method === 'CI') {
            $('#jigoshop_shippingcalc_fixed_mode').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_fixed_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_fixed_fee1').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_fixed_fee2').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_packaging').closest('tr').fadeIn();
            $('#jigoshop_shippingcalc_ins').closest('tr').fadeIn();
        }
        else if (calc_method === 'F') {
            $('#jigoshop_shippingcalc_fixed_mode').closest('tr').fadeIn();
            toggle_fixeds();
            $('#jigoshop_shippingcalc_packaging').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_ins').closest('tr').fadeOut();
        }
        else if (calc_method === 'N') {
            $('#jigoshop_shippingcalc_fixed_mode').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_fixed_code').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_fixed_fee1').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_fixed_fee2').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_packaging').closest('tr').fadeOut();
            $('#jigoshop_shippingcalc_ins').closest('tr').fadeOut();
        }
        else {
            $('#jigoshop_shippingcalc_fixed_mode').closest('tr').hide();
            $('#jigoshop_shippingcalc_fixed_code').closest('tr').hide();
            $('#jigoshop_shippingcalc_fixed_fee1').closest('tr').hide();
            $('#jigoshop_shippingcalc_fixed_fee2').closest('tr').hide();
            $('#jigoshop_shippingcalc_packaging').closest('tr').hide();
            $('#jigoshop_shippingcalc_ins').closest('tr').hide();
        }
    }

    function toggle_fixeds() {
    	var calc_method = $('#jigoshop_shippingcalc_method').val();
        var fixed_mode = $('#jigoshop_shippingcalc_fixed_mode').val();
        
        if (calc_method === 'F') {
            if (fixed_mode === 'C') {
                $('#jigoshop_shippingcalc_fixed_code').closest('tr').fadeIn();
                $('#jigoshop_shippingcalc_fixed_fee1').closest('tr').fadeOut();
                $('#jigoshop_shippingcalc_fixed_fee2').closest('tr').fadeOut();
            }
            else if (fixed_mode === 'F') {
                $('#jigoshop_shippingcalc_fixed_code').closest('tr').fadeOut();
                $('#jigoshop_shippingcalc_fixed_fee1').closest('tr').fadeIn();
                $('#jigoshop_shippingcalc_fixed_fee2').closest('tr').fadeIn();
            }
            else {
                $('#jigoshop_shippingcalc_fixed_code').closest('tr').hide();
                $('#jigoshop_shippingcalc_fixed_fee1').closest('tr').hide();
                $('#jigoshop_shippingcalc_fixed_fee2').closest('tr').hide();
            }
        }

    }

});