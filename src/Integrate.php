<?php

namespace WenprisePinyinSlug;

class Integrate
{

    public function __construct()
    {
        add_filter('wp_unique_post_slug', [$this, 'wp_unique_post_slug'], 10, 6);
        add_filter('name_save_pre', [$this, 'name_save_pre']);

        add_filter('rest_pre_insert_post', [$this, 'convert_rest_slug'], 10, 2);
        add_filter('rest_pre_insert_page', [$this, 'convert_rest_slug'], 10, 2);

        add_filter('pre_category_nicename', [$this, 'pre_category_nicename'], 10, 2);

        add_filter('wp_insert_term_data', [$this, 'wp_insert_term_data'], 10, 3);
        add_filter('wp_update_term_data', [$this, 'wp_update_term_data'], 10, 4);

        add_filter('sanitize_file_name', [$this, 'sanitize_file_name'], 10, 4);
    }


    /**
     * @param $slug
     * @param $post_ID
     * @param $post_status
     * @param $post_type
     * @param $post_parent
     * @param $original_slug
     *
     * @return mixed|string
     */
    function wp_unique_post_slug($slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug)
    {
        // 不处理附件别名
        if ($post_type === 'attachment') {
            return $slug;
        }

        // 还原编码前的别名
        $decoded_slug = urldecode($slug);

        if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $decoded_slug)) {
            $slug = urlencode(Helpers::slug_convert($decoded_slug));
        }

        return $slug;
    }


    /**
     * 文章别名保存之前，如果没有设置，自动转换为拼音
     *
     * @param $slug
     *
     * @return mixed
     */
    function name_save_pre($slug)
    {

        // 手动编辑时，不自动转换为拼音
        if ($slug && $slug !== '') {
            return $slug;
        }

        // 替换文章标题
        return Helpers::slug_convert($_POST[ 'post_title' ] ? : '');
    }


    /**
     * Rest Api 中，文章别名保存之前，如果没有设置，自动转换为拼音
     *
     * @param $prepared_post
     * @param $request
     *
     * @return mixed
     */
    function convert_rest_slug($prepared_post, $request)
    {
        // 获取文章标题
        $post_title = '';
        $saved_post = null;

        // 获取已保存文章
        if (isset($request[ 'id' ])) {
            $saved_post = get_post($request[ 'id' ]);
        }

        // 获取标题
        if (isset($request[ 'title' ])) {
            $post_title = $request[ 'title' ];
        } elseif (isset($request[ 'id' ])) {
            $post_title = $saved_post->post_title;
        }

        // 1. 已发布状态下，如果设置了 slug，说明编辑了 slug，
        // 1.1 如果 slug 为空，说明删除了 slug 需要重新生成
        // 1.2 如果 slug 不为空，说明手动设置了 slug, 使用设置的 slug, 不自动生成
        if ($request[ 'status' ] === 'publish') {

            // 不处理已保存、且文章已有 slug 的情况，避免编辑时修改掉原有中文 slug
            if ($saved_post && $saved_post->post_name === '') {

                if (empty($request[ 'slug' ])) {
                    $prepared_post->post_name = Helpers::slug_convert($post_title);
                }

            }

        } else {

            // 如果上一个状态是已发布，说明执行的是 "切换到草稿" 的操作，这种情况下，不自动转换 slug
            if ( ! $saved_post || $saved_post->post_status !== 'publish') {
                // 2. 其他状态下，如果没有设置 slug, 或 slug 为空，自动生成，如果设置了 slug ,依然不自动生成
                if (empty($request[ 'slug' ])) {
                    $prepared_post->post_name = Helpers::slug_convert($post_title);
                }
            }

        }

        return $prepared_post;
    }


    /**
     * 替换分类标题为拼音
     *
     * @param $slug
     *
     * @return mixed
     */
    function pre_category_nicename($slug)
    {

        // 手动编辑时，不自动转换为拼音
        if ($slug) {
            return $slug;
        }

        $tag_name = isset($_POST[ 'tag-name' ]) && $_POST[ 'tag-name' ];

        // 替换文章标题
        if ($tag_name) {
            $slug = Helpers::slug_convert($_POST[ 'tag-name' ], 'term');
        }

        return $slug;
    }


    function wp_insert_term_data($data, $taxonomy, $args)
    {

        // 手动编辑时，不自动转换为拼音
        if ($args[ 'slug' ] === '') {
            $data[ 'slug' ] = wp_unique_term_slug(Helpers::slug_convert($data[ 'name' ], 'term'), (object)$args);
        }

        return $data;
    }


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
    function wp_update_term_data($data, $term_id, $taxonomy, $args)
    {

        // 手动编辑时，不自动转换为拼音
        if ($args[ 'slug' ] === '') {
            $data[ 'slug' ] = wp_unique_term_slug(Helpers::slug_convert($data[ 'name' ], 'term'), (object)$args);
        }

        return $data;
    }


    /**
     * 替换文件名称为拼音
     *
     * @param $filename
     *
     * @return mixed
     */
    function sanitize_file_name($filename)
    {
        $disable_file_convert = Helpers::get_option('wprs_pinyin_slug', 'disable_file_convert', 'off');

        if ($disable_file_convert === 'on') {
            return $filename;
        }

        // 手动编辑时，不自动转换为拼音
        $parts = explode('.', $filename);

        // 没有后缀时，直接返回文件名，不用再加 . 和后缀
        if (count($parts) <= 1) {
            if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $filename)) {
                return Helpers::slug_convert($filename, 'file');
            }

            return $filename;
        }

        $filename  = array_shift($parts);
        $extension = array_pop($parts);

        foreach ((array)$parts as $part) {
            $filename .= '.' . $part;
        }

        if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $filename)) {
            $filename = Helpers::slug_convert($filename, 'file');
        }

        $filename .= '.' . $extension;

        return $filename;

    }

    /**
     * 不能直接用 sanitize_title, 因为无法判断用户是否手动设置了中文 Slug, 或者用户是在编辑旧的中文 Slug，这两种情况，如果自动转化都有问题
     */
    // add_filter('sanitize_title', function ($title, $raw_title, $context)
    // {
    //     $converted_title = wprs_slug_convert($raw_title);
    //
    //     if ( ! $converted_title) {
    //         $converted_title = $title;
    //     }
    //
    //     return $converted_title;
    // }, 10, 3);

}

