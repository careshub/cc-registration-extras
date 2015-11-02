<?php
/**
 * @package   CC Registration Extras
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 *
 * @package CC Registration Extras
 * @author  David Cavins
 */
class CC_Registration_Extras {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.1.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'cc-registration-extras';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		// Load public-facing style sheet and JavaScript.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		//1. Add confirm e-mail address on BP registration form
			add_action('bp_signup_after_email', array( $this, 'registration_add_email_confirm' ),20);
			add_action('bp_signup_validate', array( $this, 'registration_check_email_confirm' ) );

		//2. A splogger check on the registration form field "About Me"
			// add_action('bp_signup_validate', array( $this, 'registration_check_about_me_weed_sploggers' ) );

		//2a. Disallowing some usernames
			add_action('bp_signup_validate', array( $this, 'registration_check_disallowed_usernames') );

		//2b. Disallowing some email domains
			add_action('bp_signup_validate', array( $this, 'registration_check_disallowed_domains') );

		//3. Disable activation e-mail, allowing for instant registration
			add_action( 'bp_core_signup_user', array( $this, 'disable_validation_of_new_users' ) );
			add_filter( 'bp_registration_needs_activation', array( $this, 'fix_signup_form_validation_text' ) );
			add_filter( 'bp_core_signup_send_activation_key', array( $this, 'disable_activation_email' ) );
			// add_action( 'bp_core_signup_user', array( $this, 'auto_login_redirect_user_to_profile' ), 98 );
			add_action( 'bp_core_signup_user', array( $this, 'auto_login_redirect_user_to_welcome_page' ), 98 );

		//4. If the user is arriving to the registration form as a result of receiving an invite, fill in both e-mail addresses to be nice.
			add_action('accept_email_invite_before', array( $this, 'invite_anyone_populate_confirm_email_field' ) );

		//5. Calculate the user's lat/lon based on their entry in the city and state field. We'll use usermeta for this, so that the user will never see it (and be confused)
			add_action( 'bp_core_signup_user', array( $this, 'cc_get_user_lat_lon' ), 88 );
			add_action( 'xprofile_updated_profile', array( $this, 'cc_get_user_lat_lon' ), 88 );

		//6. Handle the Terms of Service checkbox as a usermeta form field rather than an xprofile field.

		//7. AJAX Registration validation
			// Username validation
			add_action( 'wp_ajax_nopriv_cc_validate_username', array( $this, 'ajax_validate_username' ) );
			// Email address validation
			add_action( 'wp_ajax_nopriv_cc_validate_email', array( $this, 'ajax_validate_email' ) );

		//8. Accept Terms of Service Checkbox
			// Add Terms of Service checkbox to registration form
			add_action( 'bp_before_registration_submit_buttons', array( $this, 'add_tos_to_registration' ), 81 );
			// Make sure user accepted TOS before allowing signup
			add_filter( 'bp_signup_validate', array( $this, 'registration_check_tos') );
			// Add "Terms of Service" acceptance to usermeta on signup
			add_filter( 'bp_signup_usermeta', array( $this, 'add_usermeta_at_signup' ) );

		//9. Google ReCAPTCHA
			// Align the box to the right
			add_filter( 'ncr_bp_register_section_class', array( $this, 'captcha_align_right' ) );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if ( bp_is_register_page() )
			wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( bp_is_register_page() )
			wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.min.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	//1. Add confirm e-mail address on BP registration form
	public function registration_add_email_confirm(){
		//do_action( 'bp_signup_email_confirm_errors' );
		?>
		<label for="signup_email_confirm">Confirm Email <?php _e( '(required)', 'buddypress' ); ?></label>
		<?php do_action( 'bp_signup_email_confirm_errors' ); ?>
		<input type="text" name="signup_email_confirm" id="signup_email_confirm" value="<?php
		echo empty( $_POST['signup_email_confirm'] ) ? '' : $_POST['signup_email_confirm']; ?>" />
	<?php }

	// Make sure that the two submitted email addresses match
	public function registration_check_email_confirm(){
		global $bp;

		// Check that an email address was submitted
		$account_details = bp_core_validate_user_signup( $_POST['signup_username'], $_POST['signup_email_confirm'] );
		if ( ! empty( $account_details['errors']->errors['user_email'] ) )
				$bp->signup->errors['signup_email_confirm'] = $account_details['errors']->errors['user_email'][0];

		// Check that the two addresses match
		if ( ! empty( $_POST['signup_email'] ) ){
			//first field not empty and second field empty
			if ( empty( $_POST['signup_email_confirm'] ) )
					$bp->signup->errors['signup_email_confirm'] = 'Please confirm your address.';
			//both fields not empty but differents
			else if ( $_POST['signup_email'] != $_POST['signup_email_confirm'] )
					$bp->signup->errors['signup_email_confirm'] = 'The addresses you entered do not match.';
		}
	}

	//2. A splogger check on the registration form field "About Me"
	public function registration_check_about_me_weed_sploggers(){
		global $bp;

		//Many of our human sploggers fill in two capital letters for the "About Me" field, like "QX", so let's prevent them from signing up.
		//About Me happens to be field_10
		$field_value = $_POST[ 'field_10' ];

		//if the field is not empty we check its contents
		if ( !empty( $field_value ) ){
				//If the field contains only two capital letters, return an error. Not too helpful, though, because we don't want them to see specifically where the problem is.
				if ( preg_match( '/^[A-Z]{2}$/', $field_value ) ) {
						$bp->signup->errors['cc_suspicious_behavior'] = __('Sorry, something went deeply and seriously wrong with your registration','buddypress');
				}
		}
	}

	//2a. Send submitted usernames off to the username laundry
	public function registration_check_disallowed_usernames(){
		global $bp;

		if ( isset( $_POST[ 'signup_username' ] ) )
			$maybe_error = $this->username_laundry( $_POST[ 'signup_username' ] );

		if ( ! empty( $maybe_error ) )
			$bp->signup->errors['signup_username'] = $maybe_error;
	}

	//2a. Send submitted usernames off to the username laundry
	public function registration_check_disallowed_domains(){
		global $bp;

		if ( isset( $_POST[ 'signup_email' ] ) )
			$maybe_error = $this->email_laundry( $_POST[ 'signup_email' ] );

		if ( ! empty( $maybe_error ) )
			$bp->signup->errors['signup_email'] = $maybe_error;
	}

	/**
	 * Check emails for known bad domains
	 * @since 1.0
	 */
	public function email_laundry( $email ){
		// Get the domain only
		$passed_domain = array_pop( explode('@', $email) );
		// Let's go ahead and account for *.domain.com types, too.
		// We'll get the last two or three pieces and reform them into a domain.
		$domain_parts = explode( '.', $passed_domain );
		// If domain.com, we want two parts; if domain.co.uk, we'll need three parts.
		// Kind of a hack, check the length of the second-to-last part.
		end( $domain_parts );
		$maybe_tld = prev( $domain_parts );
		$length = ( strlen( $maybe_tld ) < 3 ) ? -3 : -2;
		$domain_parts = array_slice( $domain_parts, $length );
		$domain = implode( '.', $domain_parts );

		$towrite = PHP_EOL . 'passed: ' .  print_r( $passed_domain, TRUE );
		$towrite .= PHP_EOL . 'parts: ' .  print_r( $domain_parts, TRUE );
		$towrite .= PHP_EOL . 'final: ' .  print_r( $domain, TRUE );
		$fp = fopen('reg_domain_parsing.txt', 'a');
		fwrite($fp, $towrite);
		fclose($fp);

		$message = '';
		$illegal_domains = maybe_unserialize( get_option( 'cc_restricted_email_domains' ) );

		if ( in_array( $domain, $illegal_domains ) ) {
			$message .= 'Sorry, that is a restricted domain.' ;
		}

		return $message;
	}

	//3. Disable activation, allowing for instant registration
	function disable_validation_of_new_users( $user_id ) {
			global $wpdb;

			// No one will have to activate anymore, but let's keep this code just in case.
			//Get user e-mail, then check if that email is on our invitee list
			// $user_info = get_userdata( $user_id );

			// $args = array(
			//     'search' => $user_info->user_email,
			// );
			// $invitee_tax = get_terms( 'ia_invitees', $args );

			//get_terms returns an array if successful, an empty array on failure.
			// if ( count( $invitee_tax ) > 0 ) {

				//Hook if you want to do something before the activation
				do_action('bp_disable_activation_before_activation');

				//Need to let bp_core_activate_account do this
				// $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_status = 0 WHERE ID = %d", $user_id ) );

				//Add note on Activity Stream
				//This is also done in bp_core_activate
				// if ( function_exists( 'bp_activity_add' ) ) {
				//   $userlink = bp_core_get_userlink( $user_id );

				//   bp_activity_add( array(
				//     'user_id' => $user_id,
				//     'action' => apply_filters( 'bp_core_activity_registered_member', sprintf( __( '%s became a registered member', 'buddypress' ), $userlink ), $user_id ),
				//     'component' => 'profile',
				//     'type' => 'new_member'
				//   ) );

				// }

				//Get the user's activation key for BP. This is the activation_key in user_meta, not the key in the user table. Confusing, eh?
				$user_key = get_user_meta($user_id, 'activation_key', TRUE);

				// Activate the signup
				$awuser = apply_filters( 'bp_core_activate_account', bp_core_activate_signup( $user_key ) );

				//Hook if you want to do something before the login
				do_action('bp_disable_activation_before_login');

				//Automatically log the user in.
				//Thanks to Justin Klein's  wp-fb-autoconnect plugin for the basic code to login automatically
				$user_info = get_userdata($user_id);
				wp_set_auth_cookie($user_id);

				do_action('wp_signon', $user_info->user_login);

				// bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );
				$learn_more_url = site_url( '/2015/01/heres-what-you-can-do-on-community-commons/' );

				$message = 'Welcome to Community Commons! <a href="' . $learn_more_url . '">Learn more</a> about what you can do here. Or, <a href="http://maps.communitycommons.org/">make a map</a> or <a href="http://assessment.communitycommons.org/CHNA/SelectArea.aspx?reporttype=libraryCHNA">build a report</a>.';

				bp_core_add_message( $message );

				buddypress()->activation_complete = true;

				//Hook if you want to do something after the login
				do_action('bp_disable_activation_after_login', $user_id);
			//} //end check for invitation
		}

	//Don't show the user the screen that says "We're sending you an email..."
	public function fix_signup_form_validation_text() {
		return false;
	}

	//Don't send the activation e-mail
	public function disable_activation_email() {
		return false;
	}

	//When we log in the users after successfully signing up, send them to their profile page
	public function auto_login_redirect_user_to_profile( $user_id ) {
		bp_core_redirect( bp_core_get_user_domain( $user_id ) );
	}

	//When we log in the users after successfully signing up, send them to their profile page
	public function auto_login_redirect_user_to_welcome_page( $user_id ) {
		$redirect = apply_filters( 'cc_redirect_after_signup', site_url( '2015/01/heres-what-you-can-do-on-community-commons/' ) );
		bp_core_redirect( $redirect );
	}

	//4. If the user is arriving to the registration form as a result of receiving an invite, fill in both e-mail addresses to be nice.
	public function invite_anyone_populate_confirm_email_field() {
		if ( bp_is_register_page() ) :
		?>

		<script type="text/javascript">
			jQuery(document).ready( function() {
				jQuery("input#signup_email_confirm").val("<?php echo urldecode( bp_action_variable( 0 ) ); ?>");
			});
		</script>

		<?php
		endif;
	}

	//5. Calculate the user's lat/lon based on their entry in the city and state field. We'll use usermeta for this, so that the user will never see it (and be confused)
	public function cc_get_user_lat_lon( $user_id ) {
		$towrite = PHP_EOL . print_r( date('Y-m-d H:i:s'), TRUE );
		$fp = fopen('geocoder_results.txt', 'a');
		fwrite($fp, $towrite);
		fclose($fp);

		if ( empty( $user_id ) ) {
			$towrite = ' | User ID is empty';
			$fp = fopen('geocoder_results.txt', 'a');
			fwrite($fp, $towrite);
			fclose($fp);
			return false;
		} else {
			$towrite = ' | ' . print_r( $user_id, TRUE );
			$fp = fopen('geocoder_results.txt', 'a');
			fwrite($fp, $towrite);
			fclose($fp);
		}

		// Get the xprofile data for the city-state entry
		$location = xprofile_get_field_data( 'Location', $user_id );

		if ( empty( $location ) ) {
			//If location is empty, remove the metadata if it exists
			$removed = delete_user_meta( $user_id, 'long_lat' );
			$towrite = ' | Location is empty';
			$fp = fopen('geocoder_results.txt', 'a');
			fwrite($fp, $towrite);
			fclose($fp);

			return $removed;
		} else {
			// If location exists, attempt to get the long/lat from the Google geocoder
			$location = str_replace ( " ", "+", urlencode( $location ) );
			$details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . $location . "&sensor=false";

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $details_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			$response = json_decode( curl_exec($ch), true );

			// If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
			if ( $response['status'] != 'OK' ) {
				// A location is provided, but it's not recognized by Google.
				$removed = delete_user_meta( $user_id, 'long_lat' );
				$towrite = ' | Geocoder error status: ' . print_r( $response['status'], TRUE );
				$fp = fopen('geocoder_results.txt', 'a');
				fwrite($fp, $towrite);
				fclose($fp);

				return $response['status'];
			}

			if ( $geometry = $response['results'][0]['geometry'] ) {
				$longitude = $geometry['location']['lng'];
				$latitude = $geometry['location']['lat'];
				$coordinates = (string) $longitude . ',' . (string) $latitude;
				// Write the result to the usermeta table
				$success = update_user_meta( $user_id, 'long_lat', $coordinates );
				$towrite = ' | Updated user location meta';
				$fp = fopen('geocoder_results.txt', 'a');
				fwrite($fp, $towrite);
				fclose($fp);

				return $success;
			}
		}
	}

	// AJAX validation
	/**
	 * Check username availability
	 * Code mostly by Brajesh Singh
	 * http://buddydev.com/buddypress/creating-a-buddypress-wordpress-username-availability-checker-for-your-site/
	 * @since 1.0
	 */
	public function ajax_validate_username() {

		$towrite = PHP_EOL . 'beginning username validation: ' . print_r($_POST, TRUE);
		$fp = fopen('registration_checks.txt', 'a');
		fwrite($fp, $towrite);
		fclose($fp);

			if( ! empty( $_POST['user_name'] ) ) {
				$user_name = sanitize_user( $_POST['user_name'] );

				if ( get_user_by( 'login', $user_name ) )
					$msg = array( 'code' => 'taken', 'message' => __( 'This username is taken, please choose another one.', $this->plugin_slug ) );

				if ( empty( $msg ) ){
					$maybe_error = $this->username_laundry( $user_name );

					if ( empty( $maybe_error ) ) {
						$msg = array( 'code' => 'success', 'message' => __( 'This username is available.', $this->plugin_slug ) );
					} else {
						$msg = array( 'code' => 'error', 'message' => $maybe_error );
					}
				}
			} else {
				$msg = array( 'code' => 'error', 'message' => __( 'You must choose a username.', $this->plugin_slug ) );
			}

			$msg = apply_filters( 'cc_registration_extras_username_validate_message', $msg );

			die( json_encode( $msg ) );
	}

	/**
	 * Check usernames for undesirable characters and names we want to exclude
	 * @since 1.0
	 */
	public function username_laundry( $user_name ){
		// $bp = buddypress();
		$message = '';
		$db_illegal_names = get_option( 'illegal_names' );

		// If the field contains one of several certain values, don't allow.
		$disallowed_usernames = array( 'support' );
		$illegal_names = array_merge( $db_illegal_names, $disallowed_usernames );

		foreach ( $illegal_names as $bad_name ) {
			if ( stripos( $user_name, $bad_name ) !== false )
				$message .= 'Sorry, usernames may not include "' . $bad_name . '". ' ;
		}

		if ( strpos( $user_name, ' ') )
			$message .= 'Please do not use blank spaces in your username.';



		return $message;
	}

	/**
	 * Validate email address, checking for well-formedness and duplicates.
	 * @since 1.0
	 */
	public function ajax_validate_email() {

		if ( empty( $_POST['email'] ) )
			$msg = array( 'code' => 'error', 'message' => __( 'You must enter an email address.', $this->plugin_slug ) );

		if ( ! is_email( $_POST['email'] ) ) {
			$msg = array( 'code' => 'error', 'message' => __( 'Please enter a valid email address.', $this->plugin_slug ) );
		} else if ( get_user_by( 'email', $_POST['email'] ) ) {
			$msg = array( 'code' => 'error', 'message' => sprintf( __( 'That email address is already in use. Have you <a href="%s">forgotten your password?</a>', $this->plugin_slug ), wp_lostpassword_url() ) );
		} else {
			// Finally, check for restricted domains
			$maybe_error = $this->email_laundry( $_POST['email'] );

			if ( empty( $maybe_error ) ) {
				$msg = array( 'code' => 'success', 'message' => __( 'This email address is valid.', $this->plugin_slug ) );
			} else {
				$msg = array( 'code' => 'error', 'message' => $maybe_error );
			}
		}

		$msg = apply_filters( 'cc_registration_extras_email_validate_message', $msg );

		die( json_encode( $msg ) );
	}

	/**
	 * Add Terms of Service checkbox to register page
	 * @since 1.0
	 */
	public function add_tos_to_registration() {
		?>
	    <div id="tos" class="register-section alignright checkbox">
			<div class="editfield">
		        <label for="accept_tos"><?php echo  __( 'Community Commons Terms of Service', $this->plugin_slug )  ?></label>
				<?php do_action( 'bp_accept_tos_errors' ) ?>
	            <label><input type="checkbox" name="accept_tos" id="accept_tos" value="agreed" <?php checked( $_POST['accept_tos'], 'agreed' ); ?> /> Accept</label>
	            <p class="description">You must read and accept the Community Commons <a target="_blank" href="/terms-of-service">Terms of Service</a>.</p>
            </div>
	    </div>
	    <?php
	}

	/**
	 * Make sure user accepted TOS before allowing signup
	 * @since 1.0
	 */
	public function registration_check_tos(){
		global $bp;

		if ( $_POST['accept_tos'] != 'agreed' )
			$bp->signup->errors['accept_tos'] = 'You must read and accept the Terms of Service.';
	}

	/**
	 * Add "Terms of Service" acceptance to usermeta on signup
	 * @since 1.0
	 */
	function add_usermeta_at_signup( $usermeta ) {

		$usermeta['accept_tos'] = $_POST['accept_tos'];

		$towrite = PHP_EOL . print_r( $usermeta, TRUE );
		$fp = fopen('usermeta_transport.txt', 'a');
		fwrite($fp, $towrite);
		fclose($fp);

		return $usermeta;
	}
	/**
	 * 9. Google ReCAPTCHA
	 * Align the box to the right.
	 * @since 1.0
	 */
	public function captcha_align_right( $class ) {
		return $class . ' alignright';
	}
}