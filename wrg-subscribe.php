<?php
/*
Plugin name: WRG Subscribe
Plugin URI: http://test.com/
Author: rahul
Author URI: http://sewpafly.github.io/post-thumbnail-editor/
Version: 1.0
Description: Subscription

*/

/*
 * Useful constants
 */
define( 'PTE_PLUGINURL', plugins_url(basename( dirname(__FILE__))) . "/");
define( 'PTE_PLUGINPATH', dirname(__FILE__) . "/");
define( 'PTE_DOMAIN', "wrg-subscribe");
define( 'PTE_VERSION', "1.0");



register_activation_hook( __FILE__, 'wrg_install' );
function wrg_install() {
	global $wpdb;
	global $jal_db_version;
	$table_name = $wpdb->prefix . 'wrg_subscription';
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		first_name tinytext NOT NULL,
		last_name tinytext NOT NULL,
		email VARCHAR(50) NOT NULL,
		phone int(11) NOT NULL,
		dob DATE NOT NULL,
		website varchar(55) DEFAULT '' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	add_option( 'jal_db_version', $jal_db_version );
}


function wrg_subForm( $first_name, $last_name, $email, $dob, $phone, $website ) {
    echo '
    <style>
    div {
        margin-bottom:2px;
    }
     
    input{
        margin-bottom:4px;
    }
    </style>
    ';
 
    echo '
    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
     
    <div>
    <label for="firstname">First Name</label>
    <input type="text" name="fname" value="' . ( isset( $_POST['fname']) ? $first_name : null ) . '">
    </div>
     
    <div>
    <label for="website">Last Name</label>
    <input type="text" name="lname" value="' . ( isset( $_POST['lname']) ? $last_name : null ) . '">
    </div>

    <div>
    <label for="email">Email <strong>*</strong></label>
    <input type="text" name="email" value="' . ( isset( $_POST['email']) ? $email : null ) . '">
    </div>

    <div>
    <label for="phone">Phone <strong>*</strong></label>
    <input type="text" name="phone" value="' . ( isset( $_POST['phone']) ? $phone : null ) . '">
    </div>

    <div>
    <label for="dob">dob <strong>*</strong></label>
    <input type="date" name="dob" value="' . ( isset( $_POST['dob']) ? $dob : null ) . '">
    </div>

     
    <div>
    <label for="website">Website</label>
    <input type="text" name="website" value="' . ( isset( $_POST['website']) ? $website : null ) . '">
    </div>
     
    <input type="submit" name="submit" value="Submit"/>
    </form>
    ';
}

function wrg_subscribe_validation( $first_name, $last_name, $email, $dob, $phone, $website )  {
global $reg_errors;
$reg_errors = new WP_Error;
if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) || empty( $dob ) || empty( $phone ) || empty( $website ) ) {
    $reg_errors->add('field', 'Required form field is missing');
}
if ( !is_email( $email ) ) {
    $reg_errors->add( 'email_invalid', 'Email is not valid' );
}
if ( ! empty( $website ) ) {
    if ( ! filter_var( $website, FILTER_VALIDATE_URL ) ) {
        $reg_errors->add( 'website', 'Website is not a valid URL' );
    }
}

if ( is_wp_error( $reg_errors ) ) {
    foreach ( $reg_errors->get_error_messages() as $error ) {
        echo '<div>';
        echo '<strong>ERROR</strong>:';
        echo $error . '<br/>';
        echo '</div>';  
    }
}
}

function wrg_subscribe_insert() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wrg_subscription';	
    global $reg_errors, $first_name, $last_name, $email, $phone, $website, $dob;
    $time = strtotime($_POST['dob']);
    $new_date = date('Y-m-d', $time);
    if ( 1 > count( $reg_errors->get_error_messages() ) ) {
	    $wpdb->insert( 
			$table_name, 
			array( 
				'time' => current_time( 'mysql' ), 
				'first_name' => $first_name, 
				'last_name' => $last_name,
				'email' => $email,
				'phone' => $phone,
				'dob' => $new_date,
				'website' => $website, 
			) 
		);
		// Print last SQL query string
		//echo $wpdb->last_query;
		// Print last SQL query result
		//echo $wpdb->last_result;
		// Print last SQL query Error
		//echo $wpdb->last_error;
		//exit();
        echo 'Insert complete. Goto <a href="' . get_site_url() . '/wp-login.php">login page</a>.';   
    }
}

function custom_wrg_subscription_form() {
    if ( isset($_POST['submit'] ) ) {
        wrg_subscribe_validation(
        $_POST['fname'],
        $_POST['lname'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['dob'],
        $_POST['website'],
        );
         
        // sanitize user form input
        global  $first_name, $last_name, $email, $website, $phone;
        $first_name =   sanitize_text_field( $_POST['fname'] );
        $last_name  =   sanitize_text_field( $_POST['lname'] );
        $email      =   sanitize_email( $_POST['email'] );
        $phone  =   sanitize_text_field( $_POST['phone'] );
        $dob  =   sanitize_text_field( $_POST['dob'] );
        $website    =   esc_url( $_POST['website'] );
 
        // call @function complete_registration to create the user
        // only when no WP_error is found
        wrg_subscribe_insert(
        $first_name,
        $last_name,
        $email,
        $phone,
        $dob,
        $website
        );
    }
 
    wrg_subForm(
    	$first_name,
        $last_name,
        $email,
        $phone,
        $dob,
        $website
        );
}



add_shortcode( 'wrg-subscribe', 'wrg_subscribe_shortcode' );
 
function wrg_subscribe_shortcode() {
    ob_start();
    custom_wrg_subscription_form();
    return ob_get_clean();
}








add_action('admin_menu', 'wrg_subscription_plugin_setup_menu');
function wrg_subscription_plugin_setup_menu(){
        add_menu_page( 'WRG Subscription Page', 'WRG Subscription Plugin', 'manage_options', 'wrg-subscription-plugin', 'wrg_init' );
}
 
function wrg_init(){
	global $wpdb;
	$table_name = $wpdb->prefix . "wrg_subscription";
	$userdetails = $wpdb->get_results( "SELECT * FROM $table_name" );
	echo '<h1 class="wp-heading-inline">USER DETAILS</h1>';
	echo '<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" />';
	echo '<script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js" /></script>';
	
	echo '<script type="text/javascript">
		jQuery(document).ready(function() {
		    jQuery("#example").DataTable();
		} );
		</script>';

        echo "<table id='example' class='display' style='width:100%'>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>DOB</th>
                <th>Phone</th>
                <th>Website</th>
            </tr>
        </thead>
        <tbody>
            <tr>";
        foreach ($userdetails as $user) {
        echo    "<td>".$user->first_name."</td>
                <td>".$user->last_name."</td>
                <td>".$user->email."</td>
                <td>".$user->dob."</td>
                <td>".$user->phone."</td>
                <td>".$user->website."</td>
            </tr>";
        }
        echo "</tbody>
        <tfoot>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>DOB</th>
                <th>Phone</th>
                <th>Website</th>
            </tr>
        </tfoot>
    </table>";



}