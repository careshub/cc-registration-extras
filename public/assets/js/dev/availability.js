jQuery(document).ready(function(){
	var timer = 0;

	jQuery("input#signup_username").wrap('<div id="username_checker" class="ajax-validation"></div> ');
	jQuery("#username_checker").append("<span class='loading' style='display:none'></span>")
	jQuery("#username_checker").append("<span id='name-info'></span> ");

	jQuery("input#signup_username").keyup( function() {
		clearTimeout( timer );
		timer = setTimeout( ajax_check_username, 500 );
	} );

	function ajax_check_username() {
		jQuery("#username_checker span.loading").css({display:'block'});

		var user_name = jQuery("input#signup_username").val();

		jQuery.post( ajaxurl, {
			action: 'cc_validate_username',
			// 'cookie': encodeURIComponent(document.cookie),
			'user_name':user_name
		},
		function( response ){
			var resp = jQuery.parseJSON( response );

			if( resp.code == 'success' )
				show_message( resp.message, 0 );
			else
				show_message( resp.message, 1 );
		});
	}

	function show_message( msg, is_error ) {
		jQuery("#username_checker #name-info").removeClass().css({display:'block'});
		jQuery("#username_checker span.loading").css({display:'none'});
		jQuery("#username_checker #name-info").empty().html(msg);

		if(is_error)
			jQuery("#username_checker #name-info").addClass("error");
		else
			jQuery("#username_checker #name-info").addClass("validated");
	}
});