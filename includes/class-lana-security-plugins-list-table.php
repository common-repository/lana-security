<?php

class Lana_Security_Plugins_List_Table extends WP_List_Table {

	/** @var bool $display_activated_message */
	private $display_activated_message = false;

	/** @var bool $display_deactivated_message */
	private $display_deactivated_message = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $page;

		parent::__construct( array(
			'singular' => 'lana_security_plugin',
			'plural'   => 'lana_security_plugins',
			'ajax'     => false
		) );

		$page = $this->get_pagenum();
	}

	/**
	 * Set display activate message
	 *
	 * @param bool $display_activated_message
	 */
	public function set_display_activated_message( $display_activated_message ) {
		$this->display_activated_message = $display_activated_message;
	}

	/**
	 * Set display deactivate message
	 *
	 * @param bool $display_deactivated_message
	 */
	public function set_display_deactivated_message( $display_deactivated_message ) {
		$this->display_deactivated_message = $display_deactivated_message;
	}

	/**
	 * Get table classes
	 * @return array
	 */
	protected function get_table_classes() {
		return array( 'wp-list-table', 'widefat', 'lana_security_plugins', 'plugins' );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		$classes = array();

		/** add active class */
		if ( true == $item['option'] ) {
			$classes[] = 'active';
		}

		/** add inactive class */
		if ( false == $item['option'] ) {
			$classes[] = 'inactive';
		}

		echo sprintf( '<tr class="%s">', implode( ' ', $classes ) );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * get_columns function
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'plugin'      => __( 'Plugin', 'lana-security' ),
			'description' => __( 'Description', 'lana-security' )
		);

		return $columns;
	}

	/**
	 * Add bulk actions
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'activate'   => __( 'Activate', 'lana-security' ),
			'deactivate' => __( 'Deactivate', 'lana-security' )
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
		return sprintf( '<input type="checkbox" name="lana_security_plugin[]" value="%s" />', $item['id'] );
	}

	/**
	 * The plugin column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function _column_plugin( $item ) {
		global $page;

		$actions = array();

		/** add settings to plugin */
		if ( isset( $item['settings'] ) ) {
			$actions['settinggs'] = '<a href="' . admin_url( esc_url( 'admin.php?page=lana-security-settings.php' . $item['settings'] ) ) . '">' . __( 'Settings', 'lana-security' ) . '</a>';
		}

		/** add deactivate to active plugin */
		if ( true == $item['option'] ) {
			$actions['deactivate'] = '<a href="' . wp_nonce_url( 'admin.php?page=lana-security.php&action=deactivate&lana_security_plugin=' . urlencode( $item['id'] ) . '&paged=' . $page, $item['id'] . '_plugin_deactivate' ) . '">' . __( 'Deactivate', 'lana-security' ) . '</a>';
		}

		/** add activate to inactive plugin */
		if ( false == $item['option'] ) {
			$actions['activate'] = '<a href="' . wp_nonce_url( 'admin.php?page=lana-security.php&action=activate&lana_security_plugin=' . urlencode( $item['id'] ) . '&paged=' . $page, $item['id'] . '_plugin_activate' ) . '">' . __( 'Activate', 'lana-security' ) . '</a>';
		}

		return sprintf( '<td class="plugin-title column-primary">%s%s</td>', $this->column_plugin( $item ), $this->row_actions( $actions, true ) );
	}

	/**
	 * The plugin column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_plugin( $item ) {
		return sprintf( '<strong>%s</strong>', $item['label'] );
	}

	/**
	 * The description column
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_description( $item ) {
		$plugin_meta = array();

		/** add version */
		$plugin_meta['version'] = sprintf( __( 'Version %s' ), $item['version'] );

		/** add author */
		$author                = '<a href="' . esc_url( 'http://lana.codes/' ) . '">' . __( 'Lana Codes', 'lana-security' ) . '</a>';
		$plugin_meta['author'] = sprintf( __( 'By %s' ), $author );

		return sprintf( '<div class="plugin-description"><p>%s</p></div><div class="second">%s</div>', $item['description'], implode( ' | ', $plugin_meta ) );
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

		if ( 'top' == $which && true === $this->display_activated_message ) :
			?>
            <div id="message" class="updated notice notice-info">
                <p><?php _e( 'Selected plugins <strong>activated</strong>.', 'lana-security' ); ?></p>
            </div>
		<?php
		endif;

		if ( 'top' == $which && true === $this->display_deactivated_message ) :
			?>
            <div id="message" class="updated notice notice-info">
                <p><?php _e( 'Selected plugins <strong>deactivated</strong>.', 'lana-security' ); ?></p>
            </div>
		<?php
		endif;
		?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
            </div>

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
		global $lana_security_settings;

		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'lana_security_plugins_per_page' );
		$current_page = $this->get_pagenum();

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$total_items = sizeof( $lana_security_settings );
		$this->items = array_splice( $lana_security_settings, ( $current_page - 1 ) * $per_page, $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ( ( $total_items > 0 ) ? ceil( $total_items / $per_page ) : 1 )
		) );
	}

	/**
	 * Process bulk actions
	 */
	public function process_bulk_action() {
		global $lana_security_settings;

		$action               = $this->current_action();
		$lana_security_plugin = isset( $_REQUEST['lana_security_plugin'] ) ? wp_unslash( $_REQUEST['lana_security_plugin'] ) : '';

		/** check action */
		if ( ! $action ) {
			return;
		}

		/** check plugins */
		if ( ! is_array( $lana_security_plugin ) ) {
			return;
		}

		/**
		 * Activate
		 * plugins
		 */
		if ( 'activate' == $action ) {

			if ( ! isset( $_POST['_wpnonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( __( 'Sorry, you are not allowed to process bulk actions.', 'lana-security' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Sorry, you are not allowed to manage plugins.', 'lana-security' ) );
			}

			if ( ! empty( $lana_security_plugin ) ) {

				foreach ( $lana_security_plugin as $plugin_id ) {
					if ( update_option( $plugin_id, true ) ) {
						$lana_security_settings[ $plugin_id ]['option'] = get_option( $plugin_id );
					}
				}

				$this->display_activated_message = true;
			}
		}

		/**
		 * Deactivate
		 * plugins
		 */
		if ( 'deactivate' == $action ) {

			if ( ! isset( $_POST['_wpnonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( __( 'Sorry, you are not allowed to bulk actions.', 'lana-security' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Sorry, you are not allowed to manage plugins.', 'lana-security' ) );
			}

			if ( ! empty( $lana_security_plugin ) ) {

				foreach ( $lana_security_plugin as $plugin_id ) {
					if ( update_option( $plugin_id, false ) ) {
						$lana_security_settings[ $plugin_id ]['option'] = get_option( $plugin_id );
					}
				}

				$this->display_deactivated_message = true;
			}
		}
	}
}