<?php
/**
 * Reusable extensions for the Zephr site.
 *
 * Plugin Name: Zephr
 * Plugin URI: https://www.zephr.com/
 * Description: The subscription experience platform.
 * Version: 1.0.5
 * Author: Zephr
 * Requires PHP: 7.4
 * Requires WP: 5.8
 *
 * @package Zephr
 */

namespace Zephr;

// Autoloader.
require_once __DIR__ . '/inc/autoload.php';

// Functionality.
require_once __DIR__ . '/inc/body-classes.php';
require_once __DIR__ . '/inc/functions-cron.php';
require_once __DIR__ . '/inc/functions-meta.php';
require_once __DIR__ . '/inc/functions-assets.php';
require_once __DIR__ . '/inc/functions-cache.php';
require_once __DIR__ . '/inc/asset-loader-bridge.php';

Admin_Settings::instance();
Block_Feature::instance();
Page_Proxy::instance();
Proxy::instance();
Rest_API::instance();
Sync_Users::instance();
Zephr_Browser::instance();

// Scaffolded classes.
require_once __DIR__ . '/functions.php';
