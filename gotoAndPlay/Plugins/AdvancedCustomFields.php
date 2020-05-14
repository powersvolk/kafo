<?php
namespace gotoAndPlay\Plugins;

define('THEME_ACF_DIRECTORY', THEME_DIRECTORY . 'acf');

class AdvancedCustomFields {

    public function __construct() {
        // acf save to json
        add_filter('acf/settings/save_json', [$this, 'saveJson']);
        // acf load from json
        add_filter('acf/settings/load_json', [$this, 'loadJson']);
        // add google maps api key
        add_filter('acf/settings/google_api_key', function () {
            return GOOGLE_MAPS_API_KEY;
        });
        // custom taxonomy select field
        add_filter('acf/load_field/name=wc_attr_filter_min', [$this, 'loadWcProductAttributes']);
        add_filter('acf/load_field/name=wc_attr_filter_max', [$this, 'loadWcProductAttributes']);
        add_filter('acf/load_field/name=wc_attr_filter', [$this, 'loadWcProductAttributes']);
        // filters select
        add_filter('acf/load_field/name=term_filter_item', [$this, 'loadFilterList']);
        // add fields to all woocommerce attributes
        add_filter('acf/location/rule_types', [$this, 'addAcfWcAttrRule']);
        add_filter('acf/location/rule_values/woocommerce', [$this, 'addAcfcAttrValue']);
        add_filter('acf/location/rule_match/woocommerce', [$this, 'addAcfWcAttrMatch'], 10, 3);
        // use on init to add acf options pages
        add_action('init', [$this, 'init']);
    }

    private function createAcfCacheDirectory() {
        if (!is_dir(THEME_ACF_DIRECTORY)) {
            if (is_writable(__DIR__)) {
                mkdir(THEME_ACF_DIRECTORY, 0755);
            }
        }
    }

    public function saveJson($path = '') {
        $path = THEME_ACF_DIRECTORY;

        return $path;
    }

    public function loadJson($paths = []) {
        if (is_array($paths) && count($paths) > 0) {
            unset($paths[0]);
        } else {
            $paths = [];
        }

        $paths[] = THEME_ACF_DIRECTORY;

        return $paths;
    }

    public function init() {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_sub_page([
                'page_title' => __('Search Settings'),
                'menu_title' => __('Search'),
                'slug' => 'gtap-search',
                'parent_slug' => 'themes.php',
            ]);
            acf_add_options_sub_page([
                'page_title' => __('Header Settings'),
                'menu_title' => __('Header'),
                'slug' => 'gtap-header',
                'parent_slug' => 'themes.php',
            ]);
            acf_add_options_sub_page([
                'page_title' => __('Footer Settings'),
                'menu_title' => __('Footer'),
                'slug' => 'gtap-footer',
                'parent_slug' => 'themes.php',
            ]);
            acf_add_options_sub_page([
                'page_title' => __('404 Settings'),
                'menu_title' => __('404'),
                'slug' => 'gtap-404',
                'parent_slug' => 'themes.php',
            ]);
            acf_add_options_sub_page([
                'page_title' => __('Global images'),
                'menu_title' => __('Images'),
                'slug' => 'gtap-images',
                'parent_slug' => 'themes.php',
            ]);
            acf_add_options_sub_page([
                'page_title' => __('Filters'),
                'menu_title' => __('Filters'),
                'slug' => 'gtap-filters',
                'parent_slug' => 'themes.php',
            ]);
            acf_add_options_sub_page([
                'page_title' => __('Settings'),
                'menu_title' => __('Settings'),
                'slug' => 'gtap-settings',
                'parent_slug' => 'themes.php',
            ]);
            acf_add_options_sub_page([
                'page_title' => __('Modules'),
                'menu_title' => __('Modules'),
                'slug' => 'gtap-modules',
                'parent_slug' => 'themes.php',
            ]);
            acf_add_options_sub_page([
                'page_title' => __('Popups'),
                'menu_title' => __('Popups'),
                'slug' => 'gtap-popups',
                'parent_slug' => 'themes.php',
            ]);
        }

        // add acf icon field
        if (class_exists('acf_field')) {
            $plugins = [
                new ACFIconField(),
                new ACFMultiTaxonomyField(),
            ];
        }

        $this->createACFCacheDirectory();
    }

    public function loadWcProductAttributes($field) {
        $field['choices'] = [];
        $attrList         = wc_get_attribute_taxonomies();

        foreach ($attrList as $attr) {
            $field['choices'][wc_attribute_taxonomy_name($attr->attribute_name)] = $attr->attribute_label;
        }

        return $field;
    }

    public function addAcfWcAttrRule($choices) {
        $choices['Forms']['woocommerce'] = 'Woocommerce';

        return $choices;
    }

    public function addAcfcAttrValue($choices) {
        $choices['attribute'] = 'Attributes';

        return $choices;
    }

    public function addAcfWcAttrMatch($match, $rule, $options) {
        $attrList = wc_get_attribute_taxonomy_names();
        if (function_exists('get_current_screen')) {
            $tax = get_current_screen();
        } else {
            global $current_screen;
            $tax = $current_screen;
        }

        return ($tax ? in_array($tax->taxonomy, $attrList) : false);
    }

    public function loadFilterList($field) {
        $field['choices'] = [];

        if (have_rows('filter_list', 'options')) {
            $n = 0;
            while (have_rows('filter_list', 'options')) {
                the_row();
                $field['choices'][$n] = get_sub_field('product_filter_title');
                $n++;
            }
        }

        return $field;
    }

}
