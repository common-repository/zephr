<?php
/**
 * Class File for REST API extensions.
 *
 * @package Zephr
 */

namespace Zephr; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

use WP_REST_Request;

/**
 * Adds custom REST Endpoints.
 */
class Rest_API {
	use Instance;

	/**
	 * The Rest Base.
	 *
	 * @var string
	 */
	const REST_BASE = 'zephr/v1';

	/**
	 * Set everything up.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Registers the REST Routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			self::REST_BASE,
			'/features/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_features' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/features/(?P<id>[A-Za-z0-9\-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_feature_from_request' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'id' => [
						'validate_callback' => function( $param ) {
							return in_array( $param, wp_list_pluck( $this->get_features(), 'id' ), true );
						},
					],
				],
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/features/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_feature' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'title'        => [
						'required' => true,
						'type'     => 'string',
					],
					'type'         => [
						'type'     => 'string',
						'required' => true,
						'enum'     => [
							'HTML',
							'JSON',
							'SDK',
							'REDIRECT',
						],
					],
					'desc'         => [
						'type'     => 'string',
						'required' => true,
					],
					'css-selector' => [
						'type' => 'string',
					],
					'content-type' => [
						'required' => true,
						'type'     => 'string',
						'enum'     => [
							'TEXT',
							'IMAGE',
							'VIDEO',
							'ADVERTISING',
						],
					],
				],
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/zephr-options/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'set_zephr_onboarded' ],
				'permission_callback' => function () {
					return current_user_can( Admin_Settings::instance()->get_settings_page_capability() );
				},
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/zephr-keys/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'validate_keys' ],
				'permission_callback' => function () {
					return current_user_can( Admin_Settings::instance()->get_settings_page_capability() );
				},
				'args'                => [
					'tenant_id' => [
						'type'     => 'string',
						'required' => true,
					],
					'key'       => [
						'type'     => 'string',
						'required' => true,
					],
					'secret'    => [
						'type'     => 'string',
						'required' => true,
					],
				],
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/sites/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_sites' ],
				'permission_callback' => function () {
					return current_user_can( Admin_Settings::instance()->get_settings_page_capability() );
				},
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/sites/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_site' ],
				'permission_callback' => function () {
					return current_user_can( Admin_Settings::get_settings_page_capability() );
				},
				'args'                => [
					'title'  => [
						'required' => true,
						'type'     => 'string',
					],
					'prefix' => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/zephr-domain/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_domain' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'zephr_domain' => [
						'type'     => 'string',
						'required' => true,
					],
				],
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/sync-users/',
			[
				'methods'             => 'POST',
				'callback'            => [ Sync_Users::instance(), 'schedule_user_migrate' ],
				'permission_callback' => function () {
					return current_user_can( Admin_Settings::instance()->get_settings_page_capability() );
				},
			],
		);

		register_rest_route(
			self::REST_BASE,
			'/sync-users/clear/',
			[
				'methods'             => 'POST',
				'callback'            => [ Sync_Users::instance(), 'clear_zephr_user_admin_notice' ],
				'permission_callback' => function () {
					return true;
				},
			],
		);
	}

	/**
	 * Queries graphql api for sites.
	 *
	 * @return array
	 */
	public static function get_sites() {
		$sites = remember(
			'zephr_graphql_sites',
			function() {
				return Graphql_Client::post( [], '{  listSites { title slug domains { url isDefault isPreferred environment } }  }' );
			},
			5 * MINUTE_IN_SECONDS
		);
		return $sites['listSites'] ?? [];
	}

	/**
	 * Queries graphql api for features.
	 *
	 * @return array
	 */
	public static function get_features() {
		$features = remember(
			'zephr_graphql_features',
			function() {
				return Graphql_Client::post( [], '{  features { id slug type label latestVersion liveVersion }  }' );
			},
			5 * MINUTE_IN_SECONDS
		);
		return array_values(
			array_filter(
				$features['features'],
				function( $feature ) {
					return 'HTML' === $feature['type'];
				}
			)
		) ?? [];
	}

	/**
	 * Queries graphql api for a specific feature.
	 *
	 * @param WP_REST_Request $request The rest request.
	 * @return array
	 */
	public function get_feature_from_request( $request ) {
		return $this->get_feature( $request['id'] );
	}

	/**
	 * Gets a specified property of a feature by id.
	 *
	 * @param string $id       The id of the feature.
	 * @param string $prop_key The key of the property.
	 * @return mixed
	 */
	public function get_feature_prop( $id, $prop_key ) {
		$matches = array_filter(
			$this->get_features(),
			function( $feature ) use ( $id ) {
				return $feature['id'] === $id;
			}
		);
		if ( empty( $matches ) ) {
			return null;
		}
		$match = array_shift( $matches );
		return $match[ $prop_key ] ?? null;
	}

	/**
	 * Gets the zephr feature.
	 *
	 * @param string $id The name of the zephr feature.
	 * @return array
	 */
	public function get_feature( $id ) {
		$response = remember(
			"zephr_graphql_feature_${id}",
			function() use ( $id ) {
				$vars  = [
					'key' => [
						'id'      => $id,
						'type'    => $this->get_feature_prop( $id, 'type' ),
						'version' => $this->get_feature_prop( $id, 'liveVersion' ) ?? $this->get_feature_prop( $id, 'latestVersion' ),
					],
				];
				$query = 'query featureVersion($key: FeatureVersionKey) { featureVersion(feature: $key){  id, version, type, cssSelector    }}';
				return Graphql_Client::post( $vars, $query );
			},
			5 * MINUTE_IN_SECONDS
		);
		return $response;
	}

	/**
	 * Sets the onboarded option.
	 *
	 * @param WP_REST_Request $request The rest request.
	 * @return WP_REST_Response
	 */
	public function set_zephr_onboarded( WP_REST_Request $request ) {
		$params       = $request->get_json_params();
		$zephr_option = get_option( 'zephr' );

		$zephr_option['onboarded'] = strval( $params['onboarded'] );

		update_option(
			'zephr',
			$zephr_option,
			false,
		);

		return [ 'onboarded' => true ];
	}

	/**
	 * Sets the zephr site option.
	 *
	 * @param WP_REST_Request $request The rest request.
	 * @return WP_REST_Response
	 */
	public function save_domain( WP_REST_Request $request ) {
		$params       = $request->get_json_params();
		$zephr_option = get_option( 'zephr', [] );

		if ( $zephr_option['zephr_domain'] !== $params['zephr_domain'] ) {
			$zephr_option['zephr_domain'] = $params['zephr_domain'];

			update_option(
				'zephr',
				$zephr_option,
				false,
			);
		}

		return [ 'saved' => true ];
	}

	/**
	 * Validates the API keys.
	 *
	 * @param WP_REST_Request $request The rest request.
	 * @return WP_REST_Response
	 */
	public function validate_keys( WP_REST_Request $request ) {
		update_option(
			'zephr',
			[
				'zephr_admin_key'    => $request['key'],
				'zephr_admin_secret' => $request['secret'],
				'zephr_tenant_id'    => $request['tenant_id'],
			],
			true
		);

		// Clear the related caches.
		delete_transient( 'zephr_graphql_sites' );

		return [
			'validated' => Admin_Settings::instance()->validate_api_key(),
		];
	}

	/**
	 * Creates a new feature rule.
	 *
	 * @param WP_REST_Request $request The rest request.
	 * @return WP_REST_Response
	 */
	public function create_feature( WP_REST_Request $request ) {
		$title        = $request['title'];
		$slug         = sanitize_title( $title );
		$type         = $request['type']; // one of HTML, JSON, SDK, REDIRECT.
		$desc         = $request['desc'];
		$ent_id       = wp_generate_password( 6, false );
		$css_selector = $request['css-selector'];
		$content_type = $request['content-type']; // one of TEXT, IMAGE, VIDEO, ADVERTISING.

		$graph_state = [
			'slug'        => '%slug%',
			'label'       => '%label%',
			'description' => '',
			'graph_state' => [
				[
					'type'  => 'entry',
					'id'    => 'graph/1',
					'graph' => [
						[
							'type'   => 'EntryNode',
							'id'     => 'graph/1#1',
							'output' => 'graph/1#2',
						],
						[
							'type'   => 'DecisionPoint',
							'id'     => 'graph/1#2',
							'tests'  => [
								[
									'order' => 0,
									'type'  => 'RequestCrawlerVerifiedTest',
								],
							],
							'passed' => 'graph/1#8',
							'failed' => 'graph/1#3',
						],
						[
							'type'   => 'DecisionPoint',
							'id'     => 'graph/1#3',
							'tests'  => [
								[
									'order' => 0,
									'type'  => 'AuthenticatedSessionTest',
								],
							],
							'passed' => 'graph/1#4',
							'failed' => 'graph/1#5',
						],
						[
							'type'   => 'DecisionPoint',
							'id'     => 'graph/1#4',
							'tests'  => [
								[
									'order'     => 0,
									'type'      => 'UserFeatureTest',
									'featureId' => '%ent_id%',
								],
							],
							'passed' => 'graph/1#7',
							'failed' => 'graph/1#6',
						],
						[
							'type'   => 'OutputNode',
							'id'     => 'graph/1#5',
							'label'  => 'Anonymous',
							'mode'   => 'anonymous',
							'output' => 'graph/2#1',
						],
						[
							'type'   => 'OutputNode',
							'id'     => 'graph/1#6',
							'label'  => 'Registered',
							'mode'   => 'registered',
							'output' => 'graph/3#1',
						],
						[
							'type'   => 'OutputNode',
							'id'     => 'graph/1#7',
							'label'  => 'Customer',
							'mode'   => 'customer',
							'output' => 'graph/4#1',
						],
						[
							'type'            => 'Transformation',
							'id'              => 'graph/1#8',
							'transformations' => [
								[
									'order' => 0,
									'type'  => 'FeatureContentPristine',
								],
							],
						],
					],
				],
				[
					'id'    => 'graph/2',
					'type'  => 'graph',
					'graph' => [
						[
							'id'       => 'graph/2#1',
							'type'     => 'InputNode',
							'position' => [
								'x' => 30,
								'y' => 30,
							],
						],
					],
				],
				[
					'id'    => 'graph/3',
					'type'  => 'graph',
					'graph' => [
						[
							'id'       => 'graph/3#1',
							'type'     => 'InputNode',
							'position' => [
								'x' => 30,
								'y' => 30,
							],
						],
					],
				],
				[
					'id'    => 'graph/4',
					'type'  => 'graph',
					'graph' => [
						[
							'id'       => 'graph/4#1',
							'type'     => 'InputNode',
							'position' => [
								'x' => 30,
								'y' => 30,
							],
						],
					],
				],
			],
		];

		$placeholders = [
			'%ent_id%' => $ent_id,
			'%slug%'   => $slug,
			'%label%'  => $title,
		];

		$vars     = [
			'feature' => [
				'id'           => $slug,
				'type'         => $type,
				'slug'         => $slug,
				'label'        => $title,
				'description'  => $desc,
				'verified'     => false,
				'entitlement'  => [
					'id' => $ent_id,
				],
				'targetType'   => 'CSS_SELECTOR',
				'cssSelector'  => $css_selector,
				'contentType'  => $content_type,
				'allowBrowser' => true,
				'graphState'   => str_replace(
					array_keys( $placeholders ),
					array_values( $placeholders ),
					wp_json_encode( $graph_state )
				),
			],
		];
		$query    = 'mutation createFeatureVersion($feature: FeatureInput) {  createFeatureVersion(feature: $feature) { slug }}';
		$response = Graphql_Client::post( $vars, $query );
		forget( 'zephr_graphql_features' );
		return $response;
	}

	/**
	 * Creates a new site.
	 *
	 * @param WP_REST_Request $request The rest request.
	 * @return WP_REST_Response
	 */
	public function create_site( WP_REST_Request $request ) {
		$title     = $request['title'];
		$slug      = sanitize_title( $title );
		$prefix    = sanitize_title( $request['prefix'] );
		$tenant_id = get_option( 'zephr' )['zephr_tenant_id'] ?? '';

		$vars     = [
			'site' => [
				'slug'             => $slug,
				'title'            => $title,
				'defaultOriginUrl' => home_url(),
				'domains'          => [
					[
						'url'         => "${tenant_id}-${prefix}.cdn.zephr.com",
						'isDefault'   => true,
						'isPreferred' => true,
						'progress'    => [
							'sslCertificateGenerated' => false,
							'cdnSetup'                => false,
							'dnsRecordUpdates'        => false,
							'setupComplete'           => false,
						],
						'environment' => 'LIVE',
					],
					[
						'url'         => "${tenant_id}-${prefix}.preview.zephr.com",
						'isDefault'   => true,
						'isPreferred' => true,
						'progress'    => [
							'sslCertificateGenerated' => false,
							'cdnSetup'                => false,
							'dnsRecordUpdates'        => false,
							'setupComplete'           => false,
						],
						'environment' => 'STAGING',
					],
				],
				'routes'           => [],
				'cacheRules'       => [],
				'requestHeaders'   => [],
				'oauth2Config'     => (object) [],
				'oauthProviders'   => [],
			],
		];
		$query    = 'mutation createSite($site: SiteInput) {  createSite(site: $site) { title slug domains { url isDefault isPreferred environment } } }';
		$response = Graphql_Client::post( $vars, $query );
		forget( 'zephr_graphql_sites' );
		return $response;
	}
}
