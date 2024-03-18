<?php
/**
 * Debbuger for WordPress. Main class.
 *
 * @class   WP_Debugger
 * @package WpHelper\Classes
 */

namespace WpHelper\Debugger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP_Debugger
 */
class WP_Debugger {

	/**
	 * The key at which you can display a dump
	 *
	 * @var string
	 */
	private $get_key = 'wp-debugger';

	/**
	 * List of timers
	 *
	 * @var array<string, float>
	 */
	private $timer_storage = array();

	/**
	 * List of used memory
	 *
	 * @var array<string, float>
	 */
	private $byte_storage = array();

	/**
	 * List of entries in the current session
	 *
	 * @var array<string, mixed>
	 */
	private $page_data = array();

	/**
	 * Marker for logging type
	 *
	 * 0 - dont log, 1 - to  log file, 2 - var dump on page
	 *
	 * @var int
	 */
	private $page_data_log_option = 0;


	/**
	 * Marker for tracking dump location in third-party code
	 *
	 * @var int
	 */
	private $do_ping = 0;

	/**
	 * __construct
	 *
	 * @param  int $page_data   logging type 0 - dont log, 1 - to  log file, 2 - var dump on page.
	 * @param  int $do_ping  0|1 - show code line.
	 * @return void
	 */
	public function __construct( int $page_data = 0, int $do_ping = 0 ) {
		$this->timer_storage['init_wp_debugger'] = microtime( true );
		$this->byte_storage['init_wp_debugger']  = memory_get_usage();

		$this->page_data_log_option = $page_data;
		$this->do_ping              = $do_ping;

		if ( isset ( $_GET[ $this->get_key ] ) ) { // phpcs:ignore

			if ( 'phpinfo' === (string) $_GET[ $this->get_key ] ) {  // phpcs:ignore
				add_action( 'init', array( $this, 'phpinfo' ), 9999 );
			}

			if ( 'pagedata' === (string) $_GET[ $this->get_key ] ) {  // phpcs:ignore
				$this->page_data_log_option = 2;
			}
		}

		if ( 0 !== $this->page_data_log_option ) {
			// plugins_loaded init wp_head wp_footer shutdown.
			add_action( 'plugins_loaded', array( $this, 'set_page_data' ) );
			add_action( 'init', array( $this, 'set_page_data' ) );
			add_action( 'wp_head', array( $this, 'set_page_data' ) );
			add_action( 'wp_footer', array( $this, 'set_page_data' ) );
			add_action( 'admin_head', array( $this, 'set_page_data' ) );
			add_action( 'admin_footer', array( $this, 'set_page_data' ) );
			add_action( 'shutdown', array( $this, 'set_page_data' ) );
		}
	}

	/**
	 * Adding data to the list during action
	 *
	 * @return void
	 */
	public function set_page_data(): void {
		$action_name = current_action();

		$last_key = array_key_last( $this->page_data );

		$this->page_data[ $action_name ] = array(
			'time'      => $this->get_time( 'init_wp_debugger' ),
			'diff_time' => microtime( true ),
			'memory'    => $this->readable_bytes( memory_get_usage() ),
		);

		if ( null !== $last_key && isset( $this->page_data[ $last_key ] ) ) {
			$prev_time = $this->page_data[ $last_key ]['time'];
		} else {
			$prev_time = 0;
		}

		$this->page_data[ $action_name ]['diff_time'] = $this->page_data[ $action_name ]['time'] - $prev_time;

		if ( 'shutdown' === $action_name ) {
			if ( 1 === $this->page_data_log_option ) {
				$this->log( $this->page_data );
			} elseif ( 2 === $this->page_data_log_option ) {
				$this->var_dump( $this->page_data );
			}
		}
	}

	/**
	 * Keeps track of time
	 *
	 * @param  string $point recorded data key.
	 * @return void
	 */
	public function set_time( string $point ): void {
		$this->timer_storage[ $point ] = microtime( true );
	}

	/**
	 * Tracks used memory
	 *
	 * @param  string $point recorded data key.
	 * @return void
	 */
	public function set_byte( string $point ): void {
		$this->byte_storage[ $point ] = memory_get_usage();
	}

	/**
	 * Get time by key
	 *
	 * @param  string $point recorded data key.
	 * @return float|null
	 */
	public function get_time( string $point = 'init_wp_debugger' ): ?float {
		$time = null;

		if ( isset( $this->timer_storage[ $point ] ) && microtime( true ) > $this->timer_storage[ $point ] ) {
			$time = microtime( true ) - $this->timer_storage[ $point ];
		}

		return $time;
	}
	/**
	 * Get used memory by key
	 *
	 * @param  string $point recorded data key.
	 * @return float|null
	 */
	public function get_byte( string $point = 'init_wp_debugger' ): ?float {
		$byte = null;

		if ( isset( $this->byte_storage[ $point ] ) ) {
			$byte = memory_get_usage() - $this->byte_storage[ $point ];
		}

		return $byte;
	}

	/**
	 * Show php data
	 *
	 * @return void
	 */
	public function phpinfo(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'forbidden' );
		}
		if ( isset ( $_GET[ $this->get_key ] ) ) { // phpcs:ignore
			phpinfo(); // phpcs:ignore
			if ( $this->do_ping ) {
				echo esc_html( $this->ping( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ) ) ); // phpcs:ignore
			}
		}
	}

	/**
	 * Write code line
	 *
	 * @param  array<int, mixed> $backtrace  of  debug_backtrace.
	 * @return string
	 */
	protected function ping( array $backtrace ): string {

		if ( isset( $backtrace[0]['file'] ) && isset( $backtrace[0]['line'] ) ) {
			$file = $backtrace[0]['file'];
			$line = $backtrace[0]['line'];
			return sprintf( '< L:%s  F:%s >', $line, $file );
		}
		return '';
	}

	/**
	 * Dump data
	 *
	 * @param  mixed $data any data.
	 * @return void
	 */
	public function var_dump( $data ): void {
		if ( isset ( $_GET[ $this->get_key ] ) ) { // phpcs:ignore
			echo '<pre>';
			var_dump( $data ); // phpcs:ignore
			if ( $this->do_ping ) {
				echo esc_html( $this->ping( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ) ) );  // phpcs:ignore
			}
			echo '</pre>';
		}
	}

	/**
	 * Converts a long string of bytes into a readable format e.g KB, MB, GB, TB, YB
	 *
	 * @param int $bytes num The number of bytes.
	 */
	public function readable_bytes( int $bytes ): string {
		$i     = floor( log( $bytes ) / log( 1024 ) );
		$sizes = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );

		return sprintf( '%.02F', $bytes / pow( 1024, $i ) ) * 1 . ' ' . $sizes[ $i ];
	}

	/**
	 * Preparing a message for writing to a file
	 *
	 * @param  mixed $data any data.
	 * @return void
	 */
	public function log( mixed $data ): void {
		$message = '';

		if ( is_array( $data ) || is_object( $data ) ) {
			$message = print_r( $data, true ); // phpcs:ignore
		} elseif ( null === $data ) {
			$message = 'Null';
		} elseif ( empty( $data ) ) {
			$message = '-Empty-';
		} else {
			$message = $data;
		}
		if ( $this->do_ping ) {
			$message .= '    ' . $this->ping( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ) ); // phpcs:ignore
		}

		$this->write_log( $message );
	}

	/**
	 * Write data to file
	 *
	 * @param  string $message prapared string to file.
	 * @return void
	 */
	private function write_log( string $message ): void {
		$path     = WPDEBUG_LOG_PATH . '/error.log';
		$data_log = gmdate( 'Y-m-d H:i:s' ) . ' - ' . $message . PHP_EOL;
		file_put_contents( $path, $data_log, FILE_APPEND ); // phpcs:ignore
	}
}
