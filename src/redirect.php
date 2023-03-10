<?php

function wprs_auto_generate_slug()
{
    $supported_post_types = get_post_types(['public' => true]);

    unset($supported_post_types[ 'attachment' ]);

    // Get all published posts
    $args = [
        'post_type'      => get_post_types(['public' => true]),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ];

    $posts = get_posts($args);

    // Loop through each post and generate a new slug
    foreach ($posts as $post) {
        $old_slug = $post->post_name;
        $new_slug = wprs_slug_convert($post->post_title);

        // Update the post with the new slug
        wp_update_post([
            'ID'        => $post->ID,
            'post_name' => $new_slug,
        ]);

        // Add a redirect from the old slug to the new one
        if ($new_slug != $old_slug) {
            wprs_add_redirect($old_slug, $new_slug);
        }
    }
}

add_action('init', 'wprs_auto_generate_slug');

function wprs_add_redirect($old_url, $new_url)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'redirects';
    $redirect   = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE old_url = '$old_url'"));
    if ($redirect) {
        $wpdb->update($table_name, [
            'new_url' => $new_url,
        ], [
            'id' => $redirect->id,
        ]);
    } else {
        $wpdb->insert($table_name, [
            'old_url'    => $old_url,
            'new_url'    => $new_url,
            'created_at' => current_time('mysql'),
        ]);
    }
}

function wprs_create_redirects_table()
{
    global $wpdb;
    $table_name      = $wpdb->prefix . 'redirects';
    $charset_collate = $wpdb->get_charset_collate();
    $sql             = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        old_url VARCHAR(255) NOT NULL,
        new_url VARCHAR(255) NOT NULL,
        status_code VARCHAR(3) NOT NULL DEFAULT '301',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'wprs_create_redirects_table');

function wprs_redirect_old_urls()
{
    global $wpdb;
    $table_name  = $wpdb->prefix . 'redirects';
    $request_url = home_url($_SERVER[ 'REQUEST_URI' ]);
    $redirect    = $wpdb->get_row("SELECT * FROM $table_name WHERE old_url = '$request_url'");
    if ($redirect) {
        wp_redirect(esc_url($redirect->new_url), $redirect->status_code);
        exit;
    }
}

add_action('template_redirect', 'wprs_redirect_old_urls');
