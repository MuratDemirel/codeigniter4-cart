<?php

namespace MuratDemirel\Cart;

use CodeIgniter\Config\Config;
use MuratDemirel\Cart\Models\CartItems as CartItemsModel;


class CartItem {

    /**
     * Model Instance
     *
     * @var MuratDemirel\Cart\Models\CartItems
     */
    protected CartItemsModel $cartItemsModel;

    /**
     * The rowID of the cart item.
     *
     * @var int
     */
    public $id;

    /**
     * The ID of the cart.
     *
     * @var int
     */
    public $cartId;

    /**
     * The Product ID of the cart item.
     *
     * @var int|string
     */
    public $productId;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $qty;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public $name;

    /**
     * The price of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * The options for this cart item.
     *
     * @var object
     */
    public $options;

    /**
     * The option price for this cart item.
     *
     * @var int|float|null
     */
    public $optionPrice;

    /**
     * The seller id for this cart item.
     *
     * @var int|null
     */
    public $sellerId;

    /**
     * The FQN of the associated model.
     *
     * @var string|null
     */
    private $associatedModel = null;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    private $taxRate = 0;

    /**
     * Is item saved for later.
     *
     * @var boolean
     */
    private $isSaved = false;

    /**
     * Config
     *
     * @var object
     */
    protected $config;


    /**
     * The total price with option prices of the cart item.
     *
     * @var float
     */
    public $priceWithOption;

    /**
     * The unit price with TAX of the cart item.
     *
     * @var float
     */
    public $unitPrice;


    /**
     * CartItem constructor.
     *
     * @param int|object|boolean $id
     * @param int|string         $productId
     * @param string             $productTitle
     * @param float|int          $price
     * @param array              $options
     * @param int                $cartId
     * @param int|float|null     $tax
     * @param int|null           $qty
     * @param int|null           $sellerId
     * @param int|float|null     $optionPrice
     */
    public function __construct($id = false, $productId = null, $productTitle = null, $price = null, $cartId = null, array $options = [], $optionPrice = null, $qty = null, $sellerId = null, $tax = null) {


        $this->cartItemsModel = new CartItemsModel();
        $this->config = Config::get('Cart');


        if (is_integer($id)) {
            $item = $this->cartItemsModel->find($id);
            if (!$item) {
                throw new \InvalidArgumentException('Please supply a valid id.');
            }
            return new self($item);
        } else if (is_object($id)) {

            $this->productId    = $id->productId;
            $this->productTitle = $id->productTitle;
            $this->cartId       = $id->cartId;
            $this->price        = floatval($id->price);
            $this->tax          = $id->tax ?? $this->config->defaultTax;
            $this->qty          = $id->qty ?? 1;
            $this->sellerId     = $id->sellerId;
            $this->options      = json_decode($id->options);
            $this->optionPrice  = floatval($id->optionPrice);
            $this->id           = $id->id;
            $this->setUnitPrice();

        } else {
            if (empty($productId)) {
                throw new \InvalidArgumentException('Please supply a valid identifier.');
            }

            if (empty($productTitle)) {
                throw new \InvalidArgumentException('Please supply a valid name.');
            }

            if (strlen($price) < 0 || !is_numeric($price)) {
                throw new \InvalidArgumentException('Please supply a valid price.');
            }

            if (empty($cartId)) {
                throw new \InvalidArgumentException('Please supply a valid cart id.');
            }

            $this->productId    = $productId;
            $this->productTitle = $productTitle;
            $this->cartId       = $cartId;
            $this->price        = floatval($price);
            $this->tax          = $tax ?? $this->config->defaultTax;
            $this->qty          = $qty ?? 1;
            $this->sellerId     = $sellerId;
            $this->options      = json_decode(json_encode($options));
            $this->optionPrice  = floatval($optionPrice);
            $this->id           = false;
            $this->setUnitPrice();
        }
    }

    /**
     * Sets total price with option and returns it
     *
     * @return float
     */
    public function setPriceWithOption() {
        $isSum = $this->config->optionPriceSum;
        echo gettype($this->optionPrice);
        if (!floatval($this->optionPrice)) {
            $this->priceWithOption = static::numberFormat($this->price ?? 0);
            return $this->priceWithOption;
        } else if (!$isSum) {
            $this->priceWithOption = static::numberFormat($this->price ? ( $this->price + $this->optionPrice ) : 0);
            return $this->priceWithOption;
        }
        $this->priceWithOption = static::numberFormat($this->optionPrice ?? $this->price ?? 0);
        return $this->priceWithOption;
    }

    /**
     * Sets total price per unit with tax and returns it
     *
     * @return float
     */
    public function setUnitPrice() {
        $price       = $this->setPriceWithOption();
        $tax         = floatval($this->tax);
        $taxIncluded = $this->config->taxIncluded;

        if (!$price) {
            $this->unitPrice = 0;
            return $this->unitPrice;
        } else if (!$tax) {
            $this->unitPrice = floatval($price);
            return $this->unitPrice;
        } else if ($taxIncluded) {
            $this->priceWithOption = static::numberFormat(floatval($price) * ( ( 100 - $tax ) / 100 ));
            $this->unitPrice       = static::numberFormat($price);
            return $this->unitPrice;
        }
        $this->unitPrice = static::numberFormat(floatval($price) * ( ( 100 + $tax ) / 100 ));
        return $this->unitPrice;
    }

    /**
     * Return the formatted price without TAX.
     *
     * @return float
     */
    public function price() {
        return static::numberFormat($this->priceWithOption);
    }

    /**
     * Return the formatted price with TAX.
     *
     * @return float
     */
    public function priceTax() {
        return static::numberFormat($this->unitPrice);
    }

    /**
     * Returns the formatted subTotal.
     *
     * @return float
     */
    public function subTotal() {
        return static::numberFormat($this->subTotal);
    }

    /**
     * Returns the formatted total.
     *
     * @return float
     */
    public function total() {
        return static::numberFormat($this->total);
    }

    /**
     * Returns the formatted tax.
     *
     * @return float
     */
    public function tax() {
        return static::numberFormat(floatval($this->unitPrice) - floatval($this->priceWithOption));
    }

    /**
     * Returns the formatted total tax.
     *
     * @return float
     */
    public function taxTotal() {
        return static::numberFormat($this->taxTotal);
    }

    /**
     * Save the cart item to database
     *
     * @return integer inserted id
     */
    public function save() {
        $this->id = $this->cartItemsModel->insert([
            'productId'    => $this->productId,
            'productTitle' => $this->productTitle,
            'cartId'       => $this->cartId,
            'options'      => json_encode($this->options),
            'qty'          => $this->qty,
            'sellerId'     => $this->sellerId,
            'tax'          => floatval(static::numberFormat($this->tax)),
            'price'        => floatval(static::numberFormat($this->price)),
            'optionPrice'  => floatval(static::numberFormat($this->optionPrice))
        ]);

        return $this->id;
    }

    /**
     * Update the cart item on database and class with array items
     *
     * @param array $data
     *
     * @return $this
     */
    public function updateFromArray(array $data = []) {
        $mergedData              = array_merge([
            'productId'    => $this->productId,
            'productTitle' => $this->productTitle,
            'cartId'       => $this->cartId,
            'options'      => $this->options,
            'qty'          => $this->qty,
            'sellerId'     => $this->sellerId,
            'tax'          => static::numberFormat($this->tax),
            'price'        => static::numberFormat($this->price),
            'optionPrice'  => static::numberFormat($this->optionPrice)
        ], $data);
        $mergedData[ 'options' ] = json_encode($mergedData[ 'options' ]);

        $updated = $this->cartItemsModel->update($this->id, $mergedData);
        if ($updated) {
            $this->productId    = $mergedData[ 'productId' ];
            $this->productTitle = $mergedData[ 'productTitle' ];
            $this->cartId       = $mergedData[ 'cartId' ];
            $this->options      = json_decode($mergedData[ 'options' ]);
            $this->qty          = $mergedData[ 'qty' ];
            $this->sellerId     = $mergedData[ 'sellerId' ];
            $this->tax          = $mergedData[ 'tax' ];
            $this->price        = $mergedData[ 'price' ];
            $this->optionPrice  = $mergedData[ 'optionPrice' ];
            $this->setUnitPrice();
        }

        return $this;
    }

    /**
     * Set the quantity for the cart item.
     *
     * @param int|float $qty
     *
     * @return $this
     */
    public function setQuantity($qty) {
        if (empty($qty) || !is_numeric($qty)) {
            throw new \InvalidArgumentException('Please supply a valid quantity.');
        }

        $updated = $this->cartItemsModel->update($this->id, [ 'qty' => $qty ]);
        if ($updated) {
            $this->qty = $qty;
        }

        return $this;
    }

    /**
     * Delete the cart item from the database
     *
     * @return $this
     */
    public function delete() {

         return $this->cartItemsModel->delete($this->id);

    }

    /**
     * Associate the cart item with the given model.
     *
     * @param mixed $model
     *
     * @return \MuratDemirel\Cart\CartItem
     */
    public function associate($model) {
        $this->associatedModel = is_string($model) ? $model : get_class($model);

        return $this;
    }

    /**
     * Get an attribute from cart item or get the associated model.
     *
     * @param $attribute
     *
     * @return mixed
     */
    public function __get($attribute) {
        if (property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if ($attribute === 'subTotal') {
            return floatval($this->qty) * floatval($this->priceWithOption);
        }

        if ($attribute === 'total') {
            return floatval($this->unitPrice) * floatval($this->qty);
        }

        if ($attribute === 'taxTotal') {
            return floatval($this->tax()) * floatval($this->qty);
        }

        if ($attribute === 'model' && isset($this->associatedModel)) {
            return with(new $this->associatedModel())->find($this->id);
        }

        return null;
    }

    /**
     * Create a new instance from the given id.
     *
     * @param integer $id
     *
     * @return \MuratDemirel\Cart\CartItem
     */
    public static function fromId($id) {
        return new self($id);
    }

    /**
     * Create a new instance from the given item.
     *
     * @param object $item
     *
     * @return \MuratDemirel\Cart\CartItem
     */
    public static function fromItem($item) {
        return new self($item);
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array   $attributes
     * @param integer $cartId
     *
     * @return \MuratDemirel\Cart\CartItem
     */
    public static function fromArray(array $attributes, $cartId) {
        $l = array_merge([
            'productId'    => null,
            'productTitle' => null,
            'price'        => null,
            'cartId'       => null,
            'options'      => [],
            'optionPrice'  => null,
            'qty'          => 1,
            'sellerId'     => null,
            'tax'          => null,
        ], $attributes);

        return new self(false, $l[ 'productId' ], $l[ 'productTitle' ], $l[ 'price' ], $cartId, $l[ 'options' ], $l[ 'optionPrice' ], $l[ 'qty' ], $l[ 'sellerId' ], $l[ 'tax' ]);
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param int|string     $productId
     * @param string         $productTitle
     * @param float|int      $price
     * @param array          $options
     * @param int            $cartId
     * @param int|float|null $tax
     * @param int|null       $qty
     * @param int|null       $sellerId
     * @param int|float|null $optionPrice
     *
     * @return \MuratDemirel\Cart\CartItem
     */
    public static function fromAttributes($productId, $productTitle, $price, $cartId, array $options = [], $optionPrice = null, $qty = null, $sellerId = null, $tax = null) {
        return new self(false, $productId, $productTitle, $price, $cartId, $options, $optionPrice, $qty, $sellerId, $tax);
    }


    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray() {
        return $this->productId ? [

            'id'           => $this->id,
            'productId'    => $this->productId,
            'productTitle' => $this->productTitle,
            'cartId'       => $this->cartId,
            'options'      => $this->options,
            'qty'          => $this->qty,
            'sellerId'     => $this->sellerId,
            'tax'          => $this->tax . '%',

            'price'        => $this->price(),
            'unitTax'      => $this->tax(),
            'priceWithTax' => $this->priceTax(),
            'subTotal'     => $this->subTotal(),
            'totalTax'     => $this->taxTotal(),
            'total'        => $this->total(),
            'optionPrice'  => static::numberFormat($this->optionPrice)

        ] : [];
    }

    /**
     * Get the formatted number.
     *
     * @param float       $value
     * @param int|null    $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     *
     * @return string
     */
    public static function numberFormat($value, $decimals = null, $decimalPoint = null, $thousandSeparator = null) {
        $format            = config('Cart')->format;
        $decimals          = $decimals ?? $format[ 'decimals' ];
        $decimalPoint      = $decimalPoint ?? $format[ 'decimal_point' ];
        $thousandSeparator = $thousandSeparator ?? $format[ 'thousand_separator' ];

        return number_format($value, $decimals, $decimalPoint, $thousandSeparator);
    }
}
