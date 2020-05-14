<?php
namespace gotoAndPlay\Plugins;

define('ICL_DONT_PROMOTE', true);
define('ICL_DONT_LOAD_NAVIGATION_CSS', true);
define('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS', true);
define('ICL_DONT_LOAD_LANGUAGES_JS', true);

class WPML {

    public static $sitepress;

    public function __construct() {
        global $sitepress;
        self::$sitepress = $sitepress;
        if (self::isActive()) {
            // remove wpml version from head
            remove_action('wp_head', [self::$sitepress, 'meta_generator_tag']);
            // wp admin init
            add_action('admin_init', [$this, 'adminInit']);
            // remove intuitive cpo plugin conflict with wpml
            if (self::getCurrentLanguage() != self::getDefaultLanguage()) {
                wp_dequeue_script('hicpojs');
                wp_dequeue_style('hicpo');
            }
        }
    }

    public function adminInit() {
        // remove wpml widget
        remove_meta_box('icl_dashboard_widget', 'dashboard', 'side');
        //add editor WPML capabilites
        $role = get_role('editor');
        $role->add_cap('gform_full_access');
        $role->add_cap('wpml_manage_languages');
        $role->add_cap('wpml_manage_translation_options');
        $role->add_cap('wpml_manage_taxonomy_translation');
        $role->add_cap('wpml_manage_taxonomy_translation');
        $role->add_cap('wpml_manage_wp_menus_sync');
        $role->add_cap('wpml_manage_string_translation');
        $role->add_cap('wpml_manage_navigation');
        $role->add_cap('wpml_manage_theme_and_plugin_localization');
    }

    public static function isActive() {
        return self::$sitepress ? true : false;
    }

    public static function getLanguages() {
        return apply_filters('wpml_active_languages', []);
    }

    public static function getCurrentLanguage() {
        return apply_filters('wpml_current_language', null);
    }

    public static function getDefaultLanguage() {
        return apply_filters('wpml_default_language', null);
    }

}
