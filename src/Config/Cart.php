<?php

namespace MuratDemirel\Cart\Config;

use CodeIgniter\Config\BaseConfig;

class Cart extends BaseConfig {
    /**
     * This default tax rate will be used when you make a class implement the
     * taxable interface and use the HasTax trait.
     */
    public $defaultTax = 0;

    /**
     * Is prices tax included
     */
    public $taxIncluded = true;

    /**
     * Option price sum/override
     * true: sum
     * false: override
     */
    public $optionPriceSum = true;



    /**
     * Here you can set the table names
     */
    public $cartTable = 'cart';
    public $cartItemsTable = 'cart_items';

    /**
     * Allow/disallow adding products from differentSeller
     * Works only if sellerId has set with cart items
     */
    public $allowDifferentSeller = false;
    

    /**
     * This defaults will be used for the formated numbers if you don't
     * set them in the method call.
     */
    public $format = [
        'decimals' => 2,
        'decimal_point' => '.',
        'thousand_separator' => ',',
    ];
}
