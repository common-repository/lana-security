<?php

class Lana_Security_Logs_List_Table extends WP_List_Table {

	private $filter_month = '';

	/** @var Lana_Security_User_Agent_Parser $ua_parser */
	private $ua_parser = null;

	/** @var bool $display_delete_message */
	private $display_delete_message = false;

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct( array(
			'singular' => 'lana_security_log',
			'plural'   => 'lana_security_logs',
			'ajax'     => false
		) );

		$this->filter_month = ! empty( $_REQUEST['filter_month'] ) ? sanitize_text_field( $_REQUEST['filter_month'] ) : '';
	}

	/**
	 * get_columns function
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'user'    => __( 'User', 'lana-security' ),
			'comment' => __( 'Comment', 'lana-security' ),
			'user_ip' => __( 'IP Address', 'lana-security' ),
			'user_ua' => __( 'User Agent', 'lana-security' ),
			'date'    => __( 'Date', 'lana-security' )
		);

		return $columns;
	}

	/**
	 * Current action
	 * @return false|string
	 */
	public function current_action() {
		if ( isset( $_REQUEST['delete_logs'] ) ) {
			return 'delete_logs';
		}

		return parent::current_action();
	}

	/**
	 * Add bulk actions
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'lana-security' )
		);

		return $actions;
	}

	/**
	 * Default column
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return null
	 */
	public function column_default( $item, $column_name ) {
		return null;
	}

	/**
	 * The checkbox column
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="lana_security_log[]" value="%s" />', $item->id );
	}

	/**
	 * The user column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_user( $item ) {

		$user = get_user_by( 'id', $item->user_id );

		/** not exists user */
		if ( ! is_a( $user, 'WP_User' ) ) {
			return __( 'Non-member', 'lana-security' );
		}

		$user_edit_url = esc_url( admin_url( 'user-edit.php?user_id=' . $user->ID ) );

		return sprintf( '<a href="%s">#%s &ndash; %s</a>', $user_edit_url, $user->user_login, $user->user_email );
	}

	/**
	 * The comment column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_comment( $item ) {
		return lana_security_get_translate_string( $item->comment );
	}

	/**
	 * The user ip column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_user_ip( $item ) {
		return $item->user_ip;
	}

	/**
	 * The user ua column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_user_ua( $item ) {
		$ua = $this->ua_parser->parse( $item->user_agent );

		return $ua->to_full_string;
	}

	/**
	 * The date column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_date( $item ) {
		if ( empty( $item->date ) ) {
			return __( '(no date)', 'lana-security' );
		}

		$date_title   = date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $item->date ) );
		$date_content = sprintf( __( '%s ago', 'lana-security' ), human_time_diff( strtotime( $item->date ), current_time( 'timestamp' ) ) );

		return sprintf( '<time title="%s">%s</time>', $date_title, $date_content );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which
	 */
	public function display_tablenav( $which ) {

		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}

		if ( 'top' == $which && true === $this->display_delete_message ) :
			?>
            <div id="message" class="updated notice notice-success">
                <p><?php _e( 'Log entries deleted', 'lana-security' ); ?></p>
            </div>
		<?php
		endif;
		?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
            </div>

			<?php if ( 'top' == $which ) : ?>
                <div class="alignleft actions">
					<?php
					global $wpdb, $wp_locale;

					$table_name  = $wpdb->prefix . 'lana_security_logs';
					$months      = $wpdb->get_results( "SELECT DISTINCT YEAR( date ) AS year, MONTH( date ) AS month FROM " . $table_name . " ORDER BY date DESC" );
					$month_count = count( $months );

					if ( $month_count && ! ( 1 == $month_count && 0 == $months[0]->month ) ) :
						$m = isset( $_REQUEST['filter_month'] ) ? $_REQUEST['filter_month'] : 0;
						?>
                        <label for="filter-month"></label>
                        <select name="filter_month" id="filter-month">
                            <option <?php selected( $m, 0 ); ?> value='0'>
								<?php _e( 'Show all dates', 'lana-security' ); ?>
                            </option>
							<?php
							foreach ( $months as $arc_row ) {
								if ( 0 == $arc_row->year ) {
									continue;
								}

								$month = zeroise( $arc_row->month, 2 );
								$year  = $arc_row->year;

								printf( "<option %s value='%s'>%s</option>", selected( $m, $year . '-' . $month, false ), esc_attr( $year . '-' . $month ), sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year ) );
							}
							?>
                        </select>
					<?php
					endif;
					?>
                    <input type="hidden" name="page" value="lana-security-logs"/>
                    <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'lana-security' ); ?>"/>
                </div>
                <div class="alignleft actions">
					<?php
					if ( current_user_can( 'manage_lana_security_logs' ) ) {
						submit_button( __( 'Delete Logs', 'lana-security' ), 'apply', 'delete_logs', false );
					}
					?>
                </div>
			<?php endif; ?>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
            <br class="clear"/>
        </div>
		<?php
	}

	/**
	 * Prepare items
	 */
	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'lana_security_logs';

		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'lana_security_logs_per_page' );
		$current_page = $this->get_pagenum();

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$query_where = "";

		if ( $this->filter_month ) {
			$query_where = " WHERE date >= '" . date( 'Y-m-01', strtotime( $this->filter_month ) ) . " 00:00:00' ";
			$query_where .= " AND date <= '" . date( 'Y-m-t', strtotime( $this->filter_month ) ) . " 23:59:59' ";
		}

		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM " . $table_name . " " . $query_where . ";" );
		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $table_name . " " . $query_where . " ORDER BY date DESC LIMIT %d, %d;", ( $current_page - 1 ) * $per_page, $per_page ) );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ( ( $total_items > 0 ) ? ceil( $total_items / $per_page ) : 1 )
		) );

		require_once LANA_SECURITY_DIR_PATH . '/includes/class-lana-security-user-agent-parser.php';
		$this->ua_parser = new Lana_Security_User_Agent_Parser();
	}

	/**
	 * Process bulk actions
	 */
	public function process_bulk_action() {
		global $wpdb;

		$action  = $this->current_action();
		$log_ids = isset( $_REQUEST['lana_security_log'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['lana_security_log'] ) ) : array();

		/**
		 * Delete
		 * log
		 */
		if ( 'delete' == $action ) {

			if ( ! isset( $_POST['_wpnonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( __( 'Sorry, you are not allowed to process bulk actions.', 'lana-security' ) );
			}

			if ( ! current_user_can( 'manage_lana_security_logs' ) ) {
				wp_die( __( 'Sorry, you are not allowed to delete security logs.', 'lana-security' ) );
			}

			if ( ! empty( $log_ids ) ) {

				foreach ( $log_ids as $log_id ) {
					$table_name = $wpdb->prefix . 'lana_security_logs';
					$wpdb->delete( $table_name, array( 'id' => $log_id ) );
				}

				$this->display_delete_message = true;
			}
		}
	}
}