<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Context;
use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Product;
use gotoAndPlay\Template;

class ProductCat extends Template {

    protected $view = '@view-product-category';

    private $term;
    private $context;

    public function __construct() {
		
        if (is_product_category()) {
			global $yith_woocompare;
			
			$cate = get_queried_object();
			$cateID = $cate->term_id;
            global $wp_query;
            $this->term = $wp_query->get_queried_object();
	
            $this->context = [
                'categories' => $this->getCategories(),
				'menu_cat' => $this->getMenu(),
				'products' => $this->getProducts(),
                'pagination' => Context::getPagination(),
				'category_title' => __('JURA tooted', 'kafo'),
				'type_template' => get_field('term_template', 'product_cat_'.$cateID.''),
				'filter_name' => __('Filter', 'kafo'),
				'url' => get_bloginfo('url'),
				'lisasid' => __('Lisasid tootevõrdlusesse','kafo'),
				'hall' => __('JURA WE8, Hall/Hõbedane','kafo'),
				'tule' => __('Tühjenda','kafo'),
				'cat_com' => __('Kategooria - JURA-comparison','kafo'),
				'compare_name' => get_dynamic_sidebar('compare_name'),
				'wishlist_count' => $yith_woocompare->obj->list_products_html(),
            ];

            $emoModule                  = new Module('emo', $this->term);
            $this->context['emoModule'] = $emoModule->getContext();

            if (get_field('term_filter_show', $this->term)) {
                $this->context['filterSectionData'] = [
                    'title' => get_field('term_title', $this->term),
                    
					'text' => term_description($this->term->term_id, 'product_cat'),
                    'filterData' => [
                        'category' => $this->term->slug,
						'type_tem' => get_field('term_template', 'product_cat_'.$this->term->term_id.''),
                        'action' => 'filter-products',
                        'formPost' => get_term_link($this->term),
                        'modifier' => 'machine',
                        'header' => ['title' => __('Filter', 'kafo')],
                        'filters' => $this->getFilters(),
                        'footer' => [
                            'text' => __('Sinu valikutele on tulemusi ei rohkem ega vähem kui', 'kafo'),
                            'result' => $wp_query->post_count,
                            'button' => [
                                'text' => __('Filtreeri', 'kafo'),
                                'modifiers' => 'button--white button--block-xs',
                            ],
                        ],
                    ],
                ];
            } else {
                $catImage               = get_term_meta($this->term->term_id, 'thumbnail_id', true);
                $this->context['intro'] = [
                    /*
                    'image' => [
                        'srcset' => ($catImage ? Helpers::getImage($catImage, '513w') : ''),
                        'alt' => ($catImage ? get_the_title($catImage) : ''),
                    ],
                    */
                    'title' => get_field('term_title', $this->term),
                    'text' => term_description($this->term->term_id, 'product_cat'),
                ];
            }
        }
    }

    private function getFilters() {
        $filters = [];
        if (have_rows('filter_list', 'options')) {
            $n          = 0;
            $termFilter = get_field('term_filter_item', $this->term);
            if (!$termFilter) {
                $termFilter = 0;
            }

            while (have_rows('filter_list', 'options')) {
                the_row();
                if ($termFilter == $n) {
                    if (have_rows('product_filter')) {
                        while (have_rows('product_filter')) {
                            the_row();

                            $filterAttr         = get_terms(get_sub_field('wc_attr_filter'), ['hide_empty' => false]);
                            $filter             = [
                                'field' => 'checkbox',
                                'label' => get_sub_field('filter_label'),
                            ];
                            $filter['checkbox'] = [];
                            $m                  = 0;

                            foreach ($filterAttr as $attr) {
                                $check = [
                                    'id' => $attr->taxonomy . '-' . $m,
                                    'label' => $attr->name,
                                    'name' => 'tax_query[' . $attr->taxonomy . '][]',
                                    'value' => $attr->slug,
                                    'isChecked' => isset($_POST['tax_query'][$attr->taxonomy]) && in_array($attr->slug, $_POST['tax_query'][$attr->taxonomy]),
                                ];
                                if (get_sub_field('filter_range')) {
                                    $check['class'] = str_replace('_', '-', get_sub_field('wc_attr_filter') . '_control');
                                    $check['data']  = sprintf('data-min="%s" data-max="%s"', get_field('range_min', $attr), get_field('range_max', $attr));
                                }

                                $filter['checkbox'][] = $check;
                                $m++;
                            }

                            $filters[] = $filter;

                            if (get_sub_field('filter_range')) {
                                $range = [
                                    'field' => 'range-slider',
                                    'label' => get_sub_field('filter_range_label'),
                                ];

                                $range['rangeslider'] = [
                                    'minValue' => get_sub_field('filter_range_min'),
                                    'maxValue' => get_sub_field('filter_range_max'),
                                    'inputValue' => get_sub_field('filter_range_min'),
                                    'step' => 1,
                                    'controls' => str_replace('_', '-', get_sub_field('wc_attr_filter') . '_control'),
                                    'inputName' => sprintf('range[%s]', get_sub_field('wc_attr_filter')),
                                ];
                                if (isset($_POST['range'][get_sub_field('wc_attr_filter')])) {
                                    $range['rangeslider']['inputValue'] = intval($_POST['range'][get_sub_field('wc_attr_filter')]);
                                }

                                $filters[] = $range;
                            }
                        }
                    }
                }

                $n++;
            }
        }
		 
        return $filters;
    }

	private function getMenu() {
		$menu = [];
		$cats = get_field('menu_cat','option');
		if(isset($_SERVER['HTTPS'])){ 
			$protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http"; 
		} else{ 
			$protocol = 'http'; 
		}	
			
		foreach ($cats as $cat) {
			$menu[] = [
				'name' => $cat['cat_name'],
				'id' => $cat['cat_menu'],
				'link' => get_category_link($cat['cat_menu']),
				'current_url' => $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
			]; 
		} 
		// echo '<pre>';
		// print_r($categories);
		// echo '</pre>';
		return $menu;
		
	}


	private function getCategories() {
		$categories = [];
		
		if(isset($_SERVER['HTTPS'])){ 
			$protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http"; 
		} else{ 
			$protocol = 'http'; 
		} 
		
		$category = get_queried_object();
		$cat_id = $category->term_id;
		
		$ancestors = get_ancestors( $cat_id, 'product_cat' );
		if (!empty($ancestors)) {
			$args = array(
				'taxonomy'    => 'product_cat',
				'orderby'     => 'id', 
				'hide_empty'  => true, 
				'parent'      => $ancestors, 
			);
		} else {
			$args = array(
				'taxonomy'    => 'product_cat',
				'orderby'     => 'id', 
				'hide_empty'  => true, 
				'parent'      => $cat_id, 
			);
		}  
		$cats = get_categories( $args ); 
		
		foreach ($cats as $cat) {
			$categories[] = [
				'id' => $cat->term_id,
				'name' => $cat->name,
				'link' => get_category_link($cat->term_id),
				'current_url' => $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
			]; 
		} 
		// echo '<pre>';
		// print_r($categories);
		// echo '</pre>';
		return $categories;
		
	}

    private function getProducts() {
        $products = [];
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $product = new Product();
				
                $products[] = $product->getContext(['id','title', 'description', 'image', 'image_full', 'imagesrc', 'link', 'price', 'link_name', 'add_button_name', 'vordle', 'lisainfo', 'attributes', 'price_info', 'compare_button', 'cururl', 'type_cat', 'cat_id']);
				
			}
        }
		
				// echo '<pre>';
				// print_r($products);
				// echo '</pre>';
        return $products;
    }

    public function getContextFields() {
        // echo '<pre>';
				// print_r($this->context);
				// echo '</pre>';
		return $this->context;
    }

}


