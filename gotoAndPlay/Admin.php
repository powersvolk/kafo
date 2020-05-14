<?php
namespace gotoAndPlay;

class Admin {

    public function __construct() {
        // wp admin menu
        add_action('admin_menu', [$this, 'adminMenu']);
        // wp admin init
        add_action('admin_init', [$this, 'adminInit']);
        // add or remove dashboard glance itemst
        add_action('dashboard_glance_items', [$this, 'dashboardGlanceItems']);
        // change wp admin bar rendering
        add_action('wp_before_admin_bar_render', [$this, 'adminBar']);
        // change tinymce
        add_filter('tiny_mce_plugins', function ($plugins = []) {
            if (is_array($plugins)) {
                return array_diff($plugins, ['wpemoji']);
            } else {
                return [];
            }
        });
        // remove unwanted tinymce text formats
        add_filter('tiny_mce_before_init', function ($settings = []) {
            if (!is_array($settings)) {
                $settings = [];
            }

            $settings['block_formats'] = 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3';

            return $settings;
        });
    }

    public function dashboardGlanceItems() {
        // add all post types to glance
        $post_types = get_post_types(['_builtin' => false], 'objects');
        foreach ($post_types as $post_type) {
            if ($post_type->show_in_menu == false) {
                continue;
            }

            $num_posts = wp_count_posts($post_type->name);
            $num       = number_format_i18n($num_posts->publish);
            $text      = _n($post_type->labels->singular_name, $post_type->labels->name, $num_posts->publish);
            if (current_user_can('edit_posts')) {
                $output = sprintf('<a href="edit.php?post_type=%s">%s %s</a>', $post_type->name, $num, $text);
            }

            printf('<li class="page-count %s-count">%s</td>', $post_type->name, $output);
        }
    }

    public function adminInit() {
        // remove unwanted metaboxes
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
        remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
        remove_meta_box('dashboard_primary', 'dashboard', 'normal');
        remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
        // remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); // At A Glance widget
        // add admin css if it is available
        $file = get_template_directory_uri() . (Helpers::getVersion() ? sprintf('/inc/css/global.admin.%s.min.css', Helpers::getVersion()) : '/inc/css/global.min.css');
        if (file_exists($file)) {
            add_editor_style($file);
        }
    }

    public function adminMenu() {
        global $submenu;
        if (is_array($submenu)) {
            unset($submenu['themes.php'][6]);
        }

        if (THEME_DISABLE_COMMENTS) {
            remove_menu_page('edit-comments.php');
        }
    }

    public function adminBar() {
        global $wp_admin_bar;
        if ($wp_admin_bar) {
            $wp_admin_bar->remove_menu('wp-logo');
            $wp_admin_bar->remove_menu('customize');
            if (THEME_DISABLE_COMMENTS) {
                $wp_admin_bar->remove_menu('comments');
            }
        }
    }

}
