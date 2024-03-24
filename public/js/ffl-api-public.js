function initFFLJs(fKey,message,hook) {

    // don't alter the default shipping address, that is used for merch and apparel
	// if(hook === "woocommerce_before_checkout_billing_form") {
	// 	setTimeout(function() {
	// 		if (document.getElementById("ship-to-different-address-checkbox") != null){
	// 			document.getElementById("ship-to-different-address-checkbox").disabled = true;
	// 		}
	// 	},1000);
	// } else {
	// 	if (document.getElementById("ship-to-different-address-checkbox") != null){
	// 		document.getElementById("ship-to-different-address-checkbox").disabled = true;
	// 	}
	// }
	// if (document.getElementById("ship-to-different-address") != null){
	// 	document.getElementById("ship-to-different-address").style.display = 'none';
	// }
	// document.getElementById("shipping_state_field").style.display = 'none';
	// document.getElementById("shipping_country_field").style.display = 'none';

	FFL.init({
		container : 'ffl_container',
		apiKey: fKey,
		cBack : getSelected
	});

	// set the checkout message
	jQuery('.ffl_checkout_notice').html(wMes);
	
	jQuery("#ffl-map").ready(
		function(){
			 FFL.initGMap();
	});

    /* @todo these target the FFL ship first/last name, not the default shipping first last name */
	// jQuery('.woocommerce-shipping-fields__field-wrapper').find('input').val(null);
	// jQuery('.woocommerce-shipping-fields__field-wrapper').prepend(
	// 	'<p id="first_last_notice" class="notice" style="margin-bottom: 10px;">The First and Last name below help the FFL identify your gun when it arrives at their location. Enter <b><u>your</u></b> First and Last Name.</p>'
	// );

	// if(jQuery("#wizard")) {

	// 	if(hok === "woocommerce_before_checkout_billing_form") {
	// 		jQuery("#ffl_container").insertBefore(".woocommerce-billing-fields");

	// 	}



	// 	setTimeout(function () {
	// 		jQuery(document).on('click',"#wizard .actions a", function(e) {

	// 			if(localStorage.getItem("selectedFFL") === null) {
	// 				jQuery("#wizard .actions a[href='#next']").prop('href','#');
	// 				e.preventDefault();
	// 				e.stopPropagation();
	// 				return false;
	// 			} else {
	// 				if (document.getElementById("ship-to-different-address-checkbox") != null){
	// 					document.getElementById("ship-to-different-address-checkbox").disabled = false;
	// 				}
	// 				jQuery("#wizard .actions a[href='#']").prop('href','#next');

	// 			}

	// 		} );


	// 	},1000)


	// }


    /**
     * This is pre-validation before checkout is submitted
     */
	jQuery('form.woocommerce-checkout').on('checkout_place_order',
		function(e) {

            const alerts = [];

			if(localStorage.getItem("selectedFFL") === null) {
				alerts.push("An FFL dealer is required to complete your order. Please select an FFL dealer.");
            }
            if (jQuery('input[name="_shipping_ffl_cust_firstname"]').val() === '') {
                alerts.push("FFL pickup First name is required.");
            }
            if (jQuery('input[name="_shipping_ffl_cust_lastname"]').val() === '') {
                alerts.push("FFL pickup Last name is required.");
            }

            if (alerts.length) {
                window.alert(alerts.join('\n'));
                e.preventDefault();
                e.stopPropagation();
                return false;
            } else {

			    if(jQuery('.woocommerce-error').length === 0) {
					// if (document.getElementById("ship-to-different-address-checkbox") != null){
					// 	document.getElementById("ship-to-different-address-checkbox").disabled = false;
					// }
                    // document.getElementById("shipping_country").disabled = false;
                    // document.getElementById("shipping_state").disabled = false;
                }
			}

		});

        jQuery("#shipping_country").val(null).trigger('change');
	    jQuery("#order_comments").val(null).text(null);

}

function getSelected(data) {
    /* data res:
{
	"license_number": "9-95-037-06-5M-01528",
	"short_lic": "9-95-01528",
	"short_lic_nodash": "99501528",
	"license_name": "CALIFORNIA GUN GIRLS LLC",
	"business_name": "",
	"premise_street": "23121 COLTRANE AVE",
	"premise_city": "NEWHALL",
	"premise_state": "CA",
	"premise_zip_code": "91321",
	"voice_phone": "6613734658",
	"mail_street": "23121 COLTRANE AVE",
	"mail_city": "NEWHALL",
	"mail_state": "CA",
	"mail_zip_code": "91321",
	"ffl_on_file": 0,
	"premise_mail_address_mismatch": 0,
	"lat": 34.34523,
	"lng": -118.53566,
	"premise_address_verified": 1,
	"residential": 0,
	"email": null,
	"contact_required": null,
	"transfer_fee_longgun": null,
	"transfer_fee_handgun": null,
	"active": 1,
	"last_updated": null,
	"gb_ffl_on_file": null,
	"expiration_date": "2025-12-01 00:00:00",
	"list_name": "CALIFORNIA GUN GIRLS LLC"
}
    */

    var elemsActive = document.querySelectorAll(".selectedFFLDivButton");

	[].forEach.call(elemsActive, function(el) {
		el.classList.remove("selectedFFLDivButton");
	});
    
	document.getElementById(data.license_number).childNodes[0].classList.add('selectedFFLDivButton');
	localStorage.setItem("selectedFFL",data.license_number);
	document.getElementById( "place_order" ).disabled = false

    jQuery('input[name="_shipping_fflcompany"]').val(data.list_name);
    jQuery('input[name="_shipping_fflstreet"]').val(data.premise_street);
    jQuery('input[name="_shipping_fflcity"]').val(data.premise_city);
    jQuery('input[name="_shipping_fflstate"]').val(data.premise_state);
    jQuery('input[name="_shipping_fflzip"]').val(data.premise_zip_code);
    jQuery('input[name="_shipping_fflemail"]').val(data.email);
    jQuery('input[name="_shipping_fflphone"]').val(data.voice_phone);
	jQuery('input[name="_shipping_fflno"]').val(data.license_number);

    const expiry = data.expiration_date.substring(0,10);
	jQuery('input[name="_shipping_fflexp"]').val(expiry);
	
    const onFile = data.ffl_on_file ? "Yes" : "No";
	jQuery('input[name="_shipping_ffl_onfile"]').val(onFile);
	
    // May need to trigger a change event for shipping rates to compute
	jQuery("#shipping_state").trigger("change");
    jQuery("#shipping_country").val("US"); // Change the value or make some change to the internal state

	return false;
}

(function($) {
    /**
     * Link the FFL radius field to Select2
     */
    $(window).on('load', function() {
        const $radius = $('#ffl-radius');
        if ($radius.length && $.fn.select2) {
            $radius.select2({
                width: '100%'
            });
        }
    });

    /**
     * Clone the first/last name values to the checkout fields' values. 
     */
    $(window).on('load', function() {
        const $first = $('#ffl_api_widget_form input[name="_shipping_ffl_cust_firstname"]');
        const $last = $('#ffl_api_widget_form input[name="_shipping_ffl_cust_lastname"]');
        const $firstCheckout = $('.woocommerce-checkout input[name="_shipping_ffl_cust_firstname"]');
        const $lastCheckout = $('.woocommerce-checkout input[name="_shipping_ffl_cust_lastname"]');

        if ($first.length) {
            $first.on('change keyup', function() {
                $firstCheckout.val($first.val());
            });
        }
        if ($last.length) {
            $last.on('change keyup', function() {
                $lastCheckout.val($last.val());
            });
        }
    });
})(jQuery);
