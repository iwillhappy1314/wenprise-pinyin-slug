<?php


function wprs_convert_chinese_filenames()
{
    global $wpdb;
    $post_table_name     = $wpdb->prefix . 'posts';
    $postmeta_table_name = $wpdb->prefix . 'postmeta';
    $file_name_preg      = "/[^a-zA-Z0-9\-_]/";

    $uploads_dir  = wp_upload_dir();                                             // Get the WordPress uploads directory
    $uploads_path = $uploads_dir[ 'basedir' ];                                   // Get the path to the uploads directory
    $files        = Nette\Utils\Finder::findFiles('*.jpg')->from($uploads_path); // Get a list of all files in the uploads directory

    // Initialize an array to store the original and converted file names
    $file_names = [];

    // Loop through each file in the uploads directory
    foreach ($files as $file) {
        if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $file->getFilename()) || preg_match($file_name_preg, $file->getFilename())) { // Check if the file name contains Chinese characters
            $extension = $file->getExtension();

            $old_file_name = str_replace('.' . $extension, '', $file->getFilename());
            $old_post_name = sanitize_title($old_file_name);

            $new_file_name = \WenprisePinyinSlug\Helpers::slug_convert($old_file_name);
            $new_file_name = preg_replace($file_name_preg, "", $new_file_name);

            $file_dir_path = str_replace($file->getFilename(), '', $file->getPath());

            rename($file->getPathname(), $file_dir_path . '/' . $new_file_name . '.' . $extension);

            $wpdb->query($wpdb->prepare("UPDATE $post_table_name SET guid = REPLACE(guid, '$old_file_name', '$new_file_name')"));
            $wpdb->query($wpdb->prepare("UPDATE $post_table_name SET post_name = REPLACE(post_name, '$old_post_name', '$new_file_name')"));
            $wpdb->query($wpdb->prepare("UPDATE $post_table_name SET post_title = REPLACE(post_title, '$old_post_name', '$new_file_name')"));
            $wpdb->query($wpdb->prepare("UPDATE $post_table_name SET post_content = REPLACE(post_content, '$old_post_name', '$new_file_name')"));

            //这里可能会影响SEO，需要设置开关
            $wpdb->query("UPDATE $postmeta_table_name SET meta_value	 = REPLACE(meta_value, '$old_file_name', '$new_file_name')");

            $file_names[ $file->getPathname() ] = $new_file_name . '.' . $extension;
        }
    }

    return $file_names;
}

// Hook the function to run on the init action
add_action('init', 'wprs_convert_chinese_filenames');