<?php

class WC_CrossPayOnline_Spaceremit_Gateway extends WC_CrossPayOnline_Gateway
{
    public string $key = 'spaceremit';

    public function __construct()
    {
        parent::__construct();
        $this->method_title       = __('CrossPayOnline Spaceremit', $this->id);
        $this->method_description = __('Pay with Spaceremit via CrossPayOnline.', $this->id);
    }
}
