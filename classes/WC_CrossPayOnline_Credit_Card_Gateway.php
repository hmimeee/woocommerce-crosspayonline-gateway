<?php

class WC_CrossPayOnline_Credit_Card_Gateway extends WC_CrossPayOnline_Gateway
{
    public string $key = 'credit_card';

    public function __construct()
    {
        parent::__construct();
        $this->method_title       = __('CrossPayOnline Credit Card', $this->id);
        $this->method_description = __('Pay with Credit Card via CrossPayOnline.', $this->id);
    }
}
