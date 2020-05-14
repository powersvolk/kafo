<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Template as Template;
use gotoAndPlay\Helpers as Helpers;
use gotoAndPlay\Context as Context;

class Faq extends Template {

    protected $view = '@view-faq';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();

                $faq = [
                    'faqCategorySelectTitle' => get_field('faq_category_select_title'),
                    'faqCategorySectionTitle' => get_field('faq_category_section_title'),
                ];

                $faqCategories = get_terms(['taxonomy' => 'faq-category', 'hide_empty' => true,]);
                if ($faqCategories) {
                    foreach ($faqCategories as $faqCategory):
                        $faqCategoryObject = [
                            'categoryName' => $faqCategory->name,
                            'categorySlug' => $faqCategory->slug,
                        ];

                        $faqCategoryQuestions = get_posts([
                            'post_type' => 'faq',
                            'posts_per_page' => -1,
                            'tax_query' => [
                                [
                                    'taxonomy' => 'faq-category',
                                    'field' => 'term_id',
                                    'terms' => $faqCategory->term_id,
                                ]
                            ]
                        ]);

                        if ($faqCategoryQuestions) {
                            foreach ($faqCategoryQuestions as $faqCategoryQuestion) {
                                $faqCategoryObject['categoryQuestions'][] = [
                                    'id' => $faqCategoryQuestion->post_name,
                                    'title' => $faqCategoryQuestion->post_title,
                                    'content' => '<div class="text">' . apply_filters('the_content', $faqCategoryQuestion->post_content) . '</div>',
                                ];
                            };
                        }

                        $faq['faqCategories'][] = $faqCategoryObject;
                    endforeach;
                };

                $this->context = [
                    'intro' => [
                        'modifier' => 'intro--faq',
                        'bg' => Helpers::getHeroBackground(),
                        'title' => get_field('faq_intro_title'),
                        'text' => get_field('faq_intro_text'),
                    ],
                    'faq' => $faq,
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
