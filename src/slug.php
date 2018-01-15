<?php

use Overtrue\Pinyin\Pinyin;

add_filter( 'name_save_pre', function ( $slug ) {

	// 手动编辑时，不自动转换为拼音
	if ( $slug ) {
		return $slug;
	}

	// 替换文章标题
	$title = wprs_slug_convert( $_POST[ 'post_title' ] );

	return sanitize_title( $title );
}, 0 );


/**
 * 替换分类标题为拼音
 *
 * @param $slug
 *
 * @return mixed
 */
add_filter( 'pre_category_nicename', function ( $slug ) {

	// 手动编辑时，不自动转换为拼音
	if ( $slug ) {
		return $slug;
	}

	// 替换文章标题
	$title = wprs_slug_convert( $_POST[ 'tag-name' ] );

	return sanitize_title( $title );
}, 0 );


/**
 * 替换文件名称为拼音
 *
 * @param $filename
 *
 * @return mixed
 */
add_filter( 'sanitize_file_name', function ( $filename ) {

	// 手动编辑时，不自动转换为拼音
	$parts     = explode( '.', $filename );
	$filename  = array_shift( $parts );
	$extension = array_pop( $parts );

	foreach ( (array) $parts as $part ) {
		$filename .= '.' . $part;
	}

	if ( preg_match( '/[\x{4e00}-\x{9fa5}]+/u', $filename ) ) {
		$filename = wprs_slug_convert( $filename );
	}

	$filename .= '.' . $extension;

	return $filename;

}, 0 );


/**
 * 转换拼音的通用功能
 *
 * @param $name
 *
 * @return string 转换后的拼音
 */
if ( ! function_exists( 'wprs_slug_convert' ) ) {
	function wprs_slug_convert( $name ) {

		$divider = wprs_plugin_get_option( 'wprs_pinyin_slug', 'divider', '-' );
		$type    = wprs_plugin_get_option( 'wprs_pinyin_slug', 'type', '0' );

		$pinyin = new Pinyin();

		if ( (int) $type === 0 ) {
			$slug = $pinyin->permalink( $name, $divider );
		} else {
			$slug = $pinyin->abbr( $name, $divider );
		}

		return $slug;

	}
}