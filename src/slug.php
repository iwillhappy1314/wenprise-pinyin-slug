<?php

use Overtrue\Pinyin\Pinyin;

add_filter('name_save_pre', function ($slug)
{

    // 手动编辑时，不自动转换为拼音
    if ($slug && $slug != '') {
        return $slug;
    }

    // 替换文章标题
    $title = wprs_slug_convert($_POST[ 'post_title' ]);

    return sanitize_title($title);
}, 10, 1);


/**
 * 替换分类标题为拼音
 *
 * @param $slug
 *
 * @return mixed
 */
add_filter('pre_category_nicename', function ($slug)
{

    // 手动编辑时，不自动转换为拼音
    if ($slug) {
        return $slug;
    }

    // 替换文章标题
    $slug = wprs_slug_convert($_POST[ 'tag-name' ]);

    return sanitize_title($slug);
}, 10, 1);


/**
 * 添加分类时替换分类标题为拼音
 *
 * @param $data     array 需要保存到数据库中的数据
 * @param $term_id  int 分类项目 ID
 * @param $taxonomy string 分类法名称
 * @param $args     array 用户提交的数据
 *
 * @return array 修改后的需要保存到数据库中的数据
 */
add_filter('wp_insert_term_data', function ($data, $taxonomy, $args)
{

    // 手动编辑时，不自动转换为拼音
    if ($args[ 'slug' ] === '') {
        $data[ 'slug' ] = wp_unique_term_slug(wprs_slug_convert($data[ 'name' ]), (object)$args);
    }

    return $data;
}, 10, 3);


/**
 * 更新分类时分类标题为拼音
 *
 * @param $data     array 需要保存到数据库中的数据
 * @param $term_id  int 分类项目 ID
 * @param $taxonomy string 分类法名称
 * @param $args     array 用户提交的数据
 *
 * @return array 修改后的需要保存到数据库中的数据
 */
add_filter('wp_update_term_data', function ($data, $term_id, $taxonomy, $args)
{

    // 手动编辑时，不自动转换为拼音
    if ($args[ 'slug' ] === '') {
        $data[ 'slug' ] = wp_unique_term_slug(wprs_slug_convert($data[ 'name' ]), (object)$args);
    }

    return $data;
}, 10, 4);


/**
 * 替换文件名称为拼音
 *
 * @param $filename
 *
 * @return mixed
 */
add_filter('sanitize_file_name', function ($filename)
{

    // 手动编辑时，不自动转换为拼音
    $parts     = explode('.', $filename);
    $filename  = array_shift($parts);
    $extension = array_pop($parts);

    foreach ((array)$parts as $part) {
        $filename .= '.' . $part;
    }

    if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $filename)) {
        $filename = wprs_slug_convert($filename);
    }

    $filename .= '.' . $extension;

    return $filename;

}, 10, 1);


/**
 * 获取设置的值
 *
 * @param string $section 选项所属的设置区域
 * @param string $option  选项名称
 * @param string $default 找不到选项值时的默认值
 *
 * @return mixed
 */
if ( ! function_exists('wprs_plugin_get_option')) {
    function wprs_plugin_get_option($section, $option, $default = '')
    {

        $options = get_option($section);

        if (isset($options[ $option ])) {
            return $options[ $option ];
        }

        return $default;
    }

}


/**
 * 转换拼音的通用功能
 *
 * @param $name
 *
 * @return string 转换后的拼音
 */
if ( ! function_exists('wprs_slug_convert')) {
    function wprs_slug_convert($name)
    {

        $divider = wprs_plugin_get_option('wprs_pinyin_slug', 'divider', '-');
        $type    = wprs_plugin_get_option('wprs_pinyin_slug', 'type', 0);
        $length  = wprs_plugin_get_option('wprs_pinyin_slug', 'length', 50);

        $pinyin = new Pinyin();

        if ($type == 0) {
            $slug = $pinyin->permalink($name, $divider);
        } else {
            $slug = $pinyin->abbr($name, $divider);
        }

        $slug = wprs_trim_slug($slug, $length, $divider);

        return $slug;

    }
}


/**
 * 裁剪文本
 *
 * @param      $input
 * @param      $length
 * @param bool $divider
 * @param bool $strip_html
 *
 * @return bool|string
 */
if ( ! function_exists('wprs_trim_slug')) {
    function wprs_trim_slug($input, $length, $divider = '-', $strip_html = true)
    {
        // strip tags, if desired
        if ($strip_html) {
            $input = strip_tags($input);
        }

        // no need to trim, already shorter than trim length
        if (strlen($input) <= $length) {
            return $input;
        }

        $trimmed_text = substr($input, 0, $length);

        // find last space within length
        if ($divider != '') {
            $last_space   = strrpos(substr($input, 0, $length), $divider);
            $trimmed_text = substr($input, 0, $last_space);
        }

        return $trimmed_text;
    }
}