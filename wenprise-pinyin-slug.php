<?php
/*
Plugin Name:        Wenprise Pinyin Slug
Plugin URI:         https://www.wpzhiku.com/wenprise-pinyin-slug/
Description:        自动转换 WordPress 中的中文文章别名、分类项目别名、图片文件名称为汉语拼音。
Version:            3.1.0
Author:             WordPress 智库
Author URI:         https://www.wpzhiku.com/
License:            MIT
License URI:        http://opensource.org/licenses/MIT
Requires PHP:       7.4
*/

const WPRS_PS_VERSION = '3.1.0';
define('WPRS_PS_PATH', plugin_dir_path(__FILE__));

// 加载插件主要功能
require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

add_action('plugins_loaded', function ()
{
    if (PHP_VERSION_ID < 70100) {

        // 显示警告信息
        if (is_admin()) {
            add_action('admin_notices', function ()
            {
                printf('<div class="error"><p>' . __('Wenprise Pinyin Slug 需要 PHP %1$s 以上版本才能运行，您当前的 PHP 版本为 %2$s， 请升级到 PHP 到 %1$s 或更新的版本， 否则插件没有任何作用。',
                        'wprs') . '</p></div>',
                    '7.1.0', PHP_VERSION);
            });
        }

        return;
    }


    /**
     * 升级数据库
     */
    $installed_version = get_option('wprs_pinyin_slug_version', '3.0.0');

    if (version_compare($installed_version, WPRS_PS_VERSION) === -1) {
        $options = get_option('wprs_pinyin_slug');

        if (isset($options[ 'translator_api' ]) && (int)$options[ 'translator_api' ] === 1) {
            unset($options[ 'translator_api' ]);
            $options[ 'type' ] = 2;
            update_option('wprs_pinyin_slug', $options);
        }

        update_option('wprs_pinyin_slug_version', WPRS_PS_VERSION);
    }


    /**
     * 插件插件设置链接
     */
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links)
    {
        $url = admin_url('options-general.php?page=wenprise_pinyin_slug');
        $url = '<a href="' . esc_url($url) . '">' . __('设置') . '</a>';
        array_unshift($links, $url);

        return $links;
    });

    $classes = [
        \WenprisePinyinSlug\Settings::class,
        \WenprisePinyinSlug\Integrate::class,
        \WenprisePinyinSlug\BulkConvert::class,
    ];

    foreach ($classes as $class) {
        new $class;
    }
});
