<?php

class WC_CrossPayOnline_Paypal_Gateway extends WC_CrossPayOnline_Gateway
{
    public string $key = 'paypal';

    public function __construct()
    {
        parent::__construct();
        $this->method_title       = __('CrossPayOnline PayPal', $this->id);
        $this->method_description = __('Pay with PayPal via CrossPayOnline.', $this->id);
    }
}
