<?php
/*
Plugin Name:        Wenprise Pinyin Slug
Plugin URI:         https://www.wpzhiku.com/wenprise-pinyin-slug/
Description:        自动转换 WordPress 中的中文文章别名、分类项目别名、图片文件名称为汉语拼音。
Version:            1.5.4
Author:             WordPress 智库
Author URI:         https://www.wpzhiku.com/
License:            MIT License
License URI:        http://opensource.org/licenses/MIT
Requires PHP: 5.6
*/

define('WPRS_PS_PATH', plugin_dir_path(__FILE__));
define('WPRS_PS_VERSION', '1.5.3');

add_action('plugins_loaded', function ()
{
    if (PHP_VERSION_ID < 50600) {

        // 显示警告信息
        if (is_admin()) {
            add_action('admin_notices', function ()
            {
                printf('<div class="error"><p>' . __('Wenprise Pinyin Slug 需要 PHP %1$s 以上版本才能运行，您当前的 PHP 版本为 %2$s， 请升级到 PHP 到 %1$s 或更新的版本， 否则插件没有任何作用。',
                        'wprs') . '</p></div>',
                    '5.6.0', PHP_VERSION);
            });
        }

        return;
    }


    /**
     * 升级数据库
     */
    $installed_version = get_option('wprs_pinyin_slug_version', '1.4.13');

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


    // 加载插件主要功能
    require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
});


function wprs_convert_chinese_filenames()
{
    global $wpdb;
    $post_table_name     = $wpdb->prefix . 'posts';
    $postmeta_table_name = $wpdb->prefix . 'postmeta';

    $uploads_dir  = wp_upload_dir();                                             // Get the WordPress uploads directory
    $uploads_path = $uploads_dir[ 'basedir' ];                                   // Get the path to the uploads directory
    $files        = Nette\Utils\Finder::findFiles('*.jpg')->from($uploads_path); // Get a list of all files in the uploads directory

    // Initialize an array to store the original and converted file names
    $file_names = [];

    // Loop through each file in the uploads directory
    foreach ($files as $file) {
        if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $file->getFilename())) { // Check if the file name contains Chinese characters
            $extension     = $file->getExtension();

            $old_file_name = str_replace('.' . $extension, '', $file->getFilename());
            $old_post_name          = sanitize_title($old_file_name);

            $new_file_name = wprs_slug_convert($old_file_name);
            $file_dir_path = str_replace($file->getFilename(), '', $file->getPath());

            rename($file->getPathname(), $file_dir_path . '/' . $new_file_name . '.' . $extension);

            $wpdb->query("UPDATE $post_table_name SET guid = REPLACE(guid, '$old_file_name', '$new_file_name')");
            $wpdb->query("UPDATE $post_table_name SET post_name = REPLACE(post_name, '$old_post_name', '$new_file_name')");
            $wpdb->query("UPDATE $post_table_name SET post_title = REPLACE(post_title, '$old_post_name', '$new_file_name')");

            //这里可能会影响SEO，需要设置开关
            $wpdb->query("UPDATE $postmeta_table_name SET meta_value	 = REPLACE(meta_value, '$old_file_name', '$new_file_name')");

            $file_names[ $file->getPathname() ] = $new_file_name . '.' . $extension;
        }
    }

    return $file_names;
}

// Hook the function to run on the init action
// add_action('init', 'wprs_convert_chinese_filenames');