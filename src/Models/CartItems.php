<?php

namespace MuratDemirel\Cart\Models;

use CodeIgniter\Config\Config;
use CodeIgniter\Model;

class CartItems extends Model {
    protected $table;
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'id',
        'productId',
        'cartId',
        'productTitle',
        'price',
        'tax',
        'qty',
        'options',
        'sellerId',
        'optionPrice'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'createdAt';
    protected $updatedField = 'updatedAt';

    /**
     * Get config table name.
     *
     * @return string
     */
    public function __construct() {
        parent::__construct();

        $this->table = Config::get('Cart')->cartItemsTable ?? 'cart_items';
    }
}
