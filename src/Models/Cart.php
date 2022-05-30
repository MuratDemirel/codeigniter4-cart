<?php

namespace MuratDemirel\Cart\Models;

use CodeIgniter\Config\Config;
use CodeIgniter\Model;

class Cart extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['id', 'identifier', 'instance'];
    protected $useTimestamps = true;
    protected $createdField = 'createdAt';
    protected $updatedField = 'updatedAt';

    /**
     * Get config table name.
     *
     * @return string
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->table = Config::get('Cart')->cartTable ?? 'cart';
    }
}
