<?php

namespace MuratDemirel\Cart\Database\Migrations;

use CodeIgniter\Config\Config;
use CodeIgniter\Database\Migration;

class CreateCartTable extends Migration {
    protected $cartTable;
    protected $cartItemsTable;

    public function __construct() {
        parent::__construct();

        $this->cartTable      = Config::get('Cart')->cartTable ?? 'cart';
        $this->cartItemsTable = Config::get('Cart')->cartItemsTable ?? 'cart_items';
    }

    public function up() {
        $format = Config::get('Cart')->format ?? [ 'decimals' => 2 ];


        /*Create Cart Table*/
        $this->forge->addField([
            'id'         => [ 'type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true ],
            'identifier' => [ 'type' => 'varchar', 'constraint' => 255, 'null' => true ],
            'instance'   => [ 'type' => 'varchar', 'constraint' => 255, 'null' => true ],
            'createdAt'  => [ 'type' => 'datetime', 'null' => true ],
            'updatedAt'  => [ 'type' => 'datetime', 'null' => true ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable($this->cartTable, true);

        /*Create CartItems Table*/
        $this->forge->addField([
            'id'           => [ 'type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true ],
            'productId'    => [ 'type' => 'varchar', 'constraint' => 255, 'null' => false ],
            'productTitle' => [ 'type' => 'varchar', 'constraint' => 255, 'null' => true ],
            'cartId'       => [ 'type' => 'int', 'null' => false ],
            'price'        => [ 'type' => 'float', 'constraint' => '10,' . $format[ 'decimals' ], 'null' => false ],
            'tax'          => [ 'type' => 'float', 'constraint' => '10,' . $format[ 'decimals' ], 'null' => true ],
            'qty'          => [ 'type' => 'float', 'constraint' => '10,' . $format[ 'decimals' ], 'null' => false ],
            'sellerId'     => [ 'type' => 'int', 'constraint' => 11, 'null' => true ],
            'options'      => [ 'type' => 'text', 'null' => true  ],
            'optionPrice'  => [ 'type' => 'float', 'constraint' => '10,' . $format[ 'decimals' ], 'null' => true ],
            'createdAt'    => [ 'type' => 'datetime', 'null' => true ],
            'updatedAt'    => [ 'type' => 'datetime', 'null' => true ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable($this->cartItemsTable, true);
    }

    //--------------------------------------------------------------------

    public function down() {
        $this->forge->dropTable($this->cartTable, true);
        $this->forge->dropTable($this->cartItemsTable, true);
    }
}
