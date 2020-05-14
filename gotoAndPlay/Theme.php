<?php
namespace gotoAndPlay;

use gotoAndPlay\Models\Menu;
use gotoAndPlay\Plugins\GravityForms as GravityForms;
use gotoAndPlay\Plugins\AdvancedCustomFields as AdvancedCustomFields;
use gotoAndPlay\Plugins\Woocommerce;
use gotoAndPlay\Plugins\WPML as WPML;
use gotoAndPlay\Utils\FractalLoader as FractalLoader;
use gotoAndPlay\Utils\Shortcodes;
use TimberSite;
use Twig_Loader_Chain;
use Twig_SimpleFilter;
use WP_Query;

class Theme extends TimberSite {

    public static $admin;
    public static $gravity;
    public static $acf;
    public static $wpml;
    public static $ajax;
    public static $shortodes;
    public static $woocommerce;
    public static $cData = [];

    public function __construct() {
        self::$wpml        = new WPML();
        self::$acf         = new AdvancedCustomFields();
        self::$gravity     = new GravityForms();
        self::$admin       = new Admin();
        self::$ajax        = new Ajax();
        self::$shortodes   = new Shortcodes();
        self::$woocommerce = new Woocommerce();
        // remove emojicon support
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        // remove api
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        // remove wordpress version from head
        remove_action('wp_head', 'wp_generator');
        // remove links to the extra feeds such as category feeds
        remove_action('wp_head', 'feed_links_extra', 3);
        // remove links to the general feeds (Post and Comment Feed)
        remove_action('wp_head', 'feed_links', 2);
        // remove link to the Really Simple Discovery service endpoint, EditURI link
        remove_action('wp_head', 'rsd_link');
        // remove link to the Windows Live Writer manifest file.
        remove_action('wp_head', 'wlwmanifest_link');
        // remove wordpress shortlink
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        // remove wc cart message
        add_filter('wc_add_to_cart_message_html', function() {
            return;
        });
        // remove version from scripts
        add_filter('style_loader_src', [$this, 'removeVersion'], 10, 2);
        add_filter('script_loader_src', [$this, 'removeVersion'], 10, 2);
        // remove woocommerce stylesheets
        add_filter('woocommerce_enqueue_styles', '__return_false');
        // allow shortcodes in widgets
        add_filter('widget_text', 'do_shortcode');
        // filters for twig
        add_filter('get_twig', [$this, 'extendTwig']);
        add_filter('timber_context', [Context::class, 'globalContext']);
        add_filter('timber/loader/loader', function($loader) {
            $fractalLoader = new FractalLoader(get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'templates');
            // add our loader to the end of the chain (i.e. Timber does its lookup, then runs ours)
            $chainLoader = new Twig_Loader_Chain([$loader, $fractalLoader]);

            return $chainLoader;
        });

        // Remove link to registration page
        add_filter( 'register', [$this, 'gtap_remove_registration_link']);
        // Redirect away from default registration page
        add_action( 'init', [$this, 'gtap_redirect_registration_page']);

        // allow svg upload
        add_filter('upload_mimes', function($mimes = []) {
            $mimes['svg'] = 'image/svg+xml';

            return $mimes;
        });
        // read more
        add_filter('excerpt_more', function() {
            return '...';
        });
        add_filter('excerpt_length', function() {
            return 10;
        }, 999);
        // list class
        add_filter('wp_insert_post_data', function($data) {
            $data['post_content'] = str_replace('<ul>', '<ul class="list">', $data['post_content']);

            return $data;
        });
        add_filter('script_loader_tag', [$this, 'add_async_attribute'], 10, 2);
        // body classes
        //add_filter('body_class', [$this, 'addMenuOpenClasses']);
        // after setup theme
        add_action('after_setup_theme', function() {
            // add woocommerce support
            add_theme_support('woocommerce');
            // adds html5 support for elements
            add_theme_support('html5', ['comment-list', 'comment-form', 'search-form', 'gallery', 'caption']);
            // add featured image to post types
            add_theme_support('post-thumbnails', ['post', 'page', 'brand', 'package']);
            // image sizes

            // Emo
            add_image_size('3840x2220', 3840, 2220, true);
            add_image_size('1920x1110', 1920, 1110, true);
            add_image_size('2880x2220', 2880, 2220, true);
            add_image_size('1440x1110', 1440, 1110, true);
            add_image_size('1536x1660', 1536, 1660, true);
            add_image_size('768x830', 768, 830, true);
            add_image_size('1040x1460', 1040, 1460, true);
            add_image_size('520x730', 520, 730, true);

            //hero images
            add_image_size('2560x1440', 2560, 1440);
            add_image_size('3840x1320', 3840, 1320);
            add_image_size('1920x660', 1920, 660);
            add_image_size('2880x1320', 2880, 1320);
            add_image_size('1440x660', 1440, 660);
            add_image_size('1536x1120', 1536, 1120);
            add_image_size('768x560', 768, 560);
            add_image_size('640x1040', 640, 1040);
            add_image_size('320x520', 320, 520);

            // Hero image (small)

            add_image_size('2560x316', 2560, 316);
            add_image_size('1920x316', 1920, 316);
            add_image_size('3840x632', 3840, 632);
            add_image_size('1440x316', 1440, 316);
            add_image_size('2880x632', 2880, 632);
            add_image_size('768x295', 768, 295);
            add_image_size('1536x590', 1536, 590);
            add_image_size('320x248', 320, 248);
            add_image_size('640x496', 640, 496);

            // New detail page
            add_image_size('320x320', 320, 320, true);
            add_image_size('480x960', 480, 960, ['left', 'center']);
            add_image_size('960x1920', 960, 1920, ['left', 'center']);
            add_image_size('740x740', 740, 740, true);
            add_image_size('1440x1440', 1440, 1440, true);
            add_image_size('1480x740', 1480, 740, true);
            add_image_size('2240x740', 2240, 740, true);

            // product images
            add_image_size('531w', 531, 531);
            add_image_size('1170x800', 1170, 800);
            add_image_size('940x856', 940, 856);
            add_image_size('470x428', 470, 428);
            add_image_size('640x360', 640, 360);
            add_image_size('1280x720', 1280, 720);

            // Recipe
            add_image_size('763x352', 763, 352, true);
            add_image_size('1526x704', 1526, 704, true);

            // module images
            add_image_size('1254x1280', 1254, 1280);
            add_image_size('627x640', 627, 640);

            // front page sizes
            add_image_size('517x777', 517, 777, true);
            add_image_size('1142x1554', 1142, 1554, true);
            add_image_size('363x777', 363, 777, true);
            add_image_size('726x1554', 726, 1554, true);
            add_image_size('317x220', 317, 220, true);
            add_image_size('634x440', 634, 440, true);
            add_image_size('519x282', 519, 282, true);
            add_image_size('1038x564', 1038, 564, true);
            add_image_size('198x282', 198, 282, true);
            add_image_size('396x564', 396, 564, true);
            add_image_size('317x150', 317, 150, true);
            add_image_size('634x300', 634, 300, true);
            add_image_size('1269x268', 1269, 268, true);
            add_image_size('2538x536', 2538, 536, true);
            add_image_size('792x268', 792, 268, true);
            add_image_size('1584x536', 1584, 536, true);
            add_image_size('198x178', 198, 178, true);
            add_image_size('396x356', 396, 356, true);
            add_image_size('100x80', 100, 80, true);
            add_image_size('200x160', 200, 160, true);

            // wide article (two-col layout)
            add_image_size('570w', 570, 352, true);
            add_image_size('570w2x', 1140, 704, true);

            // featured article (blog layout)
            add_image_size('773w', 773, 352, true);
            add_image_size('773w2x', 1546, 704, true);

            // training
            add_image_size('496x229', 496, 229, true);
            add_image_size('992x458', 992, 458, true);
            add_image_size('775x361', 775, 361, true);
            add_image_size('1550x722', 1550, 722, true);

            // contact
            add_image_size('288x163', 288, 163, true);
            add_image_size('490x180', 490, 180, true);
            add_image_size('245x90', 245, 90, true);
            add_image_size('276x276', 276, 276, true);

            // product single (dynamic height)
            add_image_size('770w', 770, 800);
            add_image_size('385w', 385, 400);

            // product thumbnails
            add_image_size('85w', 85, 85, true);
            add_image_size('68w', 68, 68, true);
            add_image_size('136w', 136, 136, true);
            add_image_size('170w', 170, 170, true);

            // brand & products & articles list images (square)
            add_image_size('760x760', 760, 760, true);
            add_image_size('380x380', 380, 380, true);
            add_image_size('380x380-top', 380, 380, array('center', 'top'));
            add_image_size('360x586', 360, 586, true);
            add_image_size('720x1172', 720, 1172, true);
            add_image_size('162x72', 162, 72, true);

            // profile
            add_image_size('351x411', 351, 411, true);

            //order
            add_image_size('762x1078', 762, 1078, true);
            add_image_size('1524x2156', 1524, 2156, true);

            // add nav menus
            register_nav_menus([
                'header' => __('Header menu'),
                'blog'   => __('Blog submenu'),
                'footer' => __('Footer menu'),
                'mobile' => __('Mobile menu'),
            ]);

            // adds title tag automatically, no wp_title needed
            add_theme_support('title-tag');
            // move scripts to footer
            remove_action('wp_head', 'wp_print_scripts');
            remove_action('wp_head', 'wp_print_head_scripts', 9);
            add_action('wp_footer', 'wp_print_scripts', 5);
            add_action('wp_footer', 'wp_print_head_scripts', 5);
            // plugin scripts
            add_action('wp_head', [$this, 'includeHeadScripts']);
            // remove comment support from post types
            $this->removeComments();
            $this->createPostTypes();
        });
        add_action('init', function() {
            register_taxonomy_for_object_type('post_tag', 'training');
            register_taxonomy_for_object_type('post_tag', 'brand');
            register_taxonomy_for_object_type('faq-category', 'faq');
        });
        add_action('wp_register_scripts', [$this, 'registerScripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('template_redirect', [$this, 'prettySearch']);
        add_action('pre_get_posts', [$this, 'preGetPosts']);
        // redirect login failure
        add_action('wp_login_failed', [$this, 'redirectLogin']);
        // hijack ajax add-to-cart return
        add_action('template_redirect', [$this, 'ajaxAddProduct']);
        // rewrite rules
        add_filter('generate_rewrite_rules', function($wp_rewrite) {
            $customRules       = [
                'en/search/(.+)' => 'index.php?s=' . $wp_rewrite->preg_index(1),
                'otsing/(.+)'    => 'index.php?s=' . $wp_rewrite->preg_index(1),
            ];
            $wp_rewrite->rules = $customRules + $wp_rewrite->rules;
        });
        // Remove comment form notes
        add_filter('comment_form_defaults', function( $arguments ) {
            $arguments['comment_notes_before'] = '';
            return $arguments;
        });

        // Remove the comment query clauses filter
        add_action('init', function() {
            global $sitepress;
            remove_filter('comments_clauses', [$sitepress, 'comments_clauses']);
        });

        //enable phpmail smtp
        add_action( 'phpmailer_init', 'configure_smtp' );
        // remove Billing text from checkout messages
        add_filter( 'woocommerce_add_error', [$this, 'customize_wc_errors'] );
    }

    public function customize_wc_errors( $error ) {
        if ( strpos( $error, 'Billing ' ) !== false ) {
            $error = str_replace("Billing ", "", $error);
        }
        return $error;
    }

    public function gtap_remove_registration_link( $registration_url ) {
        return __( 'Manual registration is disabled', 'gtap' );
    }

    public function gtap_redirect_registration_page() {
        if ( isset( $_GET['action'] ) && $_GET['action'] == 'register' ) {
                ob_start();
                wp_redirect( wp_login_url() );
                ob_clean();
        }
    }

    public function getBragBar() {
        // var_dump(get_post());
    }

    public function add_async_attribute($tag, $handle) {
        if ( 'recaptcha' !== $handle ) {
            return $tag;
        }
        return str_replace( ' src', ' async defer src', $tag );
    }

    public function preGetPosts(WP_Query $query) {
        if ($query->is_main_query()) {
            if (!is_admin() && $query->is_search()) {
                $query->set('posts_per_page', 1);
            }

            if (isset($_GET['all']) && $_GET['all'] && ($query->is_posts_page || $query->is_archive())) {
                $query->set('posts_per_page', -1);
            }

            // product category page
            if ($query->is_tax('product_cat') && $query->is_archive()) {
                $query->set('posts_per_page', 30);
            }

            // product category filter query
            if ($query->is_tax('product_cat') && $query->is_archive() && isset($_POST['action']) && $_POST['action'] == 'filter-products') {
                $taxArgs = [];
                // set base category
                if (isset($_POST['product_cat'])) {
                    $taxArgs[] = [
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => $_POST['product_cat'],
                    ];
                }

                // include attribute terms
                if (!empty($_POST['tax_query'])) {
                    foreach ($_POST['tax_query'] as $slug => $terms) {
                        $taxArgs[] = [
                            'taxonomy' => $slug,
                            'field'    => 'slug',
                            'terms'    => $terms,
                        ];
                    }
                }

                // include range terms
                if (!empty($_POST['range'])) {
                    foreach ($_POST['range'] as $term => $range) {
                        $baseTerm   = get_term_by('slug', $_POST['product_cat'], 'product_cat');
                        $termFilter = get_field('term_filter_item', $baseTerm);
                        $minTerms   = [];
                        $maxTerms   = [];
                        $n          = 0;
                        if (!$termFilter) {
                            $termFilter = 0;
                        }

                        // get category filter
                        while (have_rows('filter_list', 'options')) {
                            the_row();
                            if ($termFilter == $n) {
                                // get range filter term
                                while (have_rows('product_filter')) {
                                    the_row();
                                    // get min/max terms for range
                                    if (get_sub_field('wc_attr_filter') == $term) {
                                        // set range term by term, as taxonomy cant query with 'BETWEEN'
                                        // min term args
                                        $minArgs  = [];
                                        $minSlug  = get_sub_field('wc_attr_filter_min');
                                        $minTerms = get_terms($minSlug, ['hide_empty' => false]);
                                        foreach ($minTerms as $term) {
                                            if (is_numeric($term->slug)) {
                                                if (intval($term->slug) <= $range) {
                                                    $minArgs[] = $term->term_id;
                                                }
                                            }
                                        }

                                        if ($minArgs) {
                                            $taxArgs[] = [
                                                'taxonomy' => $minSlug,
                                                'field'    => 'term_id',
                                                'terms'    => $minArgs,
                                            ];
                                        }

                                        // max term args
                                        $maxArgs  = [];
                                        $maxSlug  = get_sub_field('wc_attr_filter_max');
                                        $maxTerms = get_terms($maxSlug, ['hide_empty' => false]);
                                        foreach ($maxTerms as $term) {
                                            if (is_numeric($term->slug)) {
                                                if (intval($term->slug) >= $range) {
                                                    $maxArgs[] = $term->term_id;
                                                }
                                            }
                                        }

                                        if ($maxArgs) {
                                            $taxArgs[] = [
                                                'taxonomy' => $maxSlug,
                                                'field'    => 'term_id',
                                                'terms'    => $maxArgs,
                                            ];
                                        }
                                    }
                                }
                            }

                            $n++;
                        }
                    }
                }

                if (count($taxArgs) > 1) {
                    $taxArgs['relation'] = 'AND';
                }

                if ($taxArgs) {
                    $query->set('tax_query', $taxArgs);
                }
            }
        }
    }

    public function prettySearch() {
        if (is_search() && !empty($_GET['s'])) {
            if (WPML::getCurrentLanguage() == 'et') {
                wp_redirect(home_url('/otsing/') . urlencode(get_query_var('s')) . '/');
            } else {
                wp_redirect(home_url('/search/') . urlencode(get_query_var('s')) . '/');
            }
            exit();
        }
    }

    public function redirectLogin($username) {
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            wp_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }

    public function ajaxAddProduct() {
        if (((is_singular('product') || is_single('product')) && isset($_POST['add-to-cart'])) || (isset($_GET['removed_item']) && !is_cart())) {
            $result = [
                'success'   => 1,
                'minicart'  => Template::compileComponent('@cart', Menu::getMinicart()),
                'cartCount' => WC()->cart->get_cart_contents_count(),
            ];
            wp_send_json($result);
        }
    }

    public function registerScripts() {
        wp_register_script('recaptcha', 'https://www.google.com/recaptcha/api.js', false, false, true);
    }

    public function enqueueScripts() {
        // css
        wp_enqueue_style('global', get_template_directory_uri() . sprintf('/inc/css/global.%s.min.css', Helpers::getVersion()), []);
        // javascript
        wp_deregister_script('wp-embed');
        if (file_exists(__DIR__ . '/inc/js/jquery.min.js')) {
            wp_deregister_script('jquery');
            wp_enqueue_script('jquery', get_template_directory_uri() . '/inc/js/jquery.min.js', 'jquery', '1.11.2', true);
        }

        $development_domains = ['localhost', '127.0.0.1', '::1', 'dev.kafo.ee'];
        if (isset(Helpers::getManifest()->sites)) {
            $sites = Helpers::getManifest()->sites;
            array_push($development_domains, $sites->local, $sites->staging);
        }

        wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . $this->getRecaptchaKey(), false, false, true);

        if (in_array($_SERVER['SERVER_NAME'], $development_domains) || in_array($_SERVER['SERVER_ADDR'], $development_domains)) {
            wp_enqueue_script('global', get_template_directory_uri() . sprintf('/inc/js/global.%s.js', Helpers::getVersion()), ['jquery'], false, true);
        } else {
            wp_enqueue_script('global', get_template_directory_uri() . sprintf('/inc/js/global.%s.min.js', Helpers::getVersion()), ['jquery'], false, true);
        }

        wp_localize_script('global', 'gotoAndPlay', $this->getCdata());

    }

    public function includeHeadScripts() {
        ?>
        <?php //Google Tag Manager Plugin ?>
        <?php if ( function_exists( 'gtm4wp_the_gtm_tag' ) ) { gtm4wp_the_gtm_tag(); } ?>
        <!-- cookiebot general -->
        <script id="Cookiebot" src="https://consent.cookiebot.com/uc.js" data-cbid="d6c618e2-3a8c-4831-b350-5e296c42e473" type="text/javascript" async></script>
        <!-- end of cookiebot general -->
        <!-- ga anonymize -->
        <script>
          if (typeof ga === 'function') {
            ga('set', 'anonymizeIp', true);
          }
        </script>
        <!-- end of ga anonymize -->

        <?php
    }

    public function removeVersion($src = '') {
        if (strpos($src, '?rev=')) {
            $src = remove_query_arg('rev', $src);
        }

        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }

        return $src;
    }

    public function getRecaptchaKey() {
        return '6Lcg1IcUAAAAAHrjtxF7sdwvIGikWXAWHCavO2mz';
    }

    public function getCdata() {
        $cdata = [
            'version'      => Helpers::getVersion(),
            'nonce'        => wp_create_nonce(THEME_AJAX_NONCE),
            'templatePath' => get_template_directory_uri(),
            'svgPath'      => get_template_directory_uri() . '/inc/svg/global.' . Helpers::getVersion() . '.svg',
            'loggedIn'     => is_user_logged_in(),
            'strings'      => [],
            'rootPath'     => home_url('/'),
            'recaptchaKey' => $this->getRecaptchaKey(),
        ];
        if (WPML::isActive()) {
            $cdata['ajaxPath'] = admin_url('admin-ajax.php') . '?lang=' . WPML::getCurrentLanguage();
            $cdata['lang']     = WPML::getCurrentLanguage();
        } else {
            $cdata['ajaxPath'] = admin_url('admin-ajax.php');
        }

        foreach (self::$cData as $key => $value) {
            $cdata[$key] = $value;
        }

        return apply_filters('gotoandplay_cdata', $cdata);
    }

    public function extendTwig($twig) {
        $twig->addFilter(new Twig_SimpleFilter('path', function($text) {
            $text = get_template_directory_uri() . $text;

            return $text;
        }));

        return $twig;
    }

    private function removeComments() {
        if (THEME_DISABLE_COMMENTS) {
            foreach (get_post_types() as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                }
            }
        }
    }

    public function addMenuOpenClasses($classes) {
        if (isset($_REQUEST['reset_key']) && $_REQUEST['reset_key']) {
            $classes[] = 'is-scroll-disabled';
            $classes[] = 'h-menu-open';
        }

        return $classes;
    }

    private function createPostType($name, $args = []) {
        $defaults = [
            'public'        => true,
            'show_ui'       => true,
            'hierarchical'  => false,
            'menu_position' => 20,
            'has_archive'   => false,
        ];
        register_post_type($name, array_replace_recursive($defaults, $args));
    }

    private function createTaxonomy($post_type, $name, $args) {
        register_taxonomy($name, $post_type, $args);
    }

    private function createPostTypes() {
        $taxonomies = [
            'faq' => [
                'faq-category' => [
                    'public'         => true,
                    'label'          => __('Categories', 'admin'),
                    'hierarchical'   => true,
                ],
            ],
        ];
        $post_types = [
            'megamenu' => [
                'public'            => false,
                'label'             => __('Megamenu', 'admin'),
                'menu_icon'         => 'dashicons-layout',
                'show_in_nav_menus' => true,
                'supports'          => ['title'],
            ],
            'training' => [
                'public'            => true,
                'label'             => __('Trainings', 'admin'),
                'menu_icon'         => 'dashicons-groups',
                'show_in_nav_menus' => true,
                'supports'          => ['title', 'editor', 'author'],
                'has_archive'       => false,
                'rewrite'           => [
                    'slug'         => _x('koolitus', 'URL slug', 'kafo'),
                    'with_front'   => true,
                    'hierarchical' => false,
                ],
            ],
            'brand'    => [
                'public'            => true,
                'label'             => __('Brands', 'admin'),
                'menu_icon'         => 'dashicons-image-filter',
                'show_in_nav_menus' => true,
                'supports'          => ['title', 'editor', 'author', 'thumbnail'],
                'has_archive'       => false,
                'rewrite'           => [
                    'slug'         => _x('brandid', 'URL slug', 'kafo'),
                    'with_front'   => true,
                    'hierarchical' => false,
                ],
            ],
            'package'  => [
                'public'            => false,
                'label'             => __('Packages', 'admin'),
                'menu_icon'         => 'dashicons-carrot',
                'show_in_nav_menus' => true,
                'supports'          => ['title', 'editor', 'thumbnail'],
            ],
            'faq' => [
                'public'            => false,
                'label'             => __('FAQ', 'admin'),
                'menu_icon'         => 'dashicons-lightbulb',
                'show_in_nav_menus' => true,
                'supports'          => ['title', 'editor'],
                'has_archive'       => false,
                'taxonomies'        => ['faq-category'],
            ],
        ];
        foreach ($post_types as $key => $args) {
            $this->createPostType($key, $args);
        }

        foreach ($taxonomies as $post_type => $taxes) {
            foreach ($taxes as $key => $args) {
                $this->createTaxonomy($post_type, $key, $args);
            }
        }
    }

    public static function addDataForFrontend($key, $value) {
        self::$cData[$key] = is_array($value) ? json_encode($value) : $value;
    }

    public function configure_smtp( PHPMailer $phpmailer ){
      $phpmailer->SMTPSecure = true;
    }

}
