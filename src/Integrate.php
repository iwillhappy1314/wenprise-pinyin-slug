<?php

namespace WenprisePinyinSlug;

class Integrate {

	public function __construct() {
		add_filter( 'wp_unique_post_slug', [ $this, 'wp_unique_post_slug' ], 10, 6 );

		add_filter( 'pre_category_nicename', [ $this, 'pre_category_nicename' ], 10, 2 );

		add_filter( 'wp_insert_term_data', [ $this, 'wp_insert_term_data' ], 10, 3 );

		add_filter( 'wp_update_term_data', [ $this, 'wp_update_term_data' ], 10, 4 );

		add_filter( 'sanitize_file_name', [ $this, 'sanitize_file_name' ], 10, 4 );
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
	function wp_unique_post_slug($slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug) {
		// 1. 在这里不处理附件别名
		if (empty($slug) || $post_type === 'attachment') {
			return $slug;
		}

		// 2. 获取旧状态，添加错误处理
		$old_status = get_post_field('post_status', $post_ID, 'edit');
		if (is_wp_error($old_status)) {
			error_log('Failed to get post status: ' . $old_status->get_error_message());
			return $slug;
		}

		// 3. 扩展状态检查逻辑
		$convert_status = false;
		
		// 3.1 处理从非公开到公开的转换
		if ($post_status === 'publish' && !in_array($old_status, ['publish', 'future'])) {
			$convert_status = true;
		}
		
		// 3.2 处理定时发布
		if ($old_status === 'future' && $post_status === 'publish') {
			$convert_status = true;
		}
		
		// 3.3 处理从回收站恢复
		if ($old_status === 'trash' && $post_status !== 'trash') {
			$convert_status = true;
		}

		// 4. 执行转换
		if ($convert_status) {
			return Helpers::slug_convert($slug);
		}

		return $slug;
	}


	/**
	 * 替换分类标题为拼音
	 *
	 * @param $slug
	 *
	 * @return mixed
	 */
	function pre_category_nicename( $slug ) {

		// 手动编辑时，不自动转换为拼音
		if ( $slug ) {
			return $slug;
		}

		$tag_name = isset( $_POST[ 'tag-name' ] ) && $_POST[ 'tag-name' ];

		// 替换文章标题
		if ( $tag_name ) {
			$slug = Helpers::slug_convert( $_POST[ 'tag-name' ], 'term' );
		}

		return sanitize_title($slug);
	}


	function wp_insert_term_data( $data, $taxonomy, $args ) {

		// 手动编辑时，不自动转换为拼音
		if ( $args[ 'slug' ] === '' ) {
			$data[ 'slug' ] = wp_unique_term_slug( Helpers::slug_convert( $data[ 'name' ], 'term' ), (object) $args );
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
	function wp_update_term_data( $data, $term_id, $taxonomy, $args ) {

		// 手动编辑时，不自动转换为拼音
		if ( $args[ 'slug' ] === '' ) {
			$data[ 'slug' ] = wp_unique_term_slug( Helpers::slug_convert( $data[ 'name' ], 'term' ), (object) $args );
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
	function sanitize_file_name( $filename ) {
		$disable_file_convert = Helpers::get_option( 'wprs_pinyin_slug', 'disable_file_convert', 'off' );

		if ( $disable_file_convert === 'on' ) {
			return $filename;
		}

		// 手动编辑时，不自动转换为拼音
		$parts = explode( '.', $filename );

		// 没有后缀时，直接返回文件名，不用再加 . 和后缀
		if ( count( $parts ) <= 1 ) {
			if ( preg_match( '/[\x{4e00}-\x{9fa5}]+/u', $filename ) ) {
				return Helpers::slug_convert( $filename, 'file' );
			}

			return $filename;
		}

		$filename  = array_shift( $parts );
		$extension = array_pop( $parts );

		foreach ( (array) $parts as $part ) {
			$filename .= '.' . $part;
		}

		if ( preg_match( '/[\x{4e00}-\x{9fa5}]+/u', $filename ) ) {
			$filename = Helpers::slug_convert( $filename, 'file' );
		}

		$filename .= '.' . $extension;

		return $filename;

	}

}
