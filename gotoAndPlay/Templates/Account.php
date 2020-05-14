<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Models\Product;
use gotoAndPlay\Models\User;
use gotoAndPlay\Template as Template;
use gotoAndPlay\Helpers as Helpers;
use WC_Countries;

class Account extends Template {

    protected $view = '@view-profile';

    private $context = [];

    private static $ID;

    public static function getId() {
        return self::$ID;
    }

    public function __construct() {
        if (!User::isLoggedIn()) {
            wp_redirect(home_url());
            exit;
        }

        if (have_posts()) {
            while (have_posts()) {
                the_post();
                if (get_field('product_offers_ids', 'option')) {
                    $tabs = [
                        'orders' => [
                            'id' => 'orders',
                            'linkText' => __('Minu tellimused', 'kafo'),
                        ],
                        'specials' => [
                            'id' => 'specials',
                            'linkText' => __('Insider Specials', 'kafo'),
                        ],
                        'account' => [
                            'id' => 'account',
                            'linkText' => __('Minu andmed', 'kafo'),
                            'isCurrent' => true,
                        ],
                        'signout' => [
                            'link' => wp_logout_url(home_url()),
                            'linkText' => __('Logi välja', 'kafo'),
                            'linkIcon' => 'ic_24_logout',
                        ],
                    ];
                } else {
                    $tabs = [
                        'orders' => [
                            'id' => 'orders',
                            'linkText' => __('Minu tellimused', 'kafo'),
                        ],
                        'account' => [
                            'id' => 'account',
                            'linkText' => __('Minu andmed', 'kafo'),
                            'isCurrent' => true,
                        ],
                        'signout' => [
                            'link' => wp_logout_url(home_url()),
                            'linkText' => __('Logi välja', 'kafo'),
                            'linkIcon' => 'arrow-right',
                        ],
                    ];
                }

                $this->context = [
                    'intro' => [
                        'bg' => Helpers::getHeroBackground(self::getId()),
                        'title' => __('Minu Konto', 'kafo'),
                        'text' => sprintf(__('<strong>Tere %s!</strong> Kas teadsid, et kohv peab olema must kui öö, kuum kui põrgu ja magus kui armastus?', 'kafo'), User::getFirstName()),
                    ],
                    'mydetails' => [
                        'account' => [
                            'title' => __('Sinu andmed', 'kafo'),
                            'specialDeals' => __('Saada mulle eripakkumisi', 'kafo'),
                            'notifyAboutBlog' => __('Teavita mind blogi uudistest', 'kafo'),
                            'specialSubscribe' => User::subscribedToOffers(),
                            'newsSubscribe' => User::subscribedToBlog(),
                            'content' => $this->getMyDetails(),
                        ],
                        'addresses' => [
                            'title' => __('Muuda aadressi', 'kafo'),
                            'addNew' => __('Lisa uus aadress', 'kafo'),
                            'content' => $this->getMyAddresses(),
                            'contentNew' => $this->getNewAddress(),
                        ],
                        'company' => [
                            'title' => __('Äriklient?', 'kafo'),
                            'addNew' => __('Lisa firma andmed', 'kafo'),
                            'content' => $this->getCompany(),
                            'canAdd' => User::getCompanyDetails() ? false : true,
                        ],
                    ],
                    'accountTabs' => $tabs,
                    'pendingOrders' => [
                        'title' => __('Laekumist ootavad tellimused', 'kafo'),
                        'orders' => User::getOrders('pending'),
                    ],
                    'completeOrders' => [
                        'title' => __('Viimased tellimused', 'kafo'),
                        'orders' => User::getOrders('complete'),
                    ],
                    'productOffers' => [
                        'title' => get_field('product_offers_title', 'option'),
                        'products' => Product::getProductsForSlider(get_field('product_offers_ids', 'option')),
                    ],
                    'related' => $this->getRelatedProducts(),
                ];
            }
        }
    }

    public function getCompany() {
        $company = User::getCompanyDetails();
        $fields  = [
            [
                'name' => 'billing_company',
                'label' => __('Firma nimi', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
                'value' => ($company ? $company['billing_company'] : ''),
            ],
            [
                'name' => 'billing_reg_code',
                'label' => __('Registrikood', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
                'value' => ($company ? $company['billing_reg_code'] : ''),
            ],
            /*[
                'name' => 'billing_vat_number',
                'label' => __('Käibemaksu number', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
                'value' => ($company ? $company['billing_vat_number'] : ''),
            ],*/
            [
                'name' => 'billing_email',
                'label' => __('Email', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
                'value' => ($company ? $company['billing_email'] : ''),
            ],
            [
                'name' => 'billing_phone',
                'label' => __('Telefon', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
                'value' => ($company ? $company['billing_phone'] : ''),
            ],
            [
                'name' => 'billing_address_1',
                'label' => __('Aadress', 'kafo'),
                'placeholder' => __('Tänav, Korter / Maja', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
                'value' => ($company ? $company['billing_address_1'] : ''),
            ],
            /*[
                'name' => 'billing_address_2',
                'label' => __('Korter / Maja', 'kafo'),
                'editableType' => '@textfield',
                'validate' => false,
                'value' => ($company ? $company['billing_address_2'] : ''),
            ],*/
            [
                'name' => 'billing_city',
                'label' => __('Linn', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
                'value' => ($company ? $company['billing_city'] : ''),
            ],
            [
                'name' => 'billing_postcode',
                'label' => __('Posti indeks', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
                'value' => ($company ? $company['billing_postcode'] : ''),
            ],
            [
                'name' => 'billing_state',
                'label' => __('Maakond', 'kafo'),
                'editableType' => '@textfield',
                'validate' => false,
                'value' => ($company ? $company['billing_state'] : ''),
            ],
            /*[
                'name' => 'billing_country',
                'label' => __('Riik', 'kafo'),
                'options' => $this->getCountryList($company ? $company['billing_country'] : ''),
                'editableType' => '@select',
                'validate' => true,
            ],*/
            [
                'text' => __('Salvesta', 'kafo'),
                'type' => 'submit',
                'rowModifier' => 'form-group__row--double',
                'class' => 'js-save-editable',
                'editableType' => '@button',
                'customAttributes' => 'data-action="add-company"',
            ],
        ];

        if ($company) {
            $field = [
                'editTitle' => __('Lisa firma andmed', 'kafo'),
                'closeTitle' => __('Sulge', 'kafo'),
                'title' => sprintf('%s - %s', $company['billing_company'], $company['billing_reg_code']),
                'content' => implode('<br>', [$company['billing_vat_number'], $company['billing_address_1'] . ' ' . $company['billing_address_2'], $company['billing_city'], $company['billing_postcode'] . ' ' . $company['billing_state'], $company['billing_country']]),
                'fields' => $fields,
            ];
        } else {
            $field = [
                'editTitle' => __('Lisa firma andmed', 'kafo'),
                'closeTitle' => __('Sulge', 'kafo'),
                'isCustom' => true,
                'fields' => $fields,
            ];
        }

        $content = Template::compileComponent('@editable', ['editableValue' => $field]);

        return $content;
    }

    public function getNewAddress() {
        $fields = [
            [
                'name' => 'name',
                'label' => __('Nimi', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
            ],
            [
                'name' => 'billing_address_1',
                'label' => __('Aadress', 'kafo'),
                'placeholder' => __('Tänav, Korter / Maja', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
            ],
            /*[
                'name' => 'billing_address_2',
                'label' => __('Korter / Maja', 'kafo'),
                'editableType' => '@textfield',
                'validate' => false,
            ],*/
            [
                'name' => 'billing_city',
                'label' => __('Linn', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
            ],
            [
                'name' => 'billing_postcode',
                'label' => __('Posti indeks', 'kafo'),
                'editableType' => '@textfield',
                'validate' => true,
            ],
            [
                'name' => 'billing_state',
                'label' => __('Maakond', 'kafo'),
                'editableType' => '@textfield',
                'validate' => false,
            ],
            /*[
                'name' => 'billing_country',
                'label' => __('Riik', 'kafo'),
                'options' => $this->getCountryList(),
                'editableType' => '@select',
                'validate' => true,
            ],*/
            [
                'id' => 'default_new_location',
                'name' => 'default',
                'label' => __('Sea vaikimisi aadressiks', 'kafo'),
                'rowModifier' => 'form-group__row--double',
                'editableType' => '@check',
                'value' => 1,
            ],
            [
                'text' => __('Salvesta', 'kafo'),
                'type' => 'submit',
                'rowModifier' => 'form-group__row--double',
                'class' => 'js-save-editable',
                'editableType' => '@button',
                'customAttributes' => 'data-action="add-location"',
            ],
        ];

        $field   = [
            'editTitle' => __('Lisa uus aadress', 'kafo'),
            'closeTitle' => __('Sulge', 'kafo'),
            'title' => '',
            'content' => '',
            'isCustom' => true,
            'fields' => $fields,
        ];
        $content = Template::compileComponent('@editable', ['editableValue' => $field]);

        return $content;
    }

    public function getMyAddresses() {
        $content = [];
        foreach (User::getAddresses() as $index => $address) {
            $fields = [
                [
                    'name' => 'name',
                    'label' => __('Nimi', 'kafo'),
                    'editableType' => '@textfield',
                    'validate' => true,
                    'value' => $address['name'],
                ],
                [
                    'name' => 'billing_address_1',
                    'label' => __('Aadress', 'kafo'),
                    'placeholder' => __('Tänav, Korter / Maja', 'kafo'),
                    'editableType' => '@textfield',
                    'validate' => true,
                    'value' => $address['billing_address_1'],
                ],
                /*[
                    'name' => 'billing_address_2',
                    'label' => __('Korter / Maja', 'kafo'),
                    'editableType' => '@textfield',
                    'validate' => false,
                    'value' => $address['billing_address_2'],
                ],*/
                [
                    'name' => 'billing_city',
                    'label' => __('Linn', 'kafo'),
                    'editableType' => '@textfield',
                    'validate' => true,
                    'value' => $address['billing_city'],
                ],
                [
                    'name' => 'billing_postcode',
                    'label' => __('Posti indeks', 'kafo'),
                    'editableType' => '@textfield',
                    'validate' => true,
                    'value' => $address['billing_postcode'],
                ],
                [
                    'name' => 'billing_state',
                    'label' => __('Maakond', 'kafo'),
                    'editableType' => '@textfield',
                    'validate' => false,
                    'value' => $address['billing_state'],
                ],
                /*[
                    'name' => 'billing_country',
                    'label' => __('Riik', 'kafo'),
                    'options' => $this->getCountryList($address['billing_country']),
                    'editableType' => '@select',
                    'validate' => true,
                ],*/
                [
                    'id' => 'default_location_' . $index,
                    'name' => 'default',
                    'label' => __('Sea vaikimisi aadressiks', 'kafo'),
                    'rowModifier' => 'form-group__row--double',
                    'editableType' => '@check',
                    'isChecked' => $address['default'],
                    'value' => 1,
                ],
                [
                    'text' => __('Salvesta', 'kafo'),
                    'rowModifier' => 'form-group__row--double',
                    'class' => 'js-save-editable',
                    'editableType' => '@button',
                    'customAttributes' => 'data-action="update-location"',
                ],
                [
                    'text' => __('Kustuta', 'kafo'),
                    'rowModifier' => 'form-group__row--last',
                    'modifier' => 'button--naked button--naked-gray',
                    'editableType' => '@button',
                    'customAttributes' => 'data-action="delete-location"',
                ],
            ];

            $field     = [
                'editTitle' => __('Lisa uus aadress', 'kafo'),
                'closeTitle' => __('Sulge', 'kafo'),
                'title' => sprintf(__('Aadress #%s - %s', 'kafo'), ($index + 1), $address['name']),
                'content' => implode('<br>', [$address['billing_address_1'] . ' ' . $address['billing_address_2'], $address['billing_city'], $address['billing_postcode'] . ' ' . $address['billing_state'], $address['billing_country']]),
                'fields' => $fields,
                'index' => $index,
                'isDefault' => $address['default'],
            ];
            $content[] = Template::compileComponent('@editable', ['editableValue' => $field]);
        }

        return implode($content);
    }

    public function getCountryList($selected = false) {
        $countries = new WC_Countries();
        $list      = [];
        foreach ($countries->get_countries() as $code => $country) {
            $list[] = [
                'name' => $country,
                'value' => $code,
                'isSelected' => $code == $selected,
            ];
        }

        return $list;
    }

    public function getMyDetails() {
        $fields = [
            [
                'id' => 'firstname',
                'name' => 'billing_first_name',
                'title' => __('Eesnimi', 'kafo'),
                'content' => User::getFirstName(),
                'editableType' => '@textfield',
                'buttonData' => 'data-action="update-profile"',
                'validate' => true,
            ],
            [
                'id' => 'lastname',
                'name' => 'billing_last_name',
                'title' => __('Perenimi', 'kafo'),
                'content' => User::getLastName(),
                'editableType' => '@textfield',
                'buttonData' => 'data-action="update-profile"',
                'validate' => true,
            ],
            [
                'id' => 'email',
                'name' => 'billing_email',
                'title' => __('Email', 'kafo'),
                'content' => User::getEmail(),
                'editableType' => '@textfield',
                'buttonData' => 'data-action="update-profile"',
                'validate' => true,
            ],
            [
                'id' => 'phone',
                'name' => 'billing_phone',
                'title' => __('Telefon', 'kafo'),
                'content' => User::getPhone(),
                'editableType' => '@textfield',
                'buttonData' => 'data-action="update-profile"',
                'validate' => true,
            ],
            [
                'id' => 'user_password',
                'name' => 'user_password',
                'title' => __('Salasõna', 'kafo'),
                'value' => '',
                'content' => '**************************',
                'editableType' => '@textfield',
                'buttonData' => 'data-action="update-profile"',
                'validate' => true,
            ],
        ];

        $content = '';
        foreach ($fields as $field) {
            if (!isset($field['value'])) {
                $field['value'] = $field['content'];
            }

            if (!isset($field['label'])) {
                $field['label'] = $field['title'];
            }

            $field['fields'] = [$field];

            $content .= Template::compileComponent('@editable--compact', ['editableValue' => $field]);
        }

        return $content;
    }

    private function getRelatedProducts() {
        if (!get_field('account_related')) {
            return false;
        }

        $related = [
            'title' => get_field('account_related_title'),
            'button' => [
                'text' => get_field('account_related_label'),
                'link' => get_field('account_related_link'),
            ],
            'grid' => ['products' => []],
        ];
        foreach (get_field('account_related') as $product) {
            if (get_post_status($product) !== 'publish' || get_post_type($product) !== 'product') {
                continue;
            }

            $product                       = new Product($product);
            $related['grid']['products'][] = $product->getContext([
                'title',
                'description',
                'image',
                'link',
                'price',
            ]);
        }

        return $related;
    }

    public function getContextFields() {
        return $this->context;
    }

}
