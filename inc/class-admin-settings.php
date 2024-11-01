<?php
/**
 * Admin Functionality
 *
 * @package Zephr
 */

namespace Zephr;

/**
 * Admin Settings
 */
class Admin_Settings {
	use Instance;

	/**
	 * Option storage.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'on_admin_init' ] );
		add_action( 'admin_menu', [ $this, 'on_admin_menu' ] );
		add_action( 'admin_head', [ $this, 'on_admin_head' ] );
		add_action( 'admin_notices', [ $this, 'on_admin_notices' ] );
		add_action( 'load-toplevel_page_zephr', [ $this, 'on_settings_page' ] );
	}

	/**
	 * Register Admin Settings
	 */
	public function on_admin_init() {
		register_setting( 'zephr', 'zephr' );

		// Zephr Site Mapping.
		add_settings_section( 'zephr_site', __( 'Zephr Site', 'zephr' ), '__return_null', 'zephr' );

		add_settings_field(
			'tenant_id',
			__( 'Customer/Tenant ID', 'zephr' ),
			[ $this, 'render_field' ],
			'zephr',
			'zephr_site',
			[
				'field' => 'zephr_tenant_id',
			]
		);

		add_settings_field(
			'site',
			__( 'CDN Domain', 'zephr' ),
			[ $this, 'render_field' ],
			'zephr',
			'zephr_site',
			[
				'field' => 'zephr_domain',
			]
		);

		add_settings_section( 'zephr_browser', __( 'Browser Settings', 'zephr' ), '__return_null', 'zephr' );
		add_settings_field(
			'browser_disabled',
			__( 'Disable Zephr Browser', 'zephr' ),
			[ $this, 'render_field' ],
			'zephr',
			'zephr_browser',
			[
				'field' => 'zephr_disable_browser',
				'type'  => 'checkbox',
			]
		);

		// Admin API keys settings.
		add_settings_section( 'zephr_admin_keys', __( 'Admin API Keys', 'zephr' ), '__return_null', 'zephr' );
		add_settings_field(
			'access_key',
			__( 'Access Key', 'zephr' ),
			[ $this, 'render_field' ],
			'zephr',
			'zephr_admin_keys',
			[
				'field' => 'zephr_admin_key',
			]
		);

		add_settings_field(
			'secret_key',
			__( 'Secret Key', 'zephr' ),
			[ $this, 'render_field' ],
			'zephr',
			'zephr_admin_keys',
			[
				'field' => 'zephr_admin_secret',
				'type'  => 'password',
			]
		);

		add_settings_field(
			'onboarded',
			__( 'Onboarding Complete?', 'zephr' ),
			[ $this, 'render_field' ],
			'zephr',
			'zephr_admin_keys',
			[
				'field' => 'onboarded',
				'type'  => 'checkbox',
			]
		);
	}

	/**
	 * Register the Admin Settings Page
	 */
	public function on_admin_menu() {
		add_menu_page(
			__( 'Zephr', 'zephr' ),
			__( 'Zephr', 'zephr' ),
			static::get_settings_page_capability(),
			'zephr',
			[ $this, 'render_admin_page' ],
			'dashicons-cloud',
		);
	}

	/**
	 * Action called on the settings page.
	 */
	public function on_settings_page() {
		add_action( 'admin_notices', [ $this, 'on_settings_page_admin_notices' ] );
		add_action( 'zephr_load_wizard', [ $this, 'on_settings_page_onboarding_wizard' ] );
	}

	/**
	 * Apply Admin Notices to the settings page.
	 *
	 * @return void
	 */
	public function on_settings_page_admin_notices() {
		if ( ! $this->validate_api_key() ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'The Zephr API keys provided are not valid.', 'zephr' ),
			);
		}
	}

	/**
	 * Apply Admin Notices to the entire admin area.
	 *
	 * @return void
	 */
	public function on_admin_notices() {
		if ( ! $this->check_api_keys() ) {

			$screen = get_current_screen();

			if ( 'toplevel_page_zephr' === $screen->id ) {
				printf(
					'<div class="notice notice-large notice-api-keys"><p>%s</p></div>',
					esc_html__( 'Please set Zephr API Access and Secret Keys below.', 'zephr' ),
				);
			} else {
				printf(
					'<div class="notice notice-large notice-api-keys"><p>%s</p></div>',
					sprintf(
						wp_kses(
							/* translators: %s: Link to the Zephr plugin settings page. */
							__( 'Please set Zephr API Access and Secret Keys on the <a href="%s">plugin settings page</a>.', 'zephr' ),
							[ 'a' => [ 'href' => [] ] ]
						),
						esc_url( admin_url( 'admin.php?page=zephr', 'https' ) ),
					)
				);
			}
		}
	}

	/**
	 * Set up a div for the onboarding app to load into.
	 *
	 * @return void
	 */
	public static function on_settings_page_onboarding_wizard() {
		?>
		<div class="zephr-onboarding-wizard">
			<div id="onboarding-root"></div>
		</div>
		<?php
	}

	/**
	 * Set styles for the admin area.
	 *
	 * @return void
	 */
	public function on_admin_head() {
		echo '<style>
			.notice-api-keys,
			.notice-api-keys a {
				background: #000;
				border: none;
				color: #fff;
				font-weight: bold;
			}
		</style>';
	}

	/**
	 * Retrieve the settings page capability.
	 *
	 * @return string
	 */
	public function get_settings_page_capability() {
		/**
		 * Admin Page Capability
		 *
		 * @param string $capability Capability for menu.
		 */
		return apply_filters( 'zephr_admin_capability', 'manage_options' );
	}

	/**
	 * Check if the onboarding option has been set to complete.
	 *
	 * @return bool
	 */
	public function check_onboarding_complete() {
		$option        = get_option( 'zephr' );
		$has_admin_key = false;
		$has_onboarded = false;

		if ( is_array( $option ) ) {
			$has_onboarded = array_key_exists( 'onboarded', $option );
			$has_admin_key = array_key_exists( 'zephr_admin_key', $option );

			return $has_onboarded && $option['onboarded'] && $has_admin_key;
		}

		return false;
	}

	/**
	 * Render the admin menu page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( static::get_settings_page_capability() ) ) {
			wp_die( esc_html__( 'You do not have permissions to access this page.', 'zephr' ) );
		}

		// Check if the onboarding wizard has completed.
		if ( true !== $this->check_onboarding_complete() ) {
			do_action( 'zephr_load_wizard' );
		}

		// If wizard is done, load page.
		include __DIR__ . '/../partials/page-options.php';
	}

	/**
	 * Retrieve an option.
	 *
	 * @param string $slug Slug of the option.
	 * @return mixed
	 */
	public function get_option( string $slug ) {
		if ( ! isset( $this->options ) ) {
			$this->options = get_option( 'zephr' );
		}

		return $this->options[ $slug ] ?? null;
	}


	/**
	 * Render the settings field.
	 *
	 * @param array $args Settings field arguments.
	 */
	public function render_field( $args ) {
		if ( empty( $args['field'] ) ) {
			return;
		}

		if ( empty( $args['type'] ) ) {
			$args['type'] = 'text';
		}

		$value = $this->get_option( $args['field'] );

		switch ( $args['type'] ) {
			case 'textarea':
				$this->render_textarea( $args, $value );
				break;

			case 'checkboxes':
				$this->render_checkboxes( $args, $value );
				break;

			default:
				$this->render_text_field( $args, $value );
				break;
		}
	}

	/**
	 * Render a settings text field.
	 *
	 * @param array  $args {
	 *     An array of arguments for the text field.
	 *
	 *     @type string $field  The field name.
	 *     @type string $type   The field type. Default 'text'.
	 *     @type string $size   The field size. Default 80.
	 * }
	 * @param string $value The current field value.
	 */
	public function render_text_field( $args, $value ) {
		$args = wp_parse_args(
			$args,
			[
				'type' => 'text',
				'size' => 80,
			],
		);

		if ( 'checkbox' === $args['type'] ) {
			$checked = '1' === $value;
			$value   = '1';
		}

		printf(
			'<input type="%s" name="%s[%s]" value="%s" size="%s" %s />',
			esc_attr( $args['type'] ),
			esc_attr( 'zephr' ),
			esc_attr( $args['field'] ),
			esc_attr( $value ),
			esc_attr( $args['size'] ),
			'checkbox' === $args['type'] && ! empty( $checked ) ? 'checked' : '',
		);

		if ( 'zephr_domain' === $args['field'] ) {
			printf(
				'<span class="field-description" style="display: block;padding-top:4px;font-size:12px;">%s</span>',
				esc_html__( 'The CDN domain from the connected site\'s LIVE environment, which is configured in Delivery > Sites > Site Details.', 'zephr' ),
			);
		}
	}

	/**
	 * Render a settings textarea.
	 *
	 * @param array  $args {
	 *     An array of arguments for the textarea.
	 *
	 *     @type  string $field The field name.
	 *     @type  int    $rows  Rows in the textarea. Default 2.
	 *     @type  int    $cols  Columns in the textarea. Default 80.
	 * }
	 * @param string $value The current field value.
	 */
	public function render_textarea( $args, $value ) {
		$args = wp_parse_args(
			$args,
			[
				'rows' => 2,
				'cols' => 80,
			]
		);

		printf(
			'<textarea name="%s[%s]" rows="%d" cols="%d">%s</textarea>',
			esc_attr( 'zephr' ),
			esc_attr( $args['field'] ),
			esc_attr( $args['rows'] ),
			esc_attr( $args['cols'] ),
			esc_textarea( $value )
		);
	}

	/**
	 * Render settings checkboxes.
	 *
	 * @param  array $args {
	 *     An array of arguments for the checkboxes.
	 *
	 *     @type string $field The field name.
	 *     @type array  $boxes An associative array of the value and label
	 *                         of each checkbox.
	 * }
	 * @param  array $values Indexed array of current field values.
	 */
	public function render_checkboxes( $args, $values ) {
		foreach ( $args['boxes'] as $box_value => $box_label ) {
			printf(
				'
					<label for="%1$s_%2$s_%3$s">
						<input id="%1$s_%2$s_%3$s" type="checkbox" name="%1$s[%2$s][]" value="%3$s" %4$s>
						%5$s
					</label><br>',
				esc_attr( 'zephr' ),
				esc_attr( $args['field'] ),
				esc_attr( $box_value ),
				is_array( $values ) ? checked( in_array( $box_value, $values, true ), true, false ) : '',
				esc_html( $box_label )
			);
		}
	}

	/**
	 * Get the API access key and secret key.
	 *
	 * @return array  $args {
	 *     An array of API keys.
	 *
	 *     @type string|null $key       The API access key, or null if unpopulated.
	 *     @type string|null $secret    The API secret key, or null if unpopulated.
	 *     @type string|null $tenant_id The customer ID.
	 * }
	 */
	protected function get_api_keys() {
		return [
			'key'       => $this->get_option( 'zephr_admin_key' ) ?? null,
			'secret'    => $this->get_option( 'zephr_admin_secret' ) ?? null,
			'tenant_id' => $this->get_option( 'zephr_tenant_id' ) ?? null,
		];
	}

	/**
	 * Check if the API keys are valid.
	 *
	 * @return bool
	 */
	public function validate_api_key() {
		// Get API access key and secret key.
		[
			'key'       => $key,
			'secret'    => $secret,
			'tenant_id' => $tenant_id,
		] = $this->get_api_keys();

		// If unset they are 'valid'.
		if ( empty( $key ) || empty( $secret ) ) {
			return true;
		}

		$cache_key = 'zephr_validate_' . md5( $key . $secret . $tenant_id );
		$cache     = get_transient( $cache_key );

		if ( false !== $cache ) {
			return (bool) $cache;
		}

		$config = Client::get( '/v3/configuration' );

		// Validate that the user is on the same tenant as the site's configuration.
		$valid = ! empty( $config['tenant_id'] ) && $tenant_id === $config['tenant_id'];

		set_transient( $cache_key, $valid ? 1 : 0, HOUR_IN_SECONDS );

		return $valid;
	}

	/**
	 * Check if the API access key and secret key are populated.
	 *
	 * @return bool
	 */
	protected function check_api_keys() {
		// Get API access key and secret key.
		[
			'key'    => $key,
			'secret' => $secret,
		] = $this->get_api_keys();

		// If both are not empty, they are 'populated'.
		if ( ! empty( $key ) && ! empty( $secret ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrive the Zephr Domain.
	 *
	 * @return string
	 */
	public function get_domain() {
		return $this->get_option( 'zephr_domain' );
	}
}
