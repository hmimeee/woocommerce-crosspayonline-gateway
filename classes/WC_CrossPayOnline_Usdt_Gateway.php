<?php

class WC_CrossPayOnline_Usdt_Gateway extends WC_CrossPayOnline_Gateway
{
    public string $key = 'usdt';

    public function __construct()
    {
        parent::__construct();
        $this->method_title       = __('CrossPayOnline USDT', $this->id);
        $this->method_description = __('Pay with USDT via CrossPayOnline.', $this->id);
    }
}
