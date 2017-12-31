<?php

use Overtrue\Pinyin\Pinyin;

add_filter( 'name_save_pre', function ( $slug ) {

	// 手动编辑时，不自动转换为拼音
	if ( $slug ) {
		return $slug;
	}

	$pinyin = new Pinyin();

	// 替换文章标题
	$title = $pinyin->permalink( $_POST[ 'post_title' ] );

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

	$pinyin = new Pinyin();

	// 替换文章标题
	$title = $pinyin->permalink( $_POST[ 'tag-name' ] );

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
	$pinyin = new Pinyin();

	$parts     = explode( '.', $filename );
	$filename  = array_shift( $parts );
	$extension = array_pop( $parts );

	foreach ( (array) $parts as $part ) {
		$filename .= '.' . $part;
	}

	if ( preg_match( '/[\x{4e00}-\x{9fa5}]+/u', $filename ) ) {
		$filename = $pinyin->permalink( $filename );
	}

	$filename .= '.' . $extension;

	return $filename;

}, 0 );



