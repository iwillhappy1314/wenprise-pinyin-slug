<?php
/**
 * Wenprise Pinyin Slug 设置
 *
 * @author Amos Lee
 */
if ( ! class_exists( 'Wenprise_Pinyin_Slug_Settings' ) ):

	class Wenprise_Pinyin_Slug_Settings {

		private $settings_api;

		function __construct() {
			$this->settings_api = new WeDevs_Settings_API;

			add_action( 'admin_init', [ $this, 'admin_init' ] );
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		}

		function admin_init() {

			// set the settings
			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_fields( $this->get_settings_fields() );

			// initialize settings
			$this->settings_api->admin_init();
		}

		function admin_menu() {
			add_options_page( '别名转拼音', '别名转拼音', 'delete_posts', 'settings_api_test', [ $this, 'plugin_page' ] );
		}

		function get_settings_sections() {
			$sections = [
				[
					'id'    => 'wprs_pinyin_slug',
					'title' => __( '文章分类别名/文件名转拼音设置', 'wprs' ),
				],
			];

			return $sections;
		}

		/**
		 * 设置字段
		 *
		 * @return array settings fields
		 */
		function get_settings_fields() {
			$settings_fields = [
				'wprs_pinyin_slug' => [
					[
						'name'              => 'divider',
						'label'             => __( '拼音分隔分隔符', 'wprs' ),
						'desc'              => __( '默认为 “-”，如过不需要分隔符，请留空', 'wprs' ),
						'placeholder'       => __( '-', 'wprs' ),
						'default'           => '-',
						'sanitize_callback' => 'sanitize_text_field',
					],
					[
						'name'    => 'type',
						'label'   => __( '转换方式', 'wprs' ),
						'desc'    => __( '全拼或每个字的第一个字母', 'wprs' ),
						'type'    => 'select',
						'default' => 'no',
						'options' => [
							'0' => '全拼',
							'1' => '第一个字母',
						],
					],
				],
			];

			return $settings_fields;
		}

		function plugin_page() {
			echo '<div class="wrap">';
			$this->settings_api->show_forms();
			echo '</div>';
		}

	}

endif;


/**
 * 获取设置的值
 *
 * @param string $section 选项所属的设置区域
 * @param string $option  选项名称
 * @param string $default 找不到选项值时的默认值
 *
 * @return mixed
 */
if ( ! function_exists( 'wprs_plugin_get_option' ) ) {
	function wprs_plugin_get_option( $section, $option, $default = '' ) {

		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}

}


new Wenprise_Pinyin_Slug_Settings();