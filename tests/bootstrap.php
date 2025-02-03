<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wenprise_Pinyin_Slug
 */
// 加载插件主要功能
require dirname(__FILE__, 2) . '/vendor/autoload.php';
require dirname(__FILE__, 2) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php\n";
    exit(1);
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname(__DIR__) . '/wenprise-pinyin-slug.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';