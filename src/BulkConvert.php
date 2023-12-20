<?php

namespace WenprisePinyinSlug;

class BulkConvert
{

    public function __construct()
    {
        add_filter('bulk_actions-edit-post', [$this, 'add_bulk_action']);
        add_filter('bulk_actions-edit-page', [$this, 'add_bulk_action']);
        add_filter('handle_bulk_actions-edit-post', [$this, 'handle_bulk_action'], 10, 3);
        add_filter('handle_bulk_actions-edit-page', [$this, 'handle_bulk_action'], 10, 3);

        add_action('admin_notices', [$this, 'admin_notice']);
    }


    /**
     * 添加批量操作选项
     *
     * @param $bulk_actions
     *
     * @return mixed
     */
    function add_bulk_action($bulk_actions)
    {
        $use_translator_api = (int)Helpers::get_option('wprs_pinyin_slug', 'type', 0);

        $bulk_actions[ 'convert-slug' ] = '转换别名为' . (($use_translator_api === 2) ? '英文' : '拼音');

        return $bulk_actions;
    }


    /**
     * 处理批量操作
     *
     * @param $redirect_url
     * @param $action
     * @param $post_ids
     *
     * @return mixed|string
     */
    function handle_bulk_action($redirect_url, $action, $post_ids)
    {
        if ($action == 'convert-slug') {
            foreach ($post_ids as $post_id) {
                $post     = get_post($post_id);
                $new_slug = Helpers::slug_convert($post->post_title);

                wp_update_post([
                    'ID'        => $post->ID,
                    'post_name' => $new_slug,
                ]);
            }

            // 避免发送太快，导致 API 失败
            sleep(1);

            $redirect_url = add_query_arg('convert-slug-done', count($post_ids), $redirect_url);
        }

        return $redirect_url;
    }


    /**
     * 转换完成后显示通知。
     *
     * @return void
     */
    function admin_notice()
    {
        if (isset($_GET[ 'convert-slug-done' ])) {
            echo '<div class="notice notice-success is-dismissible"><p>转换完成，感谢使用 Wenprise Pinyin Slug!</p></div>';
        }
    }

}


