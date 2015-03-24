jQuery(document).ready(function(){
	jQuery("#signup_email_confirm").wrap('<div id="email-comparer" class="ajax-validation"></div> ');
	jQuery("#email-comparer").append("<span id='email-comparison-message'></span> ");

	jQuery("#signup_email_confirm").on( "keyup", function(){
		jQuery("#signup_email_confirm").val( jQuery.trim( jQuery("#signup_email_confirm").val() ) );
		var first_value = jQuery("#signup_email").val();
		var second_value = jQuery("#signup_email_confirm").val();

		if ( first_value != second_value ) {
			jQuery('#email-comparison-message').empty().html( 'The addresses you entered do not match.' );
			jQuery('#email-comparison-message').addClass( 'error' );
			jQuery('#email-comparison-message').css({display:'block'});
		} else {
			jQuery('#email-comparison-message').empty().html( 'The addresses you entered match.' );
			jQuery('#email-comparison-message').addClass( 'validated' );
			jQuery('#email-comparison-message').css({display:'block'});
		}
	});
});