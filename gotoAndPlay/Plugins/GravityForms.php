<?php
namespace gotoAndPlay\Plugins;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Training;
use gotoAndPlay\Utils\MailChimp\MailChimp;
use Timber;
use RGFormsModel;

class GravityForms {

    public function __construct() {
        // remove validation message
        add_filter('gform_validation_message', function ($message, $form) {
            return '';
        }, 10, 2);
        // move scripts to footer
        add_filter('gform_init_scripts_footer', function () {
            return true;
        });
        // make gravity work with ajax
        add_filter('gform_cdata_open', function ($content = '') {
            if ((defined('DOING_AJAX') && DOING_AJAX) || isset($_POST['gform_ajax'])) {
                return $content;
            }

            $content = 'document.addEventListener( "DOMContentLoaded", function() { ';

            return $content;
        });
        add_filter('gform_cdata_close', function ($content = '') {
            if ((defined('DOING_AJAX') && DOING_AJAX) || isset($_POST['gform_ajax'])) {
                return $content;
            }

            $content = ' }, false );';

            return $content;
        });
        // custom form fields markup
        add_filter('gform_field_content', [$this, 'formatGfields'], 10, 5);
        // mailchimp subscribe
        add_action('gform_after_submission', function ($entry, $form) {
            foreach ($form['fields'] as $key => $field) {
                if ($field['inputName'] == 'mailchimp_email' && isset($entry[$field['id']]) && get_field('mailchimp_api_key', 'option')) {
                    $mailchimpListId = get_field('mailchimp_list_id', 'option');
                    $mailchimp       = new MailChimp(get_field('mailchimp_api_key', 'option'));
                    $mailchimp->post("lists/$mailchimpListId/members", [
                        'email_address' => $entry[$field['id']],
                        'status' => 'subscribed',
                    ]);
                }
            }
        }, 10, 2);
        // add custom classes
        add_filter('gform_pre_render', function ($form) {
            $form['cssClass'] = (isset($form['cssClass']) ? $form['cssClass'] . ' form-group' : 'form-group');
            foreach ($form['fields'] as $key => $field) {
                if ($field['type'] == 'select') {
                    switch ($field['inputName']) {
                        case 'training_people':
                            $training          = new Training();
                            $field['choices']  = $training->getPeopleOptions();
                            $field['cssClass'] = 'js-training-people';
                        break;

                        case 'meetings_packages':
                            $packages = get_field('packages');
                            $list     = [];
                            if ($packages) {
                                foreach ($packages as $package) {
                                    $list[] = [
                                        'name' => $package['title'],
                                        'text' => $package['title'],
                                        'value' => $package['title'],
                                        'isSelected' => false,
                                    ];
                                }
                            }

                            $field['choices']  = $list;
                            $field['cssClass'] = 'js-meetings-package-select';
                        break;
                    }
                }
            }

            return $form;
        });
        add_filter('gform_field_css_class', function ($classes, $field, $form) {
            $classes .= ' form-group__row';

            return $classes;
        }, 10, 3);
        add_filter('gform_get_form_filter', function ($form_string, $form) {
            $form_string = str_replace('gform_body', 'form-group__row', $form_string);
            $form_string = str_replace('gform_fields', 'form-group', $form_string);
            $form_string = str_replace('gform_footer', 'form-group__row', $form_string);
            $form_string = str_replace('gform_validation_container', 'gform_validation_container h-hidden', $form_string);

            return $form_string;
        }, 10, 2);
        // submit button classes
        add_filter('gform_submit_button', function ($button, $form) {
            $button = str_replace('gform_button', 'gform_button button--block button--purple', $button);

            return $button;
        }, 10, 2);
        // remove unwatned fields
        add_filter('gform_add_field_buttons', function ($groups) {
            $new = [
                [
                    'name' => 'standard_fields',
                    'label' => 'Standard Fields',
                    'tooltip_class' => 'tooltip_bottomleft',
                    'fields' => [
                        [
                            'class' => 'button',
                            'data-type' => 'text',
                            'value' => 'Text',
                        ],
                        [
                            'class' => 'button',
                            'data-type' => 'select',
                            'value' => 'Drop Down',
                        ],
                        [
                            'class' => 'button',
                            'data-type' => 'email',
                            'value' => 'Email',
                        ],
                        [
                            'class' => 'button',
                            'data-type' => 'date',
                            'value' => 'Date',
                        ],
                        [
                            'class' => 'button',
                            'data-type' => 'textarea',
                            'value' => 'Paragraph',
                        ],
                        [
                            'class' => 'button',
                            'data-type' => 'checkbox',
                            'value' => 'Checkbox',
                        ],
                        [
                            'class' => 'button',
                            'data-type' => 'hidden',
                            'value' => 'Hidden',
                        ],
                        [
                            'class' => 'button',
                            'data-type' => 'html',
                            'value' => 'HTML',
                        ],
                    ],
                ],
            ];

            return $new;
        });
    }

    public function formatGfields($content, $field, $value, $leadId, $formId) {
        //don't modify admin form display
        if (is_admin()) {
            return $content;
        }

        $isEntryDetail = $field->is_entry_detail();
        $isFormEditor  = $field->is_form_editor();

        //default html markup
        $markup  = $content;
        $id      = (int) $field->id;
        $fieldId = 'input_' . $formId . "_$id";
        if (!is_array($value)) {
            $value = $this->escapeValue(esc_attr($value));
        }

        $tabindex             = $field->get_tabindex();
        $placeholderAttribute = $field->get_field_placeholder_attribute();
        $disabledText         = $field->is_form_editor() ? 'disabled="disabled"' : '';
        $requiredAttribute    = $field->isRequired ? 'aria-required="true"' : '';
        $invalidAttribute     = $field->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
        //default context

        $context = [
            'modifier' => '',
            'id' => $fieldId,
            'label' => $field->label,
            'name' => "input_{$id}",
            'value' => $value,
            'tooltip' => $field->get_description($field->description, 'gfield_description'),
            'message' => $this->getValidationMessage($field->failed_validation),
            'attributes' => "{$tabindex} {$placeholderAttribute} {$requiredAttribute} {$invalidAttribute} {$disabledText}",
            'gravityField' => true,
        ];
        if ($field->is_form_editor()) {
            $context['modifier'] .= ' is-disabled';
        }

        if ($field->failed_validation) {
            $context['modifier'] .= ' is-invalid';
        }

        if ($field->isRequired) {
            $context['validate'] = true;
        }

        // Generate context based on field type
        switch ($field->type) {
            case 'date':
            case 'number':
            case 'text':
            case 'email':
                $htmlInputType = 'text';
                if ($field->enablePasswordInput) {
                    $htmlInputType = 'password';
                }

                if ($field->type == 'number') {
                    $context['numeric'] = true;
                }

                $max_length            = is_numeric($field->maxLength) ? "maxlength='{$field->maxLength}'" : '';
                $logicEvent            = !$isFormEditor && !$isEntryDetail ? $field->get_conditional_logic_event('keyup') : '';
                $context['attributes'] = $context['attributes'] . ' ' . $logicEvent . ' ' . $max_length;
                $context['type']       = $htmlInputType;

                if ($field->type == 'date') {
                    $context['version'] = Helpers::getVersion();
                    $context['icon']    = 'calendar';
                    if (isset($field->dateFormat)) {
                        $context['customClass'] = $field->dateFormat ? $field->dateFormat : 'mdy';
                    }

                    $markup = $this->getField(['@datepicker'], $context);
                } else {
                    $markup = $this->getField(['@textfield'], $context);
                }
            break;

            case 'textarea':
                $logicEvent            = $field->get_conditional_logic_event('keyup');
                $context['attributes'] = ' ' . $logicEvent;
                $markup                = $this->getField(['@textarea'], $context);
            break;

            case 'select':
                $context['modifier']   = 'ginput_container ginput_container_select';
                $logicEvent            = $field->get_conditional_logic_event('change');
                $context['attributes'] = $context['attributes'] . ' ' . $logicEvent;
                $options               = [];
                foreach ($field->choices as $choice) {
                    $text      = (isset($choice['text']) ? esc_html($choice['text']) : '');
                    $options[] = [
                        'name' => $text,
                        'text' => $text,
                        'value' => $this->escapeValue(!empty($choice['value']) ? $choice['value'] : $text),
                        'selected' => $choice['isSelected'],
                    ];
                }

                $context['options'] = $options;
                $markup             = $this->getField(['@select'], $context);
            break;

            case 'checkbox':
            case 'radio':
                $fieldMarkup        = '';
                $choice_id          = 0;
                $isCheckbox         = ($field->type == 'checkbox' ? true : false);
                $context['isRadio'] = !$isCheckbox;
                foreach ($field->choices as $choice) {
                    // build new context for each radio element
                    $inputId               = $formId . '_' . $field->id . '_' . $choice_id++;
                    $context['id']         = 'choice_' . $inputId;
                    $context['label']      = $choice['text'];
                    $context['name']       = 'input_' . $field->id . ($isCheckbox ? '.' . $choice_id : '');
                    $context['value']      = $this->escapeValue(!empty($choice['value']) ? $choice['value'] : $choice['text']);
                    $context['attributes'] = $disabledText . ' ' . $field->get_conditional_logic_event('keyup');
                    $context['modifier']   = 'gchoice_' . $inputId;
                    if ($isCheckbox) {
                        if (!isset($_GET['gf_token']) && empty($_POST) && rgar($choice, 'isSelected')) {
                            $context['isChecked'] = true;
                        } else if (is_array($value) && RGFormsModel::choice_value_match($field, $choice, rgget($field->id . '.' . $choice_id, $value))) {
                            $context['isChecked'] = true;
                        } else if (!is_array($value) && RGFormsModel::choice_value_match($field, $choice, $value)) {
                            $context['isChecked'] = true;
                        } else {
                            $context['isChecked'] = '';
                        }
                    } else {
                        if (rgblank($value)) {
                            $context['isChecked'] = rgar($choice, 'isSelected') ? true : false;
                        } else {
                            $context['isChecked'] = RGFormsModel::choice_value_match($field, $choice, $value) ? true : false;
                        }
                    }

                    $fieldMarkup = $fieldMarkup . $this->getField(['@check'], $context);
                }

                if ($fieldMarkup) {
                    $markup = $fieldMarkup;
                }
            break;

            case 'hidden':
                if ($field['inputName']) {
                    $markup = str_replace("type='hidden'", sprintf("type='hidden' data-name='%s'", $field['inputName']), $markup);
                }
            break;
        }

        return $markup;
    }

    public function getValidationMessage($message) {
        if ($message) {
            $message = __('This field is required.', 'tavex');
        }

        return $message;
    }

    public function escapeValue($value) {
        return htmlspecialchars($value);
    }

    public function getField($field = [], $context) {
        return Timber::compile($field, $context);
    }

    public static function getFieldNameByKey($form, $key) {
        foreach ($form['fields'] as $field) {
            if ($field['inputName'] == $key) {
                return 'input_' . $field['id'];
            }
        }

        return $key;
    }

    public static function getFieldKeyById($form, $id) {
        foreach ($form['fields'] as $field) {
            if ($field['id'] == $id) {
                return $field['inputName'];
            }
        }

        return $id;
    }

}
