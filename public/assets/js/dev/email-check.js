(function ( $ ) {
	"use strict";
	var validateTimer = 0,
		compareTimer = 0;

	jQuery("#signup_email").wrap('<div id="email_checker" class="ajax-validation"></div> ');
	jQuery("#email_checker").append("<span class='loading' style='display:none'></span>")
	jQuery("#email_checker").append("<span id='email-info'></span> ");

	jQuery("#signup_email_confirm").wrap('<div id="email-comparer" class="ajax-validation"></div> ');
	jQuery("#email-comparer").append("<span id='email-comparison-message'></span> ");

	// Check the form on load if there are values to check.
	if ( $( "#signup_email" ).val() ) {
		ajax_check_email();
	}

	if ( $( "#signup_email" ).val() ) {
		compare_email_addys();
	}

	jQuery("#signup_email").on( "keyup change", function() {
		clearTimeout( validateTimer );
		validateTimer = setTimeout( ajax_check_email, 500 );
	});

	jQuery("#signup_email, #signup_email_confirm").on( "keyup change", function() {
		clearTimeout( compareTimer );
		compareTimer = setTimeout( compare_email_addys, 500 );
	} );

	function ajax_check_email(){
		jQuery("#signup_email").val( jQuery.trim( jQuery("#signup_email").val() ) );
		jQuery("#email_checker span.loading").show();

		var email = jQuery("#signup_email").val();

		jQuery.post( ajaxurl, {
			action: 'cc_validate_email',
			// 'cookie': encodeURIComponent(document.cookie),
			'email': email
		},
		function(response){
			var resp = jQuery.parseJSON( response );
			show_email_message( resp.valid_address_message, resp.valid_address );
			jQuery( "#signup_form" ).trigger({
			  type:"ajax_response_email_verified",
			  response: resp,
			});
		});
	}

	function show_email_message( msg, is_valid )	{
		jQuery("#email_checker #email-info").css({display:'block'});
		jQuery("#email_checker span.loading").hide();
		jQuery("#email_checker #email-info").empty().html( msg );

		// Also remove the BP-produced error message if one exists.
		// jQuery( "#email_checker, #email-comparer" ).parent().children( ".error" ).hide();

		if ( is_valid ) {
			jQuery("#email_checker #email-info").removeClass("error").addClass("validated");
		} else {
			jQuery("#email_checker #email-info").removeClass("validated").addClass("error");
		}
	}


	function compare_email_addys(){
		// jQuery("#signup_email_confirm").val( jQuery.trim( jQuery("#signup_email_confirm").val() ) );
		var first_value = jQuery.trim( jQuery("#signup_email").val() );
		var second_value = jQuery.trim( jQuery("#signup_email_confirm").val() );
		jQuery('#email-comparison-message').css({display:'block'});

		// Also remove the BP-produced error message if one exists.
		// jQuery( "#email_checker, #email-comparer" ).parent().children( ".error" ).hide();

		if ( first_value != second_value ) {
			jQuery('#email-comparison-message').removeClass("validated").addClass("error");
			jQuery('#email-comparison-message').empty().html( 'The addresses you entered do not match.' );
		} else {
			jQuery('#email-comparison-message').removeClass("error").addClass("validated");
			jQuery('#email-comparison-message').empty().html( 'The addresses you entered match.' );
		}
	}

}(jQuery));