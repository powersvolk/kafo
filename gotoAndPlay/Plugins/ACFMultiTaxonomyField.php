<?php
namespace gotoAndPlay\Plugins;

use gotoAndPlay\Helpers as Helpers;
use acf_field;
use acf_taxonomy_field_walker;

class ACFMultiTaxonomyField extends acf_field {

    function __construct() {

        // vars
        $this->name     = 'multitaxonomy';
        $this->label    = __("Multiple Taxonomies", 'acf');
        $this->category = 'relational';
        $this->defaults = [
            'taxonomy' => 'category',
            'field_type' => 'checkbox',
            'multiple' => 0,
            'allow_null' => 0,
            'return_format' => 'id',
        ];

        // ajax
        add_action('wp_ajax_acf/fields/multitaxonomy/query', [$this, 'ajax_query']);
        add_action('wp_ajax_nopriv_acf/fields/multitaxonomy/query', [$this, 'ajax_query']);

        // do not delete!
        parent::__construct();

    }

    // @codingStandardsIgnoreLine
    function ajax_query() {

        // validate
        if (!acf_verify_ajax()) {
            die();
        }

        // get choices
        $response = $this->get_ajax_query($_POST);

        // return
        acf_send_ajax_results($response);

    }

    // @codingStandardsIgnoreLine
    function get_ajax_query($options = []) {
        // defaults
        $options = acf_parse_args($options, [
            'post_id' => 0,
            's' => '',
            'field_key' => '',
            'paged' => 0,
        ]);

        // load field
        $field = acf_get_field($options['field_key']);
        if (!$field) {
            return false;
        }

        // bail early if taxonomy does not exist
        if (!taxonomy_exists($field['taxonomy'])) {
            return false;
        }

        // vars
        $results         = [];
        $is_hierarchical = is_taxonomy_hierarchical($field['taxonomy']);
        $is_pagination   = ($options['paged'] > 0);
        $is_search       = false;
        $limit           = 20;
        $offset          = (20 * ($options['paged'] - 1));

        // args
        $args = [
            'taxonomy' => $field['taxonomy'],
            'hide_empty' => false,
        ];

        // pagination
        // - don't bother for hierarchial terms, we will need to load all terms anyway
        if ($is_pagination && !$is_hierarchical) {
            $args['number'] = $limit;
            $args['offset'] = $offset;
        }

        // search
        if ($options['s'] !== '') {
            // strip slashes (search may be integer)
            $s = wp_unslash(strval($options['s']));

            // update vars
            $args['search'] = $s;
            $is_search      = true;
        }

        // filters
        $args = apply_filters('acf/fields/taxonomy/query', $args, $field, $options['post_id']);
        $args = apply_filters('acf/fields/taxonomy/query/name=' . $field['name'], $args, $field, $options['post_id']);
        $args = apply_filters('acf/fields/taxonomy/query/key=' . $field['key'], $args, $field, $options['post_id']);

        // get terms
        $terms = acf_get_terms($args);

        // sort into hierachial order!
        if ($is_hierarchical) {
            // update vars
            $limit  = acf_maybe_get($args, 'number', $limit);
            $offset = acf_maybe_get($args, 'offset', $offset);

            // get parent
            $parent = acf_maybe_get($args, 'parent', 0);
            $parent = acf_maybe_get($args, 'child_of', $parent);

            // this will fail if a search has taken place because parents wont exist
            if (!$is_search) {
                // order terms
                $ordered_terms = _get_term_children($parent, $terms, $field['taxonomy']);
                // check for empty array (possible if parent did not exist within original data)
                if (!empty($ordered_terms)) {
                    $terms = $ordered_terms;
                }
            }

            // fake pagination
            if ($is_pagination) {
                $terms = array_slice($terms, $offset, $limit);
            }
        }

        /// append to r
        foreach ($terms as $term) {
            // add to json
            $results[] = [
                'id' => $term->term_id,
                'text' => $this->get_term_title($term, $field, $options['post_id']),
            ];
        }

        // vars
        $response = [
            'results' => $results,
            'limit' => $limit,
        ];

        // return
        return $response;
    }

    // @codingStandardsIgnoreLine
    function get_term_title($term, $field, $post_id = 0) {

        // get post_id
        if (!$post_id) {
            $post_id = acf_get_form_data('post_id');
        }

        // vars
        $title = '';

        // ancestors
        if (!is_array($field['taxonomy'])) {
            $field['taxonomy'] = [$field['taxonomy']];
        }

        $ancestors = [];
        foreach ($field['taxonomy'] as $taxonomy) {
            $ancestors = array_merge(get_ancestors($term->term_id, $taxonomy), $ancestors);
        }

        if (!empty($ancestors)) {
            $title .= str_repeat('- ', count($ancestors));
        }

        // title
        $title .= $term->name;

        // filters
        $title = apply_filters('acf/fields/taxonomy/result', $title, $term, $field, $post_id);
        $title = apply_filters('acf/fields/taxonomy/result/name=' . $field['_name'], $title, $term, $field, $post_id);
        $title = apply_filters('acf/fields/taxonomy/result/key=' . $field['key'], $title, $term, $field, $post_id);

        // return
        return $title;
    }

    // @codingStandardsIgnoreLine
    function get_terms($value, $taxonomy = 'category') {

        // load terms in 1 query to save multiple DB calls from following code
        if (!is_array($taxonomy)) {
            $taxonomy = [$taxonomy];
        }

        $terms = [];
        foreach ($taxonomy as $tax) {
            if (count($value) > 1) {
                $terms = array_merge($terms, acf_get_terms([
                    'taxonomy' => $tax,
                    'include' => $value,
                    'hide_empty' => false,
                ]));
            }
        }

        // update value to include $post
        foreach (array_keys($value) as $i) {
            $value[$i] = get_term($value[$i]);
        }

        // filter out null values
        $value = array_filter($value);

        // return
        return $value;
    }

    // @codingStandardsIgnoreLine
    function load_value($value, $post_id, $field) {
        // get valid terms
        if (!is_array($field['taxonomy'])) {
            $field['taxonomy'] = [$field['taxonomy']];
        }

        if ($value) {
            foreach ($field['taxonomy'] as $taxonomy) {
                $terms = acf_get_valid_terms($value, $taxonomy);
                $value = array_merge($value, $terms);
            }

            $value = array_unique($value);
        }

        // return
        return $value;

    }

    // @codingStandardsIgnoreLine
    function update_value($value, $post_id, $field) {

        // vars
        if (is_array($value)) {
            $value = array_filter($value);
        }

        // return
        return $value;
    }

    // @codingStandardsIgnoreLine
    function format_value($value, $post_id, $field) {
        // bail early if no value
        if (empty($value)) {
            return false;
        }

        // force value to array
        $value = acf_get_array($value);

        // load posts if needed
        if ($field['return_format'] == 'object') {
            // get posts
            $value = $this->get_terms($value, $field["taxonomy"]);
        }

        // convert back from array if neccessary
        if ($field['field_type'] == 'select' || $field['field_type'] == 'radio') {
            $value = array_shift($value);
        }

        // return
        return $value;

    }

    // @codingStandardsIgnoreLine
    function render_field($field) {
        // force value to array
        $field['value'] = acf_get_array($field['value']);

        // vars
        $div = [
            'class' => 'acf-taxonomy-field acf-soh',
            'data-type' => $field['field_type'],
            'data-taxonomy' => $field['taxonomy'],
        ];

        ?>
    <div <?php acf_esc_attr_e($div); ?>>
        <?php

        if ($field['field_type'] == 'select') {
            $field['multiple'] = 0;
            $this->render_field_select($field);
        } else if ($field['field_type'] == 'multi_select') {
            $field['multiple'] = 1;
            $this->render_field_select($field);
        } else if ($field['field_type'] == 'radio') {
            $this->render_field_checkbox($field);
        } else if ($field['field_type'] == 'checkbox') {
            $this->render_field_checkbox($field);
        }

        ?>
        </div><?php
    }

    // @codingStandardsIgnoreLine
    function render_field_select($field) {

        // Change Field into a select
        $field['type']    = 'select';
        $field['ui']      = 1;
        $field['ajax']    = 1;
        $field['choices'] = [];

        // value
        if (!empty($field['value'])) {
            // get terms
            $terms = $this->get_terms($field['value'], $field['taxonomy']);

            // set choices
            if (!empty($terms)) {
                foreach (array_keys($terms) as $i) {
                    // vars
                    $term = acf_extract_var($terms, $i);
                    // append to choices
                    $field['choices'][$term->term_id] = $this->get_term_title($term, $field);
                }
            }
        }

        // render select
        acf_render_field($field);
    }

    // @codingStandardsIgnoreLine
    function render_field_checkbox($field) {
        // hidden input
        acf_hidden_input([
            'type' => 'hidden',
            'name' => $field['name'],
        ]);

        // checkbox saves an array
        if ($field['field_type'] == 'checkbox') {
            $field['name'] .= '[]';
        }

        if (!is_array($field['taxonomy'])) {
            $field['taxonomy'] = [$field['taxonomy']];
        }

        $taxonomies = $field['taxonomy'];
        foreach ($taxonomies as $taxonomy) {
            // taxonomy
            $taxonomy_obj         = get_taxonomy($taxonomy);
            $newField             = $field;
            $newField['taxonomy'] = $taxonomy;
            // vars
            $args = [
                'taxonomy' => $taxonomy,
                'show_option_none' => __('No', 'acf') . ' ' . $taxonomy_obj->labels->name,
                'hide_empty' => false,
                'style' => 'none',
                'walker' => new acf_taxonomy_field_walker($newField),
            ];

            // filter for 3rd party customization
            $args = apply_filters('acf/fields/taxonomy/wp_list_categories', $args, $newField);
            $args = apply_filters('acf/fields/taxonomy/wp_list_categories/name=' . $newField['_name'], $args, $newField);
            $args = apply_filters('acf/fields/taxonomy/wp_list_categories/key=' . $newField['key'], $args, $newField);

            ?>
            <p><strong><?php echo $taxonomy_obj->label; ?></strong></p>
            <div class="categorychecklist-holder">
            <ul class="acf-checkbox-list acf-bl">
                <?php if ($newField['field_type'] == 'radio' && $newField['allow_null']) { ?>
                    <li>
                        <label class="selectit">
                            <input type="radio" name="<?php echo $newField['name']; ?>"
                                   value=""/> <?php _e("None", 'acf'); ?>
                        </label>
                    </li>
                <?php } ?>
                <?php wp_list_categories($args); ?>
            </ul>
            </div><?php
        }
    }

    // @codingStandardsIgnoreLine
    function render_field_settings($field) {

        // default_value
        acf_render_field_setting($field, [
            'label' => __('Taxonomy', 'acf'),
            'instructions' => __('Select the taxonomy to be displayed', 'acf'),
            'type' => 'select',
            'multiple' => true,
            'name' => 'taxonomy',
            'choices' => acf_get_taxonomies(),
        ]);

        // field_type
        acf_render_field_setting($field, [
            'label' => __('Appearance', 'acf'),
            'instructions' => __('Select the appearance of this field', 'acf'),
            'type' => 'select',
            'name' => 'field_type',
            'optgroup' => true,
            'choices' => [
                __("Multiple Values", 'acf') => [
                    'checkbox' => __('Checkbox', 'acf'),
                    'multi_select' => __('Multi Select', 'acf'),
                ],
                __("Single Value", 'acf') => [
                    'radio' => __('Radio Buttons', 'acf'),
                    'select' => _x('Select', 'noun', 'acf'),
                ],
            ],
        ]);

        // allow_null
        acf_render_field_setting($field, [
            'label' => __('Allow Null?', 'acf'),
            'instructions' => '',
            'name' => 'allow_null',
            'type' => 'true_false',
            'ui' => 1,
        ]);

        // return_format
        acf_render_field_setting($field, [
            'label' => __('Return Value', 'acf'),
            'instructions' => '',
            'type' => 'radio',
            'name' => 'return_format',
            'choices' => [
                'object' => __("Term Object", 'acf'),
                'id' => __("Term ID", 'acf'),
            ],
            'layout' => 'horizontal',
        ]);

    }

}
