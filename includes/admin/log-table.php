<?php if ( ! defined( 'ABSPATH' ) ) exit;


if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


class Ffwcbook_Log_List_Table extends WP_List_Table {

	/**
	 * @var string Localised string displayed in the <h1> element above the able.
	 */
	protected $table_header;

	/**
	 * @var array Notices to display when loading the table. Array of arrays of form array( 'class' => {updated|error}, 'message' => 'This is the notice text display.' ).
	 */
	protected $admin_notices = array();

	/**
	 * @var array The status name => count combinations for this table's items. Used to display status filters.
	 */
	protected $status_counts = array();


	/**
	 * Enables search in this table listing. If this array
	 * is empty it means the listing is not searchable.
	 */
	protected $search_by = array();


    
    public function __construct() {
        global $status, $page;

        $this->table_header = __( 'Log', 'wpfinance' );
        $this->search_by = array(
			'hook',
			'args',
			'claim_id',
		);

        parent::__construct(array(
            'singular' => 'person',
            'plural' => 'persons',
        ));
    }

    /**
    * Add notice
    **/
    public function add_notice($notices) {
        if ( is_array($notices) && !empty($notices) ) {
            foreach ($notices as $key => $notice) {
                $this->admin_notices[] = $notice;
            }
        }
    }
        

    /**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
    public function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'ID' => __('ID', 'wpfinance'),
            //'date' => __('Date', 'wpfinance'),
            'is_backup' => __('Backup Status', 'wpfinance'),
            'view' => __('Type', 'wpfinance'),
            'backup_date' => __('Backup Date', 'wpfinance'),
            'response' => __('Actions', 'wpfinance'),
        );
        return $columns;
    }

    public function column_default($item, $column_name) {
        return $item[$column_name];
    }

    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="ID[]" value="%d" />', $item['ID']);
    }

    /**
     * [OPTIONAL] this is example, how to render column with actions,
     * when you hover row "Edit | Delete" links showed
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_ID($item) {
        $name = '';
        switch ($item['view']) {
            case 'journal':
                $name = sprintf('Journal %s', '#' . $item['ID']);
                break;

            case 'sale':
                $name = sprintf('Sale %s', '#' . $item['ID']);
                break;
            
            default:
                $name = sprintf('Invoice %s', '#' . $item['ID']);
                break;
        }
    	return  '<strong style="color: #2271b1;">' . $name . '</strong>';
    }

    /**
     * [OPTIONAL] this is example, how to render specific column
     *
     * method name must be like this: "column_[column_name]"
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_date($item) {
    	$local_time = current_datetime();
        if ( !empty($item['date']) ) {
    		return date_i18n( 'F j, Y, h:i:s A', strtotime($item['date']) + $local_time->getOffset() );
    	} 

    	return '&mdash;';
    }

     /**
     * [OPTIONAL] this is example, how to render column with actions,
     * when you hover row "Edit | Delete" links showed
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_is_backup($item) {
    	if ( strtolower($item['is_backup']) == 'success' ) {
    		$return = '<mark class="order-status status-completed"><span>Success</span></mark>';
    	} else if ( strtolower($item['is_backup']) == 'failed' ) {
    		$return = '<mark class="order-status status-failed tips" data-tip="'.$item['response'].'"><span>Failed</span></mark>';
    	} else {
    		$return = '<mark class="order-status status-on-hold"><span>Hold</span></mark>';
    	}

        return $return;
    }

    /**
     * [OPTIONAL] this is example, how to render specific column
     *
     * method name must be like this: "column_[column_name]"
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_view($item) {
        $name = '';
        switch ($item['view']) {
            case 'journal':
                $name = 'Journal';
                break;

            case 'sale':
                $name = 'Sale';
                break;
            
            default:
                $name = 'Invoice';
                break;
        }
        return  '<strong>' . $name . '</strong>';
    }

    /**
     * [OPTIONAL] this is example, how to render specific column
     *
     * method name must be like this: "column_[column_name]"
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_order_id($item) {
    	$edit_order_url = apply_filters( 'woocommerce_get_edit_order_url', get_admin_url( null, 'post.php?post=' . $item['order_id'] . '&action=edit' ) );
        return sprintf('<a href="%s">Order %s</a>', $edit_order_url, '#' . $item['order_id']);
    }

    /**
     * [OPTIONAL] this is example, how to render specific column
     *
     * method name must be like this: "column_[column_name]"
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_backup_date($item) {
    	$local_time = current_datetime();
    	if ( !empty($item['backup_date']) ) {
    		return date_i18n( 'F j, Y, h:i:s A', strtotime($item['backup_date']) + $local_time->getOffset() );
    	} 

    	return '&mdash;';
    }

    /**
     * [OPTIONAL] this is example, how to render specific column
     *
     * method name must be like this: "column_[column_name]"
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    public function column_response($item) {
    	$source = maybe_unserialize($item['source']);
    	$return = '';

        $create_url = admin_url('admin.php?page=wpffwcb_option&wpffwcb_log=create&log_id='.$item['ID'].'&type='.$item['view']);
        $update_url = admin_url('admin.php?page=wpffwcb_option&wpffwcb_log=update&log_id='.$item['ID'].'&type='.$item['view']);
        $view_url = admin_url('admin.php?page=wpffwcb_option&wpffwcb_log=view&log_id='.$item['ID'].'&time='.time().'&type='.$item['view']);
        $backup_url = admin_url('admin.php?page=wpffwcb_option&wpffwcb_log=backup&log_id='.$item['ID'].'&type='.$item['view']);

    	if ( strtolower($item['is_backup']) == 'success' ) {
    		$return .= '&nbsp;<a href="'.add_query_arg( '_wpnonce', wp_create_nonce( '__update_inv' ), $update_url ).'" class="button small">Update<a>';
    	} else if ( strtolower($item['is_backup']) == 'failed' || (isset($source['path']) && file_exists($source['path']) && empty($item['is_backup']) ) ) {
            $return .= '&nbsp;<a href="'.add_query_arg( '_wpnonce', wp_create_nonce( '__backup_inv' ), $backup_url ).'" class="button small">Backup<a>';
    		$return .= '&nbsp;<a href="'.add_query_arg( '_wpnonce', wp_create_nonce( '__update_inv' ), $update_url ).'" class="button small">Update<a>';
    	}

    	if ( isset($source['path']) && ! file_exists($source['path']) ) {
            $return .= '&nbsp;<a href="'.add_query_arg( '_wpnonce', wp_create_nonce( '__create_inv' ), $create_url ).'" class="button small">Create<a>';
    	} else {
            $return .= '&nbsp;<a target="_blank" href="'.add_query_arg( '_wpnonce', wp_create_nonce( '__view_inv' ), $view_url ).'" class="button small">View<a>';
    	}

        return $return;
    }

    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'ID' => array('ID', true),
            'date' => array('date', true),
            'view' => array('view', false),
            'order_id' => array('order_id', false),
            'backup_date' => array('backup_date', false),
        );
        return $sortable_columns;
    }

    /**
     * [OPTIONAL] Return array of bult actions if has any
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
            'backup' => 'Backup'
        );
        return $actions;
    }

    /**
     * [OPTIONAL] This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
    public function process_bulk_action() {
        if ('backup' == $this->current_action()) {
            $ids = isset($_REQUEST['ID']) ? $_REQUEST['ID'] : array();

            if (!empty($ids)) {
                global $wpdb;
                $table_name = $wpdb->prefix . WPFFWCB_TABLE;
        
                foreach ($ids as $key => $id) {
                    $data = $wpdb->get_row("SELECT * FROM $table_name WHERE `ID` = '$id'");
                    if ( isset($data->source, $data->ID) && !empty($data->source) ) {
                        $invoice_data = maybe_unserialize($data->source);
                        if ( isset($invoice_data['path'], $invoice_data['url'], $invoice_data['basename']) ) {
                            if ( ! file_exists($invoice_data['path']) ) {
                                $this->admin_notices[] = ['class' => 'notice is-dismissible notice-error', 'message' => 'File doesn\'t exist. Try to Update or Create pdf #'.$id];
                            }
                            $upload_data = Ffwcbook_dropbox::upload_file($invoice_data);

                            if ( $upload_data && isset($upload_data['http_code'], $upload_data['response']) ) {
                                if ( $upload_data['http_code'] == 200 ) {
                                    $up_data = [
                                        'is_backup' => 'success',
                                        'backup_date' => $upload_data['datetime'],
                                        'response' => $upload_data['response']
                                    ];
                                    $this->admin_notices[] = ['class' => 'notice is-dismissible notice-success', 'message' => 'Invoice Backup Success! #'.$id];
                                } else {
                                    $this->admin_notices[] = ['class' => 'notice is-dismissible notice-error', 'message' => 'Failed to Backup! #'.$id];
                                }
                            } else {
                                $this->admin_notices[] = ['class' => 'Failed to Backup. Server misconfiguration', 'message' => 'Failed to Backup! #'.$id];
                            }
                            $wpdb->update($table_name, $up_data, ['ID' => $id]);
                        } else {
                            $this->admin_notices[] = ['class' => 'Failed to Backup. Server misconfiguration', 'message' => 'Invoice data not found! #'.$id];
                            wp_die( 'Invoice data not found' );
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * Search Box
     */
    public function search_box( $text, $input_id ) {
	    if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
	        return;

	    $input_id = $input_id . '-search-input';

	    if ( ! empty( $_REQUEST['orderby'] ) )
	        echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
	    if ( ! empty( $_REQUEST['order'] ) )
	        echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />'; ?>
	    <p class="search-box">
	    	<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
	    	<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
	    	<?php submit_button( $text, 'button', false, false, array('id' => 'search-submit') ); ?>
	    </p>
	<?php }

    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    public function prepare_items($search ='') {
        global $wpdb;
        $table_name = $wpdb->prefix . WPFFWCB_TABLE;

        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged'] - 1) * $per_page) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'ID';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
        $status = (isset($_REQUEST['status']) && in_array($_REQUEST['status'], array('failed', 'success'))) ? $_REQUEST['status'] : '';

        if ( !empty($search) ) {
        	preg_match_all('!\d+!', $search, $matches);
        	if ( isset($matches[0]) && !empty($matches[0]) && is_array($matches[0]) ) {
        		$qWhere = "";
        		$separator = ' OR ';
	        	if (strpos($search, 'booking') !== false || strpos($search, 'book') !== false || strpos($search, 'bookings') !== false || strpos($search, 'booking_id') !== false) {

	        		$where = '';
	        		foreach ($matches[0] as $sc) {
	        			$where .= "`booking_id` like ('%{$sc}%')".$separator;
	        		}
	        		$where = trim($where, $separator);

	        		$qWhere .= "WHERE ({$where}) ";
	        	} else if (strpos($search, 'order') !== false || strpos($search, 'orders') !== false || strpos($search, 'order_id') !== false) {

	        		$where = '';
	        		foreach ($matches[0] as $sc) {
	        			$where .= "`order_id` like ('%{$sc}%')".$separator;
	        		}
	        		$where = trim($where, $separator);

	        		$qWhere .= "WHERE ({$where}) ";
	        	} else if (strpos($search, 'invoice') !== false || strpos($search, 'invoices') !== false || strpos($search, 'ID') !== false) {

	        		$where = '';
	        		foreach ($matches[0] as $sc) {
	        			$where .= "`ID` like ('%{$sc}%')".$separator;
	        		}
	        		$where = trim($where, $separator);

	        		$qWhere .= "WHERE ({$where}) ";
	        	} else {
        			$where_booking = $where_order = $where_ID = '';
	        		foreach ($matches[0] as $sc) {
	        			$where_booking .= "`booking_id` like ('%{$sc}%')".$separator;
	        			$where_order .= "`order_id` like ('%{$sc}%')".$separator;
	        			$where_ID .= "`ID` like ('%{$sc}%')".$separator;
	        		}
	        		$where_booking = trim($where_booking, $separator);
	        		$where_order = trim($where_order, $separator);
	        		$where_ID = trim($where_ID, $separator);

	        		$qWhere .= "WHERE (({$where_booking}) OR ({$where_order}) OR ({$where_ID})) ";
	        	}
	        	if ( !empty($status) ) {
	        		$qWhere .= "AND `is_backup` = '$status' ";
	        	}

	        	$total_items = $wpdb->get_var("SELECT COUNT(ID) FROM $table_name $qWhere");

	        	$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $qWhere ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

	        }
        } else {
        	if ( !empty($status) ) {
        		$total_items = $wpdb->get_var("SELECT COUNT(ID) FROM $table_name WHERE `is_backup` = '$status'");

        		$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE `is_backup` = '$status' ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
        	} else {
        		$total_items = $wpdb->get_var("SELECT COUNT(ID) FROM $table_name");

        		$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
        	}
        }

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    /**
	 * Return the search filter for this request, if any.
	 *
	 * @return string
	 */
	protected function get_request_search_query() {
		$search_query = ( ! empty( $_GET['s'] ) ) ? $_GET['s'] : '';
		return $search_query;
	}

	/**
	 * Display the table heading and search query, if any
	 */
	protected function display_header() {
		echo '<h1 class="wp-heading-inline">' . esc_attr( $this->table_header ) . '</h1>';

		if ( $this->get_request_search_query() ) {
			echo '<span class="subtitle">' . sprintf( __( 'Search results for: %s', 'wpfinance' ), '<strong>'.$this->get_request_search_query().'</strong>' ) . '</span>';
		}
		echo '<hr class="wp-header-end">';
	}

	/**
	 * Display the table heading and search query, if any
	 */
	public function display_admin_notices() {
		foreach ( $this->admin_notices as $notice ) {
			echo '<div id="message" class="' . $notice['class'] . '">';
			echo '	<p>' . wp_kses_post( $notice['message'] ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Return the status filter for this request, if any.
	 *
	 * @return string
	 */
	protected function get_request_status() {
		$status = ( ! empty( $_GET['status'] ) ) ? $_GET['status'] : '';
		return $status;
	}

	/**
	 * Prints the available statuses so the user can click to filter.
	 */
	protected function display_filter_by_status() {
		global $wpdb;
		$table_name = $wpdb->prefix . WPFFWCB_TABLE;

		$total_items = $wpdb->get_var("SELECT COUNT(ID) FROM $table_name");
		$total_failed = $wpdb->get_var("SELECT COUNT(ID) FROM $table_name WHERE `is_backup` = 'failed'");
		$total_success = $wpdb->get_var("SELECT COUNT(ID) FROM $table_name WHERE `is_backup` = 'success'");
		$this->status_counts = [
			'all' => $total_items,
			'failed' => $total_failed,
			'success' => $total_success
		];

		$status_list_items = array();
		$request_status    = $this->get_request_status();

		// Helper to set 'all' filter when not set on status counts passed in
		if ( ! isset( $this->status_counts['all'] ) ) {
			$this->status_counts = array( 'all' => array_sum( $this->status_counts ) ) + $this->status_counts;
		}

		foreach ( $this->status_counts as $status_name => $count ) {

			if ( 0 === $count ) {
				continue;
			}

			if ( $status_name === $request_status || ( empty( $request_status ) && 'all' === $status_name ) ) {
				$status_list_item = '<li class="%1$s"><strong>%3$s</strong> (%4$d)</li>';
			} else {
				$status_list_item = '<li class="%1$s"><a href="%2$s">%3$s</a> (%4$d)</li>';
			}

			$status_filter_url   = ( 'all' === $status_name ) ? remove_query_arg( 'status' ) : add_query_arg( 'status', $status_name );
			$status_filter_url   = remove_query_arg( array( 'paged', 's' ), $status_filter_url );
			$status_list_items[] = sprintf( $status_list_item, esc_attr( $status_name ), esc_url( $status_filter_url ), esc_html( ucfirst( $status_name ) ), absint( $count ) );
		}

		if ( $status_list_items ) {
			echo '<ul class="subsubsub">';
			echo implode( " | \n", $status_list_items );
			echo '</ul>';
		}
	}

	/**
	 * Get the text to display in the search box on the list table.
	 */
	protected function get_search_box_button_text() {
		return __( 'Search', 'wpfinance' );
	}

	/**
	 * Renders the table list, we override the original class to render the table inside a form
	 * and to render any needed HTML (like the search box). By doing so the callee of a function can simple
	 * forget about any extra HTML.
	 */
	protected function display_table() {
		echo '<form id="' . esc_attr( $this->_args['plural'] ) . '-filter" method="get">';
		foreach ( $_GET as $key => $value ) {
			if ( '_' === $key[0] || 'paged' === $key || is_array($value) || is_object($value) ) {
				continue;
			}
            echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}
		if ( ! empty( $this->search_by ) ) {
			echo $this->search_box( $this->get_search_box_button_text(), 'plugin' );
		}
		parent::display();
		echo '</form>';
	}

    public function display_page() {
		$this->prepare_items($this->get_request_search_query());

		echo '<div class="wrap">';
		$this->display_header();
		$this->display_admin_notices();
		$this->display_filter_by_status();
		$this->display_table();
		echo '</div>';
	}
}