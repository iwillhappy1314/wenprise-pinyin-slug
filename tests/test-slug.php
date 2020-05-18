<?php
/**
 * Class SampleTest
 *
 * @package Wenprise_Theme_Helper
 */

/**
 * Sample test case.
 */
class SlugTest extends WP_UnitTestCase
{

    function setUp()
    {
        // Call the setup method on the parent or the factory objects won't be loaded!
        parent::setUp();

        $this->post_id = $this->factory->post->create([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'post_title'  => '中文标题测试',
        ]);


        $this->cat_id = $this->factory->term->create([
            'name'     => '中文分类名称',
            'taxonomy' => 'category',
            'slug'     => '',
        ]);

        $this->cat_id2 = $this->factory->term->create([
            'name'     => 'English 中文分类名称',
            'taxonomy' => 'category',
            'slug'     => '',
        ]);

        $this->tag_id = $this->factory->term->create([
            'name'     => '中文标签名称',
            'taxonomy' => 'post_tag',
            'slug'     => '',
        ]);


        $this->file_id = $this->factory->attachment->create_upload_object(
            WPRS_PS_PATH . '/tests/中文图片名称.jpg'
        );

        $this->file_id3 = $this->factory->attachment->create_upload_object(
            WPRS_PS_PATH . '/tests/中文图片名称多后缀.test.jpg'
        );

    }


    /**
     * 全拼转换测试
     */
    public function test_wprs_full_slug_convert()
    {
        $this->assertEquals('zhe-shi-ce-shi', wprs_slug_convert('这是 测试 ～ ！'));
        $this->assertEquals('this-is-a-tes-zhe-shi-yi-ge-ce-shi', wprs_slug_convert('this is a tes 这是一个测试'));
    }


    /**
     * 首字母转换测试
     */
    public function test_wprs_first_slug_convert()
    {
        $option           = get_option('wprs_pinyin_slug');
        $option[ 'type' ] = 1;
        update_option('wprs_pinyin_slug', $option);

        $this->assertEquals('z-s-c-s', wprs_slug_convert('这是 测试 ～ ！'));
        $this->assertEquals('t-i-a-t-z-s-y-g-c-s', wprs_slug_convert('this is a tes 这是一个测试'));

        add_filter('wenprise_converted_slug', function ($slug, $name, $type)
        {
            return $slug . 99;
        }, 10, 3);

        $this->assertEquals('z-s-c-s99', wprs_slug_convert('这是 测试 ～ ！'));
    }


    /**
     * 首字母转换测试
     */
    public function test_wprs_devider_convert()
    {
        $option              = get_option('wprs_pinyin_slug');
        $option[ 'type' ]    = 2;
        $option[ 'divider' ] = '_';
        update_option('wprs_pinyin_slug', $option);

        $this->assertEquals('z_s_c_s', wprs_slug_convert('这是 测试 ～ ！'));
    }


    /**
     * 测试别名转换函数
     */
    public function test_baidu_api()
    {
        $option                    = get_option('wprs_pinyin_slug');
        $option[ 'type' ]          = 2;
        $option[ 'baidu_app_id' ]  = '20190115000256953';
        $option[ 'baidu_api_key' ] = 'X6UIcorRPPt01X4PJgYA';

        update_option('wprs_pinyin_slug', $option);

        $this->assertEquals('30-days-hot', wprs_slug_convert('30天热门'));
    }


    /**
     * 测试别名截断函数
     */
    public function test_wprs_trim_slug()
    {
        $slug   = 'this-is-a-test-for-limit-slug-length';
        $length = 13;

        $this->assertLessThan($length, strlen(wprs_trim_slug($slug, $length)));
        $this->assertSame(strpos($slug, wprs_trim_slug($slug, $length)), 0);
    }


    /**
     * 测试 wp_insert_post 函数
     */
    // public function test_wp_insert_post()
    // {
    //     $post_id = wp_insert_post([
    //         'post_title'  => '中文标题',
    //         'post_status' => 'publish',
    //     ]);
    //
    //     $post = get_post($post_id);
    //
    //     $this->assertEquals($post->post_name, wprs_slug_convert('中文标题'));
    // }


    /**
     * 测试文章别名转换
     */
    // public function test_post_slug_convert()
    // {
    //     $post           = get_post($this->post_id);
    //     $slug_converted = wprs_slug_convert($post->post_title);
    //
    //     $this->assertEquals($post->post_name, $slug_converted);
    // }


    /**
     * 测试分类别名转换
     */
    public function test_term_slug_convert()
    {
        $category  = get_term_by('id', $this->cat_id, 'category');
        $category2 = get_term_by('id', $this->cat_id2, 'category');
        $tag       = get_term_by('id', $this->tag_id, 'post_tag');

        $this->assertEquals($category->slug, wprs_slug_convert($category->name));
        $this->assertEquals($category2->slug, wprs_slug_convert($category2->name));
        $this->assertEquals($tag->slug, wprs_slug_convert($tag->name));
    }


    /**
     * 测试分类别名转换
     */
    public function test_wprs_convert_file_name()
    {
        $file  = get_post($this->file_id);
        $file2 = get_post($this->file_id2);
        $file3 = get_post($this->file_id3);

        $slug_converted  = wprs_slug_convert('中文图片名称');
        $slug_converted3 = wprs_slug_convert('中文图片名称多后缀.test');

        $this->assertEquals(strpos($file->post_name, $slug_converted), 0);
        $this->assertEquals(strpos($file3->post_name, $slug_converted3), 0);
    }


    /**
     * 测试获取插件设置 tag-name
     */
    public function test_wprs_plugin_get_option()
    {
        $this->assertEquals(34, wprs_slug_get_option('wprs_pinyin_slug', 'length', 34));
    }

}