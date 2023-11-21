<?php defined( 'ABSPATH' ) || exit;

class WPFFWCB_admin {
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'woocommerce_screen_ids', array( $this, 'screen_script_style') );
	}

	public function screen_script_style( $screen ){
		return array_merge($screen, array(
			$this->get_screen_id('wpffwcb_option'),
			$this->get_screen_id('wpffwcb_sale_report'),
			$this->get_screen_id('wpffwcb_log_report'),
			$this->get_screen_id('wpffwcb_settings')
		));
		return $screen;
	}

	public function get_screen_id( $slug ){
		global $_parent_pages;
		$parent = array_key_exists( $slug, $_parent_pages ) ? $_parent_pages[$slug] : '';
		return get_plugin_page_hookname( $slug, $parent );
	}

	public function admin_js_css(){
		wp_enqueue_media();
		wp_enqueue_style('thickbox');
		wp_enqueue_script('media-upload');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style('jquery-ui');
		wp_add_inline_script( 'jquery-ui-datepicker', 'jQuery( function($){ $(".wpfinance_date").datepicker({ dateFormat: "yy-mm-dd", maxDate: 0}); });' );
	}

	/**
	 * Sub menu on Woocommerce Menu
	 * @since 1.0
	 **/
	public function admin_menu() {
		$menu = add_menu_page('Finance for Woocommerce Booking', 'Finance', 'manage_options', 'wpffwcb_option', array( $this, 'invoice_screen' ), 'dashicons-welcome-widgets-menus', 58);
		add_submenu_page( 'wpffwcb_option', __('Invoices - Finance for Woocommerce Booking', 'wpfinance'), 'Invoices', 'manage_options', 'wpffwcb_option');
		$sale_report = add_submenu_page( 'wpffwcb_option', __('Sale Reports - Finance for Woocommerce Booking', 'wpfinance'), 'Sale Reports', 'manage_options', 'wpffwcb_sale_report', array( $this, 'sale_report_screen' ));
		$log_report = add_submenu_page( 'wpffwcb_option', __('Log Reports - Finance for Woocommerce Booking', 'wpfinance'), 'Log', 'manage_options', 'wpffwcb_log_report', array( $this, 'log_report_screen' ));
		$setting = add_submenu_page( 'wpffwcb_option', __('Settings - Finance for Woocommerce Booking', 'wpfinance'), 'Settings', 'manage_options', 'wpffwcb_settings', array( $this, 'settings_screen' ));

		add_action( 'admin_print_scripts-' . $menu, array( $this, 'admin_js_css') );
		add_action( 'admin_print_scripts-' . $sale_report, array( $this, 'admin_js_css') );
		add_action( 'admin_print_scripts-' . $log_report, array( $this, 'admin_js_css') );
		add_action( 'admin_print_scripts-' . $setting, array( $this, 'admin_js_css') );
		add_action( 'admin_footer', function(){ ?>
			<style type="text/css">
				.notices {
					background: #fff;
					border: 1px solid #c3c4c7;
					box-shadow: 0 1px 1px rgba(0,0,0,.04);
					padding: 1px 12px;
					border-left-color: #d63638;
					margin-top: 40px;
					border-left-width: 4px;
				}
				.notices.success {
					border-left-color: green;
				}
				.inc-journal {
					background: #fff;
					padding: 25px;
					border: 1px solid #c3c4c7;
					margin-top: 40px;
				}
				.notices + .inc-journal {
					margin-top: 10px;
				}
				.inc-journal h2 {
					margin: 0;
					font-size: 21px;
					font-weight: 400;
					line-height: 1.2;
					text-shadow: 1px 1px 1px #fff;
					padding: 0;
				}
				.jr_container {
					width: 100%;
				}
				.inc-journal label {
					display: block;
					margin-bottom: 5px;
					margin-top: 15px;
				}
				.inc-journal input[type="text"] {
					width: 100%;
					padding: 5px 10px;
				}
				.inc-journal input[type="submit"] {
					width: 100%;
					margin-top: 15px;
					padding: 5px 10px;
				}
				@media (min-width: 576px) {
					.jr_container {
						max-width: 400px;
					}
				}
				</style>
		<?php });
	}

	/**
	* Plugin Invoice Screen
	* @since 1.0
	*/
	public function invoice_screen() {
		$table = new Ffwcbook_Invoices_List_Table();
		$notices = [];
		if ( isset($_GET['invoice']) ) {
			switch ($_GET['invoice']) {
				case 'backup':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Invoice Uploaded Successfully'];
					break;

				case 'update':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Invoice Updates Successfully'];
					break;

				case 'create':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Invoice Craeted  Successfully'];
					break;
			}
		}
		$table->add_notice($notices);
		$table->display_page();
	}

	/**
	* Plugin Log Screen
	* @since 1.6
	*/
	public function log_report_screen() {
		$table = new Ffwcbook_Log_List_Table();
		$notices = [];
		if ( isset($_GET['journal']) ) {
			switch ($_GET['journal']) {
				case 'updated':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Invoice Journal Updates Successfully'];
					break;

				case 'created':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Invoice Journal Created Successfully'];
					break;
			}
		} else if ( isset($_GET['sale']) ) {
			switch ($_GET['sale']) {
				case 'updated':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Sale Report Updates Successfully'];
					break;

				case 'created':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Sale Report Created Successfully'];
					break;
			}
		} else if ( isset($_GET['invoice']) ) {
			switch ($_GET['invoice']) {
				case 'updated':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Invoice Updates Successfully'];
					break;

				case 'created':
					$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Invoice Created Successfully'];
					break;
			}
		} else if ( isset($_GET['report']) && $_GET['report'] == "backup" ) {
			$notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Report Backup Successfully'];
		}
		$table->add_notice($notices);
		$table->display_page();
	}

	/**
	* Plugin Sale Report Screen
	* @since 1.0
	*/
	public function sale_report_screen() {
		$start_date = '';
		$end_date = '';
		if ( isset($_POST['exp_sale_report']) ) {
			$errors = [];
			if ( isset($_POST['salestart']) ) {
				if ( preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['salestart'])) {
					$start_date = date('Y-m-d 00:00:00', strtotime($_POST['salestart']));
				} else {
					$errors[] = __('Invalid Start Date', 'wpfinance');
				}
			}

			if ( empty($errors) && isset($_POST['saleend']) ) {
				if ( preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['saleend'])) {
					$end_date = date('Y-m-d 23:59:59', strtotime($_POST['saleend']));
				} else {
					$errors[] = __('Invalid End Date', 'wpfinance');
				}
			}

			if ( empty($errors) && (empty($start_date) || empty($end_date)) ) {
				$errors[] = __('Start date and End Date is required', 'wpfinance');
			}

			if ( empty($errors) && ($end_date < $start_date) ) {
				$errors[] = __('End date must be greater than start date', 'wpfinance');
			}

			if ( ! empty($errors) ) {
				echo '<div class="notices">';
				foreach ($errors as $error) {
					echo '<p>' . wp_kses_post( $error ) . '</p>';
				}
				echo '</div>';
			} else {
				$is_success = WPFFWCB_generate::sale_report($end_date, $start_date, 'D');
				if ( $is_success ) {
					echo '<div class="notices success"><p>' . __('Invoice Jounal Generated', 'wpfinance') . '</p></div>';
				} else {
					echo '<div class="notices"><p>' . __('No invoices Found', 'wpfinance') . '</p></div>';
				}
			}
		} ?>
		<div class="inc-journal">
			<h2><?php _e( 'Sale Report' ); ?></h2>
			<form action="" method="post">
				<div class="jr_container">
					<label for="salestart"><?php _e('Start Date'); ?></label>
					<input type="text" class="wpfinance_date" name="salestart" value="<?php echo !empty($start_date) ? date('Y-m-d', strtotime($start_date)) : ''; ?>" placeholder="yyyy-mm-dd" id="salestart"/>

					<label for="saleend"><?php _e('End Date'); ?></label>
					<input type="text" class="wpfinance_date" name="saleend" value="<?php echo !empty($end_date) ? date('Y-m-d', strtotime($end_date)) : ''; ?>" placeholder="yyyy-mm-dd" id="saleend"/>

					<input type="submit" class="button" value="Export Sale Report" name="exp_sale_report">
				</div>
			</form>
		</div>
		<hr class="wp-header-end">
		<?php
	}

	/**
	* Get all order status array
	* @since 1.1
	**/
	private function get_order_statuses() {
		$order_statuses = array();
		foreach ( wc_get_order_statuses() as $slug => $name ) {
			$order_statuses[ str_replace( 'wc-', '', $slug ) ] = $name;
		}
		return $order_statuses;
	}

	/**
	 * Get Address Fields for the edit user pages.
	 * @since 1.1
	 * @return array Fields to display which are filtered through wpffwcb_setting_fields before being returned
	 */
	public function get_setting_fields() {
		return apply_filters(
			'wpffwcb_setting_fields',
			array(
				'global'  => array(
					'title'  => __( 'Global', 'wpfinance' ),
					'fields' => array(
						'dropbox_token' => array(
							'label'       => __( 'Dropbox Access Token', 'wpfinance' ),
							'description' => sprintf( __('Enter Dropbox Access Token. %s. Make sure you have enabled %s %s %s %s permission & Set %s as %s before Generate Access Token', 'wpfinance'), '<a href="https://www.dropbox.com/developers/apps/create" target="_blank">' . __('Click here to create Dropbox App') . '</a>', '<code>files.metadata.write</code>', '<code>files.metadata.read</code>', '<code>files.content.write</code>', '<code>files.content.read</code>', '<code>Access token expiration</code>', '<code><b>No expiration</b></code>' ),
						),
						'logo'  => array(
							'label'       => __( 'Invoice Header Logo', 'wpfinance' ),
							'description' => sprintf( __('Only %s image are allowed', 'wpfinance'), '<code>jpg</code>, <code>jpeg</code>' ),
							'type' 		  => 'upload',
							'text'		  => __( 'Change', 'wpfinance' ),
						),
						'company_info' => array(
							'label'       => __( 'Company Info', 'wpfinance' ),
							'description' => __( 'This info will show on Invoice company info section', 'wpfinance' ),
							'type'		  => 'editor'
						),
						'left_info' => array(
							'label'       => __( 'Bottom Left Info', 'wpfinance' ),
							'description' => __( 'This info will show on Invoice bottom left side', 'wpfinance' ),
							'type'		  => 'editor',
							'is_enable'   => true,
						),
						'right_info' => array(
							'label'       => __( 'Bottom Right Info', 'wpfinance' ),
							'description' => __( 'This info will show on Invoice bottom right side', 'wpfinance' ),
							'type'		  => 'editor',
							'is_enable'   => true,
						),
					),
				),
				'invoice' => array(
					'title'  => __( 'Invoice', 'wpfinance' ),
					'fields' => array(
						'order_status' => array(
							'label'       => __( 'Order Status', 'wpfinance' ),
							'description' => sprintf( __( 'Select which order statuses will generate %s. Leave blank for any status.', 'wpfinance' ), '<code>Invoices</code>' ),
							'class'       => 'js_field-status',
							'type'        => 'select',
							'multiple'    => true,
							'options'     => $this->get_order_statuses(),
						),
						'booking_status' => array(
							'label'       => __( 'Booking Status', 'wpfinance' ),
							'description' => sprintf( __( 'Select which order statuses will generate %s. Leave blank for any status.', 'wpfinance' ), '<code>Invoices</code>' ),
							'class'       => 'js_field-status',
							'type'        => 'select',
							'multiple'    => true,
							'options'     => get_wc_booking_statuses('', true),
						),
						'show_sku' => array(
							'label'       => __( 'Show SKU Info', 'wpfinance' ),
							'description' => __( 'Check, if you want to show SKU info in invoice', 'wpfinance' ),
							'type'		  => 'checkbox',
						),
						'show_variation' => array(
							'label'       => __( 'Show Variation Info', 'wpfinance' ),
							'description' => __( 'Check, if you want to show Variation info in invoice', 'wpfinance' ),
							'type'		  => 'checkbox',
						),
						'show_booking' => array(
							'label'       => __( 'Show Booking ID', 'wpfinance' ),
							'description' => __( 'Check, if you want to show Booking ID in invoice', 'wpfinance' ),
							'type'		  => 'checkbox',
						),
					),
				),
				'sale_report' => array(
					'title'  => __( 'Sale Report', 'wpfinance' ),
					'fields' => array(
						'sale_order_status' => array(
							'label'       => __( 'Order Status', 'wpfinance' ),
							'description' => sprintf( __( 'Select which order statuses will generate %s. Leave blank for any status.', 'wpfinance' ), '<code>Sale Report</code>' ),
							'class'       => 'js_field-status',
							'type'        => 'select',
							'multiple'    => true,
							'options'     => wc_get_order_statuses(),
						)
					),
				),
			)
		);
	}

	/**
	 * Save Address Fields on edit user pages.
	 * @since 1.1
	 */
	private function save_customer_meta_fields() {
		$save_fields = $this->get_setting_fields();

		$save_data = get_option( "wpffwcb_option", array() );
		foreach ( $save_fields as $fieldset ) {
			foreach ( $fieldset['fields'] as $key => $field ) {
				if ( isset( $_POST[$key], $field['type'] ) && $field['type'] === 'checkbox' ) {
					$save_data[$key] = ['value' => isset($_POST[$key]), 'enable' => 1];
				} elseif ( isset( $_POST[$key], $field['type'] ) && $field['type'] === 'editor' ) {
					$save_data[$key] = ['value' => $_POST[$key], 'enable' => 1];
				} elseif ( isset( $_POST[$key] ) ) {
					$save_data[$key] = ['value' => wc_clean($_POST[$key]), 'enable' => 1];
				} else {
					$save_data[$key] = ['value' => '', 'enable' => 1];
				}

				if ( isset($field['is_enable']) && $field['is_enable'] == 'true' ) {
					$save_data[$key]['enable'] = isset( $_POST[$key.'_is_enable'] );
				} else {
					$save_data[$key]['enable'] = 1;
				}
			}
		}
		update_option( "wpffwcb_option", $save_data );
		return $save_data;
	}

	/**
	 * Save Address Fields on edit user pages.
	 * @since 1.2
	 */
	private function reset_plugin($delete_setting = false) {
		global $wpdb;
		$table_name = $wpdb->prefix . WPFFWCB_TABLE;
		$truncatetable= $wpdb->query("TRUNCATE TABLE $table_name");

		if ( $truncatetable ) {
			$wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = 125000");
			
			if ( $delete_setting ) {
				update_option( "wpffwcb_option", []);
			}

			// empty directories
			$wp_upload_dir = wp_upload_dir();
			$upload_dir = trailingslashit($wp_upload_dir['basedir']) . WPFFWCB_DIRNAME;
			$wpffwcb_invoice = $upload_dir . '/Invoice';
			$wpffwcb_invoice_journal = $upload_dir . '/Invoice-Journal';
			$wpffwcb_sale_reports = $upload_dir . '/Sale-Reports';

			if ( is_dir($wpffwcb_invoice) ) {
				$files = glob($wpffwcb_invoice.'/*');
				foreach($files as $file) {
					if ( is_file($file) ) {
						unlink($file); 
					}
				}
			}
			if ( is_dir($wpffwcb_invoice_journal) ) {
				$files = glob($wpffwcb_invoice_journal.'/*');
				foreach($files as $file) {
					if ( is_file($file) ) {
						unlink($file); 
					}
				}
			}
			if ( is_dir($wpffwcb_sale_reports) ) {
				$files = glob($wpffwcb_sale_reports.'/*');
				foreach($files as $file) {
					if ( is_file($file) ) {
						unlink($file); 
					}
				}
			}
			
			return true;
		} else {
			return false;
		}
	}

	/**
	* Plugin Setting Screen
	* @since 1.0
	*/
	public function settings_screen() { 
		$errors = $success = [];
		$save_data = get_option( "wpffwcb_option", array() );

		if ( isset($_POST['submit']) ) {
			if ( wp_verify_nonce($_POST['wpffwcb_setting_nonce'], 'wpffwcb_setting_nonce_value' ) ) {
				$save_data = $this->save_customer_meta_fields();
				$success[] = __('Settings saved.', 'wpfinance');
			} else {
				$errors[] = __('Invalid form submitted.', 'wpfinance');
			}
		}

		if ( isset($_POST['reset']) ) {
			if ( wp_verify_nonce($_POST['wpffwcb_setting_nonce'], 'wpffwcb_setting_nonce_value' ) ) {
				if ( $this->reset_plugin(true) ) {
					$success[] = __('Reset successfully', 'wpfinance');
					$save_data = get_option( "wpffwcb_option", array() );
				} else {
					$errors[] = __('Failed to Reset.', 'wpfinance');
				}
			} else {
				$errors[] = __('Invalid form submitted.', 'wpfinance');
			}
		} ?>
		<div class="wrap">
			<h1><?php _e('Settings', 'wpfinance'); ?></h1>
			<?php if ( is_array($errors) && !empty($errors) ) {
				echo '<div id="message" class="notice notice-error">';
				foreach ($errors as $error) {
					echo "<p><strong>".__("ERROR:", "wpfinance")."</strong> ".$error."</p>";
				}
				echo '</div>';
			} else if ( is_array($success) && !empty($success) ) {
				echo '<div id="message" class="notice notice-success">';
				foreach ($success as $succes) {
					echo "<p><strong>".__("SUCCESS:", "wpfinance")."</strong> ".$succes."</p>";
				}
				echo '</div>';
			} ?>

			<form id="markorder" action="" method="post" class="validate" enctype="multipart/form-data" novalidate>
				<?php $show_fields = $this->get_setting_fields();
					foreach ( $show_fields as $fieldset_key => $fieldset ) : ?>
					<h2><?php echo $fieldset['title']; ?></h2>
					<table class="form-table">
						<?php foreach ( $fieldset['fields'] as $key => $field ) : ?>
							<tr>
								<th>
									<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
								</th>
								<td>
									<?php if ( ! empty( $field['type'] ) && 'select' === $field['type'] ) : ?>
										<select name="<?php echo (isset($field['multiple']) && $field['multiple'] == 'true') ? esc_attr( $key.'[]' ) : esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( $field['class'] ); ?>" style="width: 25em;" <?php echo (isset($field['multiple']) && $field['multiple'] == 'true') ? 'multiple' : ''; ?>>
											<?php $selected = isset($save_data[$key]['value']) ? $save_data[$key]['value'] : '';
											foreach ( $field['options'] as $option_key => $option_value ) :
												if ( is_array($selected) && in_array($option_key, $selected) ) {
													$select = true;
												} else if ( $selected == $option_key ) {
													$select = true;
												} else {
													$select = false;
												} ?>
												<option value="<?php echo esc_attr( $option_key ); ?>" <?php if ( $select ) echo 'selected="selected"'; ?>><?php echo esc_html( $option_value ); ?></option>
											<?php endforeach; ?>
										</select>
									<?php elseif ( ! empty( $field['type'] ) && 'checkbox' === $field['type'] ) : ?>
										<?php if ( isset($field['text']) ) {
											echo $field['text'];
										} ?>
										<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="1" class="<?php echo isset($field['class']) ? esc_attr( $field['class'] ) : '' ; ?>" <?php echo isset($save_data[$key]['value']) ? checked( (int) $save_data[$key]['value'], 1 ) : ''; ?> />
									<?php elseif ( ! empty( $field['type'] ) && 'button' === $field['type'] ) : ?>
										<button type="button" id="<?php echo esc_attr( $key ); ?>" class="button <?php echo esc_attr( $field['class'] ); ?>"><?php echo esc_html( $field['text'] ); ?></button>
									<?php elseif ( ! empty( $field['type'] ) && 'upload' === $field['type'] ) : ?>
										<p>

											<?php /*$fullsize_path = get_attached_file( 66342 ); 

											var_dump($fullsize_path);

											wp_get_attachment_url( 12 );*/

											?>
											<img class="ff_logo_img" src="<?php echo (isset($save_data[$key]['value']) && $save_data[$key]['value'] !== false) ? wp_get_attachment_url($save_data[$key]['value']) : WPFFWCB_ASSETS_URL . 'img/logo-default.jpg'; ?>" height="80" width="auto"/>
											<button type="button" class="ff_logo_button button <?php echo ( isset( $field['class'] ) ? esc_attr( $field['class'] ) : '' ); ?>" id="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['text'] ); ?></button>
											<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo (isset($save_data[$key]['value']) && $save_data[$key]['value'] !== false) ? $save_data[$key]['value'] : ''; ?>">
										</p>
									<?php elseif ( ! empty( $field['type'] ) && 'editor' === $field['type'] ) : ?>
										<?php 
										$value = isset($save_data[$key]['value']) ? $save_data[$key]['value'] : '';
										$args = array(
											'textarea_name' => esc_attr($key),
											'media_buttons' => false,
											'textarea_rows' => 5,
											'teeny' => true,
											'quicktags' => false,
											'tinymce' => array(
												'paste_strip_class_attributes' => true,
												'paste_remove_spans' => true,
												'paste_remove_styles' => true,
												'paste_text_use_dialog' => false,
												'keep_styles' => false,
												'wpautop' => false,
												'toolbar1' => 'bold,italic,separator,undo,redo',
												'toolbar2' => '',
												'toolbar3' => ''
											)
										);
										wp_editor( $value, esc_attr($key), $args ); ?> 
									<?php else : ?>
										<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo isset($save_data[$key]['value']) ? esc_attr( $save_data[$key]['value'] ) : '' ; ?>" class="<?php echo ( ! empty( $field['class'] ) ? esc_attr( $field['class'] ) : 'regular-text' ); ?>" />
									<?php endif; ?>
									<?php if ( isset($field['is_enable']) && $field['is_enable'] == 'true' ) { ?>
										<label for="<?php echo esc_attr( $key . '_is_enable' ); ?>"><?php _e('Enable', 'wpfinance'); ?>
											<input name="<?php echo esc_attr( $key . '_is_enable' ); ?>" type="checkbox" id="<?php echo esc_attr( $key . '_is_enable' ); ?>" value="1" class="<?php echo isset($field['class']) ? esc_attr( $field['class'] ) : ''; ?>" <?php echo isset($save_data[$key]['enable']) ? checked( (int) $save_data[$key]['enable'], 1 ) : ''; ?> />
										</label>
									<?php } ?>
									<p class="description"><?php echo isset($field['description']) ? wp_kses_post( $field['description'] ) : ''; ?></p>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endforeach; ?>
				<?php echo wp_nonce_field( "wpffwcb_setting_nonce_value", "wpffwcb_setting_nonce" ); ?>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e("Save Changes", "wpfinance"); ?>"><span class="spinner"></span>

					<input type="submit" name="reset" id="reset" class="button button-secondary" value="<?php _e("Reset", "wpfinance"); ?>">
				</p>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						$('.ff_logo_button').click(function(e) {
							var $this = $(this);
							e.preventDefault();
							var logo_uploader = wp.media({
								title: 'Invoice Logo',
								button: {
									text: 'Upload Image'
								},
								library: {
									type: ['image/jpeg', 'image/jpg']
								},
								multiple: false
							}).on('select', function() {
								var attachment = logo_uploader.state().get('selection').first().toJSON();
								$this.parent().find('img').attr('src', attachment.url);
								$this.parent().find('input[type="hidden"]').val(attachment.id);
							}).open();
						});
						$( '.js_field-status' ).selectWoo().change( this.change_country );
                    	$( '.js_field-status' ).trigger( 'change', [ true ] );
					});
				</script>
			</form>
		</div>
		<?php
	}
}

WPFFWCB_admin::instance();