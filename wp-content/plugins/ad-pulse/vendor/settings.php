<?php
/* Plugin Name: Admin Page Framework - Getting Started */
include dirname( __FILE__ ) . '/../admin-page-framework/library/apf/admin-page-framework.php';

class APF extends AdminPageFramework {

    public function setUp() {
        $adPulseLogo = dirname( __FILE__ ) . '/../assets/favicon-32x32.png';

        $this->setRootMenuPage(
            'Ad-pulse Plugin Settings',    // specify the name of the page group
            $adPulseLogo    // use 16 by 16 image for the menu icon.
        );

        $this->addSubMenuItems(
            [
                'title'                 => 'Order Numbers',        // page title
                'page_slug'             => 'order_numbers_page',    // page slug
                'screen_icon'           => $adPulseLogo     // page screen icon for WP 3.7.x or below
            ],
            [
                'title'                 => 'Order Admin',        // page title
                'page_slug'             => 'order_admin_page',    // page slug
                'screen_icon'           => $adPulseLogo     // page screen icon for WP 3.7.x or below
            ],
            [
                'title'                 => 'Order Status',        // page title
                'page_slug'             => 'order_status_page',    // page slug
                'screen_icon'           => $adPulseLogo     // page screen icon for WP 3.7.x or below
            ],
            [
                'title'                 =>    __( 'Support', 'ad-pulse' ),
                'href'                  =>    'https://www.ad-pulse.com/contacts',
                'show_page_heading_tab' =>    false,
            ]
        );

    }

    private function get_all_product_categories() {
        // Obtain product categories
        $product_categories = get_terms(['taxonomy'   => 'product_cat', 'hide_empty' => false]);
        $product_categories_in_select = array_column($product_categories, 'name', 'term_id');
        return $product_categories_in_select;
    }

    private function get_all_user_roles() {
        $user_roles_array = wp_roles()->roles;

        $roles_for_select = [];
        foreach ($user_roles_array as $role_slug => $role)
            $roles_for_select[$role_slug] = $role['name'];

        return $roles_for_select;
    }

    // Action hook methods: 'do_' + page slug.
    public function load_order_numbers_page(): void {
        $product_categories_in_select = $this->get_all_product_categories();

        $this->addSettingSections(
            array(
                'section_id'    => 'order_number',
                'title'         => __( 'Order Number'),
                'description'   => __( 'Define how each order code is generated'),
            )
        );

        $this->addSettingFields(
            // Order number definition
            [
                'field_id'      => 'order_number_definition',
                'section_id'    => 'order_number',
                'type'          => 'text',
                'title'         => 'Order Number Definition',
                'description'   => __('Type how you want your order number to be generated'),
            ],

            // Product categories in the orders changed by the order number definition
            [
                'field_id'      => 'order_number_product_categories',
                'section_id'    => 'order_number',
                'type'          => 'select',
                'is_multiple'   => true,
                'title'         => 'Product categories for order number',
                'label'         => $product_categories_in_select,
                'description'   => __('Select in which product categories the chosen order number will be generated'),
                'attributes'    => [
                    'select'        => ['style' => 'min-height: 100px;']
                ]
            ],
            
            // Submit button
            [
                'field_id'      => 'submit_button',
                'type'          => 'submit',
                'default'         => __('Save changes')
            ]
        );
    }

    public function load_order_admin_page(): void {
        $product_categories_in_select = $this->get_all_product_categories();
        $role_array = $this->get_all_user_roles();

        $this->addSettingSections(
            array(
                'section_id'    => 'order_admin_page_settings',
                'title'         => __( 'Order Admin Page'),
                'description'   => __( 'Set settings for order admin page'),
            )
        );

        $this->addSettingFields(
            // Product categories which are allowed to be listed
            [
                'field_id'      => 'product_categories_in_order_item_search',
                'section_id'    => 'order_admin_page_settings',
                'type'          => 'select',
                'is_multiple'   => true,
                'title'         => 'Product categories in order item search',
                'label'         => $product_categories_in_select,
                'description'   => __('These are the product categories which will be allowed to search by when adding new items to the order'),
                'attributes'    => [
                    'select'        => ['style' => 'min-height: 100px;']
                ]
            ],

            // User roles which will be impacted by this restriction
            [
                'field_id'      => 'product_search_restricted_user_roles',
                'section_id'    => 'order_admin_page_settings',
                'type'          => 'select',
                'is_multiple'   => true,
                'title'         => 'Product search restricted user roles',
                'label'         => $role_array,
                'description'   => __('User roles which will be impacted by the restriction set above'),
                'attributes'    => [
                    'select'        => ['style' => 'min-height: 100px;']
                ]
            ],
            
            // Submit button
            [
                'field_id'      => 'submit_button',
                'type'          => 'submit',
                'default'         => __('Save changes')
            ]
        );
    }

    public function load_order_fields_page(): void {
        $fields = [];
        $fields[] = [ // Submit button
            'field_id'      => 'submit',
            'section_id'    => 'order_status',
            'type'          => 'submit',
            'default'       => __('Save changes')
        ];

        $this->addSettingSections(
            [
                'section_id' => 'order_status',
                'title' => 'Custom Order Fields',
                'description' => __('Customize additional fields for all orders')
            ]
        );

        $this->addSettingFields(...$fields);
    }
    public function load_order_status_page(): void {

        $order_status = get_post($_GET['order_status_id']);

        $all_status = get_posts(
                [
                    'post_type' => 'order_status',
                    'posts_per_page' => -1,
                    'post__not_in' => [$order_status->ID]
                ]
        );
        $apf_saved_data = get_option('APF');

        $fields = [];

        if(is_null($order_status)) {
            $fields = $this->load_order_status_main_page($all_status, $apf_saved_data);
        } else {
            $fields = $this->load_order_status_specific_page($order_status, $all_status, $apf_saved_data);
        }

        // region General User Interface Settings

        $fields[] = [ // Submit button
            'field_id'      => 'submit',
            'type'          => 'submit',
            'default'         => __('Save changes')
        ];

        $section_title = (!is_null($order_status)? $order_status->post_title . ' - ' : '') . __('Order Status Settings');

        $this->addSettingSections(
                [
                    'section_id' => 'order_status',
                    'title' => $section_title,
                    'description' => __('Setup additional settings for this status')
                ]
        );

        $this->addSettingFields(...$fields);

        // endregion
    }
    public function do_order_numbers_page(): void {
        ?>
        <h3>Shortcodes</h3>
        <ul>
            <li><strong>[Y] -</strong> <?= __('Current Year') ?></li>
            <li><strong>[sku] -</strong> <?= __('SKU of the first product in the order') ?></li>
            <li><strong>[store] -</strong><?= __('Slug of the store associated with the order') ?></li>
            <li><strong>[seq] -</strong><?= __('Sequential number') ?></li>
        </ul>
        <?php
    }

    /**
     * Let's try using methods for filters. For filters, the method must return the output.
     * The method name is content_ + page slug, similar to the above methods for action hooks.
     */


    // region Private (Auxiliary) Functions
    
    private function load_option(&$apf_saved_data, $option_slug) {
        if($apf_saved_data['order_status']['order_status_id'] != 0) {
            $general_prefs = maybe_unserialize(get_option('order_status_general_prefs'));
            $apf_saved_data['order_status'] = array_replace(['order_status_id' => 0], is_array($general_prefs)? $general_prefs : []);

            $option = get_option($option_slug);
            if($option) {
                $apf_saved_data['order_status'][$option_slug] = $option;
            }

            update_option('APF', $apf_saved_data);
        } else {
            update_option($option_slug, $apf_saved_data['order_status'][$option_slug]);
        }
    }
    private function load_order_status_main_page($all_status, $apf_saved_data): array {

        $available_status = array_combine(
            array_column($all_status, 'post_name'),
            array_column($all_status, 'post_title')
        );

        $field_slugs = ['default_order_status', 'payments_created_blocking_order_status', 'first_payment_blocking_order_status'];
        foreach ($field_slugs as $field_slug) {
            $this->load_option($apf_saved_data, $field_slug);
        }

        $fields = [
            [
                'field_id'      => 'default_order_status',
                'section_id'    => 'order_status',
                'type'          => 'select',
                'title'         => __('Default Order Status'),
                'description'   => __('Choose a status to be shown as the default when a new order is created'),
                'label'         => $available_status,
            ],
            [
                'field_id'      => 'payments_created_blocking_order_status',
                'section_id'    => 'order_status',
                'type'          => 'select',
                'title'         => __('Payments creation blocking order status'),
                'description'   => __("Choose a status to be set as the one where the payments should have already been created or else the order will not advance"),
                'label'         => $available_status,
            ],
            [
                'field_id'      => 'first_payment_blocking_order_status',
                'section_id'    => 'order_status',
                'type'          => 'select',
                'title'         => __('Payment blocking order status'),
                'description'   => __("Choose a status to be set as the one where at least one of the installments has been paid or else the order will not advance"),
                'label'         => $available_status,
            ]
        ];

        return $fields;
    }
    private function load_order_status_specific_page($order_status, $all_status, $apf_saved_data): array {
        // region Parsing Stored Data

        $all_meta = get_post_meta($order_status->ID);

        $all_products = wc_get_products([]);
        $available_status = array_replace(
            [0 => __('All'), -1 => __('None')],
            array_combine(
                array_column($all_status, 'post_name'),
                array_column($all_status, 'post_title')
            )
        );
        $available_products = array_replace(
            [0 => __('All')],
            array_combine(
                array_column($all_products, 'id'),
                array_column($all_products, 'name')
            )
        );

        $apf_order_status = $apf_saved_data['order_status'];
        $next_status_prefix = 'next_status_';
        $next_keys = [];

        foreach($available_products as $key => $prod_name) {
            $next_keys[] = $next_status_prefix . $key;
        }

        $to_save = array_merge(
            [
                'available_when_not_editable', 
                'is_conditional', 
                'is_default', 
                'allowed_roles', 
                'minimum_payment_percentage', 
                'status_has_payment', 
                'minimum_absolute_payment', 
                'status_payment_is_percentage
            ']
        , $next_keys);
        $update_option = false;

        // iterate through order status attributes
        foreach($to_save as $meta_to_save) {
            if($apf_order_status['order_status_id'] == $order_status->ID) {
                // save order status settings to database
                update_post_meta($order_status->ID, $meta_to_save, $apf_order_status[$meta_to_save]);

                if($meta_to_save === 'is_default' && $apf_order_status[$meta_to_save] !== '0') {
                    update_option('default_order_status', get_post_meta($order_status->ID)['status_slug'][0]);
                }
            } else {
                // load order status settings to frontend variable
                if(array_key_exists($meta_to_save, $all_meta)) {
                    $apf_saved_data['order_status'][$meta_to_save] = maybe_unserialize($all_meta[$meta_to_save][0]);
                } else {
                    unset($apf_saved_data['order_status'][$meta_to_save]);
                }

                $update_option = true;
            }
        }

        // make sure frontend variable with order status info is updated
        if($update_option) {
            $apf_saved_data['order_status']['order_status_id'] = $order_status->ID;
            update_option('APF', $apf_saved_data);
        }

        // endregion

        // region Fields Generation

        $fields = [
            [ // Hidden input to retrieve the order status ID
                'field_id'      => 'order_status_id',
                'type'          => 'hidden',
                'section_id'    => 'order_status',
                'value'         => $order_status->ID,
            ],
            [ // Radio buttons to choose if the order status is set by default when creating a new order
                'field_id'      => 'is_default',
                'section_id'    => 'order_status',
                'type'          => 'checkbox',
                'title'         => __('Default Status'),
                'label'         => __('Set as default status'),
                'description'   => __('Leave the box ticked if you want this order status to be the default state on every new order'),
                'default'       => false
            ],
            [ // Radio buttons to choose whether the order status is conditional or not
                'field_id'      => 'available_when_not_editable',
                'section_id'    => 'order_status',
                'type'          => 'radio',
                'label'         => ['1' => __('Yes'), '0' => __('No')],
                'default'       => '0',
                'title'         => __('Available even when not editable'),
                'description'   => __('Select yes if you want this order status to be available to be chosen even when the order is not editable'),
            ],
            [ // Radio buttons to choose whether the order status is conditional or not
                'field_id'      => 'is_conditional',
                'section_id'    => 'order_status',
                'type'          => 'radio',
                'label'         => ['1' => __('Yes'), '0' => __('No')],
                'default'       => '0',
                'title'         => __('Conditional Order Status'),
                'description'   => __('Select yes if you want this order status to only be selectable on certain conditions'),
            ],
            [
                'field_id'      => 'order_product',
                'section_id'    => 'order_status',
                'type'          => 'select',
                'title'         => __('Product(s)'),
                'description'   => __('Choose one or more products in which this order status will be available'),
                'label'         => $available_products,
            ]
        ];

        foreach($available_products as $prodKey => $product_name) {
            $field_id = $next_status_prefix . $prodKey;
            $isAll = $prodKey == 0;
            $product_name_reference = $product_name . ($isAll? ' products' : ' product');

            $fields[] = [
                'field_id'      => $field_id,
                'section_id'    => 'order_status',
                'type'          => 'select',
                'title'         => __('Next status(es) for ' . $product_name_reference),
                'description'   => __('Choose one or more status to be available to choose from this order status'),
                'is_multiple'   => true,
                'label'         => $available_status,
                'default'       => $isAll? 0 : -1,
                'attributes'    => [
                    'select'        => ['style' => 'min-height: 100px;'],
                    'fieldrow'      => ['class' => !$isAll? 'hidden' : '']
                ]
            ];
        }

        // TODO: acabar de gerar os dados para este select e tratar do resultado

        $wp_roles = get_editable_roles();
        $user_roles = array_combine(array_keys($wp_roles), array_column($wp_roles, 'name'));
        $user_roles = array_replace([0 => __('All'), -1 => __('None')], $user_roles);

        $fields[] = [
            'field_id'      => 'allowed_roles',
            'section_id'    => 'order_status',
            'type'          => 'select',
            'title'         => __('User Roles'),
            'description'   => __('Define user roles that can edit orders which are currently in this status'),
            'is_multiple'   => true,
            'label'         => $user_roles,
            'default'       => 0,
            'attributes'    => [
                'select'        => ['style' => 'min-height: 100px;']
            ]
        ];


        $fields[] = [
            // Radio buttons to choose whether the order status has a payment associated or not
            'field_id'      => 'status_has_payment',
            'section_id'    => 'order_status',
            'type'          => 'radio',
            'label'         => ['1' => __('Yes'), '0' => __('No')],
            'default'       => '0',
            'title'         => __('Status has payment'),
            'description'   => __('Select yes if there should be a payment in order to advance from this order status onward'),
        ];

        $fields[] = [
            // Radio buttons to choose whether the order status has a payment associated or not
            'field_id'      => 'status_payment_is_percentage',
            'section_id'    => 'order_status',
            'type'          => 'radio',
            'label'         => ['1' => __('Yes'), '0' => __('No')],
            'default'       => '0',
            'title'         => __('Status payment is percentage'),
            'description'   => __('Select if the payment in order to advance from this order status onward should be a percentage or an absolute value'),
        ];

        $fields[] = [
            // Input to setup the mininum payment that must be done in this status
            'field_id'      => 'minimum_payment_percentage',
            'section_id'    => 'order_status',
            'type'          => 'number',
            'default'       => '0',
            'attributes'    => [
                    'step' => '0.01',   // Allows decimal values
                    'min'  => '0',      // Optional: set minimum value
                    'max'  => '100',    // Optional: set maximum value
                ],
            'title'         => __('Percentage of payment done in this status'),
            'description'   => __('Enter the percentage of the payment of the whole sale price for this order to advance to the next status'),
        ];

        $fields[] = [
            // Input to setup the mininum payment that must be done in this status
            'field_id'      => 'minimum_absolute_payment',
            'section_id'    => 'order_status',
            'type'          => 'number',
            'default'       => '0',
            'attributes'    => [
                    'step' => '0.01',   // Allows decimal values
                    'min'  => '0',      // Optional: set minimum value
                ],
            'title'         => __('Absolute payment done in this status'),
            'description'   => __('Enter the absolute payment value for this order to advance to the next status'),
        ];

        // endregion

        return $fields;
    }

    // endregion

}
new APF;