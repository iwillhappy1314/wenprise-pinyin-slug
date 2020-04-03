<?php
/**
 * Wenprise Pinyin Slug 设置
 *
 * @author Amos Lee
 */
if ( ! class_exists('Wenprise_Pinyin_Slug_Settings')):

    class Wenprise_Pinyin_Slug_Settings
    {

        private $settings_api;

        public function __construct()
        {
            $this->settings_api = new WeDevs_Settings_API;

            add_action('admin_init', [$this, 'admin_init']);
            add_action('admin_menu', [$this, 'admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        }


        /**
         * 加载 JS
         *
         * @param $hook
         */
        public function enqueue_scripts($hook)
        {

            if ($hook !== 'settings_page_wenprise_pinyin_slug') {
                return;
            }

            wp_enqueue_script('wenprise_pinyin_slug_admin_script', plugin_dir_url(__FILE__) . 'scripts.js');
        }


        /**
         * 初始化
         */
        public function admin_init()
        {

            // set the settings
            $this->settings_api->set_sections($this->get_settings_sections());
            $this->settings_api->set_fields($this->get_settings_fields());

            // initialize settings
            $this->settings_api->admin_init();
        }


        /**
         * 添加设置菜单
         */
        public function admin_menu()
        {
            add_options_page('别名转拼音|英文', '别名转拼音|英文', 'manage_options', 'wenprise_pinyin_slug', [$this, 'plugin_page']);
        }


        /**
         * 获取设置区域
         *
         * @return array
         */
        public function get_settings_sections()
        {
            return [
                [
                    'id'    => 'wprs_pinyin_slug',
                    'title' => __('文章分类别名/文件名转拼音设置', 'wprs'),
                ],
            ];
        }


        /**
         * 设置字段
         *
         * @return array settings fields
         */
        public function get_settings_fields()
        {
            return [
                'wprs_pinyin_slug' => [
                    [
                        'name'    => 'type',
                        'label'   => __('转换方式', 'wprs'),
                        'desc'    => __('全拼、首字母或或百度翻译 (首字母模式下，英文也会取第一个字母)', 'wprs'),
                        'type'    => 'select',
                        'default' => 0,
                        'options' => [
                            0 => '拼音全拼',
                            1 => '拼音首字母',
                            2 => '百度翻译',
                        ],
                    ],

                    [
                        'name'              => 'divider',
                        'label'             => __('拼音分隔分隔符', 'wprs'),
                        'desc'              => __('可以是：_ 或 - 或 . &nbsp; 默认为 “-”，如过不需要分隔符，请留空', 'wprs'),
                        'placeholder'       => __('-', 'wprs'),
                        'default'           => '',
                        'type'              => 'text',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],

                    [
                        'name'              => 'length',
                        'label'             => __('别名长度限制', 'wprs'),
                        'desc'              => __('超过设置的长度后，会按照指定的长度截断转换后的拼音字符串。为保持拼音的完整性，如果设置了分隔符，会在最后一个分隔符后截断', 'wprs'),
                        'type'              => 'text',
                        'default'           => '60',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],

                    [
                        'name'              => 'baidu_app_id',
                        'label'             => __('百度翻译 APP ID', 'wprs'),
                        'desc'              => __('请在百度翻译开放平台获取：http://api.fanyi.baidu.com/api/trans/product/index', 'wprs'),
                        'type'              => 'text',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],

                    [
                        'name'              => 'baidu_api_key',
                        'label'             => __('百度翻译密钥', 'wprs'),
                        'desc'              => __('请在百度翻译开放平台获取：http://api.fanyi.baidu.com/api/trans/product/index', 'wprs'),
                        'type'              => 'text',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],

                    [
                        'name'              => 'disable_file_convert',
                        'label'             => __('禁用文件名转换', 'wprs'),
                        'desc'              => __('不要自动转换文件名', 'wprs'),
                        'type'              => 'checkbox',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],

                ],
            ];
        }


        /**
         * 插件设置页面
         */
        public function plugin_page()
        {
            echo '<div class="wrap">';
            $this->settings_api->show_forms();
            echo '</div>';
        }

    }

endif;

new Wenprise_Pinyin_Slug_Settings();