// (function ( $ ) {
// 	"use strict";
	// var timer = 0;

	// jQuery("#signup_email_confirm").wrap('<div id="email-comparer" class="ajax-validation"></div> ');
	// jQuery("#email-comparer").append("<span id='email-comparison-message'></span> ");

	// jQuery("#signup_email_confirm").on( "keyup change", function() {
	// 	clearTimeout( timer );
	// 	timer = setTimeout( compare_email_addys, 500 );
	// } );

	// function compare_email_addys(){
	// 	jQuery("#signup_email_confirm").val( jQuery.trim( jQuery("#signup_email_confirm").val() ) );
	// 	var first_value = jQuery("#signup_email").val();
	// 	var second_value = jQuery("#signup_email_confirm").val();
	// 	jQuery('#email-comparison-message').removeClass().css({display:'block'});

	// 	// Also remove the BP-produced error message if one exists.
	// 	jQuery( "#email_checker, #email-comparer" ).parent().children( ".error" ).hide();

	// 	if ( first_value != second_value ) {
	// 		jQuery('#email-comparison-message').addClass( 'error' );
	// 		jQuery('#email-comparison-message').empty().html( 'The addresses you entered do not match.' );
	// 	} else {
	// 		jQuery('#email-comparison-message').addClass( 'validated' );
	// 		jQuery('#email-comparison-message').empty().html( 'The addresses you entered match.' );
	// 	}
	// }
// }(jQuery));