<?php

namespace MuratDemirel\Cart;

use CodeIgniter\Config\Config;
use CodeIgniter\HTTP\RequestInterface;
use MuratDemirel\Cart\Contracts\Buyable;
use MuratDemirel\Cart\Exceptions\InvalidRowIDException;
use MuratDemirel\Cart\Exceptions\InvalidSellerIdException;
use MuratDemirel\Cart\Exceptions\UnknownModelException;
use MuratDemirel\Cart\Models\Cart as CartModel;
use MuratDemirel\Cart\Models\CartItems as CartItemsModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Events\Events;
use CodeIgniter\I18n\Time;

class Cart {
    const DEFAULT_INSTANCE = 'default';

    /**
     * Instance session manager.
     *
     * @var \CodeIgniter\Session\Session
     */
    protected $session;

    /**
     * Instance Identifier
     *
     * @var string|integer|null
     */
    protected $identifier;

    /**
     * Config
     *
     * @var object
     */
    protected $config;

    /**
     * Model shopping cart.
     *
     * @var \MuratDemirel\Cart\Models\ShoppingCart $model
     */
    protected $cartModel;

    /**
     * Model shopping cart.
     *
     * @var \MuratDemirel\Cart\Models\ShoppingCart $model
     */
    protected $cartItemModel;

    /**
     * @var string
     */
    protected $instance;

    /**
     * CurrentCartInstance
     *
     * @var object|bool
     */
    protected $cartInstance;

    /**
     * CurrentCartInstance
     *
     * @var Array
     */
    protected $cartItems;

    /**
     * Cart constructor.
     *
     */
    public function __construct($user = null, $instance = null) {

        $this->cartModel     = new CartModel();
        $this->cartItemModel = new CartItemsModel();
        $this->cartInstance  = false;

        $this->config    = Config::get('Cart');
        $this->session   = Services::session();
        $this->cartItems = [];

        $this->setInstance($instance ?? self::DEFAULT_INSTANCE);
        $this->setIdentifier($user ?? false);

    }

    /**
     * Get the current cart instance.
     *
     * @param string|null $instance
     *
     * @return $this
     */
    public function setInstance($instance = null) {
        $this->instance = $instance ?? self::DEFAULT_INSTANCE;
        $this->setCartInstance();

        return $this;
    }

    /**
     * Set the current identifier
     *
     * @param string|integer|null $user
     *
     * @return $this
     */
    public function setIdentifier($user) {
        $this->identifier = $user;
        $this->setCartInstance();

        return $this;
    }


    /**
     *
     * Set cart instance with defined user and instance parameters
     *
     */
    protected function setCartInstance() {
        $this->cartInstance = $this->cartModel->where(array( 'identifier' => ( $this->identifier ?? 0 ), 'instance' => $this->instance ))->first();
        if ($this->cartInstance) {
            $cartItems = $this->cartItemModel->where('cartId', $this->cartInstance->id)->find();
            if (!$cartItems) {
                return;
            }
            $this->cartItems = [];
            foreach ($cartItems as $item) {
                $cartItem = CartItem::fromItem($item);
                if ($cartItem) {
                    $this->cartItems[ $cartItem->id ] = $cartItem;
                }
            }
        }
    }


    /**
     * Get selected cart instance or create one if not exists and create parameter is true and return it.
     *
     * @param bool $create
     *
     * @return bool|object
     */
    public function getCartInstance($create = false) {
        $cartInstance = $this->cartInstance;
        if (!$cartInstance && $create) {
            if (!$this->identifier) {
                helper([ 'text' ]);
                $this->setIdentifier(random_string('alnum', 30));
            }
            $this->cartModel->insert([
                'identifier' => $this->identifier,
                'instance'   => $this->instance
            ]);
            $this->setCartInstance();
            return $this->getCartInstance();
        }
        return $cartInstance;
    }

    /**
     * Add an item to the cart.
     *
     * @param mixed          $productId
     * @param mixed          $productTitle
     * @param int|float|null $qty
     * @param float|int      $price
     * @param float|int|null $optionPrice
     * @param array          $options
     * @param float|int|null $tax
     * @param int|null       $sellerId
     *
     * @return \MuratDemirel\Cart\CartItem|array
     */
    public function add($productId, $productTitle = null, $qty = null, $price = null, array $options = [], $optionPrice = null, $tax = null, $sellerId = null) {
        if ($this->isMulti($productId)) {
            return array_map(function ($item) {
                return $this->add($item);
            }, $productId);
        }

        $cartItem = $this->createCartItem($productId, $productTitle, $qty, $price, $options, $optionPrice, $tax, $sellerId);

        $arr = $cartItem->toArray();
        if (empty($arr)) {
            return false;
        }
        if (empty($this->cartItems)) {
            $existing = [];
        } else {
            $existing = array_filter($this->cartItems, function ($k) use ($arr) {
                return $k->productId == $arr[ 'productId' ] && $k->options == $arr[ 'options' ];
            });
        }
        if(!$this->config->allowDifferentSeller){
            if (!empty($this->cartItems)) {
                $existing = array_filter($this->cartItems, function ($k) use ($arr) {
                    return $k->sellerId != $arr[ 'sellerId' ];
                });
                if($existing){
                    throw new InvalidSellerIdException("Inserting products from different seller is not allowed");
                }
            }
        }
        if (empty($existing)) {
            $insertedId                     = $cartItem->save();
            $this->cartItems[ $insertedId ] = $cartItem;
        } else {
            $existing = current($existing);
            $id       = $existing->id;
            $qty      = $this->cartItems[ $id ]->qty + $cartItem->qty;
            $this->update($id, $qty);
            return $existing;
        }

        return $cartItem;
    }

    /**
     * Update the cart item with the given rowId.
     *
     * @param string $id
     * @param mixed  $qty
     *
     * @return \MuratDemirel\Cart\CartItem
     */
    public function update($id, $qty) {
        $cartItem = $this->get($id);

        if (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->setQuantity($qty);
        }


        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->id);

            return;
        }

        Events::trigger('cart.updated', $cartItem);

        return $cartItem;
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param string $rowId
     *
     * @return $this
     */
    public function remove($id) {

        if (!isset($this->cartItems[ $id ])) {
            throw new \InvalidArgumentException('Please supply a valid id.');
        }

        $cartItem = $this->cartItems[ $id ];

        if (!$cartItem->delete()) {
            throw new \LogicException('Cart item couldn\'t deleted properly. Please try again later.');
        }

        unset($this->cartItems[ $id ]);

        Events::trigger('cart.removed', $cartItem);

        return $this;
    }

    /**
     * Get a cart item from the cart by its rowId.
     *
     * @param string $id
     *
     * @return \MuratDemirel\Cart\CartItem
     */
    public function get($id) {

        if (!isset($this->cartItems[ $id ])) {
            throw new InvalidRowIDException("The cart does not contain id {$id}.");
        }

        return $this->cartItems[ $id ];
    }

    /**
     * Destroy the current cart instance.
     *
     * @return void
     */
    public function destroy() {
        if (!empty($this->cartItems)) {
            foreach ($this->cartItems as $cartItem) {
                $this->remove($cartItem->id);
            }
        }
        if ($this->cartInstance) {
            $this->cartModel->delete($this->cartInstance->id);
            $this->cartInstance = false;
        }
    }

    /**
     * Get the content of the cart.
     *
     * @return array
     */
    public function content() {
        $instance = $this->cartInstance;
        if ($instance && !empty($this->cartItems)) {
            $cartArrayItems = [];
            foreach ($this->cartItems as $cartItem) {
                $cartArrayItems[ $cartItem->id ] = $cartItem->toArray();
            }
            $instance->items = $cartArrayItems;
            $instance        = $this->setInstanceNumbers($instance);
        }

        return $instance;
    }

    public function setInstanceNumbers($instance) {
        $instance->qty = $this->count();
        $instance->subTotal = $this->subtotal();
        $instance->total = $this->total();
        $instance->tax = $this->tax($instance->total, $instance->subTotal);
        $instance->taxRate = $this->taxRate($instance->total, $instance->subTotal);

        return $instance;
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int|float
     */
    public function count() {
        $count = 0;
        array_walk($this->cartItems, function ($v) use (&$count) {
            $count += floatval($v->qty);
        });
        return CartItem::numberFormat($count);
    }

    /**
     * Get the total price of the items in the cart.
     *
     * @return int|float
     */
    public function total() {
        $count = 0;
        array_walk($this->cartItems, function ($v) use (&$count) {
            $count += floatval($v->total());
        });
        return CartItem::numberFormat($count);
    }

    /**
     * Get the total tax of the items in the cart.
     *
     * @param int|float|null $total
     * @param int|float|null $subtotal
     *
     * @return float
     */
    public function tax($total = null, $subtotal = null) {
        $total    = floatval($total ?? $this->total());
        $subtotal = floatval($subtotal ?? $this->subtotal());

        return CartItem::numberFormat(( $total - $subtotal ));
    }

    /**
     * Get the total tax rate of the items in the cart.
     *
     * @param int|float|null $total
     * @param int|float|null $subtotal
     *
     * @return float
     */
    public function taxRate($total = null, $subtotal = null) {
        $total    = floatval($total ?? $this->total());
        $subtotal = floatval($subtotal ?? $this->subtotal());

        return CartItem::numberFormat(100-(( 100 / $total ) * $subtotal));
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
     *
     * @return int|float
     */
    public function subtotal() {
        $count = 0;
        array_walk($this->cartItems, function ($v) use (&$count) {
            $count += floatval($v->subTotal());
        });
        return CartItem::numberFormat($count);
    }


    /**
     * Associate the cart item with the given rowId with the given model.
     *
     * @param string $rowId
     * @param mixed  $model
     *
     * @return void
     */
    /*public function associate($id, $model) {
        if (is_string($model) && !class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cartItem = $this->get($id);

        $cartItem->associate($model);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->set($this->instance, $content);
    }*/

    /**
     * Magic method to make accessing the total, tax and subtotal properties possible.
     *
     * @param string $attribute
     *
     * @return float|null
     */
    public function __get($attribute) {
        if ($attribute === 'total') {
            return $this->total();
        }

        if ($attribute === 'tax') {
            return $this->tax();
        }

        if ($attribute === 'subtotal') {
            return $this->subtotal();
        }

        return null;
    }


    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param mixed     $productId
     * @param mixed     $productTitle
     * @param int|float $qty
     * @param int|float $price
     * @param int|float $optionPrice
     * @param array     $options
     * @param float     $tax
     * @param int|null  $sellerId
     *
     * @return \MuratDemirel\Cart\CartItem
     */
    private function createCartItem($productId, $productTitle, $qty, $price, array $options = [], $optionPrice = null, $tax = null, $sellerId = null) {
        $cartInstance = $this->getCartInstance(true);
        if (is_array($productId)) {
            $cartItem = CartItem::fromArray($productId, $cartInstance->id);
        } else {
            $cartItem = CartItem::fromAttributes($productId, $productTitle, $price, $cartInstance->id, $options, $optionPrice, $qty, $sellerId, $tax);
        }

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     *
     * @param mixed $item
     *
     * @return bool
     */
    private function isMulti($item) {
        if (!is_array($item)) {
            return false;
        }

        return is_array(reset($item));
    }


}
