<?php

class WC_CrossPayOnline_Spaceremit_Gateway extends WC_CrossPayOnline_Gateway
{
    public string $key = 'spacemit';

    public function __construct()
    {
        parent::__construct();
        $this->method_title       = __('CrossPayOnline Spacemit', $this->id);
        $this->method_description = __('Pay with Spacemit via CrossPayOnline.', $this->id);
    }
}
