<?php

use WenprisePinyinSlug\Helpers;

function wprs_convert_chinese_filenames()
{
	global $wpdb;
	$post_table_name     = $wpdb->prefix . 'posts';
	$postmeta_table_name = $wpdb->prefix . 'postmeta';
	$file_name_preg      = "/[^a-zA-Z0-9\-_]/";

	$uploads_dir  = wp_upload_dir();
	$uploads_path = $uploads_dir['basedir'];

	// 使用 RecursiveDirectoryIterator 和 RecursiveIteratorIterator 替换 Finder
	$directory = new RecursiveDirectoryIterator($uploads_path);
	$iterator = new RecursiveIteratorIterator($directory);
	$files = new RegexIterator($iterator, '/^.+\.jpg$/i', RecursiveRegexIterator::GET_MATCH);

	// 初始化一个数组来存储原始和转换后的文件名
	$file_names = [];

	// 遍历上传目录中的每个文件
	foreach ($files as $file) {
		$file = new SplFileInfo($file[0]);
		if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $file->getFilename()) || preg_match($file_name_preg, $file->getFilename())) {
			$extension = $file->getExtension();

			$old_file_name = str_replace('.' . $extension, '', $file->getFilename());
			$old_post_name = sanitize_title($old_file_name);

			$new_file_name = Helpers::slug_convert($old_file_name);
			$new_file_name = preg_replace($file_name_preg, "", $new_file_name);

			$file_dir_path = $file->getPath();

			rename($file->getPathname(), $file_dir_path . '/' . $new_file_name . '.' . $extension);

			$wpdb->query($wpdb->prepare("UPDATE $post_table_name SET guid = REPLACE(guid, %s, %s)", $old_file_name, $new_file_name));
			$wpdb->query($wpdb->prepare("UPDATE $post_table_name SET post_name = REPLACE(post_name, %s, %s)", $old_post_name, $new_file_name));
			$wpdb->query($wpdb->prepare("UPDATE $post_table_name SET post_title = REPLACE(post_title, %s, %s)", $old_post_name, $new_file_name));
			$wpdb->query($wpdb->prepare("UPDATE $post_table_name SET post_content = REPLACE(post_content, %s, %s)", $old_post_name, $new_file_name));

			//这里可能会影响SEO，需要设置开关
			$wpdb->query($wpdb->prepare("UPDATE $postmeta_table_name SET meta_value = REPLACE(meta_value, %s, %s)", $old_file_name, $new_file_name));

			$file_names[$file->getPathname()] = $new_file_name . '.' . $extension;
		}
	}

	return $file_names;
}

// 将函数挂钩到 init 动作
add_action('init', 'wprs_convert_chinese_filenames');