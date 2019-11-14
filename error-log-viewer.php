<?php
/*
Plugin Name: Error Log Viewer by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/error-log-viewer/
Description: Get latest error log messages to diagnose website problems. Define and fix issues faster.
Author: BestWebSoft
Text Domain: error-log-viewer
Domain Path: /languages
Version: 1.1.0
Author URI: https://bestwebsoft.com/
License: GNU General Public License V3
*/

/*  @ Copyright 2019  BestWebSoft  ( https://support.bestwebsoft.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
* Add Wordpress page 'bws_panel' and sub-page of this plugin to admin-panel.
* @return void
*/

/**
* Add option page in admin menu
*/
if ( ! function_exists( 'rrrlgvwr_admin_menu' ) ) {
	function rrrlgvwr_admin_menu() {
		bws_general_menu();
		$settings = add_submenu_page(
			'bws_panel',
			__( 'Error Log Viewer Settings', 'error-log-viewer' ),
			'Error Log Viewer',
			'manage_options',
			'rrrlgvwr.php',
			'rrrlgvwr_settings_page'
		);
		add_action( 'load-' . $settings, 'rrrlgvwr_add_tabs' );
	}
}

/**
 * Internationalization
 */
if ( ! function_exists( 'rrrlgvwr_plugins_loaded' ) ) {
	function rrrlgvwr_plugins_loaded() {
		load_plugin_textdomain( 'error-log-viewer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/**
* Plugin initialization
*/
if ( ! function_exists ( 'rrrlgvwr_init' ) ) {
	function rrrlgvwr_init() {
		global $rrrlgvwr_plugin_info;

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( ! $rrrlgvwr_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$rrrlgvwr_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $rrrlgvwr_plugin_info, '3.9' );
	}
}

/**
* Admin init
*/
if ( ! function_exists( 'rrrlgvwr_admin_init' ) ) {
	function rrrlgvwr_admin_init() {
		/* Add variable for bws_menu */
		global $bws_plugin_info, $rrrlgvwr_plugin_info;

		if ( empty( $bws_plugin_info ) )
			$bws_plugin_info = array(
				'id'		=> '301',
				'version'	=> $rrrlgvwr_plugin_info['Version'],
			);

		/* Call register settings function */
		if ( ! is_admin() || ( isset( $_GET['page'] ) && "rrrlgvwr.php" == $_GET['page'] ) ) {
			rrrlgvwr_settings();
		}
	}
}

/**
* Register settings for plugin
*/
if ( ! function_exists( 'rrrlgvwr_settings' ) ) {
	function rrrlgvwr_settings() {
		global $rrrlgvwr_options, $rrrlgvwr_options_default, $rrrlgvwr_plugin_info;

		$rrrlgvwr_options_default = array(
			'plugin_option_version'		=> $rrrlgvwr_plugin_info['Version'],
			'php_error_log_visible'		=> 0,
			'lines_count'				=> 10,
			'confirm_filesize'			=> 0,
			'error_log_path'			=> '',
			'count_visible_log'			=> 0,
			'frequency_send'			=> 1,
			'hour_day'					=> 3600,
			'display_settings_notice'	=> 1,
			'suggest_feature_banner'	=> 1,
			'display_method'			=> 'lines',
			'date_from'					=> '',
			'date_to'					=> ''
		);

		if ( ! get_option( 'rrrlgvwr_options' ) ) {
			add_option( 'rrrlgvwr_options', $rrrlgvwr_options_default );
		}

		$rrrlgvwr_options = get_option( 'rrrlgvwr_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $rrrlgvwr_options['plugin_option_version'] ) || $rrrlgvwr_options['plugin_option_version'] != $rrrlgvwr_plugin_info['Version'] ) {
			$rrrlgvwr_options_default['display_settings_notice'] = 0;
			$rrrlgvwr_options							= array_merge( $rrrlgvwr_options_default, $rrrlgvwr_options );
			$rrrlgvwr_options['plugin_option_version']	= $rrrlgvwr_plugin_info['Version'];
			update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
		}
	}
}

/**
* Function register settings page
*/
if ( ! function_exists( 'rrrlgvwr_settings_page' ) ) {
	function rrrlgvwr_settings_page() {
		global $rrrlgvwr_options, $rrrlgvwr_options_default, $rrrlgvwr_plugin_info, $tenMb;
		$tenMb = 10485760;
		$message = $error = "";

		$plugin_basename = plugin_basename( __FILE__ );

		$error_logging_enabled	= ini_get( 'log_errors' ) && ( ini_get( 'log_errors' ) != 'Off' );									/* Check if error logging enabled on the server */
		$php_error_path			= ini_get( 'error_log' );
		$php_error_log_name		= substr( $php_error_path, strripos( $php_error_path, DIRECTORY_SEPARATOR ) + 1 );					/* Leave only name of the php error log */
		$home_path 				= ( '/' == substr( get_home_path(), strlen( get_home_path() )-1 )  ) ? substr( get_home_path(), 0, strlen( get_home_path() )-1 ) : get_home_path();
		$wp_error_files			= rrrlgvwr_find_log_files( $home_path );																/* Add in array all .log files in all subdir of home wp directory */
		$saved_log				= glob( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR . '*.txt' ); /* Array of saved files in saved_logs */
		$saved_log_name			= str_replace( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR, '', $saved_log ); /* Array of file's name in saved_logs */

		if ( ! $error_logging_enabled ) {
			$php_error_mes = __( 'Error logging on your server is disabled. Try to logging via WordPress function.', 'error-log-viewer' );
		} elseif ( empty( $php_error_path ) ) {
			$php_error_mes = __( 'Error log filename is not set. Try to logging via WordPress function.', 'error-log-viewer' );
		} elseif ( ( strpos( $php_error_path, "/" ) === false ) && ( strpos( $php_error_path, "\\" ) === false ) ) {
			$php_error_mes = sprintf( __( 'The current error_log value %s is not supported. Please change it to an absolute path.', 'error-log-viewer' ), esc_html( $php_error_path ) );
		} elseif ( ! is_readable( $php_error_path ) ) {
			$php_error_mes = sprintf ( __( 'The log file %s does not exist or is inaccessible.', 'error-log-viewer' ), esc_html( $php_error_path ) );
		} else {
			$php_error_mes	= esc_html( $php_error_path );
		}

		/* Save settings */
		if ( isset( $_REQUEST['rrrlgvwr_settings_submit'] ) && check_admin_referer( $plugin_basename, 'rrrlgvwr_nonce_name' ) ) {
			/* Check for monitor file and search changes for sending email */
			$rrrlgvwr_options['count_visible_log']		= 0;												/* Total count of log */
			$rrrlgvwr_options['file_path']				= '';												/* Save files path for sender */
			$rrrlgvwr_options['php_error_log_visible']	= ( isset( $_POST['rrrlgvwr_php_visible'] ) ) ? 1 : 0;
			if ( $rrrlgvwr_options['php_error_log_visible'] == 1 ) {
				$rrrlgvwr_options['file_path'][0]		= $php_error_path;
				$rrrlgvwr_options['count_visible_log']	+= $rrrlgvwr_options['php_error_log_visible'];
			}
			foreach ( $wp_error_files as $key => $file ) {
				$name		= str_replace ( substr( $file, 0, strripos( $file, '/' )+1 ), '', $file );	/* Name of log file without way */
				$subname	= substr( $name, 0, strpos( $name, '.' ) );									/* Name of log file without extensions */
				$subname	= $key . "_" . $subname . '_visible';										/* Add '_visible' to the name */
				if ( is_readable( $file ) ) {
					$rrrlgvwr_options[ $subname ] = ( isset( $_POST[ $subname ] ) ) ? 1 : 0;
					if ( $file == $php_error_path && $rrrlgvwr_options['php_error_log_visible'] == 1 ) {
						continue;
					}
					if ( $rrrlgvwr_options[ $subname ] == 1 ) {
						$rrrlgvwr_options['file_path'][ $key+1 ]	=	$file;
						$rrrlgvwr_options['count_visible_log']		+=	$rrrlgvwr_options[ $subname ];
					}
				} elseif ( ! is_readable( $file ) && isset( $_POST[ $subname ] ) )
					$error = sprintf( __( "File %s isn't readable, change permissions to the file", 'error-log-viewer' ), esc_html( $name ) );

			}
			/* Create log if not exists */
			if ( isset( $_POST['rrrlgvwr_create_log'] ) ) {
				switch ( $_POST['rrrlgvwr_create_log'] ) {
					case 'htaccess':
						$create_mes		= rrrlgvwr_edit_htaccess();
						if ( empty( $create_mes ) ) {
						 	$message	= __( "File '.htaccess' updated successfully and plugin create 'php-errors.log' in plugin log folder", 'error-log-viewer' );
						} else {
							$error		= $create_mes;
						}
						break;
					case 'config_ini_set':
						$create_mes		= rrrlgvwr_edit_wpconfig_iniset();
						if ( empty( $create_mes ) ) {
						 	$message	= __( "File 'wp-config' updated successfully and plugin create 'php-errors.log' in plugin log folder", 'error-log-viewer' );
						} else {
							$error		= $create_mes;
						}
					 	break;
					case 'config_debug':
						$create_mes		= rrrlgvwr_edit_wpconfig_debug();
						if ( empty( $create_mes ) ) {
							$message	= __( "File 'wp-config' updated successfully and plugin create 'debug.log' in wp-content directory", 'error-log-viewer' );
						} else {
							$error		= $create_mes;
						}
						break;
				}
				$wp_error_files	= rrrlgvwr_find_log_files( $home_path );
			}

			/* Sending email options */
			$rrrlgvwr_options['send_email'] = isset( $_POST['rrrlgvwr_send_email'] ) ? 1 : 0;
			if ( 1 == $rrrlgvwr_options['send_email'] && '' != $rrrlgvwr_options['file_path'] ) {
				if ( false != is_email( $_POST['rrrlgvwr_email'] ) && ! empty( $_POST['rrrlgvwr_email'] ) ) {
					$rrrlgvwr_options['email']				= $_POST['rrrlgvwr_email'];
					$rrrlgvwr_options['frequency_send']		= $_POST['rrrlgvwr_frequency_send'];
					$rrrlgvwr_options['hour_day']			= $_POST['rrrlgvwr_hour_day'];
					update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
					rrrlgvwr_shedule_activation();
				} else {
					$error = __( "Make sure that the email field isn't empty or you wrote an email correctly", 'error-log-viewer' );
				}
			} elseif ( 1 == $rrrlgvwr_options['send_email'] && '' == $rrrlgvwr_options['file_path'] ) {
				rrrlgvwr_shedule_deactivation();
				$error = __( 'Select at least one log file', 'error-log-viewer' );
			} else {
				rrrlgvwr_shedule_deactivation();
			}
			

			$message = __( 'Settings saved successfully', 'error-log-viewer' );
			update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
		}

		/* Show selected file with necessary settings */
		if ( isset( $_POST['rrrlgvwr_submit_show_content'] ) && check_admin_referer( $plugin_basename, 'rrrlgvwr_nonce_name' ) ) {
			if ( isset( $_POST['rrrlgvwr_select_log'] ) ) {
				$rrrlgvwr_options['error_log_path']		= $_POST['rrrlgvwr_select_log'];
				$rrrlgvwr_options['confirm_filesize']	= filesize( $rrrlgvwr_options['error_log_path'] );
			}
			$rrrlgvwr_options['lines_count']			= $_POST['rrrlgvwr_lines_count'];
			update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
		}

		/* Save content from textarea into the file in saved_logs */
		if ( isset( $_POST['rrrlgvwr_save_content'] ) && check_admin_referer( $plugin_basename, 'rrrlgvwr_nonce_name' ) ) {
			if ( isset( $_POST['rrrlgvwr_newcontent'] ) && ! empty( $_POST['rrrlgvwr_newcontent'] ) ) {
				$save_mes		= rrrlgvwr_save_file( $_POST['rrrlgvwr_newcontent'] );
				if ( '' == $save_mes )
					$message	= __( 'File was saved successfully', 'error-log-viewer' );
				else
					$error		= __( "Plugin couldn't save the file. Try to change permissions to the directory, or try again", 'error-log-viewer' );
			}
			$saved_log			= glob( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR . '*.txt' ); /* Refresh list of files in directory */
			$saved_log_name		= str_replace( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR, '', $saved_log );
		}

		/* Clear selected log file */
		if ( isset( $_POST['rrrlgvwr_clear_file'] ) && check_admin_referer( $plugin_basename, 'rrrlgvwr_nonce_name' ) ) {
			$clear_mes		= rrrlgvwr_clear_file( $_POST['rrrlgvwr_clear_file_name'] );
			if ( '' == $clear_mes ) {
				$message	= __( 'File was cleared successfully', 'error-log-viewer' );
			} else {
				$error		= $clear_mes;
			}
		}

		/* Custom wp list table class */
		$saved_logs = new Error_Log_Saved_Files();
		if ( $saved_logs->current_action() ) {
			$saved_logs_action = $saved_logs->current_action();
		} else {
			$saved_logs_action = isset( $_REQUEST['saved_logs_action'] ) ? $_REQUEST['saved_logs_action'] : '';
		}

		$rrrlgvwr_check_del = isset( $_REQUEST['rrrlgvwr_check_del'] ) ? $_REQUEST['rrrlgvwr_check_del'] : 0;
		switch ( $saved_logs_action ) {
			case 'delete':
				$rrrlgvwr_check_dels = is_array( $rrrlgvwr_check_del ) ? $rrrlgvwr_check_del : array( $rrrlgvwr_check_del );
				foreach ( $rrrlgvwr_check_dels as $check_del ) {
					if ( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR . $check_del . '.txt' ) ) {
						if ( @unlink( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR . $check_del . '.txt' ) ) {
							$message = __( 'File was deleted successfully', 'error-log-viewer' );
						} else {
							$error = sprintf( __( "Couldn't delete file %s, change permission to the 'wp-content' or 'error-log-viewer' plugin directory", 'error-log-viewer' ), ( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR . $check_del . '.txt' ) );
						}
					}

				$saved_log		= glob( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR . '*.txt' ); /* Refresh list of files in directory */
				$saved_log_name	= str_replace( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR, '', $saved_log );
				}
				break;
			default:
				break;

		}

		$saved_logs_data = array();
		foreach ( $saved_log_name as $name ) {
			$date				= str_replace( '_', '-', substr( $name, 0, strpos( $name, '-' ) ) ) . "&#010;" . str_replace( '_', ':', substr( $name, strpos( $name, '-' )+1 , strpos( $name, '_log' ) - ( strpos( $name, '-' )+1 ) ) );
			$saved_logs_data[]	= array(
				'name'	=> $name,
				'check'	=> substr( $name, 0, strpos( $name, '.' ) ),
				'title'	=> sprintf( '<a target="_blank" class="row-title" href="' . plugin_dir_url( __FILE__ ) . 'saved_logs/'. $name . '">%1$s</a>', __( 'Saved log from', 'error-log-viewer' ) ),
				'date'	=> $date,
				'size'	=> rrrlgvwr_file_size( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR . $name ),
			);
		}
		
		$saved_logs->rrrlgvwr_saved_file = $saved_logs_data;
		$saved_logs->prepare_items();

		if ( isset ( $_POST['rrrlgvwr_submit_show_content'] ) ) {
			if('lines' == $_POST['rrrlgvwr_show_content'] ) {
				$rrrlgvwr_options['display_method'] = 'lines';
			} elseif('date' == $_POST['rrrlgvwr_show_content'] ) {
				$rrrlgvwr_options['display_method'] = 'date';
				$rrrlgvwr_options['date_from'] = strval( $_POST['rrrlgvwr_from'] );
				$rrrlgvwr_options['date_to'] = strval( $_POST['rrrlgvwr_to'] );	
			} elseif( 'all '== $_POST['rrrlgvwr_show_content'] ) {
				$rrrlgvwr_options['display_method'] = 'all';
			}
		}

		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
			$rrrlgvwr_options = $rrrlgvwr_options_default;
			update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
			$message = __( 'All plugin settings were restored.', 'error-log-viewer' );
		} ?>
		<div class="wrap">
			<h1><?php _e( 'Error Log Viewer Settings', 'error-log-viewer' ); ?></h1>
			<?php if ( 0 != $rrrlgvwr_options['count_visible_log'] ) { 
				?>
				<h2 class="nav-tab-wrapper">
					<a class="nav-tab<?php if ( isset( $_GET['tab'] ) && 'logmonitor' == $_GET['tab'] || ! isset( $_GET['tab'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=rrrlgvwr.php&amp;tab=logmonitor"><?php _e( 'Log monitor' , 'error-log-viewer' ) ?></a>
					<a class="nav-tab<?php if ( isset( $_GET['tab'] ) && 'settings' == $_GET['tab'] || ( ! isset( $_GET['tab'] ) && $rrrlgvwr_options['count_visible_log'] == 0 ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=rrrlgvwr.php&amp;tab=settings"><?php _e( 'Settings', 'error-log-viewer' ); ?></a>
				</h2>
			<?php
			}
			bws_show_settings_notice(); ?>
			<div class="updated fade below-h2" <?php if ( empty( $message ) || "" != $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( "" == $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! isset( $_GET['tab'] ) && 0 == $rrrlgvwr_options['count_visible_log'] || isset( $_GET['tab'] ) && 'settings' == $_GET['tab'] ) {
				if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
					bws_form_restore_default_confirm( $plugin_basename );
				} else { ?>
					<form class="bws_form" method="post" action="admin.php?page=rrrlgvwr.php&amp;tab=settings">
						<h3 class="title"><?php _e( 'PHP Error Log', 'error-log-viewer' ); ?></h3>
						<?php if ( $error_logging_enabled && ( ! empty( $php_error_path ) ) && ( is_readable( $php_error_path ) ) ) { ?>
							<table class="form-table">
								<tr valign="top">
									<th scope="row" class="th-full">
										<label for="rrrlgvwr-php-visible">
											<input type="checkbox" name="rrrlgvwr_php_visible" id="rrrlgvwr-php-visible" <?php if ( isset( $rrrlgvwr_options['php_error_log_visible'] ) && $rrrlgvwr_options['php_error_log_visible'] == 1 ) echo 'checked="checked"' ?> />
											<?php echo esc_html( $php_error_log_name ); ?>
										</label>
									</td>
									<td><?php echo $php_error_mes; ?></td>
									<?php if ( file_exists( $php_error_path ) ) { ?>
										<td>
											<?php if ( 0 == filesize( $php_error_path ) )
												_e( 'The file is empty', 'error-log-viewer' );
											else
												echo rrrlgvwr_file_size( $php_error_path ); ?>
										</td>
										<td>
											<?php _e( 'Last update', 'error-log-viewer' );
											echo ': ' . date( 'Y-m-d H:i:s', filemtime( $php_error_path ) ); ?>
										</td>
									<?php } ?>
								</tr>
							</table>
						<?php } else { ?>
							<p><?php echo $php_error_mes; ?></p>
						<?php } ?>
						<h3 class="title"><?php _e( 'WordPress Error Log', 'error-log-viewer' ); ?></h3>
						<?php if ( 0 == count( $wp_error_files ) ) { ?>
							<p><?php _e( "Plugin didn't find log files in your Wordpress directory. You can create log file by yourself or using the plugin.", 'error-log-viewer' ); ?></p>
						<?php }; ?>
						<table class="form-table">
							<?php if ( 0 == count( $wp_error_files )  ) { ?>
								<tr valign="top">
									<th scope="row" class="th-full">
										<span><?php _e( "Error logging via '.htaccess' using 'php_flag' and 'php_value'", 'error-log-viewer' ); ?></span>
									</th>
									<td>
										<fieldset>
											<legend class="screen-reader-text"><span><?php _e( "Error logging via '.htaccess' using 'php_flag' and 'php_value'", 'error-log-viewer' ); ?></span></legend>
											<label for="rrrlgvwr-create-htaccess">
												<input type="radio" name="rrrlgvwr_create_log" id="rrrlgvwr-create-htaccess" value="htaccess" />
												<?php _e( "Add the following code in your '.htaccess' file and create 'php-errors.log' file in 'log' directory in the plugin folder", 'error-log-viewer' ); ?>
											</label>
											<pre class="rrrlgvwr-pre"># log php errors<br>php_flag  log_errors on<br>php_flag display_errors off<br>php_value error_log <?php echo dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR . "php-errors.log"; ?></pre>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="th-full">
										<span>
											<?php _e( "Error logging via 'wp-config.php' using 'ini_set'", 'error-log-viewer' ); ?>
										</span>
									</th>
									<td>
										<fieldset>
											<legend class="screen-reader-text"><span><?php _e( "Error logging via 'wp-config.php' using 'ini_set'", 'error-log-viewer' ); ?></span></legend>
											<label for="rrrlgvwr-create-config-ini-set">
												<input type="radio" name="rrrlgvwr_create_log" id="rrrlgvwr-create-config-ini-set" value="config_ini_set" />
												<?php _e( "Add the following code in the 'wp-config.php' file and create 'php-errors.log' file in 'log' directory in the plugin folder", 'error-log-viewer' ); ?>
											</label>
											<pre class="rrrlgvwr-pre">@ini_set( 'log_errors','On' );<br>@ini_set( 'display_errors','Off' );<br>@ini_set( 'error-log-viewer', '<?php echo dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR . "php-errors.log"; ?>' );</pre>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="th-full">
										<span>
											<?php _e( "Error logging via 'wp-config.php' using 'WP_DEBUG'", 'error-log-viewer' ); ?>
										</span>
									</th>
									<td>
										<fieldset>
											<legend class="screen-reader-text"><span><?php _e( "Error logging via 'wp-config.php' using 'WP_DEBUG'", 'error-log-viewer' ); ?></span></legend>
											<label for="rrrlgvwr-create-config-debug">
												<input type="radio" name="rrrlgvwr_create_log" id="rrrlgvwr-create-config-debug" value="config_debug" />
												<?php _e( "Add the following code in the 'wp-config.php' file and create 'debug.log' in the 'wp-content' directory", 'error-log-viewer' ); ?>
											</label>
											<pre class="rrrlgvwr-pre">define('WP_DEBUG', true);<br>define('WP_DEBUG_LOG', true);<br>define('WP_DEBUG_DISPLAY', false);<br>@ini_set('display_errors', 0);</pre>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="th-full" colspan="2">
										<?php _e( "Files '.htaccess' and 'wp-config.php' are very important for normal working of your site. Please save them necessarily before changes. You can create custom file for logging and edit required file by yourself. See also", 'error-log-viewer' ); ?>:
										<a target="_blank" href="https://codex.wordpress.org/Debugging_in_WordPress"><?php _e( 'Debugging on WordPress', 'error-log-viewer' ); ?></a>
										<a target="_blank" href="https://codex.wordpress.org/Editing_wp-config.php"><?php _e( 'Editing', 'error-log-viewer' ); ?> wp-config.php</a>
									</th>
								</tr>
							<?php };
							foreach ( $wp_error_files as $key => $file ) {
								$name		= str_replace ( substr( $file, 0, strripos( $file, '/' )+1 ), '', $file );
								$subname	= substr( $name, 0, strpos( $name, '.' ) );
								$id			= $key . '-' . $subname;
								$subname	= $key . '_' . $subname . '_visible'; ?>
								<tr valign="top">
									<th scope="row" class="th-full">
										<label for="rrrlgvwr-<?php echo $id; ?>">
											<input type="checkbox" name="<?php echo $subname;?>" id="rrrlgvwr-<?php echo $id; ?>" <?php if ( isset ( $rrrlgvwr_options[ $subname ] ) && ( $rrrlgvwr_options[ $subname ] == 1 ) ) echo 'checked="checked"' ?> />
											<?php echo esc_html( $name ); ?>
										</label>
									</th>
									<td><?php echo $file; ?></td>
									<td>
										<?php if ( 0 == filesize( $file ) )
											_e( 'The file is empty', 'error-log-viewer' );
										else
											echo rrrlgvwr_file_size( $file ); ?>
									</td>
									<td>
										<?php _e( 'Last update', 'error-log-viewer' );
										echo ': ' . date( 'Y-m-d H:i:s', filemtime( $file ) ); ?>
									</td>
								</tr>
							<?php } ?>
						</table>
						<h3 class="title"><?php _e( 'Sending Settings', 'error-log-viewer' ); ?></h3>
						<table class="form-table">
							<tr valign="top">
								<th scope="row" class="th-full" colspan="2">
									<label for="rrrlgvwr-send-email">
										<input type="checkbox" name="rrrlgvwr_send_email" id="rrrlgvwr-send-email" <?php if ( isset( $rrrlgvwr_options['send_email'] ) && $rrrlgvwr_options['send_email'] == 1 ) echo 'checked="checked"'; ?> />
										<?php _e( 'Notify about new error logs (via email)', 'error-log-viewer' ); ?>
									</label>
								</th>
							</tr>
							<tr valign="top" class="rrrlgvwr-email-field">
								<th scope="row">
									<?php _e( 'Email', 'error-log-viewer' ); ?>
								</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span><?php _e( 'Email', 'error-log-viewer' ); ?></span></legend>
										<input type="text" class="regular-text code" name="rrrlgvwr_email" id="rrrlgvwr-email" value="<?php if ( isset( $rrrlgvwr_options['email'] ) ) echo $rrrlgvwr_options['email']; ?>" />
									</fieldset>
								</td>
							</tr>
							<tr valign="top" class="rrrlgvwr-interval-field">
								<th scope="row"><?php _e( 'Send every', 'error-log-viewer' ); ?></th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span><?php _e( 'Send every', 'error-log-viewer' ); ?></span> </legend>
										<input type="number" class="small-text" min="1" name="rrrlgvwr_frequency_send" value="<?php echo $rrrlgvwr_options['frequency_send']; ?>" />
										<select name="rrrlgvwr_hour_day">
											<option value="3600" <?php if ( $rrrlgvwr_options['hour_day'] == 3600 ) echo 'selected="selected"'; ?>><?php _e( 'Hour', 'error-log-viewer' ); ?></option>
											<option value="86400" <?php if ( $rrrlgvwr_options['hour_day'] == 86400 ) echo 'selected="selected"'; ?>><?php _e( 'Day', 'error-log-viewer' ); ?></option>
										</select>
									</fieldset>
								</td>
							</tr>
						</table>
						<p class="submit">
							<input id="bws-submit-button" type="submit" name="rrrlgvwr_settings_submit" value="<?php _e( 'Save Settings', 'error-log-viewer' );?>" class="button button-primary" />
							<?php wp_nonce_field( $plugin_basename, 'rrrlgvwr_nonce_name' ); ?>
						</p>
					</form>
					<?php bws_form_restore_default_settings( $plugin_basename );
				}
			} elseif ( ! isset( $_GET['tab'] ) || isset( $_GET['tab'] ) && 'logmonitor' == $_GET['tab'] ) { ?>
				<form method="post" action="admin.php?page=rrrlgvwr.php&amp;tab=logmonitor">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'File', 'error-log-viewer' ); ?></th>
							<?php if ( isset( $rrrlgvwr_options['count_visible_log'] ) && $rrrlgvwr_options['count_visible_log'] > 1 ) { ?>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span><?php _e( 'File', 'error-log-viewer' ); ?></span></legend>
										<select name="rrrlgvwr_select_log">
											<?php if ( $rrrlgvwr_options['php_error_log_visible'] == 1 ) : ?>
												<option value="<?php echo $php_error_path?>"<?php if ( $rrrlgvwr_options['error_log_path'] == $php_error_path ) echo 'selected="selected"'; ?>><?php echo $php_error_log_name; ?></option>
											<?php endif;
											foreach ( $wp_error_files as $key => $file ) {
												$name		= str_replace ( substr( $file, 0, strripos( $file, '/' )+1 ), '', $file );
												$subname	= substr( $name, 0, strpos( $name, '.' ) );
												$subname	= $key . "_" . $subname . '_visible';
												if ( $rrrlgvwr_options[ $subname ] == 1 ) {
													if ( $file == $php_error_path && 1 == $rrrlgvwr_options['php_error_log_visible'] ){
														continue;
													} ?>													
													<option value="<?php echo $file; ?>"<?php if ( $rrrlgvwr_options['error_log_path'] == $file ) echo 'selected="selected"';?>><?php echo $name; ?></option>
												<?php }
											} ?>
										</select>
									</fieldset>
								</td>
							<?php } elseif ( isset( $rrrlgvwr_options['count_visible_log'] ) && $rrrlgvwr_options['count_visible_log'] == 1 ) { ?>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span><?php _e( 'File', 'error-log-viewer' ); ?></span></legend>
											<span>
												<?php if ( 1 == $rrrlgvwr_options['php_error_log_visible'] ) {
													echo $php_error_log_name;
													$rrrlgvwr_options['error_log_path'] = $php_error_path;
													update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
												} else {
													foreach ( $wp_error_files as $key => $file ) {
														$name		= str_replace ( substr( $file, 0, strripos( $file, '/' )+1 ), '', $file );
														$subname	= substr( $name, 0, strpos( $name, '.' ) );
														$subname	= $key . "_" . $subname . '_visible';
														if ( isset( $rrrlgvwr_options[ $subname ] ) && 1 == $rrrlgvwr_options[ $subname ] ) {
															echo $name;
															$rrrlgvwr_options['error_log_path'] = $file;
															update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
														}
													}
												} ?>
											</span>
									</fieldset>
								</td>
							<?php } ?>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Show', 'error-log-viewer' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Show', 'error-log-viewer' ); ?></span></legend>
									<label for="rrrlgvwr-show-line">
									<input type="radio" name="rrrlgvwr_show_content" id="rrrlgvwr-show-line" value="lines" <?php checked( 'lines', $rrrlgvwr_options['display_method'] ); ?> />
										<span><?php _e( 'last', 'error-log-viewer' ); ?></span>
										<input type="number" class="small-text" min="1" name="rrrlgvwr_lines_count" value="<?php echo $rrrlgvwr_options['lines_count']; ?>" />
										<span><?php _e( 'lines', 'error-log-viewer' ); ?></span>
									</label><br>
									<label for="rrrlgvwr-show-date">
										<input type="radio" name="rrrlgvwr_show_content" id="rrrlgvwr-show-date" value="date" <?php checked( 'date', $rrrlgvwr_options['display_method'] ) ?> />
										<span><?php _e( 'log from', 'error-log-viewer' ); ?></span>
										<input type="text" id="rrrlgvwr-from" name="rrrlgvwr_from" value="<?php echo $rrrlgvwr_options['date_from'] ?>" />
										<span class="rrrlgvwr-indent"><?php _e( 'to', 'error-log-viewer' ); ?></span>
										<input type="text" id="rrrlgvwr-to" name="rrrlgvwr_to" value="<?php echo $rrrlgvwr_options['date_to'] ?>" />
									</label><br>
									<div class="hide-if-js rrrlgvwr-date-search-mes">
										<p>
											<span><?php _e( 'JavaScript is disable on your site. To search logs by dates, please enter in the first and second field dates among which you want see the log. Your entry should look like', 'error-log-viewer' ); ?></span>
											<code>07/10/2015<span class="rrrlgvwr-indent"><?php _e( 'to', 'error-log-viewer' ); ?></span>07/19/2015.</code>
											<span><?php _e( 'The first two numbers means month, the second two numbers means day of months, the last four numbers means year', 'error-log-viewer' ); ?></span>
										</p>
									</div>
									<label for="rrrlgvwr-show-all">
										<input type="radio" name="rrrlgvwr_show_content" id="rrrlgvwr-show-all" value="all" <?php checked( 'all', $rrrlgvwr_options['display_method'] ) ?> />
										<span><?php _e( 'full file', 'error-log-viewer' ); ?></span>
									</label>
									<?php if ( filesize( $rrrlgvwr_options['error_log_path'] ) > $tenMb ) { ?>
										<div class="hide-if-js rrrlgvwr-date-search-mes">
											<p><?php _e( 'File size is more than 10 Mb. Be careful to check this option', 'error-log-viewer' ); ?></p>
										</div>
									<?php }; ?>
								</fieldset>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="rrrlgvwr_submit_show_content" class="button button-primary" value="<?php _e( 'View', 'error-log-viewer' ); ?>" />
						<?php if ( ! empty( $rrrlgvwr_options['error_log_path'] ) && $rrrlgvwr_options['error_log_path'] == $php_error_path ) { ?>
							<span class="rrrlgvwr-indent"><?php _e( 'or', 'error-log-viewer' ); ?></span>
							<input type="submit" name="rrrlgvwr_save_content" class="button" value="<?php _e( 'Save as TXT file', 'error-log-viewer' ); ?>" />
						<?php } ?>
					</p>
					<?php if ( ! empty( $rrrlgvwr_options['error_log_path'] ) ) { ?>
						<textarea id="rrrlgvwr_textarea_content" name="rrrlgvwr_newcontent" class="large-text code" rows="16" readonly="readonly"><?php if ( isset ( $_POST['rrrlgvwr_submit_show_content'] ) ) {			
							switch ( $_POST['rrrlgvwr_show_content'] ) {
								case 'lines':
									esc_textarea( rrrlgvwr_read_last_lines( $rrrlgvwr_options['error_log_path'], $rrrlgvwr_options['lines_count'] ) );
									break;
								case 'date':
									$first_date	= strtotime( $_POST['rrrlgvwr_from'] );
									$last_date	= strtotime( $_POST['rrrlgvwr_to'] );
									esc_textarea( rrrlgvwr_read_lines_by_date( $rrrlgvwr_options['error_log_path'], $first_date, $last_date ) );
									break;
								case 'all':
									esc_textarea( rrrlgvwr_read_full_file( $rrrlgvwr_options['error_log_path'] ) );
									break;
								default:
									esc_textarea( rrrlgvwr_read_last_lines( $rrrlgvwr_options['error_log_path'], $rrrlgvwr_options['lines_count'] ) );
									break;
							}
						} else {
							esc_textarea( rrrlgvwr_read_last_lines( $rrrlgvwr_options['error_log_path'], $rrrlgvwr_options['lines_count'] ) ); 
						}	?>						
						</textarea>
						<table class="form-table">
							<tr valign="top">
								<th scope="row">
									<span><?php _e( 'File', 'error-log-viewer' ); ?></span>
									<a target="_blank" href="<?php echo str_replace( $home_path, get_home_url(), $rrrlgvwr_options['error_log_path'] ); ?>">
										<?php echo str_replace ( substr( $rrrlgvwr_options['error_log_path'], 0, strripos( $rrrlgvwr_options['error_log_path'], '/' )+1 ), '', $rrrlgvwr_options['error_log_path'] ); ?>
									</a>
									<span>
										<?php _e( ' with size ', 'error-log-viewer' );
										if ( file_exists( $php_error_path ) ) {
												if ( 0 == filesize( $php_error_path ) ) {
													_e( 'The file is empty ', 'error-log-viewer' );
												} else {
													echo rrrlgvwr_file_size( $php_error_path );
												}
											_e( 'Last update', 'error-log-viewer' );
												echo ': ' . date( 'Y-m-d H:i:s', filemtime( $php_error_path ) );
											} ?>
									<span>
								</th>
							</tr>
						</table>
						<p class="submit">
							<input type="submit" class="button button-primary" name="rrrlgvwr_clear_file" id="rrrlgvwr-clear-file" value="<?php _e( 'Clear log file', 'error-log-viewer' );?>" />
							<input type="hidden" value="<?php echo $rrrlgvwr_options['error_log_path']; ?>" name="rrrlgvwr_clear_file_name" />
						</p>
					<?php };

					wp_nonce_field( $plugin_basename, 'rrrlgvwr_nonce_name' ); ?>
				</form>
				<?php if ( ! empty( $rrrlgvwr_options['error_log_path'] ) && $php_error_path == $rrrlgvwr_options['error_log_path'] ) { ?>
					<h3 class="title"><?php _e( 'Saved log files', 'error-log-viewer' ); ?></h3>
					<form class="rrrlgvwr-saved-logs-table" method="post" action="admin.php?page=rrrlgvwr.php&amp;tab=logmonitor">
						<?php $saved_logs->display();
						wp_nonce_field( $plugin_basename, 'rrrlgvwr_nonce_name' ); ?>
					</form>
				<?php }
			}
			bws_plugin_reviews_block( $rrrlgvwr_plugin_info['Name'], 'error-log-viewer' ); ?>
		</div>
	<?php }
}

/* Function find all log files in home wordpress directory */
if ( ! function_exists( 'rrrlgvwr_find_log_files' ) ) {
	function rrrlgvwr_find_log_files( $directory ) {
		/* Home path directory */
		if ( file_exists( $directory ) ) {
			/* Function add in array all directory in home directory including subdir */
			rrrl_glob_recursive( $directory, $directories );
			$files	= array();
			foreach ( $directories as $directory ) {
				foreach( glob( $directory . "/*.log" ) as $file ) {
					$files[] = $file;
				}
			}
			return $files;
		} else {
			return printf( __( "Directory %s is not exists, or isn't readable", 'error-log-viewer' ), esc_html( $directory ) );
		}
	}
}

/* Function glob recursive, add in array all dir and subdir in home directory */
if ( ! function_exists( 'rrrl_glob_recursive' ) ) {
	function rrrl_glob_recursive( $directory, &$directories = array() ) {
		foreach ( glob( $directory, GLOB_ONLYDIR | GLOB_NOSORT ) as $folder ) {
			$directories[]	= $folder;
			rrrl_glob_recursive( $folder . "/*", $directories );
		}
	}
}

/* Function count and round file size */
if ( ! function_exists( 'rrrlgvwr_file_size' ) ) {
	function rrrlgvwr_file_size( $path ) {
		global $tenMb;
		if ( file_exists( $path ) ) {
			if ( filesize( $path ) < $tenMb ) {
				return "&#8764;" . round( filesize( $path )/1024, 2 ) . '&nbsp;Kb&nbsp;';
			} else {
				return "&#8764;" . round( filesize( $path )/1024/1024, 2 ) . '&nbsp;Mb&nbsp;';
			}
		} else {
			return printf( __( 'File %s is not exists', 'error-log-viewer' ), esc_html( $file ) );
		}
	}
}
/* Function return size of directory with files */
if ( ! function_exists( 'rrrlgvwr_path_size' ) ) {
	function rrrlgvwr_path_size( $path ) {
		global $tenMb;
		if ( file_exists( $path ) ) {
			$summ_size	= 0;
			$dir		= scandir( $path );
			foreach( $dir as $file ) {
				if ( ( '.' != $file ) && ( '..' != $file ) ) {
					if ( is_dir( $path . DIRECTORY_SEPARATOR . $file ) ) {
						$summ_size	+= rrrlgvwr_path_size( $path . DIRECTORY_SEPARATOR . $file );
					} else {
						$summ_size	+= filesize( $path . DIRECTORY_SEPARATOR . $file );
					}
				}
			}
			if ( $summ_size < $tenMb ) {
				return "&#8764;" . round( $summ_size/1024, 2 ) . '&nbsp;Kb&nbsp;';
			} else {
				return "&#8764;" . round( $summ_size/1024/1024, 2 ) . '&nbsp;Mb&nbsp;';
			}
		} else {
			return printf( __( 'File %s is not exists', 'error-log-viewer' ), esc_html( $file ) );
		}
	}
}

/* Function read X last lines from file*/
if ( ! function_exists( 'rrrlgvwr_read_last_lines' ) ) {
	function rrrlgvwr_read_last_lines( $file, $lines ) {
		$handle	= @fopen( $file, "r" );
		if ( ! empty( $handle ) ) {
			$linecounter	= $lines;
			$pos			= -2;
			$beginning		= false;
			$text			= array();
			while ( $linecounter > 0 ) {
				$t	= "";
				while ( $t != "\n" ) {
					if ( fseek( $handle, $pos, SEEK_END ) == -1 ) {
						$beginning	= true;
						break;
					}
					$t	= fgets( $handle );
					$pos --;
				}
				$linecounter --;
				if ( $beginning ) {
					rewind( $handle );
				}
				$text[ $lines-$linecounter-1 ]	= fgets( $handle );
				if ( $beginning )
					break;
			}
			fclose ( $handle );
			foreach ( $text as $line ) {
				echo $line;
			}
		} else
			return printf( __( "Couldn't open the file %s. Make sure file is exists or is readable.", 'error-log-viewer' ), esc_html( $file ) );
	}
}

/* Read log in file from date to date */
if ( ! function_exists( 'rrrlgvwr_read_lines_by_date' ) ) {
	function rrrlgvwr_read_lines_by_date( $file, $first_date, $last_date ) {
		$pattern	= '/\[(.*?)\s/';
		$count_line	= 0;
		$handle		= @fopen( $file, 'r' );
		$line_date  = 0;

		if ( ! empty( $handle ) ) {
			while ( ! feof( $handle ) ) {
				$line = fgets( $handle );
				if ( preg_match( $pattern, $line, $matches ) ) {
					$line_date	= $matches[1];
				}
				if ( strtotime( $line_date ) >= $first_date && strtotime( $line_date ) <= $last_date ) {
					$count_line ++;
					echo $line;
				} else {
					continue;
				}
			}
			if ( 0 == $count_line ) {
				printf( __( 'No log in search date from %s to %s', 'error-log-viewer' ), date( 'Y-m-d', $first_date ), date( 'Y-m-d', $last_date ) );
			}
			fclose( $handle );
		} else {
			return printf( __( "Couldn't open the file %s. Make sure file is exists or is readable.", 'error-log-viewer' ), esc_html( $file ) );
		}
	}
}

/* Function read full file */
if ( ! function_exists( 'rrrlgvwr_read_full_file' ) ) {
	function rrrlgvwr_read_full_file( $file ) {
		$handle	= @fopen( $file, 'r' );
		if ( ! empty( $handle ) ) {
			while ( ! feof( $handle ) ) {
				$line	= fgets( $handle );
				echo $line;
			}
			fclose( $handle );
		} else
			return printf( __( "Couldn't open the file %s. Make sure file is exists or is readable.", 'error-log-viewer' ), esc_html( $file ) );
	}
}

/* Save file in saved_logs */
if ( ! function_exists( 'rrrlgvwr_save_file' ) ) {
	function rrrlgvwr_save_file( $content ) {
		if ( @is_writable( dirname( __FILE__ ) ) ) {
			if ( ! file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' ) ) {
				mkdir( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' );
			}
			$file	= @fopen( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'saved_logs' . DIRECTORY_SEPARATOR . date( 'Y_m_d-h_i_s' ) . '_log.txt', 'w' );
			if ( ! empty( $file ) ) {
				fwrite( $file, $content );
				fclose( $file );
			} else {
				return __( "Plugin couldn't saved the file, change permissions to the 'wp-content' or 'error-log-viewer' plugin directory, or try again", 'error-log-viewer' );
			}
		} else {
			return __( "Plugin couldn't open the 'error-log-viewer' plugin directory, try to change permission to the 'wp-content' or 'error-log-viewer' plugin directory and try again", 'error-log-viewer' );
		}
	}
}

/* Clear file */
if ( ! function_exists( 'rrrlgvwr_clear_file' ) ) {
	function rrrlgvwr_clear_file( $file ) {
		$handle	= @fopen( $file, 'w' );
		if ( ! empty( $handle ) ) {
			fclose( $handle );
		} else
			return printf( __( "Couldn't open the file %s. Make sure file is exists or is readable.", 'error-log-viewer' ), esc_html( $file ) );
	}
}

/* Edit .htaccess and create log file into the plugin log directory */
if ( ! function_exists( 'rrrlgvwr_edit_htaccess' ) ) {
	function rrrlgvwr_edit_htaccess() {
		$file	= get_home_path() . '.htaccess'; /* Path to the .htaccess file */
		$string	= PHP_EOL . "# log php errors" . PHP_EOL . "php_flag  log_errors on" . PHP_EOL . "php_flag  log_errors on" . PHP_EOL . "php_value error_log " . plugin_dir_path( __FILE__ ) . "log/php-errors.log" . PHP_EOL;
		/* Check is .htaccess writable */
		if ( @is_writable( $file ) ) {
			/* Check is .htacces already containes required string */
			if ( false == strstr( file_get_contents( $file ), $string ) ) {
				$htaccess = fopen( $file, 'a+' );
				fwrite( $htaccess, $string );
				fclose( $htaccess );
				if ( @is_writable( plugin_dir_path( __FILE__ ) ) ) {
					if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'log' . DIRECTORY_SEPARATOR . 'php-errors.log' ) ) {
						mkdir( plugin_dir_path( __FILE__ ) . 'log' );
						$log	= fopen( plugin_dir_path( __FILE__ ) . 'log' . DIRECTORY_SEPARATOR . 'php-errors.log', 'w' );
						fclose( $log );
					}
				} else {
					return __( "File '.htaccess' contains the following code, but plugin couldn't create 'php-errors.log', change permissions to the 'wp-content' or 'error-log-viewer' plugin directory, or create this file by yourself", 'error-log-viewer' );
				}
			} else {
				/* Check if writable plugin folder and create log file */
				if ( @is_writable( plugin_dir_path( __FILE__ ) ) ) {
					if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'log' . DIRECTORY_SEPARATOR . 'php-errors.log' ) ) {
						mkdir( plugin_dir_path( __FILE__ ) . 'log' );
						$log	= fopen( plugin_dir_path( __FILE__ ) . 'log' . DIRECTORY_SEPARATOR . 'php-errors.log', 'w' );
						fclose( $log );
					}
				} else {
					return __( "File '.htaccess' contains the following code, but plugin couldn't create 'php-errors.log', change permissions to the 'wp-content' or 'error-log-viewer' plugin directory, or create this file by yourself", 'error-log-viewer' );
				}
				return __( "File '.htaccess' already contains this code", 'error-log-viewer' );
			}
		} else {
			return __( "File '.htaccess' isn't available. Please change permissions to the file, or try the next method", 'error-log-viewer' );
		}
	}
}

/* Edit wp-config.php via ini-set and create log file into the plugin log directory */
if ( ! function_exists( 'rrrlgvwr_edit_wpconfig_iniset' ) ) {
	function rrrlgvwr_edit_wpconfig_iniset() {
		$file			= get_home_path() . 'wp-config.php';					/* Path to the wp-congig.php file */
		$pattern		= "/define\(\s?'WP_DEBUG'\s?,\s?(false|true)\s?\);/";	/* Find define('WP_DEBUG', false|true); string in the wp-congig.php file */
		/* Required string */
		$string_iniset	= PHP_EOL . "@ini_set('log_errors','On');" . PHP_EOL . "@ini_set('display_errors','Off');" . PHP_EOL . "@ini_set('error-log-viewer', '" . plugin_dir_path( __FILE__ ) . "log" . DIRECTORY_SEPARATOR . "php-errors.log');" . PHP_EOL;
		$string_debug	= "define('WP_DEBUG', true);" . PHP_EOL . "define('WP_DEBUG_LOG', true);" . PHP_EOL . "define('WP_DEBUG_DISPLAY', false);" . PHP_EOL . "@ini_set('display_errors', 0);" . PHP_EOL;
		/* Check is wp-config writable*/
		if ( @is_writable( $file ) ) {
			/* Check is wp-config already containes required strings */
			if ( strstr( file_get_contents( $file ), $string_iniset ) == false && strstr( file_get_contents( $file ), $string_debug ) == false ) {
				$wpconfig	= fopen( $file, 'c+' );
				while ( ! feof( $wpconfig ) ) {
					$line		= fgets( $wpconfig );
					if ( preg_match( $pattern, $line, $matches ) ) {
						$offset	= ftell( $wpconfig );
					}
				}
				$last_content = file_get_contents( $file, NULL, NULL, $offset ); /* Save content after pattern */
				fseek( $wpconfig, $offset ); /* Put the pointer after pattern*/
				fwrite( $wpconfig, $string_iniset . $last_content ); /* Write required string and content after pattern*/
				fclose( $wpconfig );
				/* Check if writable plugin folder and create log file */
				if ( @is_writable( plugin_dir_path( __FILE__ ) ) ) {
					if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'log' . DIRECTORY_SEPARATOR . 'php-errors.log' ) ) {
						mkdir( plugin_dir_path( __FILE__ ) . 'log' );
						$log = fopen( plugin_dir_path( __FILE__ ) . 'log' . DIRECTORY_SEPARATOR . 'php-errors.log', 'w' );
						fclose( $log );
					}
				} else {
					return __( "File 'wp-config.php' contains the following code, but plugin couldn't create 'php-errors.log', change permissions to the 'wp-content' or 'error-log-viewer' plugin directory, or create this file by yourself", 'error-log-viewer' );
				}
			} else {
				/* Check if writable plugin folder and create log file */
				if ( @is_writable( plugin_dir_path( __FILE__ ) ) ) {
					if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'log' . DIRECTORY_SEPARATOR . 'php-errors.log' ) ) {
						mkdir( plugin_dir_path( __FILE__ ) . 'log' );
						$log = fopen( plugin_dir_path( __FILE__ ) . 'log' . DIRECTORY_SEPARATOR . 'php-errors.log', 'w' );
						fclose( $log );
					}
				} else {
					return __( "File 'wp-config.php' contains the following code, but plugin couldn't create 'php-errors.log', change permissions to the 'wp-content' or 'error-log-viewer' plugin directory, or create this file by yourself", 'error-log-viewer' );
				}
				return __( "File 'wp-config.php' already contains this code", 'error-log-viewer' );
			}
		} else {
			return __( "Plugin couldn't open and rewritable 'wp-config.php', change permissions to the file, or try the next method", 'error-log-viewer' );
		}
	}
}

/* Edit wp-config.php and create debug.log into the wp-content directory */
if ( ! function_exists( 'rrrlgvwr_edit_wpconfig_debug' ) ) {
	function rrrlgvwr_edit_wpconfig_debug() {
		$file			= get_home_path() . 'wp-config.php';					/* Path to the wp-congig.php file */
		$pattern		= "/define\(\s?'WP_DEBUG'\s?,\s?(false|true)\s?\);/";	/* Find define('WP_DEBUG', false|true); string in the wp-congig.php file */
		/* Required string */
		$string_iniset	= PHP_EOL . "@ini_set('log_errors','On');" . PHP_EOL . "@ini_set('display_errors','Off');" . PHP_EOL . "@ini_set('error-log-viewer', '" . plugin_dir_path( __FILE__ ) . "log" . DIRECTORY_SEPARATOR . "php-errors.log');" . PHP_EOL;
		$string_debug	= "define('WP_DEBUG', true);" . PHP_EOL . "define('WP_DEBUG_LOG', true);" . PHP_EOL . "define('WP_DEBUG_DISPLAY', false);" . PHP_EOL . "@ini_set('display_errors', 0);" . PHP_EOL;
		/* Check is wp-config writable*/
		if ( @is_writable( $file ) ) {
			/* Check is wp-config already containes required strings */
			if ( false == strstr( file_get_contents( $file ), $string_iniset )  && false == strstr( file_get_contents( $file ), $string_debug ) ) {
				$wpconfig	= fopen( $file, 'c+' );
				while ( ! feof( $wpconfig ) ) {
					$line	= fgets( $wpconfig );
					if ( preg_match( $pattern, $line, $matches ) ) {
						$offset			= ftell( $wpconfig );
						$delta_offset	= strlen( $line );
					}
				}
				$last_content	= file_get_contents( $file, NULL, NULL, $offset );
				fseek( $wpconfig, $offset - $delta_offset );
				fwrite( $wpconfig, $string_debug . $last_content );
				fclose( $wpconfig );

				/* Check if writable wp-content directory and create log file */
				$log = @fopen( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'debug.log', 'w' );
				if ( empty( $log ) ) {
					return __( "File 'wp-config.php' contains the following code, but plugin couldn't create 'debug.log', change permissions to the 'wp-content' directory, or create this file by yourself", 'error-log-viewer' );
				}
			} else {
				/* Check if writable wp-content directory and create log file */
				$log = @fopen( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'debug.log', 'w' );
				if ( empty( $log ) ) {
					return __( "File 'wp-config.php' contains the following code, but plugin couldn't create 'debug.log', change permissions to the 'wp-content' directory, or create this file by yourself", 'error-log-viewer' );
				}
				return __( "File 'wp-config.php' already contains this code", 'error-log-viewer' );
			}
		} else {
			return __( "Plugin couldn't open and rewritable 'wp-config.php', change permissions to the file, or try the next method", 'error-log-viewer' );
		}
	}
}

/**
* Shedule options
*/
/* Activate shedule */
if ( ! function_exists( 'rrrlgvwr_shedule_activation' ) ) {
	function rrrlgvwr_shedule_activation() {
		if ( ! wp_next_scheduled( 'rrrlgvwr_shedule_event' ) ) {
			wp_schedule_event( time(), 'rrrlgvwr_interval', 'rrrlgvwr_shedule_event' );
		}
	}
}

/* Message content */
if ( ! function_exists( 'rrrlgvwr_send_log' ) ) {
	function rrrlgvwr_send_log() {
		$rrrlgvwr_options	= get_option( 'rrrlgvwr_options' );
		$message		= array();

		if ( $rrrlgvwr_options['hour_day'] == 3600 ) {
			$period	= '&nbsp;' . __( 'hour', 'error-log-viewer' );
		} else {
			$period	= '&nbsp;' . __( 'day', 'error-log-viewer' );
		}
		$subject	= __( 'Saved file from ', 'error-log-viewer' ) . site_url();

		foreach ( $rrrlgvwr_options['file_path'] as $key => $file ) {
			if ( file_exists( $file ) ) {
				$name					= str_replace ( substr( $file, 0, strripos( $file, '/' )+1 ), '', $file );
				$subname				= "_" . $key . "_" . substr( $name, 0, strpos( $name, '.' ) );
				$change_wp_file_size	= filesize( $file );

				if ( $change_wp_file_size && $change_wp_file_size != $rrrlgvwr_options[ 'change_file_size' . $subname ] && ! empty( $rrrlgvwr_options[ 'change_file_size' . $subname ] ) ) {
					$message[] = __( 'During the last', 'error-log-viewer' ) . ' ' . $rrrlgvwr_options['frequency_send'] . $period . ' ' . __( 'file' , 'error-log-viewer' ) . ' ' . $name . ' ' . __( 'have been changed', 'error-log-viewer' );
					$rrrlgvwr_options[ 'change_file_size' . $subname ]	= $change_wp_file_size;
					update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
				}
				if ( empty( $rrrlgvwr_options[ 'change_file_size' . $subname ] ) ) {
					$rrrlgvwr_options[ 'change_file_size' . $subname ]	= $change_wp_file_size;
					update_option( 'rrrlgvwr_options', $rrrlgvwr_options );
				}
			}
		}
		if ( empty( $message ) ) {
			$message = __( 'No new errors on your site', 'error-log-viewer' );
		} else {
			$message = implode( "\n", array_unique( $message ) ) . "\n" . __( 'For more information go to the', 'error-log-viewer' ) . ' ' . admin_url( '/admin.php?page=rrrlgvwr.php&amp;tab=settings' );
		}
		wp_mail( $rrrlgvwr_options['email'], $subject, $message );
	}
}

/* Cron Shedules */
if ( ! function_exists( 'rrrlgvwr_interval_schedule' ) ) {
	function rrrlgvwr_interval_schedule( $schedules ) {
		global $rrrlgvwr_options;
		if ( empty( $rrrlgvwr_options ) ) {
			rrrlgvwr_settings();
		}

		$interval = $rrrlgvwr_options['frequency_send'] * $rrrlgvwr_options['hour_day'];
		$schedules['rrrlgvwr_interval'] = array(
			'interval'	=> $interval,
			'display'	=> __( 'Send Email Interval', 'error-log-viewer' ),
		);
		return $schedules;
	}
}

if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ) ) {
	/* Create class Error_Log_Saved_Files to display saved error log */
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	if ( ! class_exists( 'Error_Log_Saved_Files' ) ) {
		class Error_Log_Saved_Files extends WP_List_Table {
			public $rrrlgvwr_saved_file;

			function __construct() {
				parent::__construct( array(
					'singular'	=> 'log_file',
					'plural'	=> 'log_files',
					'ajax'	 	=> false,
					)
				);
			}

			function get_columns() {
				$columns = array(
					'cb'		=> '<input type="checkbox" />', /* Render a checkbox instead of text */
					'title'		=> __( 'Link', 'error-log-viewer' ),
					'date'		=> __( 'Date', 'error-log-viewer' ),
					'size'		=> __( 'Size', 'error-log-viewer' ),
				);
				return $columns;
			}

			function column_default( $item, $column_name ) {
				switch( $column_name ) {
					case 'title':
					case 'date':
					case 'size':
						return $item[ $column_name ];
					default:
						return print_r( $item, true ); /* Show the whole array for troubleshooting purposes */
				}
			}

			function column_cb( $item ) {
				return sprintf( '<input type="checkbox" name="rrrlgvwr_check_del[]" value="%s" />', $item['check'] );
			}

			function no_items() {
				printf( '<i>%s</i>', __( 'There are no saved files', 'error-log-viewer' ) );
			}

			function get_sortable_columns() {
				$sortable_columns = array(
					'date'	=> array( 'date', false ),
					'size'	=> array( 'size', false ),
				);
				return $sortable_columns;
			}

			function column_title( $item ) {
				/* Build row actions */
				$actions = array(
					'view'		=> sprintf( '<a target="_blank" href="' . plugin_dir_url( __FILE__ ) . 'saved_logs/' . '%1$s">%2$s</a>', $item['name'], __( 'View', 'error-log-viewer' ) ),
					'delete'	=> sprintf( '<a href="admin.php?page=rrrlgvwr.php&amp;tab=logmonitor&saved_logs_action=%1$s&rrrlgvwr_check_del=%2$s">%3$s</a>', 'delete', $item['check'], __( 'Delete', 'error-log-viewer' ) ),
				);
				/* Return the title contents */
				return sprintf( '%1$s %2$s', $item['title'], $this->row_actions( $actions ) );
			}

			function get_bulk_actions() {
				$actions = array(
					'delete'	=> __( 'Delete', 'error-log-viewer' ),
				);
				return $actions;
			}

			function prepare_items() {
				$columns				= $this->get_columns();
				$hidden					= array();
				$sortable				= $this->get_sortable_columns();
				$this->_column_headers	= array( $columns, $hidden, $sortable );

				$per_page				= 5;
				$this->items			= $this->rrrlgvwr_saved_file;

				$current_page			= $this->get_pagenum();
				$total_items			= count( $this->rrrlgvwr_saved_file );
				$this->items			= array_slice( $this->rrrlgvwr_saved_file, ( ( $current_page - 1 )*$per_page ), $per_page );

				$this->set_pagination_args( array(
					'total_items'		=> $total_items,
					'per_page'			=> $per_page
				) );
			}
		}
	}
}

/**
 * Sending mail about fatal error
 */
if ( ! function_exists( 'rrrlgvwr_handle_fatal_error' ) ) {
	function rrrlgvwr_handle_fatal_error() {
		$error = error_get_last();
		if ( null != $error ) {
			$rrrlgvwr_options  = get_option( 'rrrlgvwr_options' );
			$fatal_error_types = array(
				E_ERROR,
				E_PARSE,
				E_CORE_ERROR,
				E_USER_ERROR,
				E_COMPILE_ERROR,
				E_RECOVERABLE_ERROR,
			);
			if ( isset( $rrrlgvwr_options['send_email'] ) && 1 == $rrrlgvwr_options['send_email'] &&  isset( $error['type'] ) && in_array( $error['type'], $fatal_error_types, true ) ) {
				$subject = __( 'Fatal error on ', 'error-log-viewer' ) . site_url();
				$message = __( 'An unexpected fatal error occurred on the site.', 'error-log-viewer' ) . "\n" .
                           sprintf( __( 'Fatal error: %s in %s on line %d', 'error-log-viewer' ), $error['message'], $error['file'], $error['line'] );

				if ( ! function_exists('wp_mail') ){
					require_once( ABSPATH . 'wp-includes/pluggable.php' );
                }
				wp_mail( $rrrlgvwr_options['email'], $subject, $message );
			}
		}
	}
}

/**
* Enqueue script and styles
*/
if ( ! function_exists ( 'rrrlgvwr_admin_head' ) ) {
	function rrrlgvwr_admin_head() {
		global $rrrlgvwr_options;
		if ( isset( $_REQUEST['page'] ) && 'rrrlgvwr.php' == $_REQUEST['page'] ) {
			wp_enqueue_script( 'rrrlgvwr_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_style( 'rrrlgvwr_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-datepicker-style' , '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css' );

			wp_localize_script(
				'rrrlgvwr_script',
				'rrrlgvwr_confirm',
				array(
					'confirm_filesize'	=> $rrrlgvwr_options['confirm_filesize'],
					'confirm_mes'		=> __( 'File size is more than 10 Mb. Are you sure you want to see full file?', 'error-log-viewer' ),
					'clear_mes'			=> __( 'Are you sure you want to clear the file?', 'error-log-viewer' ),
				)
			);
		}
	}
}

/**
* Function to add action links to the plugin menu
*/
if ( ! function_exists ( 'rrrlgvwr_plugin_action_links' ) ) {
	function rrrlgvwr_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row */
			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin	= plugin_basename( __FILE__ );
			}
			if ( $file == $this_plugin ) {
				$settings_link	= '<a href="admin.php?page=rrrlgvwr.php&amp;tab=settings">' . __( 'Settings', 'error-log-viewer' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/**
* Function to add links to the plugin description on the plugins page
*/
if ( ! function_exists( 'rrrlgvwr_register_plugin_links' ) ) {
	function rrrlgvwr_register_plugin_links( $links, $file ) {
		$base	= plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() ) {
				$links[]	=	'<a href="admin.php?page=rrrlgvwr.php&amp;tab=settings">' . __( 'Settings', 'error-log-viewer' ) . '</a>';
				$links[]	=	'<a href="https://support.bestwebsoft.com/hc/en-us/sections/201247209" target="_blank">' . __( 'FAQ', 'error-log-viewer' ) . '</a>';
				$links[]	=	'<a href="https://support.bestwebsoft.com">' . __( 'Support', 'error-log-viewer' ) . '</a>';
			}
		}
		return $links;
	}
}

/* add admin notices */
if ( ! function_exists ( 'rrrlgvwr_admin_notices' ) ) {
	function rrrlgvwr_admin_notices() {
		global $hook_suffix, $rrrlgvwr_plugin_info;
		if ( 'plugins.php' == $hook_suffix ) {
			bws_plugin_banner_to_settings( $rrrlgvwr_plugin_info, 'rrrlgvwr_options', 'error-log-viewer', 'admin.php?page=rrrlgvwr.php&amp;tab=settings' );
		}
		if ( isset( $_GET['page'] ) && 'rrrlgvwr.php' == $_GET['page'] ) {
			bws_plugin_suggest_feature_banner( $rrrlgvwr_plugin_info, 'rrrlgvwr_options', 'error-log-viewer' );
		}
	}
}

/* add help tab  */
if ( ! function_exists( 'rrrlgvwr_add_tabs' ) ) {
	function rrrlgvwr_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id' 			=> 'rrrlgvwr',
			'section' 		=> '201247209'
		);
		bws_help_tab( $screen, $args );
	}
}

/* Deactivate shedule */
if ( ! function_exists( 'rrrlgvwr_shedule_deactivation' ) ) {
	function rrrlgvwr_shedule_deactivation() {
		wp_clear_scheduled_hook( 'rrrlgvwr_shedule_event' );
	}
}

/* Register uninstall hook */
if ( ! function_exists( 'rrrlgvwr_uninstall' ) ) {
	function rrrlgvwr_uninstall() {
		global $wpdb;

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				delete_option( 'rrrlgvwr_options' );
			}
			switch_to_blog( $old_blog );
		} else {
			delete_option( 'rrrlgvwr_options' );
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

add_action( 'admin_menu', 'rrrlgvwr_admin_menu' );
add_action( 'init', 'rrrlgvwr_init' );
add_action( 'admin_init', 'rrrlgvwr_admin_init' );
/* Plugin Internationalization */
add_action( 'plugins_loaded', 'rrrlgvwr_plugins_loaded' );
/* Enqueue script and style */
add_action( 'admin_enqueue_scripts', 'rrrlgvwr_admin_head' );
/* Function to add action links to the plugin menu. */
add_filter( 'plugin_action_links', 'rrrlgvwr_plugin_action_links', 10, 2 );
/* Function to add links to the plugin description on the plugins page. */
add_filter( 'plugin_row_meta', 'rrrlgvwr_register_plugin_links', 10, 2 );
/* add admin notices */
add_action( 'admin_notices', 'rrrlgvwr_admin_notices' );
/* Activation shedule */
add_action( 'rrrlgvwr_shedule_event', 'rrrlgvwr_send_log' );
/* Cron shedules */
add_filter( 'cron_schedules', 'rrrlgvwr_interval_schedule' );

register_shutdown_function( 'rrrlgvwr_handle_fatal_error' );
register_deactivation_hook( __FILE__, 'rrrlgvwr_shedule_deactivation' );
register_uninstall_hook( __FILE__, 'rrrlgvwr_uninstall' );
