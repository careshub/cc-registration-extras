jQuery(document).ready(function(){
	var timer = 0;

	jQuery("#signup_email").wrap('<div id="email_checker" class="ajax-validation"></div> ');
	jQuery("#email_checker").append("<span class='loading' style='display:none'></span>")
	jQuery("#email_checker").append("<span id='email-info'></span> ");

	jQuery("#signup_email").keyup( function() {
		clearTimeout( timer );
		timer = setTimeout( ajax_check_email, 500 );
	});

	function ajax_check_email(){
		jQuery("#signup_email").val( jQuery.trim( jQuery("#signup_email").val() ) );
		jQuery("#email_checker span.loading").css({display:'block'});

		var email = jQuery("#signup_email").val();

		jQuery.post( ajaxurl, {
			action: 'cc_validate_email',
			// 'cookie': encodeURIComponent(document.cookie),
			'email': email
		},
		function(response){
			var resp = jQuery.parseJSON( response );

			if( resp.code == 'success' )
				show_email_message(resp.message,0);
			else
				show_email_message(resp.message,1);
		});
	}

	function show_email_message( msg, is_error )	{
		jQuery("#email_checker #email-info").removeClass().css({display:'block'});
		jQuery("#email_checker span.loading").css({display:'none'});
		jQuery("#email_checker #email-info").empty().html( msg );

		if(is_error)
			jQuery("#email_checker #email-info").addClass("error");
		else
			jQuery("#email_checker #email-info").addClass("validated");
	}
});