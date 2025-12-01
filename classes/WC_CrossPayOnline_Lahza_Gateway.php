<?php

class WC_CrossPayOnline_Lahza_Gateway extends WC_CrossPayOnline_Gateway
{
    public string $key = 'lahza';

    public function __construct()
    {
        parent::__construct();
        $this->method_title       = __('CrossPayOnline Lahza', $this->id);
        $this->method_description = __('Pay with Lahza via CrossPayOnline.', $this->id);
    }
}
