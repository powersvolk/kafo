<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Article;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Product;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template;

class ProductSingle extends Template {

    protected $view = '@view-single-product-v2';

    private $product;
    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();

                $emoModule     = new Module('emo', get_the_id());
                $this->product = new Product();

                $this->context = [
                    'productPoster' => [
                        'cururl' => "https://{$_SERVER['HTTP_HOST']}",
						'title' => $this->product->getTitle(),
						'sku_title' => __('Tootekood:','kafo'),
						'sku' => get_post_meta(get_the_id(), '_sku', true ),
                        'description' => $this->product->getDescription(),
						'producrs_attributes' => get_field('product_attribute'),
						'price_info' => __('Enimostetud mudel! Toode on laos','kafo'),
						'compare_button' => do_shortcode('[yith_compare_button]'),
                        'sliderData' => [
                            'modifier' => 'slider--vertical',
                            'thumbnailNavigation' => true,
                            'slides' => $this->product->getSlides(),
                        ],
                        'rating' => [
                            'score' => $this->product->getReviewAverage(),
                            'count' => $this->product->getReviewCount(),
                            'link' => '#single-product-2',
                        ],
                        'formData' => $this->product->getFormData(),
                        'variations' => $this->product->getVariationAttr(),
                        'price' => $this->product->getDisplayPrice(),
                        'postpayPrice' => $this->product->getDisplayLease(),
                        'qtyTooltip' => $this->product->getDisplayQtyTooltip(),
                        'addToCart' => $this->product->getCartButton(),
                        'transportOptions' => $this->getShippingInfo(),
                        'postPayModal' => $this->getPostpayModal(get_the_id()),
                    ],
                    'accordion' => $this->getAccordion(),
                    'relatedList' => $this->getRelatedProducts(),
                    'accessories' => $this->getAccessories(),
                    'emoModule' => $emoModule->getContext(),
					'product_infos' => get_field('product_content_rows'),
					'techs' => get_field('tehnilised_andmed'),
					'hoolds' => $this->getHoolds(),
					'pdfs' => get_field('product_files'),
					'main_url' => "https://{$_SERVER['HTTP_HOST']}",
					'omadused'  => __('Omadused','kafo'),
					'tehnilised_andmed' => __('Tehnilised andmed','kafo'),
					'lisatarvikud' => __('Lisatarvikud','kafo'),
					'hooldustooted' => __('Hooldustooted','kafo'),
					'kasutusjuhendid' => __('Kasutusjuhendid','kafo'),
					'arvustused' => __('Arvustused','kafo'),
					'template_jura' => get_field('use_jura_template')[0],
                ];

                if (get_field('use_new_product_template')) {
                    $addOns = [
                        'singleHero' => $this->getSingleHero(),
                        'sections' => $this->product->getSections(),
                        'blogSection' => $this->product->getBlogSection(),
                        'midHero' => $this->getSingleHeroMid(),
                        'isDarkHeader' => get_field('use_dark_theme', $this->product->id),
                    ];
                    $this->context = array_merge($this->context, $addOns);
                } else {
                    $this->view = '@view-single-product';
                }

                $this->includeScripts();
            }
        }
    }




    private function getPostpayModal($postId = false) {
        return [
            'id' => 'modal-postpay',
            'modifier' => 'modaal--collapsed modaal--large js-lhv-calc',
            'collapsed' => true,
            'title' => '',
            'filterData' => [
                'header' => [
                    'title' => __('Järelmaksu kalkulaator', 'kafo'),
                    'content' => [
                        'leaseTypes' => $this->product->getLeaseTypes(),
                        'dataList' => [
                            ['label' => __('Toote hind:', 'kafo'), 'data' => '<span class="js-lhv-calc-price"></span>'],
                            ['label' => __('Sissemakse:', 'kafo'), 'data' => '<span class="js-lhv-calc-pay"></span>'],
                            ['label' => __('Osamaksed:', 'kafo'), 'data' => '<span class="js-lhv-calc-pay-per"></span>'],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'field' => 'range-slider',
                        'label' => __('Sissemakse', 'kafo'),
                        'rangeslider' => [
                            'attributes' => ' data-price="' . $this->product->getPrice() . '"',
                            'inputName' => 'percentage',
                            'minValue' => '0',
                            'maxValue' => '90',
                            'inputValue' => '10',
                            'step' => '10',
                            'postfix' => '%',
                        ],
                    ],
                    [
                        'field' => 'range-slider',
                        'label' => __('Periood', 'kafo'),
                        'rangeslider' => [
                            'inputName' => 'period',
                            'minValue' => '6',
                            'maxValue' => '48',
                            'inputValue' => '48',
                            'step' => '3',
                        ],
                    ],
                ],
                'footer' => [
                    'text' => '',
                    'result' => '<span class="js-lhv-calc-per-month"> / ' . __('kuus', 'kafo') . '</span>',
                    'button' => [
                        'text' => __('Lisa ostukorvi', 'kafo'),
                        'modifiers' => 'button--white button--icon button--block-xs js-add-to-cart',
                        'icon' => 'arrow-right',
                        'customAttributes' => 'data-id="'. $postId .'"',
                    ],
                ],
            ],
        ];
    }

    private function includeScripts() {
        switch ($this->product->getProductType()) {
            case 'simple':
                wp_register_script(
                    'wc-add-to-cart',
                    plugins_url('assets/js/frontend/add-to-cart.min.js', WC_PLUGIN_FILE),
                    ['jquery'],
                    WC_VERSION,
                    true
                );
                wp_enqueue_script('wc-add-to-cart');
            break;

            case 'variable':
                wp_register_script(
                    'wc-add-to-cart-variation',
                    plugins_url('assets/js/frontend/add-to-cart-variation.min.js', WC_PLUGIN_FILE),
                    ['jquery', 'wp-util'],
                    WC_VERSION,
                    true
                );
                wp_enqueue_script('wc-add-to-cart-variation');
            break;
        }
    }

    private function getShippingInfo() {
        $shipping = [];
        if (have_rows('shipping_list', 'options')) {
            while (have_rows('shipping_list', 'options')) {
                the_row();
                $shipping[] = [
                    'title' => get_sub_field('title'),
                    'content' => get_sub_field('description'),
                ];
            }
        }

        return $shipping;
    }

    private function getAccordion() {
        //tab_hide_content
        $accordion = [
            'tab1' => [
                'title' => get_field('tab_title_content'),
                'type' => 'full',
                'properties' => [],
                'isHidden' => get_field('tab_hide_content'),
            ],
            'tab2' => [
                'title' => get_field('tab_title_testimonial'),
                'content' => [
                    'title' => get_field('posts_quote'),
                    'description' => get_field('posts_description'),
                    'button' => [
                        'text' => get_field('posts_label'),
                        'link' => get_field('posts_link'),
                    ],
                    'cards' => [],
                ],
                'testimonials' => [],
                'isHidden' => get_field('tab_hide_testimonial'),
            ],
            'tab3' => [
                'title' => sprintf(get_field('tab_title_review'), $this->product->getReviewCount()),
                'reviews' => Review::getProductReviews(get_the_ID()),
                'canReview' => get_option('woocommerce_review_rating_verification_required') === 'no',
                'reviewForm' => $this->generateCommentForm(),
                'isHidden' => get_field('tab_hide_review'),
                'showMore' => __('Näita rohkem', 'theme'),
            ],
            'tab4' => [
                'tables' => get_field('tab_specs'),
                'title' => get_field('tab_specs_title'),
                'isHidden' => get_field('tab_hide_specs'),
            ],
        ];

        if (get_field('product_content_type') == 'full') {
            $accordion['tab1']['properties']['propertiesTitle']       = get_field('product_info_title');
            $accordion['tab1']['properties']['propertiesContent']     = get_the_content();
            $accordion['tab1']['properties']['propertiesLogo']        = ['srcset' => Helpers::getImage(get_field('product_logo'), '162x72')];
            $accordion['tab1']['properties']['propertiesDescription'] = get_field('product_descr_short');

            if (get_field('product_content_image')) {
                $accordion['tab1']['properties']['propertiesImage'] = ['srcset' => sprintf('%s 1x', Helpers::getImage(get_field('product_content_image'), '1170x800'))];
            }

            if (have_rows('product_files')) {
                $fileList = [];
                while (have_rows('product_files')) {
                    the_row();
                    $fileList[] = [
                        'element' => 'a',
                        'text' => get_sub_field('label'),
                        'link' => get_sub_field('file'),
                        'icon' => 'arrow-right',
                        'iconAlign' => 'right',
                        'modifier' => 'button--naked',
                    ];
                }

                $accordion['tab1']['properties']['propertiesLinks'] = $fileList;
            }

            if (have_rows('product_features')) {
                $featuresList = [];
                while (have_rows('product_features')) {
                    the_row();
                    $featuresList[] = [
                        'icon' => get_sub_field('icon'),
                        'text' => get_sub_field('label'),
                    ];
                }

                $accordion['tab1']['properties']['propertiesList'] = $featuresList;
            }
        } else if (get_field('product_content_type') == 'simple') {
            $accordion['tab1']['type'] = 'split';
            $rows                      = [];
            $index = 0;
            if (have_rows('product_content_rows')) {
                while (have_rows('product_content_rows')) {
                    the_row();
                    $rows[$index] = [
                        'image' => [
                            'srcset' => sprintf('%s 1x, %s x2', Helpers::getImage(get_sub_field('image'), '470x428'), Helpers::getImage(get_sub_field('image'), '940x856')),
                            'alt' => get_the_title(get_sub_field('image')),
                        ],
                        'content' => get_sub_field('content'),
                    ];

                    $title = get_sub_field('title');
                    if( $title )
                        $rows[$index]['title'] = $title;

                    $index++;
                }
            }

            $accordion['tab1']['content'] = $rows;
        }

        if (get_field('posts_list')) {
            $cards = [];
            foreach (get_field('posts_list') as $post) {
                $article = new Article($post);
                $cards[] = [
                    'link'        => $article->getPermalink(),
                    'modifier'    => 'card--testimonial',
                    'element'     => 'a',
                    'title'       => $article->getTitle(),
                    'description' => false,
                    'background'  => $article->getImageSrcSet('360x586', true),
                ];
            }

            $accordion['tab2']['content']['cards'] = $cards;
        }
		
		// echo '<pre>';
		// print_r($accordion);
		// echo '</pre>';
		
        return $accordion;
    }

    private function generateCommentForm() {
        // causes fatal error otherwise
        remove_filter( 'comment_form_field_comment', [ 'acf_form_comment', 'comment_form_field_comment' ], 999 );

        $commenter = wp_get_current_commenter();

        $comment_form = [
            'fields'        => [
                'author' => '<div class="form-group__row">' . Template::compileComponent( '@textfield', [
                        'id'    => 'author',
                        'name'  => 'author',
                        'label' => __( 'Nimi', 'kafo' ),
                        'value' => esc_attr( $commenter['comment_author'] ),
                        'validate' => true
                    ] ) . '</div>',
                'email'  => '<div class="form-group__row">' . Template::compileComponent( '@textfield', [
                        'id'    => 'email',
                        'name'  => 'email',
                        'label' => __( 'Email', 'kafo' ),
                        'value' => esc_attr( $commenter['comment_author_email'] ),
                        'validate' => true
                    ] ) . '</div>',
            ],
            'title_reply'   => '',
            'label_submit'  => __( 'Lisa', 'kafo' ),
            'class_submit'  => 'button button--block',
            'logged_in_as'  => '',
            'comment_field' => '',
            'submit_button' => Template::compileComponent( '@button', [
                'modifier' => 'button--block',
                'id'       => '%2$s',
                'name'     => '%1$s',
                'class'    => '%3$s',
                'value'    => '%4$s',
                'type'     => 'submit',
                'text'     => __( 'Lisa kirjeldus', 'kafo' ),
            ] ),
            'submit_field'  => '<div class="form-group__row form-group__row--last">%1$s %2$s</div>'
        ];

        if ( $account_page_url = wc_get_page_permalink( 'myaccount' ) ) {
            $comment_form['must_log_in'] = '<div class="form-group__row form-group__row--clean">' . sprintf( __( 'Lisamiseks pead olema&nbsp;<a href="#login">sisse logitud</a>.', 'kafo' ), esc_url( $account_page_url ) ) . '</div>';
        }

        if ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) {
            $comment_form['comment_field'] = '<div class="comment-form-rating js-rating-form"><select name="rating" id="rating" aria-required="true" required style="display:none">
                    <option value="">' . esc_html__( 'Rate&hellip;', 'woocommerce' ) . '</option>
                    <option value="5">' . esc_html__( 'Perfektne', 'woocommerce' ) . '</option>
                    <option value="4">' . esc_html__( 'Hea', 'woocommerce' ) . '</option>
                    <option value="3">' . esc_html__( 'Keskmine', 'woocommerce' ) . '</option>
                    <option value="2">' . esc_html__( 'Enam vähem', 'woocommerce' ) . '</option>
                    <option value="1">' . esc_html__( 'Halb', 'woocommerce' ) . '</option>
                </select><span class="rating__result"><span class="js-rating-selected">5</span>/<span class="js-rating-total">5</span></span></div>';
        }

        $comment_form['comment_field'] .= '<div class="form-group__row form-group__row--clean">' . Template::compileComponent( '@textarea', [
                'id'    => 'comment',
                'name'  => 'comment',
                'label' => __( 'Lisa kirjeldus', 'kafo' ),
                'validate' => true,
            ] ) . '</div>';

        ob_start();
        comment_form( $comment_form );
        $form = ob_get_clean();

        return $form;
    }

    private function getRelatedProducts() {
        $related = [];

        if (have_rows('products_related')) {
            while (have_rows('products_related')) {
                the_row();
                $productList = [
                    'title' => get_sub_field('title'),
                    'button' => [
                        'text' => get_sub_field('more_label'),
                        'link' => get_sub_field('more_link'),
                    ],
                    'products' => [],
                ];
                $products    = get_sub_field('products_list');
                if ($products) {
                    foreach ($products as $product) {
                        $productObj                = new Product($product);
                        $productList['products'][] = $productObj->getContext(['id','title', 'description', 'image', 'link', 'price']);
                    }
                }

                $related[] = $productList;
            }
        }

        return $related;
    }

	private function getHoolds() {
        $hoolds = [];

		$products = get_field('product_hooldustooted');
        if ($products) {
			foreach ($products as $product) {
				$productObj  = new Product($product);
				$hoolds[] = $productObj->getContext(['id','title', 'description', 'image', 'link', 'price']);
			}
		}

        return $hoolds;
    }



    private function getAccessories() {
        // Connected accessories
        // $accessories = [];
        $accessories = false;
		
		

        return $accessories;
    }

    public function getContextFields() {
        // echo '<pre>';
		// print_r($this->context);
		// echo '</pre>';
		
		return $this->context;
    }

    /* NEW DETAIL PAGE */

    public function getCurrency() {
        return $this->product->getCurrency();
    }

    private function getSingleHero() {
        $videoPlaceholder = $this->product->getProductVideoPlaceholder();
        return [
            'image' => sprintf('%s 380w, %s 760w, %s 1440w', Helpers::getImage(Helpers::getImageId($this->ID), '760x360'), Helpers::getImage(Helpers::getImageId($this->ID), '1520x720'), Helpers::getImage(Helpers::getImageId($this->ID), '2880x1440')),
            'addToCart' => $this->product->getCartButton(),
            'title' => $this->product->getTitle(),
            'price' => $this->product->getDisplayPrice()['text'],
            'postpayPrice' => $this->product->getDisplayLease()['price'],
            'postpayPriceTooltip' => $this->product->getDisplayLease(),
            'postpayTime' => $this->product->getDisplayLease()['time'],
            'currency' => $this->getCurrency(),
            'strings' => [
                'priceLabel' => __('Hind ostes', 'kafo'),
                'postpayLabel' => __('Hind järelmaksuga', 'kafo'),
            ],
            'features' => $this->product->getFeaturedInfo(),
            'score' => [
                'review' => [
                    'title' => __('Loe arvustusi', 'kafo'),
                    'link' => '#single-product-1',
                    'count' => $this->product->getReviewCount()
                ],
                'rating' => $this->product->getReviewAverage()
            ],
            'video' => [
                'image' => [
                    'srcset' => ($videoPlaceholder ? sprintf('%s 380w', Helpers::getImage($this->product->getProductVideoPlaceholder(), '192x108')) : false),
                ],
                'video' => $this->product->getProductVideo(),
            ],
            'thumbnailNavigation' => false,
            'sliderMod' => 'js-dots-slider',
            'sliderDotsModifier' => 'slider--dots',
            'slides' => $this->product->getMobileGallery(),
            'reelImagePlaceholder' => $this->getReelPlaceholder(),
            'reel' => $this->getReelImages('reel_images'),
        ];
    }

    private function getReelPlaceholder() {
        return sprintf('%s 380w', Helpers::getImage(get_field('reel_placeholder'), '192x108'));
    }

    private function getReelImages($tag) {
        $images = get_field($tag, $this->ID);

        $srcsets;
        if (is_array($images)) {
            $srcsets = [];
            foreach ($images as $image) {
                $srcsets[] = sprintf('%s 380w', Helpers::getImage($image['ID'], '160x160'));
            }
        }

        return $srcsets;
    }

    private function getSingleHeroMid() {
        $hero = $this->getSingleHero();

        $hero['modifier'] = 'single-hero__mid-hero';
        $hero['features'] = null;
        $hero['score'] = null;
        $hero['video'] = null;
        $hero['slides'] = null;

        $imageID = get_field('mid-hero')['image'];
        $hero['image'] = sprintf('%s 380w, %s 760w, %s 1440w', Helpers::getImage($imageID, '760x360'), Helpers::getImage($imageID, '1520x720'), Helpers::getImage($imageID, '2880x1440'));
        $hero['bulletpoints'] = get_field('mid-hero')['bulletpoints'];
        $hero['transportOptions'] = $this->getShippingInfo();

        return $hero;
    }

}
