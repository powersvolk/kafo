<?php
namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers;
use RGFormsModel;

class Module {

    private $fieldBase;
    private $context = [
        'module_emo' => [],
    ];

    public function __construct($field, $idOrPostData = false) {
        $this->fieldBase = $idOrPostData;

        if (!$idOrPostData || !get_field($field . '_defaults', $this->fieldBase)) {
            $this->fieldBase = 'options';
        }

        if (have_rows($field . '_fields', $this->fieldBase)) {
            while (have_rows($field . '_fields', $this->fieldBase)) {
                the_row();
                $emo = $this->setContext($field);
                if ($emo) {
                    $this->context['module_' . $field][] = $emo;
                }
            }
        }
    }

    private function setContext($module = '') {
        $context = [];
        switch ($module) {
            case 'emo':
                if (get_sub_field('title') || get_sub_field('description') || get_sub_field('cta_label') || get_sub_field('image') || get_sub_field('video')) {
                    $context = [
                        'title'       => get_sub_field('title'),
                        'description' => get_sub_field('description'),
                    ];

                    if (get_sub_field('cta_label')) {
                        $context['button'] = [
                            'text' => get_sub_field('cta_label'),
                            'link' => get_sub_field('cta_link'),
                        ];
                    }

                    if (get_sub_field('video')) {
                        $context['video'] = Helpers::getYoutubeUrl(get_sub_field('video'));
                    }

                    $layout = get_sub_field('layout_settings');
                    if ($layout && is_array($layout)) {
                        $modifiers = [];
                        foreach ($layout as $type) {
                            if (($type == 'top-wave' && in_array('bottom-wave', $layout)) || ($type == 'bottom-wave' && in_array('top-wave', $layout))) {
                                if (!in_array('emo--top-and-bottom-wave', $modifiers)) {
                                    $modifiers[] = 'emo--top-and-bottom-wave';
                                }
                            } else {
                                $modifiers[] = 'emo--' . $type;
                            }
                        }

                        $context['modifier'] = implode(' ', $modifiers);
                    }

                    if (is_array($layout) && in_array('background', $layout)) {
                        $context['bg'] = Helpers::getEmoBackground();
                    } else {
                        $context['image'] = (get_sub_field('image') ? sprintf('%s 1x, %s 2x', Helpers::getImage(get_sub_field('image'), '627x640'), Helpers::getImage(get_sub_field('image'), '1254x1280')) : '');
                    }

                    if (get_sub_field('has_modal') && get_sub_field('modal_form_id')) {
                        $form             = RGFormsModel::get_form(get_sub_field('modal_form_id'));
                        $context['modal'] = [
                            'id'          => 'gform-id--' . $form->id,
                            'modifier'    => 'modaal--small',
                            'title'       => $form->title,
                            'gravityForm' => do_shortcode('[gravityform id="' . $form->id . '" title="false" description="false" ajax="true"]'),
                        ];
                    }
                }
            break;
        }

        return $context;
    }

    public function getContext() {
        if (empty($this->context)) {
            return [];
        }

        return $this->context;
    }

}
