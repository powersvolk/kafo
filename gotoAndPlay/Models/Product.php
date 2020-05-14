<?php
namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers;
use WP_Query;
use WP_Post;
use WC_Product;

class Product {

    /** @var WC_Product */
    private $product;
    private $ID;
    private $title;
    private $description;
    private $content;
    private $imageId;
    private $currency;
    private $inStock;
    private $reviewCount;
    private $reviewAverage;

    public function __construct($idOrPostData = false) {

        if ($idOrPostData) {
            if ($idOrPostData instanceof WP_Post) {
                global $post;
                $post = $idOrPostData;
                setup_postdata($post);
            } else {
                $args  = [
                    'post_status' => 'publish',
                    'post_type' => 'product',
                    'p' => $idOrPostData,
                    'posts_per_page' => 1,
                ];
                $query = new WP_Query($args);
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                    }
                }
            }
        }

        $this->setup();
        wp_reset_query();
    }

    private function setup() {
        $this->ID            = get_the_ID();
        $this->product       = wc_get_product($this->ID);
        $this->title         = get_the_title();
        $this->description   = wpautop(get_the_excerpt());
        $this->content       = Helpers::getFormattedContent(get_the_content());
        $this->imageId       = get_post_thumbnail_id();
        $this->currency      = get_woocommerce_currency_symbol();
        $this->inStock       = $this->product->is_in_stock();
        $this->reviewCount   = count( get_comments( ['post_id' => $this->ID] ) );
        $this->reviewAverage = $this->product->get_average_rating();
    }

    public function getId() {
        return $this->ID;
    }

    
	public function getContext($fields = []) {
        $context       = [];
        $context['id'] = $this->ID;
        foreach ($fields as $key) {
            $value = false;
            switch ($key) {
                case 'id':
                    $value = $this->ID;
                break;

                case 'title':
                    $value = $this->title;
                break;

                case 'description':
                    //terms != descr
                    $value = get_field('product_descr_list', $this->ID);
                    if (get_field('include_stock', $this->ID)) {
                        if ($this->inStock) {
                            $value .= ' / ' . __('laos olemas', 'kafo');
                        } else {
                            $value .= ' / ' . __('hetkel otsas', 'kafo');
                        }
                    }
                break;

                case 'image':
                    $value = sprintf('%s 380w, %s 760w', Helpers::getImage(Helpers::getImageId($this->ID), '380x380'), Helpers::getImage(Helpers::getImageId($this->ID), '760x760'));
                break;
				
				case 'image_full':
                    $value = Helpers::getImage(Helpers::getImageId($this->ID), 'full');
                break;
				
				case 'link_name':
                    $value = __('Vaata', 'kafo');
                break;
				
				case 'lisainfo':
                    $value = __('Lisainfo', 'kafo');
                break;
				
				case 'type_cat':
                    // $cate = get_queried_object();
					// $cateID = $cate->term_id;
					$cateID = get_the_category($this->ID);
					$value = get_field('term_template', 'product_cat_'.$cateID.'');
                break;
					
				
				case 'vordle':
                    $value = __('Võrdle', 'kafo');
                break;
				
				case 'compare_button':
                    $value = __('Võrdle', 'kafo');
                break;
				
				case 'cururl':
                    $value = get_site_url();
                break;
				
				case 'add_button_name':
                    $value = __('Lisa Korvi', 'kafo');
                break;
				
				case 'attributes':
                    $value = get_field('product_attribute',$this->ID);
                break;
				
				case 'price_info':
                   $value = __('Enimostetud mudel! Toode on laos ', 'kafo');
                break;
				
                case 'link':
                    $value = get_permalink($this->ID);
                break;

                case 'timestamp':
                    $value = get_the_time('U', $this->ID);
                break;

                case 'priceRaw':
                    $value = $this->product->get_regular_price();
                    if ($this->product->is_on_sale()) {
                        $value = $this->product->get_sale_price();
                    }
                break;

                case 'price':
                    $productPrice = $this->product->get_price() . $this->currency;
                    if ($this->product->is_on_sale()) {
                        if ($this->product->is_type('variable')) {
                            $variations = $this->product->get_available_variations();
                            if (isset($variations[0]))
                                $productPrice = $variations[0]['display_regular_price'];
                        } else {
                            $productPrice = $this->product->get_regular_price();
                        }

                        $productPrice .= $this->currency;
                        $value['sale'] = $this->product->get_price() . $this->currency;
                    }

                    $value['normal'] = $productPrice;
                break;
            }

            $context[$key] = $value;
        }

        return $context;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getContent() {
        return $this->content;
    }

    public function getReviewAverage() {
        return $this->reviewAverage;
    }

    public function getReviewCount() {
        return $this->reviewCount;
    }

    public function getProductType() {
        return $this->product->get_type();
    }

    public function getWCProduct() {
        return $this->product;
    }

    public function getFormData() {
        $formData = [
            'class' => '',
            'data' => '',
        ];
        switch ($this->product->get_type()) {
            case 'variable':
                $formData['class'] = 'variations_form';
                $formData['data']  = sprintf('data-product_id="%s" data-product_variations="%s"', absint($this->product->get_id()), htmlspecialchars(wp_json_encode($this->product->get_available_variations())));
            break;
        }

        return $formData;
    }

    public function getPrice() {
        return $this->product->get_price();
    }

    public function getDisplayPrice() {
        $price     = $this->product->get_price();
        $regularPrice = $this->product->get_regular_price();
        $isSale    = $this->product->is_on_sale();
        $priceHtml = [
            'text' => ($isSale ? floatval($price) : floatval($regularPrice)) . ' ' . $this->currency,
            'appendText' => sprintf('<del>%1$s %3$s</del> (-%2$s %3$s)', ($isSale ? floatval($regularPrice) : floatval($price)), floatval($regularPrice - $price), $this->currency),
            'isSale' => $isSale,
        ];

        if ($this->product->get_type() == 'variable') {
            $prices = $this->product->get_variation_prices(true);

            if (!empty($prices['price'])) {
                $min_price     = current($prices['price']);
                $max_price     = end($prices['price']);
                $min_reg_price = current($prices['regular_price']);
                $max_reg_price = end($prices['regular_price']);
                if ($isSale && floatval($min_price) !== floatval($max_price)) {
                    $priceHtml['text']       = floatval($min_price) . ' - ' . floatval($max_price) . ' ' . $this->currency;
                    $priceHtml['appendText'] = sprintf('<del>%1$s %3$s</del> (-%2$s %3$s)', floatval($min_reg_price), floatval($min_reg_price - $min_price), $this->currency);
                } else {
                    $priceHtml['text'] = ($isSale ? floatval($min_price) : floatval($min_reg_price)) . ' ' . $this->currency;
                }
            }
        }

        return $priceHtml;
    }

    public function getDisplayLease() {
        if (get_field('show_lease_info', $this->getId())) {
            return [
                'price' => floatval(Helpers::getFormattedPrice(get_field('lease_price', $this->getId()))),
                'time' => __(' / kuus', 'kafo'),
                'tooltip' => [
                    'id' => 'postpay',
                    'content' => get_field('lease_tooltip', $this->getId()),
                ],
            ];
        } else {
            return false;
        }
    }

    public function getLeaseTypes() {
        if(get_field('use_lease_types', $this->getID())) {
            $types = [
                // default lease - LHV
                [
                    'id' => 'lease-lhv',
                    'label' => __('LHV järelmaks', 'kafo'),
                    'name' => 'lease-type',
                    'value' => 'lhv',
                    'isChecked' => true
                ],
                [
                    'id' => 'lease-esto',
                    'label' => __('KAFO järelmaks', 'kafo'),
                    'name' => 'lease-type',
                    'value' => 'esto',
                    'data' => sprintf('data-fee="%s" data-interest="%s"', get_field('lease_fee', $this->getID()), get_field('lease_interest', $this->getID()))
                ]
            ];
            return $types;
        } else {
            return false;
        }
    }

    public function getDisplayQtyTooltip() {
        if (get_field('show_quantity_info', $this->getId())) {
            return [
                'tooltip' => [
                    'id' => 'qtyTooltip',
                    'content' => get_field('quantity_tooltip', $this->getId()),
                ],
            ];
        } else {
            return false;
        }
    }

    public function getSlides() {
        $slides    = [];
        $slideList = $this->product->get_gallery_image_ids();

        if ($slideList) {
            foreach ($slideList as $slideId) {
                $slides[] = [
                    'image' => sprintf('%s 627w, %s 1254w', Helpers::getImage($slideId, '627x640'), Helpers::getImage($slideId, '1254x1280')),
                    'thumbnail' => sprintf('%s 68w, %s 136w', Helpers::getImage($slideId, '68w'), Helpers::getImage($slideId, '136w')),
                    'alt' => get_the_title($slideId),
                    'fullImage' => Helpers::getImage($slideId, '2560x1440'),
                ];
            }
        }

        if (get_field('product_video', $this->ID) && get_field('product_video_thumbnail', $this->ID)) {
            $thumbId  = get_field('product_video_thumbnail', $this->ID);
            $slides[] = [
                'class' => 'is-playing',
                'video' => get_field('product_video', $this->ID),
                'thumbnail' => sprintf('%s 68w, %s 136w', Helpers::getImage($thumbId, '68w'), Helpers::getImage($thumbId, '136w')),
                'alt' => get_the_title($thumbId)
            ];
        }

        return $slides;
    }

	

    public function getDisplayAttr($displayOne = true) {
        $attrs        = $this->product->get_attributes();
        $displayAttrs = [];

        foreach ($attrs as $key => $attr) {
            if (!$attr['visible']) {
                continue;
            }

            $attrBase = $attr->get_taxonomy_object();
            $attrObj  = [
                'prependText' => $attrBase->attribute_label,
                'variationInputs' => [],
            ];

            $attrTerms = $attr->get_terms();
            foreach ($attrTerms as $term) {
                $termObj = [
                    'id' => $attrBase->attribute_name . '-' . $term->slug,
                    'name' => $attrBase->attribute_name,
                    'label' => $term->slug,
                    'value' => $term->term_id,
                ];
                if (get_field('attr_color', $term)) {
                    unset($termObj['label']);
                    $termObj['modifier'] = 'form-radio--color';
                    $termObj['color']    = get_field('attr_color', $term);
                }

                $attrObj['variationInputs'][] = $termObj;
            }

            $attrObj['variationInputs'][0]['isChecked'] = true;
            $displayAttrs[]                             = $attrObj;
        }

        if ($displayAttrs && $displayOne) {
            $displayAttrs = $displayAttrs[0];
        }

        return $displayAttrs;
    }

    public function getVariationAttr($displayOne = true) {
        if ($this->product->get_type() != 'variable') {
            return $this->getDisplayAttr();
        }

        $variationData = $this->product->get_available_variations();

        $variations  = $this->product->get_variation_attributes();
        $displayVars = [];

        foreach ($variations as $name => $options) {
            $attrObj  = [
                'prependText' => wc_attribute_label($name),
                'variationInputs' => [],
            ];
            $selected = isset($_REQUEST['attribute_' . sanitize_title($name)]) ? wc_clean(stripslashes(urldecode($_REQUEST['attribute_' . sanitize_title($name)]))) : $this->product->get_variation_default_attribute($name);

            foreach ($options as $index => $term) {
                $variationId = 0;
                foreach($variationData as $data) {
                    if(isset($data['attributes']['attribute_' . $name]) && $data['attributes']['attribute_' . $name] == $term) {
                        $variationId = $data['variation_id'];
                    }
                }

                $termObj  = [
                    'modifier' => 'js-product-attr',
                    'id' => $name . '-' . $term,
                    'name' => 'attribute_' . $name,
                    'label' => esc_html(apply_filters('woocommerce_variation_option_name', $name)),
                    'value' => $term,
                    'data' => sprintf('data-variation-id="%s"', $variationId),
                    'isChecked' => ( $selected ) ? $selected : ( $index == 0 ) ? 1 : 0,
                ];
                $termItem = get_term_by('slug', $term, $name);
                if (get_field('attr_color', $termItem)) {
                    unset($termObj['label']);
                    $termObj['modifier'] .= ' form-radio--color';
                    $termObj['color']    = get_field('attr_color', $termItem);
                }

                $attrObj['variationInputs'][] = $termObj;
            }

            $displayVars[] = $attrObj;
        }

        if ($displayVars && $displayOne) {
            $displayVars = $displayVars[0];
        }

        return $displayVars;
    }

    public function getCartButton() {
        $cartMin     = apply_filters('woocommerce_quantity_input_min', $this->product->get_min_purchase_quantity(), $this->product);
        $cartMax     = apply_filters('woocommerce_quantity_input_max', $this->product->get_max_purchase_quantity(), $this->product);
        $cartCurrent = isset($_POST['quantity']) ? wc_stock_amount($_POST['quantity']) : $this->product->get_min_purchase_quantity();
        if ($cartMax < $cartMin) {
            $cartMax = $cartMin;
        }

        $options = [
            [
                'name' => $cartMin,
                'value' => $cartMin,
            ],
        ];
        for ($c = $cartMin; $c < $cartMax; $c++) {
            $options[] = [
                'name' => $c,
                'value' => $c,
            ];
        }

        return [
            //'amountOptions' => $options,
            'amountVal' => $cartCurrent,
            'cartValue' => $this->ID,
            'type' => $this->product->get_type(),
        ];
    }

    public static function getProductsForSlider($ids) {
        $products = [];
        if ($ids) {
            $args  = [
                'post_status' => 'publish',
                'post_type' => 'product',
                'orderby' => 'post__in',
                'post__in' => $ids,
                'posts_per_page' => -1,
            ];
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $product    = new Product();
                    $products[] = $product->getContext(['title', 'description', 'image', 'link', 'price']);
                }
            }

            wp_reset_query();
        }

        return $products;
    }

    /* NEW SINGLE VIEW FUNCTIONS */

    public function getFeaturedInfo() {
        return get_field('featured_info');
    }

    public function getProductVideo() {
        return get_field('product_video');
    }

    public function getProductVideoPlaceholder() {
        return get_field('product_video-thumbnail');
    }

    public function getSections() {
        $sections = get_field('sections');

        if ($sections) {
            foreach ($sections as $key => $section) {
                $sections[$key]['image'] = sprintf('%s 740w, %s 1440w',
                    Helpers::getImage($section['image'], '740x740'),
                    Helpers::getImage($section['image'], '1440x1440')
                );

                if ((strpos($sections[$key]['modifier'], 'image-section--reverse') !== false) || (strpos($sections[$key]['modifier'], 'image-section--full') !== false)) {
                    $sections[$key]['imageModifier'] = 'image-section__image--wide';
                    $sections[$key]['image'] = sprintf('%s 1480w, %s 2240w',
                        Helpers::getImage($section['image'], '1480x740'),
                        Helpers::getImage($section['image'], '2240x740')
                );
                }

                if ($section['slides']) {
                    $sections[$key]['sliderMod'] = 'js-dots-slider';

                    foreach ($section['slides'] as $_key => $_slide) {
                        $sections[$key]['slides'][$_key]['image'] = sprintf('%s 380w, %s 760w, %s 1440w', Helpers::getImage($_slide['image'], '760x360'), Helpers::getImage($_slide['image'], '1520x720'), Helpers::getImage($_slide['image'], '2880x1440'));
                    }
                }
            }
        }

        return $sections;
    }

    public function getMobileGallery() {
        $imageIds = $this->product->get_gallery_image_ids();

        $slides = [];
        $slides[] = [
            'image' => sprintf('%s 320w, %s 480w', Helpers::getImage(Helpers::getImageId($this->ID), '480x960'), Helpers::getImage(Helpers::getImageId($this->ID), '960x1920')),
            'alt' => 'image'
        ];

        if (is_array($imageIds)) {
            foreach ($imageIds as $id) {
                $slides[] = [
                    'image' => sprintf('%s 320w, %s 480w', Helpers::getImage($id, '480x960'), Helpers::getImage($id, '960x1920')),
                    'alt' => 'image'
                ];
            }
        }

        return $slides;
    }

    public function getBlogSection() {
        $posts = get_field('posts_list', $this->ID);

        $slider['thumbnailNavigation'] = false;
        $slider['sliderMod'] = 'js-dots-slider';
        $slider['modifier'] = 'slider--dots';

        if ($posts) {
            foreach ($posts as $postId) {
                if (get_field('slider_image', $postId)) {
                    $slider['slides'][] = [
                        'image' => sprintf('%s 380w, %s 760w, %s 1440w', Helpers::getImage(get_field('slider_image', $postId), '760x360'), Helpers::getImage(get_field('slider_image', $postId), '1520x720'), Helpers::getImage(get_field('slider_image', $postId), '2880x1440')),
                        'alt' => 'Blog post image',
                        'title' => get_the_title($postId),
                        'text' => substr(wp_strip_all_tags(get_post($postId)->post_content), 0, 103) . '...',
                        'cta' => [
                            'text' => get_field('posts_label', $this->ID),
                            'link' => get_permalink($postId),
                        ]
                    ];
                } else {
                    continue;
                }
            }
        }

        if (!empty($slider['slides'])) {
            return $slider;
        }

        return false;
    }
}
