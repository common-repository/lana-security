<?php
/**
 * Plugin Name: Lana Security
 * Plugin URI: http://lana.codes/lana-product/lana-security/
 * Description: Simple and easy to use security plugin.
 * Version: 1.1.8
 * Author: Lana Codes
 * Author URI: http://lana.codes/
 * Text Domain: lana-security
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or die();
define( 'LANA_SECURITY_VERSION', '1.1.8' );
define( 'LANA_SECURITY_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'LANA_SECURITY_DIR_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Lana Security
 * Modifiable constants
 */
if ( ! defined( 'LANA_SECURITY_DEFAULT_LOGIN_LOGS_CLEANUP_AMOUNT' ) ) {
	define( 'LANA_SECURITY_DEFAULT_LOGIN_LOGS_CLEANUP_AMOUNT', 1000 );
}

/**
 * Language
 * load
 */
load_plugin_textdomain( 'lana-security', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

/**
 * Lana Security
 * get translate string
 *
 * @param $string
 *
 * @return mixed
 */
function lana_security_get_translate_string( $string ) {

	$translate_strings = array();

	preg_match( '/%([a-z0-9 _.\-@]*)%/', $string, $matches );

	if ( array_key_exists( 1, $matches ) ) {
		$user_login = $matches[1];

		$string = str_replace( $matches[0], 'user_login', $string );

		$translate_strings['admin_password_changed_by_user_login'] = sprintf( __( 'admin password changed by %s', 'lana-security' ), $user_login );
		$translate_strings['user_deleted_by_user_login']           = sprintf( __( 'user deleted by %s', 'lana-security' ), $user_login );
	}

	$translate_strings['lana_security_plugin_activated']   = __( 'Lana Security plugin activated', 'lana-security' );
	$translate_strings['lana_security_plugin_deactivated'] = __( 'Lana Security plugin deactivated', 'lana-security' );

	if ( in_array( $string, $translate_strings ) ) {
		return $translate_strings[ $string ];
	}

	return $string;
}

/**
 * Add plugin action links
 *
 * @param $links
 *
 * @return mixed
 */
function lana_security_add_plugin_action_links( $links ) {

	$settings_url = esc_url( admin_url( 'admin.php?page=lana-security-settings.php' ) );

	/** add settings link */
	$settings_link = sprintf( '<a href="%s">%s</a>', $settings_url, __( 'Settings', 'lana-security' ) );
	array_unshift( $links, $settings_link );

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'lana_security_add_plugin_action_links' );

/**
 * Install Lana Security
 * - create security log table
 * - create login log table
 */
function lana_security_install() {
	lana_security_create_security_logs_table();
	lana_security_create_login_logs_table();
}

register_activation_hook( __FILE__, 'lana_security_install' );

/**
 * Activate Lana Security
 * add security log
 */
function lana_security_activate_log() {
	lana_security_add_security_log_to_wpdb( get_current_user_id(), 'lana_security_plugin_activated' );
}

register_activation_hook( __FILE__, 'lana_security_activate_log' );

/**
 * Deactivate Lana Security
 * add security log
 */
function lana_security_deactivate_log() {
	lana_security_add_security_log_to_wpdb( get_current_user_id(), 'lana_security_plugin_deactivated' );
}

register_deactivation_hook( __FILE__, 'lana_security_deactivate_log' );

/**
 * Create security logs table
 */
function lana_security_create_security_logs_table() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name      = $wpdb->prefix . 'lana_security_logs';

	/** create table */
	$wpdb->query( "CREATE TABLE IF NOT EXISTS " . $table_name . " (
	  id bigint(20) NOT NULL auto_increment,
	  user_id bigint(20) DEFAULT NULL,
	  username varchar(255) DEFAULT NULL,
	  user_ip varchar(255) NOT NULL,
	  user_agent varchar(255) NOT NULL,
	  comment text NOT NULL,
	  date datetime DEFAULT NULL,
	  PRIMARY KEY (id)
	) " . $charset_collate . ";" );
}

/**
 * Create login logs table
 */
function lana_security_create_login_logs_table() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name      = $wpdb->prefix . 'lana_security_login_logs';

	/** create table */
	$wpdb->query( "CREATE TABLE IF NOT EXISTS " . $table_name . " (
	  id bigint(20) NOT NULL auto_increment,
	  username varchar(255) DEFAULT NULL,
	  status int(1) NOT NULL,
	  comment text NOT NULL,
	  user_ip varchar(255) NOT NULL,
	  user_agent varchar(255) NOT NULL,
	  date datetime DEFAULT NULL,
	  PRIMARY KEY (id)
	) " . $charset_collate . ";" );
}

/**
 * Lana Security
 * session start
 */
function lana_security_register_session() {
	if ( ! session_id() ) {
		session_start();
	}
}

/**
 * Add Lana Security
 * custom wp roles
 */
function lana_security_custom_wp_roles() {

	/**
	 * Administrator
	 * role
	 */
	$administrator_role = get_role( 'administrator' );

	if ( is_a( $administrator_role, 'WP_Role' ) ) {
		$administrator_role->add_cap( 'manage_lana_security_logs' );
		$administrator_role->add_cap( 'manage_lana_security_login_logs' );
	}
}

add_action( 'admin_init', 'lana_security_custom_wp_roles' );

/**
 * Lana Security - Settings page
 * load admin scripts
 *
 * @param $hook
 */
function lana_security_settings_admin_scripts( $hook ) {

	if ( 'lana-security_page_lana-security-settings' != $hook ) {
		return;
	}

	/** lana security settings admin js */
	wp_register_script( 'lana-security-settings-admin', LANA_SECURITY_DIR_URL . '/assets/js/lana-security-settings-admin.js', array( 'jquery' ), LANA_SECURITY_VERSION, true );
	wp_enqueue_script( 'lana-security-settings-admin' );
}

add_action( 'admin_enqueue_scripts', 'lana_security_settings_admin_scripts' );

/**
 * Lana Security - Settings page
 * load admin styles
 *
 * @param $hook
 */
function lana_security_settings_admin_styles( $hook ) {

	if ( 'lana-security_page_lana-security-settings' != $hook ) {
		return;
	}

	/** lana security settings admin css */
	wp_register_style( 'lana-security-settings-admin', LANA_SECURITY_DIR_URL . '/assets/css/lana-security-settings-admin.css', array(), LANA_SECURITY_VERSION );
	wp_enqueue_style( 'lana-security-settings-admin' );
}

add_action( 'admin_enqueue_scripts', 'lana_security_settings_admin_styles' );

/**
 * Login styles
 */
function lana_security_login_styles() {
	wp_register_style( 'lana-security-login', LANA_SECURITY_DIR_URL . '/assets/css/lana-security-login.css', array(), LANA_SECURITY_VERSION );
	wp_enqueue_style( 'lana-security-login' );
}

add_action( 'login_enqueue_scripts', 'lana_security_login_styles' );

/**
 * Lana Security
 * add admin page
 */
function lana_security_admin_menu() {
	global $lana_security_plugins_page;
	global $lana_security_logs_page;
	global $lana_security_login_logs_page;

	/** Lana Security page (Plugins page) */
	$lana_security_plugins_page = add_menu_page( __( 'Lana Security', 'lana-security' ), __( 'Lana Security', 'lana-security' ), 'manage_options', 'lana-security.php', 'lana_security_plugins_page', 'dashicons-shield-alt', 81 );

	/** add screen options */
	add_action( 'load-' . $lana_security_plugins_page, 'lana_security_plugins_page_screen_options' );

	/** Security Logs page */
	$lana_security_logs_page = add_submenu_page( 'lana-security.php', __( 'Security Logs', 'lana-security' ), __( 'Security Logs', 'lana-security' ), 'manage_lana_security_logs', 'lana-security-logs.php', 'lana_security_logs_page' );

	/** add screen options */
	add_action( 'load-' . $lana_security_logs_page, 'lana_security_logs_page_screen_options' );

	/** Login Logs page */
	$lana_security_login_logs_page = add_submenu_page( 'lana-security.php', __( 'Login Logs', 'lana-security' ), __( 'Login Logs', 'lana-security' ), 'manage_lana_security_login_logs', 'lana-security-login-logs.php', 'lana_security_login_logs_page' );

	/** add screen options */
	add_action( 'load-' . $lana_security_login_logs_page, 'lana_security_login_logs_page_screen_options' );

	/** Settings page */
	add_submenu_page( 'lana-security.php', __( 'Settings', 'lana-security' ), __( 'Settings', 'lana-security' ), 'manage_options', 'lana-security-settings.php', 'lana_security_settings_page' );

	/** call register settings function */
	add_action( 'admin_init', 'lana_security_register_settings' );
}

add_action( 'admin_menu', 'lana_security_admin_menu', 12 );

/**
 * Lana Security
 * plugins page screen options - add per page option
 */
function lana_security_plugins_page_screen_options() {
	global $lana_security_plugins_page;

	$screen = get_current_screen();

	if ( $screen->id != $lana_security_plugins_page ) {
		return;
	}

	$args = array(
		'label'   => __( 'Plugins per page', 'lana-security' ),
		'default' => 10,
		'option'  => 'lana_security_plugins_per_page'
	);
	add_screen_option( 'per_page', $args );
}

/**
 * Lana Security
 * logs page screen options - add per page option
 */
function lana_security_logs_page_screen_options() {
	global $lana_security_logs_page;

	$screen = get_current_screen();

	if ( $screen->id != $lana_security_logs_page ) {
		return;
	}

	$args = array(
		'label'   => __( 'Logs per page', 'lana-security' ),
		'default' => 25,
		'option'  => 'lana_security_logs_per_page'
	);
	add_screen_option( 'per_page', $args );
}

/**
 * Lana Security
 * login logs page screen options - add per page option
 */
function lana_security_login_logs_page_screen_options() {
	global $lana_security_login_logs_page;

	$screen = get_current_screen();

	if ( $screen->id != $lana_security_login_logs_page ) {
		return;
	}

	$args = array(
		'label'   => __( 'Logs per page', 'lana-security' ),
		'default' => 25,
		'option'  => 'lana_security_login_logs_per_page'
	);
	add_screen_option( 'per_page', $args );
}

/**
 * Lana Security
 * logs page - set screen options
 *
 * @param $screen_value
 * @param $option
 * @param $value
 *
 * @return mixed
 */
function lana_security_logs_page_set_screen_option( $screen_value, $option, $value ) {

	if ( 'lana_security_plugins_per_page' == $option ) {
		$screen_value = $value;
	}

	if ( 'lana_security_logs_per_page' == $option ) {
		$screen_value = $value;
	}

	if ( 'lana_security_login_logs_per_page' == $option ) {
		$screen_value = $value;
	}

	return $screen_value;
}

add_filter( 'set-screen-option', 'lana_security_logs_page_set_screen_option', 10, 3 );

/**
 * Register settings
 */
function lana_security_register_settings() {
	global $lana_security_settings;

	register_setting( 'lana-security-settings-group', 'lana_security_encrypt_version' );
	register_setting( 'lana-security-settings-group', 'lana_security_insecure_files' );
	register_setting( 'lana-security-settings-group', 'lana_security_login_captcha' );
	register_setting( 'lana-security-settings-group', 'lana_security_register_captcha' );
	register_setting( 'lana-security-settings-group', 'lana_security_lostpassword_captcha' );
	register_setting( 'lana-security-settings-group', 'lana_security_logs' );
	register_setting( 'lana-security-settings-group', 'lana_security_logs_cleanup_by_amount' );
	register_setting( 'lana-security-settings-group', 'lana_security_logs_cleanup_amount' );
	register_setting( 'lana-security-settings-group', 'lana_security_logs_cleanup_by_time' );
	register_setting( 'lana-security-settings-group', 'lana_security_logs_cleanup_time' );
	register_setting( 'lana-security-settings-group', 'lana_security_login_logs' );
	register_setting( 'lana-security-settings-group', 'lana_security_login_logs_cleanup_by_amount' );
	register_setting( 'lana-security-settings-group', 'lana_security_login_logs_cleanup_amount' );
	register_setting( 'lana-security-settings-group', 'lana_security_login_logs_cleanup_by_time' );
	register_setting( 'lana-security-settings-group', 'lana_security_login_logs_cleanup_time' );

	$lana_security_settings = array(
		'lana_security_encrypt_version'      => array(
			'id'          => 'lana_security_encrypt_version',
			'option'      => get_option( 'lana_security_encrypt_version', false ),
			'label'       => __( 'Encrypt Version', 'lana-security' ),
			'description' => __( 'Encrypt WordPress version in frontend scripts and styles, and remove generator', 'lana-security' ),
			'version'     => '1.0.0'
		),
		'lana_security_insecure_files'       => array(
			'id'          => 'lana_security_insecure_files',
			'option'      => get_option( 'lana_security_insecure_files', false ),
			'label'       => __( 'Insecure Files', 'lana-security' ),
			'description' => __( 'Block insecure files (readme.html, license.txt) with htaccess', 'lana-security' ),
			'version'     => '1.0.0'
		),
		'lana_security_login_captcha'        => array(
			'id'          => 'lana_security_login_captcha',
			'option'      => get_option( 'lana_security_login_captcha', false ),
			'label'       => __( 'Login Captcha', 'lana-security' ),
			'description' => __( 'Add simple number captcha in WordPress login form', 'lana-security' ),
			'version'     => '1.0.1'
		),
		'lana_security_register_captcha'     => array(
			'id'          => 'lana_security_register_captcha',
			'option'      => get_option( 'lana_security_register_captcha', false ),
			'label'       => __( 'Registration Captcha', 'lana-security' ),
			'description' => __( 'Add simple number captcha in WordPress registration form', 'lana-security' ),
			'version'     => '1.0.1'
		),
		'lana_security_lostpassword_captcha' => array(
			'id'          => 'lana_security_lostpassword_captcha',
			'option'      => get_option( 'lana_security_lostpassword_captcha', false ),
			'label'       => __( 'Lost Password Captcha', 'lana-security' ),
			'description' => __( 'Add simple number captcha in WordPress lost password form', 'lana-security' ),
			'version'     => '1.0.1'
		),
		'lana_security_logs'                 => array(
			'id'          => 'lana_security_logs',
			'option'      => get_option( 'lana_security_logs', false ),
			'label'       => __( 'Security Logs', 'lana-security' ),
			'description' => __( 'Monitors: activate and deactivate Lana Security plugin, password change (roles: only administrator), delete user (roles: all)', 'lana-security' ),
			'settings'    => '#lana-security-logs',
			'version'     => '1.0.1'
		),
		'lana_security_login_logs'           => array(
			'id'          => 'lana_security_login_logs',
			'option'      => get_option( 'lana_security_login_logs', false ),
			'label'       => __( 'Login Logs', 'lana-security' ),
			'description' => __( 'Monitors: success and failed login with comment', 'lana-security' ),
			'settings'    => '#lana-security-login-logs',
			'version'     => '1.0.0'
		)
	);
}

/**
 * Lana Security
 * plugins page
 */
function lana_security_plugins_page() {
	global $lana_security_settings;

	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once LANA_SECURITY_DIR_PATH . '/includes/class-lana-security-plugins-list-table.php';

	$lana_security_plugins_list_table = new Lana_Security_Plugins_List_Table();

	/** manage actions */
	$action               = $lana_security_plugins_list_table->current_action();
	$lana_security_plugin = isset( $_REQUEST['lana_security_plugin'] ) ? wp_unslash( $_REQUEST['lana_security_plugin'] ) : '';

	if ( $action && is_string( $lana_security_plugin ) ) {

		/** activate plugin */
		if ( 'activate' == $action ) {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Sorry, you are not allowed to manage this plugin.' ) );
			}

			check_admin_referer( $lana_security_plugin . '_plugin_activate' );

			if ( update_option( $lana_security_plugin, true ) ) {
				$lana_security_settings[ $lana_security_plugin ]['option'] = get_option( $lana_security_plugin );
				$lana_security_plugins_list_table->set_display_activated_message( true );
			}
		}

		/** deactivate plugin */
		if ( 'deactivate' == $action ) {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Sorry, you are not allowed to manage this plugin.' ) );
			}

			check_admin_referer( $lana_security_plugin . '_plugin_deactivate' );

			if ( update_option( $lana_security_plugin, false ) ) {
				$lana_security_settings[ $lana_security_plugin ]['option'] = get_option( $lana_security_plugin );
				$lana_security_plugins_list_table->set_display_deactivated_message( true );
			}
		}
	}

	/** prepare items */
	$lana_security_plugins_list_table->prepare_items();
	?>
    <div class="wrap">
        <h2>
			<?php _e( 'Security Plugins', 'lana-security' ); ?>
        </h2>
        <br/>

        <form id="lana-security-plugins-form" method="post">
			<?php $lana_security_plugins_list_table->display(); ?>
        </form>
    </div>
	<?php
}

/**
 * Lana Security
 * logs page
 */
function lana_security_logs_page() {
	if ( ! get_option( 'lana_security_logs', false ) ) :
		?>
        <div class="wrap">
            <h2><?php _e( 'Security Logs', 'lana-security' ); ?></h2>

            <p><?php printf( __( 'Logs are disabled. Go to the <a href="%s">Settings</a> page to enable.', 'lana-security' ), esc_url( admin_url( 'admin.php?page=lana-security-settings.php' ) ) ); ?></p>
        </div>
		<?php

		return;
	endif;

	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once LANA_SECURITY_DIR_PATH . '/includes/class-lana-security-logs-list-table.php';

	$lana_security_logs_list_table = new Lana_Security_Logs_List_Table();

	/** manage actions */
	$action = $lana_security_logs_list_table->current_action();

	if ( $action ) {

		/** delete logs */
		if ( 'delete_logs' == $action ) {

			if ( ! current_user_can( 'manage_lana_security_logs' ) ) {
				wp_die( __( 'Sorry, you are not allowed to delete logs.', 'lana-security' ) );
			}

			check_admin_referer( 'bulk-lana_security_logs' );

			$table_name = $wpdb->prefix . 'lana_security_logs';
			$wpdb->query( "TRUNCATE TABLE " . $table_name . ";" );
		}
	}

	/** prepare items */
	$lana_security_logs_list_table->prepare_items();
	?>
    <div class="wrap">
        <h2>
			<?php _e( 'Security Logs', 'lana-security' ); ?>
        </h2>
        <br/>

        <form id="lana-security-logs-form" method="post">
			<?php $lana_security_logs_list_table->display(); ?>
        </form>
    </div>
	<?php
}

/**
 * Lana Security
 * login logs page
 */
function lana_security_login_logs_page() {
	if ( ! get_option( 'lana_security_login_logs', false ) ) :
		?>
        <div class="wrap">
            <h2><?php _e( 'Login Logs', 'lana-security' ); ?></h2>

            <p><?php printf( __( 'Logs are disabled. Go to the <a href="%s">Settings</a> page to enable.', 'lana-security' ), esc_url( admin_url( 'admin.php?page=lana-security-settings.php' ) ) ); ?></p>
        </div>
		<?php

		return;
	endif;

	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once LANA_SECURITY_DIR_PATH . '/includes/class-lana-security-login-logs-list-table.php';

	$lana_security_login_logs_list_table = new Lana_Security_Login_Logs_List_Table();

	/** manage actions */
	$action = $lana_security_login_logs_list_table->current_action();

	if ( $action ) {

		/** delete logs */
		if ( 'delete_logs' == $action ) {

			if ( ! current_user_can( 'manage_lana_security_login_logs' ) ) {
				wp_die( __( 'Sorry, you are not allowed to delete login logs.', 'lana-security' ) );
			}

			check_admin_referer( 'bulk-lana_security_login_logs' );

			$table_name = $wpdb->prefix . 'lana_security_login_logs';
			$wpdb->query( "TRUNCATE TABLE " . $table_name . ";" );
		}
	}

	/** prepare items */
	$lana_security_login_logs_list_table->prepare_items();
	?>
    <div class="wrap">
        <h2>
			<?php _e( 'Login Logs', 'lana-security' ); ?>
        </h2>
        <br/>

        <form id="lana-security-login-logs-form" method="post">
			<?php $lana_security_login_logs_list_table->display(); ?>
        </form>
    </div>
	<?php
}

/**
 * Lana Security
 * settings page
 */
function lana_security_settings_page() {
	?>
    <div class="wrap">
        <h2><?php _e( 'Lana Security Settings', 'lana-security' ); ?></h2>

		<?php settings_errors(); ?>

        <hr/>
        <a href="<?php echo esc_url( 'http://lana.codes/' ); ?>" target="_blank">
            <img src="<?php echo esc_url( LANA_SECURITY_DIR_URL . '/assets/img/plugin-header.png' ); ?>"
                 alt="<?php esc_attr_e( 'Lana Codes', 'lana-security' ); ?>"/>
        </a>
        <hr/>

        <form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php settings_fields( 'lana-security-settings-group' ); ?>

            <h2 class="title"><?php _e( 'Security Settings', 'lana-security' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="lana-security-encrypt-version">
							<?php _e( 'Encrypt Version', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_encrypt_version" id="lana-security-encrypt-version">
                            <option value="0"
								<?php selected( get_option( 'lana_security_encrypt_version', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_encrypt_version', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                        <p class="description">
							<?php _e( 'Encrypt WordPress version in frontend scripts and styles, and remove generator', 'lana-security' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lana-security-insecure-files">
							<?php _e( 'Insecure Files', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_insecure_files" id="lana-security-insecure-files">
                            <option value="0"
								<?php selected( get_option( 'lana_security_insecure_files', true ), false ); ?>>
								<?php _e( 'Deny', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_insecure_files', true ), true ); ?>>
								<?php _e( 'Allow', 'lana-security' ); ?>
                            </option>
                        </select>
                        <p class="description">
							<?php _e( 'Block insecure files (readme.html, license.txt) with htaccess', 'lana-security' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php _e( 'Login Settings', 'lana-security' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="lana-security-login-captcha">
							<?php _e( 'Login Captcha', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_login_captcha" id="lana-security-login-captcha">
                            <option value="0"
								<?php selected( get_option( 'lana_security_login_captcha', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_login_captcha', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lana-security-register-captcha">
							<?php _e( 'Registration Captcha', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_register_captcha" id="lana-security-register-captcha">
                            <option value="0"
								<?php selected( get_option( 'lana_security_register_captcha', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_register_captcha', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lana-security-lostpassword-captcha">
							<?php _e( 'Lost Password Captcha', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_lostpassword_captcha" id="lana-security-lostpassword-captcha">
                            <option value="0"
								<?php selected( get_option( 'lana_security_lostpassword_captcha', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_lostpassword_captcha', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php _e( 'Log Settings', 'lana-security' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="lana-security-logs">
							<?php _e( 'Security Logs', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_logs" id="lana-security-logs"
                                data-tr-target=".logs, .logs.cleanup-amount, .logs.cleanup-time">
                            <option value="0"
								<?php selected( get_option( 'lana_security_logs', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_logs', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="logs cleanup-by">
                    <th scope="row">
                        <label for="lana-security-logs-cleanup-by-amount">
							<?php _e( 'Cleanup by Amount', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_logs_cleanup_by_amount"
                                id="lana-security-logs-cleanup-by-amount" data-tr-target=".logs.cleanup-amount">
                            <option value="0"
								<?php selected( get_option( 'lana_security_logs_cleanup_by_amount', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_logs_cleanup_by_amount', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="logs cleanup cleanup-amount">
                    <th scope="row">
                        <label for="lana-security-logs-cleanup-amount">
							<?php _e( 'Cleanup Amount', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="lana_security_logs_cleanup_amount"
                               id="lana-security-logs-cleanup-amount"
                               value="<?php echo esc_attr( get_option( 'lana_security_logs_cleanup_amount' ) ); ?>">
                        <p class="description">
							<?php _e( 'Deletes logs that exceed the set value.', 'lana-security' ); ?>
                        </p>
                    </td>
                </tr>
                <tr class="logs cleanup-by">
                    <th scope="row">
                        <label for="lana-security-logs-cleanup-by-time">
							<?php _e( 'Cleanup by Time', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_logs_cleanup_by_time"
                                id="lana-security-logs-cleanup-by-time" data-tr-target=".logs.cleanup-time">
                            <option value="0"
								<?php selected( get_option( 'lana_security_logs_cleanup_by_time', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_logs_cleanup_by_time', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="logs cleanup cleanup-time">
                    <th scope="row">
                        <label for="lana-security-logs-cleanup-time">
							<?php _e( 'Cleanup Time', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="lana_security_logs_cleanup_time"
                               id="lana-security-logs-cleanup-time"
                               value="<?php echo esc_attr( get_option( 'lana_security_logs_cleanup_time' ) ); ?>">
                        <p class="description">
							<?php _e( 'Deletes logs that are older than the set days.', 'lana-security' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lana-security-login-logs">
							<?php _e( 'Login Logs', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_login_logs" id="lana-security-login-logs"
                                data-tr-target=".login-logs.cleanup, .login-logs.cleanup-by">
                            <option value="0"
								<?php selected( get_option( 'lana_security_login_logs', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_login_logs', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="login-logs cleanup-by">
                    <th scope="row">
                        <label for="lana-security-login-logs-cleanup-by-amount">
							<?php _e( 'Cleanup by Amount', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_login_logs_cleanup_by_amount"
                                id="lana-security-login-logs-cleanup-by-amount"
                                data-tr-target=".login-logs.cleanup-amount">
                            <option value="0"
								<?php selected( get_option( 'lana_security_login_logs_cleanup_by_amount', true ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_login_logs_cleanup_by_amount', true ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="login-logs cleanup cleanup-amount">
                    <th scope="row">
                        <label for="lana-security-login-logs-cleanup-amount">
							<?php _e( 'Cleanup Amount', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="lana_security_login_logs_cleanup_amount"
                               id="lana-security-login-logs-cleanup-amount"
                               value="<?php echo esc_attr( get_option( 'lana_security_login_logs_cleanup_amount', LANA_SECURITY_DEFAULT_LOGIN_LOGS_CLEANUP_AMOUNT ) ); ?>">
                        <p class="description">
							<?php _e( 'Deletes logs that exceed the set value.', 'lana-security' ); ?>
                        </p>
                    </td>
                </tr>
                <tr class="login-logs cleanup-by">
                    <th scope="row">
                        <label for="lana-security-login-logs-cleanup-by-time">
							<?php _e( 'Cleanup by Time', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="lana_security_login_logs_cleanup_by_time"
                                id="lana-security-login-logs-cleanup-by-time" data-tr-target=".login-logs.cleanup-time">
                            <option value="0"
								<?php selected( get_option( 'lana_security_login_logs_cleanup_by_time', false ), false ); ?>>
								<?php _e( 'Disabled', 'lana-security' ); ?>
                            </option>
                            <option value="1"
								<?php selected( get_option( 'lana_security_login_logs_cleanup_by_time', false ), true ); ?>>
								<?php _e( 'Enabled', 'lana-security' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="login-logs cleanup cleanup-time">
                    <th scope="row">
                        <label for="lana-security-login-logs-cleanup-time">
							<?php _e( 'Cleanup Time', 'lana-security' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="lana_security_login_logs_cleanup_time"
                               id="lana-security-login-logs-cleanup-time"
                               value="<?php echo esc_attr( get_option( 'lana_security_login_logs_cleanup_time' ) ); ?>">
                        <p class="description">
							<?php _e( 'Deletes logs that are older than the set days.', 'lana-security' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary"
                       value="<?php esc_attr_e( 'Save Changes', 'lana-security' ); ?>"/>
            </p>

        </form>
    </div>
	<?php
}

/**
 * Replace WordPress version in script and style src
 *
 * @param $src
 *
 * @return string
 */
function lana_security_replace_wp_version_strings( $src ) {
	global $wp_styles, $wp_version;

	if ( ! get_option( 'lana_security_encrypt_version', false ) ) {
		return $src;
	}

	if ( is_a( $wp_styles, 'WP_Styles' ) ) {
		$wp_styles->default_version = crc32( $wp_styles->default_version );
	}

	parse_str( parse_url( $src, PHP_URL_QUERY ), $query );

	if ( ! empty( $query['ver'] ) && $query['ver'] === $wp_version ) {
		$crypted_wp_version = crc32( $wp_version );
		$src                = add_query_arg( 'ver', $crypted_wp_version, $src );
	}

	return $src;
}

add_filter( 'script_loader_src', 'lana_security_replace_wp_version_strings', 1019 );
add_filter( 'style_loader_src', 'lana_security_replace_wp_version_strings', 1019 );

/**
 * Hide WordPress version strings from generator meta tag
 *
 * @param $type
 *
 * @return string
 */
function lana_security_remove_the_generator( $type ) {

	if ( ! get_option( 'lana_security_encrypt_version', false ) ) {
		return $type;
	}

	return '';
}

add_filter( 'the_generator', 'lana_security_remove_the_generator' );

/**
 * Clean up wp_head() from unused or unsecure stuff
 */
function lana_security_remove_unsecure_head() {

	if ( ! get_option( 'lana_security_encrypt_version', false ) ) {
		return;
	}

	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'index_rel_link' );
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
}

add_filter( 'init', 'lana_security_remove_unsecure_head' );

/**
 * Lana Security
 * deny insecure files (readme.html, license.txt)
 *
 * @param $rules
 *
 * @return string
 */
function lana_security_deny_insecure_files( $rules ) {

	if ( ! get_option( 'lana_security_insecure_files', true ) ) {
		return $rules;
	}

	$htaccess = PHP_EOL;
	$htaccess .= '#BEGIN Lana Security' . PHP_EOL;

	/** block readme.html */
	$htaccess .= '<Files "readme.html">' . PHP_EOL;
	$htaccess .= '  order deny,allow' . PHP_EOL;
	$htaccess .= '  deny from all' . PHP_EOL;
	$htaccess .= '</Files>' . PHP_EOL;

	/** block license.txt */
	$htaccess .= '<Files "license.txt">' . PHP_EOL;
	$htaccess .= '  order deny,allow' . PHP_EOL;
	$htaccess .= '  deny from all' . PHP_EOL;
	$htaccess .= '</Files>' . PHP_EOL;

	$htaccess .= '#END Lana Security' . PHP_EOL;
	$htaccess .= PHP_EOL;

	return $htaccess . $rules;
}

add_filter( 'mod_rewrite_rules', 'lana_security_deny_insecure_files' );

/**
 * Lana Security
 * Flush rewrite rules after option update
 *
 * @param $option
 */
function lana_security_rewrite_rules_flush( $option ) {
	global $wp_rewrite;

	if ( 'lana_security_insecure_files' != $option ) {
		return;
	}

	$wp_rewrite->flush_rules();
	$wp_rewrite->init();
}

add_action( 'update_option', 'lana_security_rewrite_rules_flush', 100, 1 );

/**
 * Lana Security
 * get captcha
 * @return string
 */
function lana_security_get_captcha() {

	error_reporting( 0 );
	@lana_security_register_session();

	$image = imagecreatetruecolor( 70, 30 );
	$white = imagecolorallocate( $image, 255, 255, 255 );
	$black = imagecolorallocate( $image, 0, 0, 0 );
	$num1  = rand( 1, 9 );
	$num2  = rand( 1, 9 );
	$str   = $num1 . ' + ' . $num2 . ' = ';
	$font  = dirname( __FILE__ ) . '/assets/fonts/bebas.ttf';

	imagefill( $image, 0, 0, $white );
	imagettftext( $image, 18, 0, 0, 24, $black, $font, $str );

	ob_start();
	imagepng( $image );
	$image_data = ob_get_clean();
	imagedestroy( $image );

	$_SESSION['lana_security']['captcha'] = $num1 + $num2;

	return $image_data;
}

/**
 * Lana Security
 * get base64 encoded captcha
 * @return string
 */
function lana_security_get_base64_captcha() {
	return base64_encode( lana_security_get_captcha() );
}

/**
 * Lana Security
 * Register session in login
 */
function lana_security_register_session_in_login() {
	@lana_security_register_session();
}

add_action( 'login_init', 'lana_security_register_session_in_login' );

/**
 * Lana Security
 * Add captcha to login form
 */
function lana_security_add_captcha_to_login_form() {

	if ( ! get_option( 'lana_security_login_captcha', false ) ) {
		return;
	}
	?>
    <p class="lana-captcha">
        <label for="captcha">
			<?php _e( 'Captcha', 'lana-security' ); ?>
            <br/>
            <img src="data:image/png;base64,<?php echo esc_attr( lana_security_get_base64_captcha() ); ?>"
                 class="captcha-image"/>
            <input type="number" name="lana_captcha" id="captcha" class="input captcha-input" size="2" min="0" max="20"
                   required/>
        </label>
    </p>
	<?php
}

add_action( 'login_form', 'lana_security_add_captcha_to_login_form' );

/**
 * Lana Security
 * Add captcha to register form
 */
function lana_security_add_captcha_to_register_form() {

	if ( ! get_option( 'lana_security_register_captcha', false ) ) {
		return;
	}

	?>
    <p class="lana-captcha">
        <label for="captcha">
			<?php _e( 'Captcha', 'lana-security' ); ?>
            <br>
            <img src="data:image/png;base64,<?php echo esc_attr( lana_security_get_base64_captcha() ); ?>"
                 class="captcha-image"/>
            <input type="number" name="lana_captcha" id="captcha" class="input captcha-input" size="2" min="0" max="20"
                   required/>
        </label>
    </p>
	<?php
}

add_action( 'register_form', 'lana_security_add_captcha_to_register_form' );

/**
 * Lana Security
 * Add captcha to lostpassword form
 */
function lana_security_add_captcha_to_lostpassword_form() {

	if ( ! get_option( 'lana_security_lostpassword_captcha', false ) ) {
		return;
	}

	?>
    <p class="lana-captcha">
        <label for="captcha">
			<?php _e( 'Captcha', 'lana-security' ); ?>
            <br/>
            <img src="data:image/png;base64,<?php echo esc_attr( lana_security_get_base64_captcha() ); ?>"
                 class="captcha-image"/>
            <input type="number" name="lana_captcha" id="captcha" class="input captcha-input" size="2" min="0" max="20"
                   required/>
        </label>
    </p>
	<?php
}

add_action( 'lostpassword_form', 'lana_security_add_captcha_to_lostpassword_form' );

/**
 * Lana Security
 * Verify the login captcha
 *
 * @param WP_user $user
 *
 * @return WP_Error|WP_user
 */
function lana_security_validate_login_captcha( $user ) {

	if ( ! get_option( 'lana_security_login_captcha', false ) ) {
		return $user;
	}

	if ( is_wp_error( $user ) ) {
		return $user;
	}

	@lana_security_register_session();

	if ( ! isset( $_POST['lana_captcha'] ) ) {
		return new WP_Error( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha field is not set.', 'lana-security' )
		) ) );
	}

	if ( empty( $_POST['lana_captcha'] ) ) {
		return new WP_Error( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha field is empty.', 'lana-security' )
		) ) );
	}

	if ( ! isset( $_SESSION['lana_security']['captcha'] ) ) {
		return new WP_Error( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha is not set.', 'lana-security' )
		) ) );
	}

	if ( $_POST['lana_captcha'] != $_SESSION['lana_security']['captcha'] ) {
		return new WP_Error( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha is incorrect.', 'lana-security' )
		) ) );
	}

	return $user;
}

add_filter( 'authenticate', 'lana_security_validate_login_captcha', 100, 1 );

/**
 * Lana Security
 * Verify the register captcha
 *
 * @param $sanitized_user_login
 * @param $user_email
 * @param WP_Error $errors
 */
function lana_security_validate_register_captcha( $sanitized_user_login, $user_email, $errors ) {

	if ( ! get_option( 'lana_security_register_captcha', false ) ) {
		return;
	}

	@lana_security_register_session();

	if ( ! isset( $_POST['lana_captcha'] ) ) {
		$errors->add( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha field is not set.', 'lana-security' )
		) ) );
	}

	if ( empty( $_POST['lana_captcha'] ) ) {
		$errors->add( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha field is empty.', 'lana-security' )
		) ) );
	}

	if ( ! isset( $_SESSION['lana_security']['captcha'] ) ) {
		$errors->add( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha is not set.', 'lana-security' )
		) ) );
	}

	if ( $_POST['lana_captcha'] != $_SESSION['lana_security']['captcha'] ) {
		$errors->add( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha is incorrect.', 'lana-security' )
		) ) );
	}
}

add_action( 'register_post', 'lana_security_validate_register_captcha', 100, 3 );

/**
 * Lana Security
 * Verify the lostpassword captcha
 *
 * @param WP_Error $errors
 */
function lana_security_validate_lostpassword_captcha( $errors ) {

	if ( ! get_option( 'lana_security_lostpassword_captcha', false ) ) {
		return;
	}

	@lana_security_register_session();

	if ( ! isset( $_POST['lana_captcha'] ) ) {
		$errors->add( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha field is not set.', 'lana-security' )
		) ) );
	}

	if ( empty( $_POST['lana_captcha'] ) ) {
		$errors->add( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha field is empty.', 'lana-security' )
		) ) );
	}

	if ( ! isset( $_SESSION['lana_security']['captcha'] ) ) {
		$errors->add( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha is not set.', 'lana-security' )
		) ) );
	}

	if ( $_POST['lana_captcha'] != $_SESSION['lana_security']['captcha'] ) {
		$errors->add( 'error_captcha', vsprintf( '<strong>%s</strong> %s', array(
			__( 'ERROR:', 'lana-security' ),
			__( 'The captcha is incorrect.', 'lana-security' )
		) ) );
	}
}

add_action( 'lostpassword_post', 'lana_security_validate_lostpassword_captcha', 100, 1 );

/**
 * Lana Security
 * Add login log to database
 *
 * @param $user
 * @param $username
 * @param $password
 *
 * @return mixed
 */
function lana_security_add_login_log( $user, $username, $password ) {

	if ( empty( $username ) || empty( $password ) ) {
		return $user;
	}

	if ( ! get_option( 'lana_security_login_logs', false ) ) {
		return $user;
	}

	if ( is_a( $user, 'WP_Error' ) ) {
		foreach ( $user->errors as $error => $description ) {
			lana_security_add_login_log_to_wpdb( $username, 0, $error );
		}
	}

	if ( is_a( $user, 'WP_User' ) ) {
		lana_security_add_login_log_to_wpdb( $username, 1 );
	}

	return $user;
}

add_filter( 'authenticate', 'lana_security_add_login_log', 101, 3 );

/**
 * Lana Security
 * Add admin change password log to database
 *
 * @param $user_id
 */
function lana_security_add_admin_change_password_log( $user_id ) {

	if ( ! isset( $_POST['pass1'] ) || '' == $_POST['pass1'] ) {
		return;
	}

	/** only administrator */
	if ( ! user_can( $user_id, 'administrator' ) ) {
		return;
	}

	$user = wp_get_current_user();
	lana_security_add_security_log_to_wpdb( $user_id, sprintf( 'admin_password_changed_by_%%%s%%', $user->user_login ) );
}

add_action( 'profile_update', 'lana_security_add_admin_change_password_log', 100, 1 );

/**
 * Lana Security
 * Add user deleted log to database
 *
 * @param $user_id
 */
function lana_security_add_user_deleted_log( $user_id ) {
	$user = wp_get_current_user();
	lana_security_add_security_log_to_wpdb( $user_id, sprintf( 'user_deleted_by_%%%s%%', $user->user_login ) );
}

add_action( 'delete_user', 'lana_security_add_user_deleted_log', 100, 1 );

/**
 * Lana Security
 * add security log to database
 *
 * @param $user_id
 * @param $comment
 */
function lana_security_add_security_log_to_wpdb( $user_id, $comment = '' ) {
	global $wpdb;

	if ( get_option( 'lana_security_logs', false ) ) {

		$wpdb->hide_errors();

		$user     = get_userdata( $user_id );
		$username = $user->user_login;

		$wpdb->insert( $wpdb->prefix . 'lana_security_logs', array(
			'user_id'    => $user_id,
			'username'   => $username,
			'comment'    => $comment,
			'user_ip'    => sanitize_text_field( lana_security_get_user_ip() ),
			'user_agent' => sanitize_text_field( lana_security_get_user_agent() ),
			'date'       => current_time( 'mysql' )
		), array( '%s', '%s', '%s', '%s' ) );
	}
}

/**
 * Lana Security
 * add login log to database
 *
 * @param $username
 * @param $status
 * @param $comment
 */
function lana_security_add_login_log_to_wpdb( $username, $status, $comment = '' ) {
	global $wpdb;

	if ( get_option( 'lana_security_login_logs', false ) ) {

		$wpdb->hide_errors();

		$wpdb->insert( $wpdb->prefix . 'lana_security_login_logs', array(
			'username'   => $username,
			'status'     => $status,
			'comment'    => $comment,
			'user_ip'    => sanitize_text_field( lana_security_get_user_ip() ),
			'user_agent' => sanitize_text_field( lana_security_get_user_agent() ),
			'date'       => current_time( 'mysql' )
		), array( '%s', '%d', '%s', '%s', '%s' ) );
	}
}

/**
 * Get user IP
 * @return mixed
 */
function lana_security_get_user_ip() {

	$client  = @$_SERVER['HTTP_CLIENT_IP'];
	$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	$remote  = $_SERVER['REMOTE_ADDR'];

	if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
		$ip = $client;
	} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
		$ip = $forward;
	} else {
		$ip = $remote;
	}

	return $ip;
}

/**
 * Get user agent
 * @return mixed
 */
function lana_security_get_user_agent() {

	if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return '';
	}

	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return '';
	}

	return $_SERVER['HTTP_USER_AGENT'];
}

/**
 * Lana Security
 * update cleanup option - add schedule event
 *
 * @param $option
 */
function lana_security_update_cleanup_option_add_schedule_event( $option ) {

	$logs_cleanup_by_amount_update_options = array(
		'lana_security_logs_cleanup_by_amount',
		'lana_security_logs_cleanup_amount'
	);

	$logs_cleanup_by_time_update_options = array(
		'lana_security_logs_cleanup_by_time',
		'lana_security_logs_cleanup_time'
	);

	$login_logs_cleanup_by_amount_update_options = array(
		'lana_security_login_logs_cleanup_by_amount',
		'lana_security_login_logs_cleanup_amount'
	);

	$login_logs_cleanup_by_time_update_options = array(
		'lana_security_login_logs_cleanup_by_time',
		'lana_security_login_logs_cleanup_time'
	);

	if ( in_array( $option, $logs_cleanup_by_amount_update_options ) ) {
		lana_security_logs_cleanup_by_amount_schedule_event();
	}

	if ( in_array( $option, $logs_cleanup_by_time_update_options ) ) {
		lana_security_logs_cleanup_by_time_schedule_event();
	}

	if ( in_array( $option, $login_logs_cleanup_by_amount_update_options ) ) {
		lana_security_login_logs_cleanup_by_amount_schedule_event();
	}

	if ( in_array( $option, $login_logs_cleanup_by_time_update_options ) ) {
		lana_security_login_logs_cleanup_by_time_schedule_event();
	}
}

add_action( 'added_option', 'lana_security_update_cleanup_option_add_schedule_event' );
add_action( 'updated_option', 'lana_security_update_cleanup_option_add_schedule_event' );

/**
 * Lana Security
 * logs cleanup by amount - create a scheduled event
 */
function lana_security_logs_cleanup_by_amount_schedule_event() {
	if ( ! get_option( 'lana_security_logs_cleanup_by_amount', false ) ) {
		wp_clear_scheduled_hook( 'lana_security_logs_cleanup_by_amount' );

		return;
	}

	if ( ! wp_next_scheduled( 'lana_security_logs_cleanup_by_amount' ) ) {
		wp_schedule_event( time(), 'hourly', 'lana_security_logs_cleanup_by_amount' );
	}
}

add_action( 'plugins_loaded', 'lana_security_logs_cleanup_by_amount_schedule_event' );

/**
 * Lana Security
 * logs cleanup by time - create a scheduled event
 */
function lana_security_logs_cleanup_by_time_schedule_event() {
	if ( ! get_option( 'lana_security_logs_cleanup_by_time', false ) ) {
		wp_clear_scheduled_hook( 'lana_security_logs_cleanup_by_time' );

		return;
	}

	if ( ! wp_next_scheduled( 'lana_security_logs_cleanup_by_time' ) ) {
		wp_schedule_event( time(), 'hourly', 'lana_security_logs_cleanup_by_time' );
	}
}

add_action( 'plugins_loaded', 'lana_security_logs_cleanup_by_time_schedule_event' );

/**
 * Lana Security
 * login logs cleanup by amount - create a scheduled event
 */
function lana_security_login_logs_cleanup_by_amount_schedule_event() {
	if ( ! get_option( 'lana_security_login_logs_cleanup_by_amount', true ) ) {
		wp_clear_scheduled_hook( 'lana_security_login_logs_cleanup_by_amount' );

		return;
	}

	if ( ! wp_next_scheduled( 'lana_security_login_logs_cleanup_by_amount' ) ) {
		wp_schedule_event( time(), 'hourly', 'lana_security_login_logs_cleanup_by_amount' );
	}
}

add_action( 'plugins_loaded', 'lana_security_login_logs_cleanup_by_amount_schedule_event' );

/**
 * Lana Security
 * login logs cleanup by time - create a scheduled event
 */
function lana_security_login_logs_cleanup_by_time_schedule_event() {
	if ( ! get_option( 'lana_security_login_logs_cleanup_by_time', false ) ) {
		wp_clear_scheduled_hook( 'lana_security_login_logs_cleanup_by_time' );

		return;
	}

	if ( ! wp_next_scheduled( 'lana_security_login_logs_cleanup_by_time' ) ) {
		wp_schedule_event( time(), 'hourly', 'lana_security_login_logs_cleanup_by_time' );
	}
}

add_action( 'plugins_loaded', 'lana_security_login_logs_cleanup_by_time_schedule_event' );

/**
 * Lana Security
 * cleanup logs by amount - delete query
 *
 * @param $table_name
 * @param $cleanup_amount
 */
function lana_security_cleanup_logs_by_amount_delete_query( $table_name, $cleanup_amount ) {
	global $wpdb;

	$cleanup_amount = absint( $cleanup_amount );

	/** check amount */
	if ( $cleanup_amount <= 0 ) {
		return;
	}

	$table_name = $wpdb->prefix . $table_name;

	/** delete query */
	$wpdb->query( "DELETE lana_security_logs FROM " . $table_name . " AS lana_security_logs
						JOIN ( 
						    SELECT id FROM " . $table_name . " ORDER BY id DESC LIMIT 1 OFFSET " . $cleanup_amount . "
						) AS lana_security_logs_limit ON lana_security_logs.id <= lana_security_logs_limit.id;" );
}

/**
 * Lana Security
 * cleanup logs by time - delete query
 *
 * @param $table_name
 * @param $cleanup_time
 */
function lana_security_cleanup_logs_by_time_delete_query( $table_name, $cleanup_time ) {
	global $wpdb;

	$cleanup_time = absint( $cleanup_time );

	/** check time */
	if ( $cleanup_time <= 0 ) {
		return;
	}

	$table_name = $wpdb->prefix . $table_name;

	/** delete query */
	$wpdb->query( "DELETE FROM " . $table_name . " WHERE DATEDIFF( NOW(), date ) >= " . $cleanup_time . ";" );
}

/**
 * Lana Security
 * logs cleanup logs by amount
 */
function lana_security_logs_cleanup_logs_by_amount() {

	/** check by amount */
	if ( ! get_option( 'lana_security_logs_cleanup_by_amount', false ) ) {
		return;
	}

	lana_security_cleanup_logs_by_amount_delete_query( 'lana_security_logs', get_option( 'lana_security_logs_cleanup_amount' ) );
}

add_action( 'lana_security_logs_cleanup_by_amount', 'lana_security_logs_cleanup_logs_by_amount' );

/**
 * Lana Security
 * logs cleanup logs by time
 */
function lana_security_logs_cleanup_logs_by_time() {

	/** check by time */
	if ( ! get_option( 'lana_security_logs_cleanup_by_time', false ) ) {
		return;
	}

	lana_security_cleanup_logs_by_amount_delete_query( 'lana_security_logs', get_option( 'lana_security_logs_cleanup_time' ) );
}

add_action( 'lana_security_logs_cleanup_by_time', 'lana_security_logs_cleanup_logs_by_time' );

/**
 * Lana Security
 * login logs cleanup logs by amount
 */
function lana_security_login_logs_cleanup_logs_by_amount() {

	/** check by amount */
	if ( ! get_option( 'lana_security_login_logs_cleanup_by_amount', true ) ) {
		return;
	}

	lana_security_cleanup_logs_by_amount_delete_query( 'lana_security_login_logs', get_option( 'lana_security_login_logs_cleanup_amount', LANA_SECURITY_DEFAULT_LOGIN_LOGS_CLEANUP_AMOUNT ) );
}

add_action( 'lana_security_login_logs_cleanup_by_amount', 'lana_security_login_logs_cleanup_logs_by_amount' );

/**
 * Lana Security
 * login logs cleanup logs by time
 */
function lana_security_login_logs_cleanup_logs_by_time() {

	/** check by time */
	if ( ! get_option( 'lana_security_login_logs_cleanup_by_time', false ) ) {
		return;
	}

	lana_security_cleanup_logs_by_amount_delete_query( 'lana_security_login_logs', get_option( 'lana_security_login_logs_cleanup_time' ) );
}

add_action( 'lana_security_login_logs_cleanup_by_time', 'lana_security_login_logs_cleanup_logs_by_time' );