<?php
/**
 * Main bootstrap class for Micro Site Care: Post Last Updated Date.
 */

namespace MSCLUD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	const OPTION_KEY = 'msclu_options';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Module instance.
	 *
	 * @var Module|null
	 */
	private $module = null;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Analytics instance.
	 *
	 * @var object|null
	 */
	private $analytics = null;

	/**
	 * Admin analytics instance.
	 *
	 * @var object|null
	 */
	private $admin_analytics = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Activate plugin.
	 */
	public static function activate() {
		$options = get_option( self::OPTION_KEY );
		if ( ! is_array( $options ) ) {
			update_option( self::OPTION_KEY, self::default_options() );
		}

		if ( ! get_option( 'msclu_activated_time' ) ) {
			update_option( 'msclu_activated_time', time() );
		}
	}

	/**
	 * Deactivate plugin.
	 */
	public static function deactivate() {
		// Reserved for deactivation cleanup hooks.
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings = new Settings( $this );

		$this->module = new Module( $this );

		// Register the [msclu_last_updated] shortcode (skipped if a Pro extension owns it).
		if ( ! $this->is_pro_active() ) {
			add_shortcode( 'msclu_last_updated', array( $this, 'render_shortcode' ) );
		}

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'maybe_render_review_notice' ) );
			add_action( 'admin_init', array( $this, 'maybe_handle_review_dismiss' ) );
		}
	}

	/**
	 * Renders the [msclu_last_updated] shortcode.
	 *
	 * Attributes:
	 * - post_id  (int)  Post to render for. Defaults to the current post.
	 * - relative (bool) Force relative ("3 days ago") or absolute date for this instance.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id'  => 0,
				'relative' => '',
			),
			$atts,
			'msclu_last_updated'
		);

		$context = array( 'source' => 'shortcode' );

		if ( '' !== $atts['relative'] ) {
			$context['date_mode'] = filter_var( $atts['relative'], FILTER_VALIDATE_BOOLEAN ) ? 'relative' : 'site';
		}

		return $this->module->get_last_updated_html( (int) $atts['post_id'], $context );
	}

	/**
	 * Shows a one-time, dismissible review request on the plugin's settings page.
	 *
	 * @return void
	 */
	public function maybe_render_review_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_msclu-settings' !== $screen->id ) {
			return;
		}

		if ( get_option( 'msclu_review_dismissed' ) ) {
			return;
		}

		$since = (int) get_option( 'msclu_activated_time', 0 );
		if ( $since <= 0 ) {
			// Start the clock for installs that predate this option.
			update_option( 'msclu_activated_time', time() );
			return;
		}

		if ( ( time() - $since ) < ( 7 * DAY_IN_SECONDS ) ) {
			return;
		}

		$review_url  = 'https://wordpress.org/support/plugin/micro-site-care-post-last-updated-date/reviews/#new-post';
		$dismiss_url = wp_nonce_url( add_query_arg( 'msclu_dismiss_review', '1' ), 'msclu_dismiss_review' );
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php esc_html_e( 'Enjoying MSC: Post Last Updated Date? A quick review would really help other WordPress users find it.', 'micro-site-care-post-last-updated-date' ); ?>
				<a href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Leave a review', 'micro-site-care-post-last-updated-date' ); ?></a>
				&nbsp;·&nbsp;
				<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'No thanks', 'micro-site-care-post-last-updated-date' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Permanently dismisses the review request.
	 *
	 * @return void
	 */
	public function maybe_handle_review_dismiss() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['msclu_dismiss_review'] ) ) {
			return;
		}

		check_admin_referer( 'msclu_dismiss_review' );
		update_option( 'msclu_review_dismissed', 1 );
		wp_safe_redirect( remove_query_arg( array( 'msclu_dismiss_review', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Default options.
	 *
	 * @return array<string,mixed>
	 */
	public static function default_options() {
		return array(
			'module_enabled' => 1,
			'post_types'     => array( 'post', 'page' ),
			'post_type_mode' => 'include',
			'position'       => 'after',
			// translators: %s is the formatted post last-updated date.
			'label_text'     => __( 'Updated %s', 'micro-site-care-post-last-updated-date' ),
			'date_mode'      => 'site',
			'custom_format'  => 'F j, Y',
			'modified_only'  => 1,
		);
	}

	/**
	 * Option getter.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public function get_option( $key, $default = null ) {
		$db_options    = (array) get_option( self::OPTION_KEY, array() );
		$free_defaults = self::default_options();
		// DB values take priority; defaults fill gaps for any unset Free keys.
		$options = array_merge( $free_defaults, $db_options );
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Save merged options.
	 *
	 * @param array<string,mixed> $new_options New values.
	 * @return bool
	 */
	public function update_options( $new_options ) {
		// Read current row without applying defaults, to preserve any Pro-extended fields.
		$current = (array) get_option( self::OPTION_KEY, array() );
		$merged  = array_merge( $current, $new_options );
		return (bool) update_option( self::OPTION_KEY, $merged );
	}

	/**
	 * Whether pro plugin is active.
	 *
	 * @return bool
	 */
	public function is_pro_active() {
		return (bool) apply_filters( 'msclu_pro_active', false );
	}

	/**
	 * Feature switch helper.
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public function has_feature( $feature ) {
		$map = array(
			'analytics'         => false,
			'admin_analytics'   => false,
			'cron'              => false,
			'meta_registration' => false,
			'bulk_actions'      => false,
			'shortcode'         => true,
			'ajax'              => false,
		);

		return ! empty( $map[ $feature ] );
	}

	/**
	 * Returns rendered Last Updated markup for a post.
	 *
	 * @param int                $post_id Post ID.
	 * @param array<string,mixed> $context Render context.
	 * @return string
	 */
	public function get_last_updated_markup( $post_id = 0, $context = array() ) {
		return $this->module->get_last_updated_html( $post_id, $context );
	}
}
