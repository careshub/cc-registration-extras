<?php 
/**
* Create a user's setting page to capture input.
*/
class CCGESettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        // add_options_page(
        //     'Settings Admin', 
        //     'My Settings', 
        //     'manage_options', 
        //     'ccgr_extra', 
        //     array( $this, 'create_admin_page' )
        // );
        add_users_page(
            'CC Registration Extras', 
            'CC Registration Extras', 
            'manage_options', 
            'ccgr_extras_options', 
            array( $this, 'create_admin_page' )
            );
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        // Set class property
        $this->options = get_option( 'cc_restricted_email_domains' );

        if ( ! isset( $_REQUEST['settings-updated'] ) )
            $_REQUEST['settings-updated'] = false;
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>CC Registration Extras</h2>

            <?php if ( $_REQUEST['settings-updated'] !== false ) : ?>
                <div class="updated fade"><p><strong><?php _e( 'Options saved', 'cpfb' ); ?></strong></p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'ccgr_extras_options' );   
                do_settings_sections( 'ccgr_extras' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'ccgr_extras_options', // Group name. Must match the settings_fields function call
            'cc_restricted_email_domains', // Option name
            array( $this, 'sanitize' ) // Callback function for validation.
        );

        add_settings_section(
            'ccgr_extras_options', // ID for the section
            'Registration Extras', // Title
            array( $this, 'print_section_info' ), // Callback function. Outputs section description.
            'ccgr_extras' // Page name. Must match do_settings_section function call.
        );  

        add_settings_field(
            'restricted_domains', // ID for the field
            'Restricted Email Domains', // Title 
            array( $this, 'print_domain_form_field' ), // Callback function. Outputs form field inputs.
            'ccgr_extras', // Page name. Must match do_settings_section function call.
            'ccgr_extras_options' // ID of the settings section that this goes into (same as the first argument of add_settings_section).         
        );

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {

        $input = preg_split( '/\s+/', trim( $input ) );

        return $input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info() {
        // print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function print_domain_form_field() {
        ?>
                <p>Enter one known bad domain on each line. Example: </p>
        <blockquote><code>garbage.com</code><br /><code>baloney.net</code></blockquote>
        <p style="margin-bottom:1em;">If the domain is a dynamic domain, like <code>*.domain.com</code>, enter <code>domain.com</code> only.</p>

        <textarea  rows="30" cols="40" name="cc_restricted_email_domains"><?php echo $this->prepare_restricted_domains_for_form(); ?></textarea>
        <?php
    }

    public function prepare_restricted_domains_for_form() {
        // Set class property
        $this->options = get_option( 'cc_restricted_email_domains' );

        $restricted_domains = maybe_unserialize( $this->options );

        return implode( PHP_EOL, $restricted_domains );
    }
}

$ccge_settings_page = new CCGESettingsPage();