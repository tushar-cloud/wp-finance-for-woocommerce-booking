<?php
/**
 * Plugin Name: WP Finance for Woocommerce Booking
 * Version: 1.9
 * Plugin URI: https://www.freelancer.com/u/zabubakar
 * Description: Automatically generate invoices for Booking Order and sale report with Dropbox as backup file storage
 * Author: Abubakar Wazih Tushar
 * Author URI: https://www.freelancer.com/u/zabubakar
 * Requires at least: 5.6
 * Tested up to: 6.2.2
 * Requires PHP: 7.4
 *
 * Text Domain: wpfinance
 * Domain Path: /languages/
 *
 * @author Abubakar Wazih Tushar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! function_exists( 'Finance_for_wc_booking') ) {
	function WP_finance_for_wc_booking() {
		defined( 'WPFFWCB_VER' )		|| define( 'WPFFWCB_VER', '1.9' );
		defined( 'WPFFWCB_FILE' )		|| define( 'WPFFWCB_FILE', __FILE__ );
		defined( 'WPFFWCB_DIR' )		|| define( 'WPFFWCB_DIR', dirname( WPFFWCB_FILE ) );
		defined( 'WPFFWCB_DIRNAME' )	|| define( 'WPFFWCB_DIRNAME', 'wp-finance-for-wcb' );
		defined( 'WPFFWCB_URL' )		|| define( 'WPFFWCB_URL', plugin_dir_url( WPFFWCB_FILE ) );
		defined( 'WPFFWCB_PATH' )		|| define( 'WPFFWCB_PATH', plugin_dir_path( WPFFWCB_FILE ) );
		defined( 'WPFFWCB_ASSETS_DIR' )	|| define( 'WPFFWCB_ASSETS_DIR', trailingslashit( WPFFWCB_DIR ) . 'assets' );
		defined( 'WPFFWCB_ASSETS_URL' )	|| define( 'WPFFWCB_ASSETS_URL', esc_url( trailingslashit( plugins_url( '/assets/', WPFFWCB_FILE ) ) ) );
		defined( 'WPFFWCB_TABLE' )		|| define( 'WPFFWCB_TABLE', 'wpfinance' );
		
		# load text domain
		add_action( 'plugins_loaded', function() {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', function() {
					echo '<div class="error"><p><strong>' . sprintf( esc_html__( '"WP Finance for Woocommerce Booking" requires WooCommerce to be installed and active. You can download %s here.', 'wpfinance' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
				});
				deactivate_plugins( plugin_basename( WPFFWCB_FILE ) );
				return;
			}
			if ( ! class_exists( 'WC_Bookings' ) ) {
				add_action( 'admin_notices', function() {
					echo '<div class="error"><p><strong>' . sprintf( esc_html__( '"WP Finance for Woocommerce Booking" requires WooCommerce Booking to be installed and active. You can purchase %s here.', 'wpfinance' ), '<a href="https://woocommerce.com/products/woocommerce-bookings/" target="_blank">WooCommerce Booking</a>' ) . '</strong></p></div>';
				});
				deactivate_plugins( plugin_basename( WPFFWCB_FILE ) );
				return;
			}

			load_plugin_textdomain( 'wpfinance', false, dirname( plugin_basename( WPFFWCB_FILE ) ) . '/languages/' );
		});

		# load the loader class 
		require WPFFWCB_PATH . 'includes/mpdf/vendor/autoload.php';
		require WPFFWCB_PATH . 'includes/core/autoload.php';
		require WPFFWCB_PATH . 'includes/core/generate.php';
		require WPFFWCB_PATH . 'includes/core/dropbox.php';
		require WPFFWCB_PATH . 'includes/admin/invoice-table.php';
		require WPFFWCB_PATH . 'includes/admin/log-table.php';
		require WPFFWCB_PATH . 'includes/admin/loader.php';
	}
}
WP_finance_for_wc_booking();