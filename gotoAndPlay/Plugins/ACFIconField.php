<?php
namespace gotoAndPlay\Plugins;

use gotoAndPlay\Helpers;
use acf_field;

class ACFIconField extends acf_field {

    private $choices = [];

    public function __construct() {
        $this->name     = 'icon_select';
        $this->label    = _x('Icon Select', 'noun', 'acf');
        $this->category = 'choice';
        $icons          = (array) Helpers::getManifest()->icons;
        if ($icons) {
            asort($icons);
            $this->choices = $icons;
        }

        $this->defaults = [
            'multiple'      => 0,
            'allow_null'    => 0,
            'choices'       => $this->choices,
            'default_value' => '',
            'ui'            => 0,
            'ajax'          => 0,
            'placeholder'   => '',
            'return_format' => 'value',
        ];
        add_filter('acf/load_field/type=icon_select', [$this, 'loadChoices']);
        parent::__construct();
    }

    public function loadChoices($field) {
        $field['choices'] = $this->choices;

        return $field;
    }

    // @codingStandardsIgnoreLine
    public function render_field_settings($field) {
        $choices = (['' => '-'] + $this->choices);
        acf_render_field_setting($field, [
            'label'        => __('Default Value', 'acf'),
            'instructions' => __('Enter each default value on a new line', 'acf'),
            'name'         => 'default_value',
            'choices'      => $choices,
            'type'         => 'select',
        ]);
        // allow_null
        acf_render_field_setting($field, [
            'label'        => __('Allow Null?', 'acf'),
            'instructions' => '',
            'name'         => 'allow_null',
            'type'         => 'true_false',
            'ui'           => 1,
        ]);
        // multiple
        acf_render_field_setting($field, [
            'label'        => __('Select multiple values?', 'acf'),
            'instructions' => '',
            'name'         => 'multiple',
            'type'         => 'true_false',
            'ui'           => 1,
        ]);
    }

    // @codingStandardsIgnoreLine
    public function render_field($field) {
        // convert
        $field['value']   = acf_get_array($field['value'], false);
        $field['choices'] = acf_get_array($field['choices']);
        if (empty($field['value'])) {
            $field['value'] = [''];
        }

        // allow null
        if ($field['allow_null'] && !$field['multiple']) {
            $field['choices'] = (['' => '-'] + $field['choices']);
        }

        // vars
        $atts = [
            'id'              => $field['id'],
            'class'           => $field['class'],
            'name'            => $field['name'],
            'data-multiple'   => $field['multiple'],
            'data-allow_null' => $field['allow_null'],
        ];
        // multiple
        if ($field['multiple']) {
            $atts['multiple'] = 'multiple';
            $atts['size']     = 5;
            $atts['name']     .= '[]';
        }

        // special atts
        foreach (['readonly', 'disabled'] as $k) {
            if (!empty($field[$k])) {
                $atts[$k] = $k;
            }
        }

        echo '<select ' . acf_esc_attr($atts) . '>';
        $this->walk($field['choices'], $field['value']);
        echo '</select>';
    }

    public function walk($choices, $values) {
        // bail early if no choices
        if (empty($choices)) {
            return;
        }

        // loop
        foreach ($choices as $k => $v) {
            // optgroup
            if (is_array($v)) {
                // optgroup
                echo '<optgroup label="' . esc_attr($k) . '">';
                // walk
                $this->walk($v, $values);
                // close optgroup
                echo '</optgroup>';
                // break
                continue;
            }

            // vars
            $search = html_entity_decode($k);
            $pos    = array_search($search, $values);
            $atts   = ['value' => $k];
            // validate selected
            if ($pos !== false) {
                $atts['selected'] = 'selected';
                $atts['data-i']   = $pos;
            }

            // option
            echo '<option ' . acf_esc_attr($atts) . '>' . $v . '</option>';
        }
    }

}
