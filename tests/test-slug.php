<?php
/**
 * Class SampleTest
 *
 * @package Wenprise_Theme_Helper
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Sample test case.
 */
class SlugTest extends TestCase
{

    var $post_id;
    var $cat_id;
    var $cat_id2;
    var $tag_id;
    var $file_id;
    var $file_id2;

    function set_up()
    {
        global $wpdb;

        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');

        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}posts");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}postmeta");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}terms");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}term_taxonomy");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}term_relationships");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}termmeta");

        $this->post_id = wp_insert_post([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'post_title'  => '中文标题测试',
        ]);

        $this->cat_id  = wp_insert_term('中文分类名称', 'category');
        $this->cat_id2 = wp_insert_term('English 中文分类名称', 'category');
        // $this->tag_id  = wp_insert_term('中文标签名称', 'post_tag');

        $this->file_id  = $this->upload_image_to_media_library(dirname(__FILE__) . '/中文图片名称.jpg');
        $this->file_id2  = $this->upload_image_to_media_library(dirname(__FILE__) . '/中文图片名称多后缀.test.jpg');
    }


    /**
     * 全拼转换测试
     */
    public function test_wprs_full_slug_convert()
    {
        $this->assertEquals('zhe-shi-ce-shi', \WenprisePinyinSlug\Helpers::slug_convert('这是 测试 ～ ！'));
        $this->assertEquals('this-is-a-tes-zhe-shi-yi-ge-ce-shi', \WenprisePinyinSlug\Helpers::slug_convert('this is a tes 这是一个测试'));
    }


    /**
     * 首字母转换测试
     */
    public function test_wprs_divider_convert()
    {
        $option              = (array)get_option('wprs_pinyin_slug');
        $option[ 'type' ]    = 1;
        $option[ 'divider' ] = '_';
        update_option('wprs_pinyin_slug', $option);

        $this->assertEquals('z_s_c_s_l_z', \WenprisePinyinSlug\Helpers::slug_convert('这是 测试例子'));
    }


    /**
     * 首字母转换测试
     */
    public function test_wprs_first_slug_convert()
    {
        $option              = (array)get_option('wprs_pinyin_slug');
        $option[ 'type' ]    = 1;
        $option[ 'divider' ] = '-';
        update_option('wprs_pinyin_slug', $option);

        $this->assertEquals('z-s-c-s', \WenprisePinyinSlug\Helpers::slug_convert('这是 测试 ～ ！'));
        $this->assertEquals('t-i-a-t-z-s-y-g-c-s', \WenprisePinyinSlug\Helpers::slug_convert('this is a tes 这是一个测试'));

        add_filter('wenprise_converted_slug', function ($slug, $name, $type)
        {
            return $slug . 99;
        }, 10, 3);

        $this->assertEquals('z-s-c-s99', \WenprisePinyinSlug\Helpers::slug_convert('这是 测试 ～ ！'));
    }


    /**
     * 测试别名转换函数
     */
    // public function test_baidu_api()
    // {
    //     $option                    = get_option('wprs_pinyin_slug');
    //     $option[ 'type' ]          = 2;
    //     $option[ 'baidu_app_id' ]  = '20190115000256953';
    //     $option[ 'baidu_api_key' ] = 'X6UIcorRPPt01X4PJgYA';
    //
    //     update_option('wprs_pinyin_slug', $option);
    //
    //     $this->assertEquals('popular-in-30-days', \WenprisePinyinSlug\Helpers::slug_convert('30天热门'));
    // }


    /**
     * 测试别名截断函数
     */
    public function test_wprs_trim_slug()
    {
        $slug   = 'this-is-a-test-for-limit-slug-length';
        $length = 13;

        $this->assertLessThan($length, strlen(\WenprisePinyinSlug\Helpers::trim_slug($slug, $length)));
        $this->assertSame(strpos($slug, \WenprisePinyinSlug\Helpers::trim_slug($slug, $length)), 0);
    }


    /**
     * 测试 wp_insert_post 函数
     */
    public function test_wp_insert_post()
    {
        $post_id = wp_insert_post([
            'post_title'  => '中文标题',
            'post_status' => 'publish',
        ]);

        $post_id2 = wp_insert_post([
            'post_title'  => '中文标题',
            'post_status' => 'publish',
        ]);

        $post  = get_post($post_id);
        $post2 = get_post($post_id2);

        $this->assertEquals($post->post_name, \WenprisePinyinSlug\Helpers::slug_convert('中文标题'));
        $this->assertEquals($post->post_name, \WenprisePinyinSlug\Helpers::slug_convert('中文标题'));
    }


    /**
     * 测试文章别名转换
     */
    public function test_post_slug_convert()
    {
        $post           = get_post($this->post_id);
        $slug_converted = \WenprisePinyinSlug\Helpers::slug_convert($post->post_title);

        $this->assertEquals($post->post_name, $slug_converted);
    }


    /**
     * 测试分类别名转换
     */
    public function test_term_slug_convert()
    {
        $category  = get_term_by('id', $this->cat_id, 'category');
        $category2 = get_term_by('id', $this->cat_id2, 'category');
        $tag       = get_term_by('id', $this->tag_id, 'post_tag');

        // var_dump($tag);

        $this->assertEquals($category->slug, \WenprisePinyinSlug\Helpers::slug_convert($category->name));
        $this->assertEquals($category2->slug, \WenprisePinyinSlug\Helpers::slug_convert($category2->name));
        // $this->assertEquals($tag->slug, \WenprisePinyinSlug\Helpers::slug_convert($tag->name));
    }


    /**
     * 测试分类别名转换
     */
    public function test_wprs_convert_file_name()
    {
        $file  = get_post($this->file_id);
        $file2 = get_post($this->file_id2);

        $slug_converted  = \WenprisePinyinSlug\Helpers::slug_convert('中文图片名称');
        $slug_converted2 = \WenprisePinyinSlug\Helpers::slug_convert('中文图片名称多后缀.test');

        $this->assertEquals(strpos($file->post_name, $slug_converted), 0);
        $this->assertEquals(strpos($file2->post_name, $slug_converted2), 0);
    }


    /**
     * 测试获取插件设置 tag-name
     */
    public function test_wprs_plugin_get_option()
    {
        $this->assertEquals(34, \WenprisePinyinSlug\Helpers::get_option('wprs_pinyin_slug', 'length', 34));
    }


    /**
     * 上传图片到 WordPress 媒体库
     *
     * @param string $file_path      图片文件的完整路径
     * @param int    $post_id        关联的文章ID(可选)
     * @param string $desc           图片描述(可选)
     * @return int|WP_Error          成功返回附件ID,失败返回WP_Error
     */
    function upload_image_to_media_library($file_path, $post_id = 0, $desc = '') {
        // 确保包含需要的文件
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // 检查文件是否存在
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', '图片文件不存在');
        }

        // 获取文件信息
        $file_info = pathinfo($file_path);
        $file_name = $file_info['basename'];

        // 检查是否为允许的图片类型
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array(strtolower($file_info['extension']), $allowed_types)) {
            return new WP_Error('invalid_file_type', '不支持的图片格式');
        }

        // 准备上传数据
        $upload = array(
            'name'     => $file_name,
            'type'     => mime_content_type($file_path),
            'tmp_name' => $file_path,
            'error'    => 0,
            'size'     => filesize($file_path)
        );

        // 复制文件到上传目录
        $uploads = wp_upload_dir();
        $new_file_path = $uploads['path'] . '/' . wp_unique_filename($uploads['path'], $file_name);

        if (!copy($file_path, $new_file_path)) {
            return new WP_Error('copy_failed', '复制文件失败');
        }

        $file = array(
            'file' => $new_file_path,
            'url'  => $uploads['url'] . '/' . basename($new_file_path),
            'type' => mime_content_type($new_file_path)
        );

        if (isset($file['error'])) {
            return new WP_Error('upload_error', $file['error']);
        }

        // 准备附件数据
        $attachment = array(
            'post_mime_type' => $file['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', $file_name),
            'post_content'   => $desc,
            'post_status'    => 'inherit',
            'guid'           => $file['url']
        );

        // 插入附件
        $attach_id = wp_insert_attachment($attachment, $file['file'], $post_id);

        if (is_wp_error($attach_id)) {
            return $attach_id;
        }

        // 生成附件的元数据
        $attach_data = wp_generate_attachment_metadata($attach_id, $file['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
}