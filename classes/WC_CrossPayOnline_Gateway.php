<?php
class WC_CrossPayOnline_Gateway extends WC_Payment_Gateway
{

    public string $key;

    public string $instructions;

    public string $api_key;

    public string $mode;

    public array $payment_processors;

    public string $callback_url;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id                 = 'crosspayonline_' . $this->key . '_gateway';
        $this->icon               = apply_filters($this->id . '_icon', '');
        $this->has_fields         = false;

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title        = $this->get_option('title', ucwords(str_replace('_', ' ', $this->id)));
        $this->description  = $this->get_option('description', ucwords(str_replace('_', ' ', $this->id)) . ' For WooCommerce');
        $this->instructions = $this->get_option('instructions', $this->description);

        $options = get_option('wc_crosspayonline_options');
        $this->api_key = $options['api_key'] ?? '';
        $this->mode = $options['mode'] ?? '';
        $this->callback_url = get_site_url() . '/' . 'wp-content/plugins/woocommerce-crosspayonline/callbacks/' . $this->mode . '.php';

        $processors = $options['payment_processors'] ?? '';
        if ($processors) {
            $this->payment_processors = json_decode($processors, true);
        } else {
            $this->payment_processors = array();
        }

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

        // Customer Emails
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }


    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = apply_filters($this->id . '_form_fields', array(
            'enabled'      => array(
                'title'   => __('Enable/Disable', $this->id),
                'type'    => 'checkbox',
                'label'   => __('Enable', $this->id),
                'default' => 'no',
            ),
            'title'        => array(
                'title'       => __('Title', $this->id),
                'type'        => 'safe_text',
                'description' => __('This controls the title which the user sees during checkout.', $this->id),
                'default'     => $this->method_title,
                'desc_tip'    => true,
            ),
            'description'  => array(
                'title'       => __('Description', $this->id),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', $this->id),
                'default'     => $this->method_description,
                'desc_tip'    => true,
            ),
            'instructions' => array(
                'title'       => __('Instructions', $this->id),
                'type'        => 'textarea',
                'description' => __('Instructions that will be added to the thank you page and emails.', $this->id),
                'default'     => $this->method_description,
                'desc_tip'    => true,
            ),
        ));
    }


    /**
     * Output for the order received page.
     */
    public function thankyou_page()
    {
        if ($this->instructions) {
            echo wpautop(wptexturize($this->instructions));
        }
    }


    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions($order, $sent_to_admin)
    {

        if ($this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status('on-hold')) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function get_process_data($order_id, $processor = 'credit_card')
    {
        $order = wc_get_order($order_id);
        $total = $order->get_total();
        $total_bill = $total;
        $c_email = $order->billing_email;
        $c_name = $order->billing_first_name . ' ' . $order->billing_last_name;
        $c_mobile = $order->billing_phone;

        switch ($processor) {
            case 'credit_card':
                $endpoint = 'createInvoiceByAccount';
                break;
            case 'paypal':
                $endpoint = 'createInvoiceByAccountPaypal';
                break;
            case 'spacemit':
                $endpoint = 'createInvoiceByAccountSpaceremit';
                break;
            case 'lahza':
                $endpoint = 'createInvoiceByAccountLahza';
                break;
            case 'usdt':
                $endpoint = 'createInvoiceByAccountUSDT';
                break;
            default:
                $endpoint = 'createInvoiceByAccount';
        }

        $bill_url = 'https://crosspayonline.com/api/' . $endpoint . '?api_data=82e4b4fd3a16ad99229af9911ce8e6d2&invoice_id=' . $order_id . '&apiKey=' . $this->api_key . '&total=' . $total_bill . '&currency=USD&inv_details={"inv_items": [{"name": "Shoping from store","quntity": "1.00","unitPrice": "' . $total_bill . '","totalPrice": "' . $total_bill . '","currency": "USD"}],"inv_info":[{"row_title":"Vat","row_value":"0"},{"row_title":"Delevery","row_value":"0"},{"row_title":"Promo Code","row_value":0},{"row_title":"Discounts","row_value":0}],"user" :{"userName":"test"}}&return_url=' . $this->callback_url . '&email=' . $c_email . '&mobile=' . $c_mobile . '&name=' . $c_name;

        // Mark as on-hold (we're awaiting the payment)
        $order->update_status('on-hold', __('Awaiting for payment.', 'crosspayonline_gateway'));

        // Reduce stock levels
        wc_reduce_stock_levels($order_id);

        // Remove cart
        WC()->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result'     => 'success',
            'redirect'    => $bill_url
        );
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        return $this->get_process_data($order_id, $this->key);
    }
} // end \WC_CrossPayOnline_Gateway class