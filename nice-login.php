<?php
/*

/*
 * Plugin Name: Wordpress Nice Login
 * Version: 1.0
 * Description: User friendly ajax based wordpress login/register. 
 * Author: Hassan Jamal
 * Text Domain: nice-login
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Author URI: http://www.webocado.com/
 * Requires at least: 4.0
 * Tested up to: 4.4.2

 */


if(defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && isset($_REQUEST['lang']) ){

	add_filter('plugin_locale', 'webocado_nl_fix_locale',999,2);
	function webocado_nl_fix_locale($locale){
		return sanitize_text_field($_REQUEST['lang']);
	}
}


add_action( 'init', 'webocado_nl_load_textdomain' );
/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function webocado_nl_load_textdomain() {

	load_plugin_textdomain( 'nice-login', false, basename( dirname( __FILE__ ) ) . '/lang' ); 
}

register_activation_hook( __FILE__, 'webocado_nl_NiceLoginActivate' );

function webocado_nl_NiceLoginActivate( ) {
	

}

/*  Include scripts and stylesheets
 *
 */

add_action('wp_head', 'webocado_nl_NiceLogin_ajaxurl');

function webocado_nl_NiceLogin_ajaxurl() {

	echo '<script type="text/javascript">
	var ajaxurl = "' . admin_url('admin-ajax.php') . '";
</script>';
}

add_action( 'wp_enqueue_scripts', 'webocado_nl_NiceLoginScripts' );
//add_action( 'admin_enqueue_scripts', 'webocado_nl_NiceLoginScripts' );

function webocado_nl_NiceLoginScripts() {
	wp_enqueue_script( 'jquery' );

	wp_register_script( 'nice-login', plugins_url( 'nice-login.js', __FILE__ ), array( 'jquery' ));
	wp_enqueue_script( 'nice-login' );

	wp_register_style( 'nice-login-default', plugins_url( 'nice-login.css', __FILE__ ) );
	wp_enqueue_style( 'nice-login-default' );
}

add_action( 'wp_ajax_nopriv_webocado_nl_forgot', 'ajax_webocado_nl_forgot' );

function ajax_webocado_nl_forgot(){

	$user_login = $_POST['user_login'];

	$errors = array('error'=>true);
	$errors['errors'] = array();

	global $wpdb, $wp_hasher;
	
	if ( empty( $user_login) ) {

		$errors['errors'][] = array('email_user_required'=>array(__( "Email/User ID required.",'nice-login')));
		echo json_encode($errors);
		wp_die();

	} else if ( strpos( $user_login, '@' ) ) {
		$user_data = get_user_by( 'email', sanitize_email( $user_login ) );
		if ( empty( $user_data ) ){
			$errors['errors'][] = array('invalid_user'=>array(__( "This user does not exist.",'nice-login')));
			echo json_encode($errors);
			wp_die();
		}
	} else {
		$login = sanitize_text_field($user_login);
		$user_data = get_user_by('login', $login);
	}

	do_action('lostpassword_post');
	if ( !$user_data ) {
		$errors['errors'][] = array('invalid_user'=>array(__( "This user does not exist.",'nice-login')));
		echo json_encode($errors);
		wp_die();
	}
    // redefining user_login ensures we return the right case in the email
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;
    do_action('retreive_password', $user_login);  // Misspelled and deprecated
    do_action('retrieve_password', $user_login);
    $allow = apply_filters('allow_password_reset', true, $user_data->ID);
    if ( ! $allow )
    {
    	$errors['errors'][] = array('reset_allow'=>array(__( "Reseting not allowed.",'nice-login')));
    	echo json_encode($errors);
    	wp_die();
    }
    else if ( is_wp_error($allow) )
    {
    	$errors['errors'][] = array('general_error'=>array(__( "Unknown error.",'nice-login')));
    	echo json_encode($errors);
    	wp_die();
    }
    $key = wp_generate_password( 20, false );
    do_action( 'retrieve_password_key', $user_login, $key );

    if ( empty( $wp_hasher ) ) {
    	require_once ABSPATH . 'wp-includes/class-phpass.php';
    	$wp_hasher = new PasswordHash( 8, true );
    }
    $hashed = $wp_hasher->HashPassword( $key );    
    $wpdb->update( $wpdb->users, array( 'user_activation_key' => time().":".$hashed ), array( 'user_login' => $user_login ) );
    $message = __('Someone requested that the password be reset for the following account:','nice-login') . "\r\n\r\n";
    $message .= network_home_url( '/' ) . "\r\n\r\n";
    $message .= sprintf(__('Username: %s','nice-login'), $user_login) . "\r\n\r\n";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.','nice-login') . "\r\n\r\n";
    $message .= __('To reset your password, visit the following address:','nice-login') . "\r\n\r\n";
    $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

    if ( is_multisite() )
    	$blogname = $GLOBALS['current_site']->site_name;
    else
    	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    $title = sprintf( __('[%s] Password Reset','nice-login'), $blogname );

    $title = apply_filters('retrieve_password_title', $title);
    $message = apply_filters('retrieve_password_message', $message, $key);

    $redirect = $_POST['nl_redirect'];

    if(parse_url($redirect)['host']!=parse_url(site_url())['host'])
    	$redirect = site_url();

    update_user_meta( $user_data->ID, 'nl_redirect',$redirect );

    if ( $message && !wp_mail($user_email, $title, $message) )

    {
    	$errors['errors'][] = array('email_error'=>array(__( "The e-mail could not be sent.",'nice-login')));
    	echo json_encode($errors);
    	wp_die();
    }

    $errors['error']=false;
    $errors['data'] = "<p class='webocado_nl_notice'>".__( "Link for password reset has been emailed to you. Please check your email.",'nice-login')."</p>";
    echo json_encode($errors);
    wp_die();
}


add_action( 'wp_ajax_nopriv_webocado_nl_resend', 'ajax_webocado_nl_resend' );

function ajax_webocado_nl_resend() {

	$res = array();

	$userid = sanitize_text_field($_POST['userid']);
	if(get_userdata( $userid )===false)
	{
		$res['sent']=__( "Invalid user.",'nice-login');
	}
	else
	{
		$code = get_user_meta( $userid, 'webocado_nl_activation', true );
		if($code!="")
		{
			$activation_link = add_query_arg( array( 'webocado_nl_registration_key' => $code, 'user' => $userid ), get_home_url());

			$redirect = sanitize_text_field($_POST['nl_redirect']);

			if(parse_url($redirect)['host']!=parse_url(site_url())['host'])
				$redirect = site_url();

			update_user_meta( $userid, 'webocado_nl_redirect',$redirect );

			$sent = wp_mail( get_userdata($userid)->user_email, sprintf(__( 'Activate your account - %s','nice-login'),get_bloginfo('name')), sprintf(__('Please activate your account at %s by visiting: %s' ,'nice-login'),get_bloginfo('name'),$activation_link));



			if($sent==true)
			{
				$res['sent']=__("Activation email sent successfully.",'nice-login');
			}
			else
			{
				$res['sent']=__("Activation email sending failed. Please try again.",'nice-login');
			}
		}
		else
		{
			$res['sent']=__("This user is already activated.",'nice-login');
		}
	}

	echo json_encode($res);

	wp_die(); // this is required to terminate immediately and return a proper response
}



add_action( 'wp_ajax_nopriv_webocado_nl_login', 'ajax_webocado_nl_login' );

function ajax_webocado_nl_login() {

	
	$errors = array('error'=>true);
	$errors['errors'] = array();

	$login_user_email = sanitize_text_field($_POST['nl_login_user_email']);

	if(empty($login_user_email))
	{
		$errors['errors'][] = array('email_required'=>array(__("Email is required.",'nice-login')));
	}
	else if (!filter_var($login_user_email, FILTER_VALIDATE_EMAIL)) {
		$errors['errors'][] = array('email_invalid'=>array(__("Invalid email address.",'nice-login')));
	}

	if($_POST['nl_login_user_pass']=="")
	{
		$errors['errors'][] = array('pass_required'=>array(__("Password is required.",'nice-login')));
	}

	if(count($errors['errors'])>0)
	{
		echo json_encode($errors);

		wp_die();
	}
	else
	{
		$user = get_user_by( 'email', $login_user_email);

		if( $user === false){
			$errors['errors'][]=array('user_invalid'=>array(__("This user does not exist.",'nice-login')));
			echo json_encode($errors);

			wp_die();
		}
		else
		{
			if ( get_user_meta( $user->ID, 'webocado_nl_activation', true ) != false ) {

				$user = new WP_Error('activation_failed', __('User is not activated.','nice-login').'<br /><a id="webocado_nl_resend_activation_link" class="webocado_nl_resend_activation_link" data-userid="'.$user->ID.'" href="javascript:;">'.__('Resend activation link.','nice-login').'</a><div id="webocado_nl_resend_activation_link_response" class="webocado_nl_resend_activation_link_response"></div>');

				echo json_encode(array('error'=>true,'errors'=>$user));

				wp_die();

			}

			$rem = false;
			if(sanitize_text_field($_POST['nl_login_remember'])=='1')
				$rem = true;

			$creds = array(
				'user_login'    => $user->user_login,
				'user_password' => $_POST['nl_login_user_pass'],
				'remember'      => $rem 
				);

			$user = wp_signon( $creds, false );

			if ( is_wp_error( $user ) ) {
				echo json_encode(array('error'=>true,'errors'=>$user));

				wp_die();
			}
			else
			{
				echo json_encode(array('error'=>false,'data'=>"<p class='webocado_nl_notice'>".__("Login success, refreshing page...",'nice-login')."</p>"));

				wp_die();
			}
		}
	}
}


add_action( 'wp_ajax_nopriv_webocado_nl_add_user', 'ajax_webocado_nl_add_user' );

function ajax_webocado_nl_add_user() {

    //global $wpdb; // this is how you get access to the database
    //$wpdb->nl_extra_fields = $wpdb->prefix . "nl_extra_fields";

    $errors = array('error'=>true);
    $errors['errors'] = array();

    $user_email = sanitize_email($_POST['nl_user_email']);
    $fn = sanitize_text_field($_POST['nl_first_name']);
    $ln = sanitize_text_field($_POST['nl_last_name']);

    if(empty($user_email))
    {
    	$errors['errors'][]=array('email_required'=>array(__("Email is required.",'nice-login')));
    }
    else if ($user_email!=$_POST['nl_user_email'] || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    	$errors['errors'][]=array('email_invalid'=>array(__("Invalid email address.",'nice-login')));
    }
    if($_POST['nl_user_pass']=="")
    {
    	$errors['errors'][] = array('pass_required'=>array(__("Password is required.",'nice-login')));
    }
    else if(strlen($_POST['nl_user_pass'])<6)
    {
    	$errors['errors'][] = array('pass_min'=>array(__("Password should contain minimum 6 characters.",'nice-login')));
    }
    if(strlen($_POST['nl_user_pass'])>60)
    {
    	$errors['errors'][] = array('pass_max'=>array(__("Password should contain maximum 60 characters.",'nice-login')));
    }
    if($_POST['nl_pass_confirm']!=$_POST['nl_user_pass'])
    {
    	$errors['errors'][] = array('pass_matach'=>array(__("Passwords do not match.",'nice-login')));
    }
    if(strlen($fn)>60)
    {
    	$errors['errors'][] = array('fn_max'=>array(__("First name should contain maximum 60 characters.",'nice-login')));
    }
 //    if(empty($fn))
 //    {
 //    	$errors['errors'][] = array('fn_required'=>array(__("Please enter your first name.",'nice-login')));
 //    }
	// if(empty($ln))
 //    {
 //    	$errors['errors'][] = array('ln_required'=>array(__("Please enter your last name.",'nice-login')));
 //    }

    if(strlen($ln)>60)
    {
    	$errors['errors'][] = array('ln_max'=>array(__("Last name should contain maximum 60 characters.",'nice-login')));
    }


    if(count($errors['errors'])>0)
    {
    	echo json_encode($errors);

    	wp_die();
    }
    else
    {

    	$newuser = array(
    		'user_pass' =>   $_POST['nl_user_pass'],
    		'user_login' => $user_email,
    		'user_email' => $user_email,
    		'first_name' => $fn,
    		'last_name' => $ln,
    		'role' => 'subscriber'
    		);

    	$user_id = wp_insert_user($newuser);
    	if ( $user_id && !is_wp_error( $user_id ) ) {
    		$code = sha1( $user_id . time() );
    		$activation_link = add_query_arg( array( 'webocado_nl_registration_key' => $code, 'user' => $user_id ), get_home_url());

    		add_user_meta( $user_id, 'webocado_nl_activation', $code, true );

    		$redirect = esc_url($_POST['nl_redirect']);

    		if($_POST['nl_redirect']!= $redirect || parse_url($redirect)['host']!=parse_url(site_url())['host'])
    			$redirect = site_url();

    		update_user_meta( $user_id, 'webocado_nl_redirect',$redirect );

    		$rem = '0';
    		if(sanitize_text_field($_POST['nl_remember'])=='1')
    			$rem = '1';

    		$sub = '0';
    		if(sanitize_text_field($_POST['nl_subscribe'])=='1')
    			$sub = '1';

    		update_user_meta( $user_id, 'webocado_nl_remember',$rem);
    		update_user_meta( $user_id, 'webocado_nl_subscribe',$sub);

    		wp_mail( $user_email, sprintf(__( 'Activate your account - %s','nice-login'),get_bloginfo('name')), sprintf(__('Please activate your account at %s by visiting: %s' ,'nice-login'),get_bloginfo('name'),$activation_link));


    		echo json_encode(array('error'=>false,'data'=>"<p class='webocado_nl_notice'>".__('An activation email has been sent to your email address, you should activate your account by visiting the activation link','nice-login')."</p>"));
    		wp_die(); 
    	}
    	else
    	{
    		
    		echo json_encode(array('error'=>true,'errors'=>$user_id));
    		wp_die();
    	}


    }

}


// override core function
if ( !function_exists('wp_authenticate') ) :
function wp_authenticate($username, $password) {
	$username = sanitize_user($username);
	$password = trim($password);

	$user = apply_filters('authenticate', null, $username, $password);

	if ( $user == null ) {
        // TODO what should the error message be? (Or would these even happen?)
        // Only needed if all authentication handlers fail to return anything.
		$user = new WP_Error('authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.','nice-login'));
	} elseif ( get_user_meta( $user->ID, 'webocado_nl_activation', true ) != false ) {

		$user = new WP_Error('activation_failed', __('User is not activated.','nice-login').'<br /><a id="webocado_nl_resend_activation_link" class="webocado_nl_resend_activation_link" data-userid="'.$user->ID.'" href="javascript:;">'.__('Resend activation link.','nice-login').'</a><div id="webocado_nl_resend_activation_link_response" class="webocado_nl_resend_activation_link_response"></div>');

	add_action('login_head', 'webocado_nl_NiceLogin_ajaxurl');

	wp_register_script( 'nice-login', plugins_url( 'nice-login.js', __FILE__ ), array( 'jquery' ));
	wp_enqueue_script( 'nice-login' ); 

	}

	$ignore_codes = array('empty_username', 'empty_password');

	if (is_wp_error($user) && !in_array($user->get_error_code(), $ignore_codes) ) {
		do_action('wp_login_failed', $username);
	}

	return $user;
}
endif;

	add_action( 'template_redirect', 'webocado_nl_activate_user' );

	function webocado_nl_activate_user() {

		$code = filter_input( INPUT_GET, 'webocado_nl_registration_key' );

		if ( $code!=NULL ) {
			$user_id = filter_input( INPUT_GET, 'user', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
			if ( $user_id ) {
            		// get user meta activation hash field
				$code_stored = get_user_meta( $user_id, 'webocado_nl_activation', true );
				if ( $code == $code_stored ) {
					delete_user_meta( $user_id, 'webocado_nl_activation' );
					$nl_redirect = get_user_meta( $user_id, 'webocado_nl_redirect', true );
					delete_user_meta( $user_id, 'webocado_nl_redirect' );
					wp_set_auth_cookie( $user_id,get_user_meta( $user_id, 'webocado_nl_remember', true )=='1' ? true:false);
					wp_redirect( $nl_redirect );
					exit;
				}
				else
				{
				//setcookie('nl_invalid_activation', $user_id);
				//wp_redirect( get_permalink() );
					add_action( 'wp_footer', function() use (&$user_id) { webocado_nl_invalid_activation($user_id); } );
				//do_action( 'set_invalid_activation_userid',  $user_id );

				}
			}
			else
			{
				echo(__("Invalid User ID.",'nice-login'));
			}
		}
	}

	function webocado_nl_invalid_activation($u) {
		if(!is_user_logged_in() ){
			echo '<div id="webocado_nl_invalid_activation"><div class="webocado_nl_wrap"><div class="webocado_nl_cont"><span id="webocado_nl_inv_close"></span>'.__('Invalid activation code.','nice-login').'<br /><a id="webocado_nl_resend_invalid_activation_link" class="webocado_nl_resend_activation_link" data-userid="'.$u.'" href="javascript:;">'.__('Resend activation link','nice-login').'</a><div id="webocado_nl_resend_invalid_activation_link_response" class="webocado_nl_resend_activation_link_response"></div></div></div></div>';
		}
	}

	function webocado_nl_print_form() {
		if(!is_user_logged_in() ){
			?>
			<div id="webocado_nl_forms" >
				<span id="webocado_nl_close"></span>
				<div class="webocado_nl_wrap">
					<form id="webocado_nl_login_form" action="javascript:;">
						<h1><?php echo __("Log In",'nice-login'); ?></h1>
						<ul id="webocado_nl_login_response"></ul>
						<div class="webocado_nl_field">
							<label for="webocado_nl_login_user_email"><?php echo __("Email",'nice-login'); ?><span class="webocado_nl_r">*</span></label>
							<input type="text" name="nl_login_user_email" id="webocado_nl_login_user_email" />
						</div>
						<div class="webocado_nl_field">
							<label for="webocado_nl_login_user_pass"><?php echo __("Password",'nice-login'); ?><span class="webocado_nl_r">*</span></label>
							<input type="password" name="nl_login_user_pass" id="webocado_nl_login_user_pass" />
						</div>
						<div class="webocado_nl_field">
							<div class="webocado_nl_input webocado_nl_cb">
								<span class="webocado_nl_cb_wrap"><input type="checkbox" name="nl_login_remember" id="webocado_nl_login_remember" value="1" /><span></span></span>
								<label for="webocado_nl_login_remember"><?php echo __("Remember me",'nice-login'); ?></label>
							</div>
						</div>
						<input type="hidden" name="action" value="webocado_nl_login">
						<input type="hidden" name="lang" value="<?php echo get_locale(); ?>">

						<div class="webocado_nl_field">
							<div class="webocado_nl_input">
								<button class="webocado_nl_login_btn webocado_nl_btn" id="webocado_nl_login_btn"><?php echo __("Login",'nice-login'); ?></button><span class="webocado_nl_mid"> or </span>
								<button class="webocado_nl_or_register_btn webocado_nl_btn" id="webocado_nl_or_register_btn"><?php echo __("Register",'nice-login'); ?></button>
							</div>
						</div>

						<div class="webocado_nl_field webocado_nl_bottom">
							<div class="webocado_nl_input">
								<a href="javascript:;" class="webocado_nl_or_forgot_btn" id="webocado_nl_or_forgot_btn"><?php echo __("Forgot password?",'nice-login'); ?></a>
							</div>
						</div>

					</form>
					<form id="webocado_nl_register_form" action="javascript:;">
						<h1><?php echo __("Create an account",'nice-login'); ?></h1>
						<ul id="webocado_nl_register_response"></ul>
						<div class="webocado_nl_field">
							<label for="webocado_nl_user_email"><?php echo __("Email",'nice-login'); ?><span class="webocado_nl_r">*</span></label>
							<input type="text" name="nl_user_email" id="webocado_nl_user_email" />
						</div>
						<div class="webocado_nl_field">
							<label for="webocado_nl_user_pass"><?php echo __("Password",'nice-login'); ?><span class="webocado_nl_r">*</span></label>
							<input type="password" name="nl_user_pass" id="webocado_nl_user_pass" />
						</div>
						<div class="webocado_nl_field">
							<label for="webocado_nl_pass_confirm"><?php echo __("Re-Type Password",'nice-login'); ?><span class="webocado_nl_r">*</span></label>
							<input type="password" name="nl_pass_confirm" id="webocado_nl_pass_confirm" />
						</div>
						<div class="webocado_nl_field">
							<label for="webocado_nl_first_name"><?php echo __("Name",'nice-login'); ?><span class="webocado_nl_r">*</span></label>
							<div class="webocado_nl_input">
								<div class="webocado_nl_half">
									<input type="text" name="nl_first_name" id="webocado_nl_first_name" placeholder="<?php echo __("First Name",'nice-login'); ?>" />
								</div>
								<div class="webocado_nl_half">
									<input type="text" name="nl_last_name" id="webocado_nl_last_name" placeholder="<?php echo __("Last Name",'nice-login'); ?>" />
								</div>
							</div>
						</div>
						<div class="webocado_nl_field">
							<div class="webocado_nl_input webocado_nl_cb">
								<span class="webocado_nl_cb_wrap"><input type="checkbox" name="nl_remember" id="webocado_nl_remember" value="1"/><span></span></span>
								<label for="webocado_nl_remember"><?php echo __("Remember me",'nice-login'); ?></label>
							</div>
						</div>
						<div class="webocado_nl_field">
							<div class="webocado_nl_input webocado_nl_cb">
								<span class="webocado_nl_cb_wrap"><input type="checkbox" name="nl_subscribe" id="webocado_nl_subscribe" value="1" /><span></span></span>
								<label for="webocado_nl_subscribe"><?php echo __("Subscribe to our newsletter",'nice-login'); ?></label>
							</div>
						</div>
						<input type="hidden" name="action" value="webocado_nl_add_user">
						<input type="hidden" name="lang" value="<?php echo get_locale(); ?>">
						<input type="hidden" name="nl_redirect" class="webocado_nl_redirect">

						<div class="webocado_nl_field">
							<div class="webocado_nl_input">
								<button class="webocado_nl_register_btn webocado_nl_btn" id="webocado_nl_register_btn"><?php echo __("Create account",'nice-login'); ?></button>
							</div>
						</div>
						<div class="webocado_nl_field webocado_nl_bottom">
							<div class="webocado_nl_input">
								<a href="javascript:;" class="webocado_nl_or_login_btn" id="webocado_nl_or_login_btn"><?php echo __("Already registered? Log In",'nice-login'); ?></a>
							</div>
						</div>
					</form>
					<form id="webocado_nl_forgot_form" action="javascript:;">
						<h1><?php echo __("Forgot password",'nice-login'); ?></h1>
						<ul id="webocado_nl_forgot_response"></ul>
						<div class="webocado_nl_field">
							<label for="webocado_nl_user_login"><?php echo __("Email / User ID",'nice-login'); ?><span class="webocado_nl_r">*</span></label>
							<input type="text" name="nl_user_login" id="webocado_nl_user_login" />
						</div>
						<input type="hidden" name="action" value="webocado_nl_forgot">
						<input type="hidden" name="lang" value="<?php echo get_locale(); ?>">
						<input type="hidden" name="nl_redirect" class="webocado_nl_redirect">

						<div class="webocado_nl_field">
							<div class="webocado_nl_input">
								<button class="webocado_nl_forgot_btn webocado_nl_btn" id="webocado_nl_forgot_btn"><?php echo __("Send reset password link",'nice-login'); ?></button>
							</div>
						</div>
						<div class="webocado_nl_field webocado_nl_bottom">
							<div class="webocado_nl_input">
								<a href="javascript:;" class="webocado_nl_or_loginr_btn" id="webocado_nl_or_loginr_btn"><?php echo __("Login/Register",'nice-login'); ?></a>
							</div>
						</div>
					</form>
				</div>
			</div>
			<div id="webocado_nl_overlay"></div>
			<? }
		}

		add_action( 'wp_footer', 'webocado_nl_print_form' );

		?>
