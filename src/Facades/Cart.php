<?php

namespace MuratDemirel\Cart\Facades;

use MuratDemirel\Cart\Cart as ShoppingCart;

/**
 * @method static \MuratDemirel\Cart\Cart instance($instance = null)
 * @method static \MuratDemirel\Cart\Cart currentInstance()
 * @method static \MuratDemirel\Cart\Cart add($id, $name = null, $qty = null, $price = null, array $options = [], $taxrate = null)
 * @method static \MuratDemirel\Cart\Cart update($rowId, $qty)
 * @method static \MuratDemirel\Cart\Cart remove($rowId)
 * @method static \MuratDemirel\Cart\Cart get($rowId)
 * @method static \MuratDemirel\Cart\Cart destroy()
 * @method static \MuratDemirel\Cart\Cart content()
 * @method static \MuratDemirel\Cart\Cart count()
 * @method static \MuratDemirel\Cart\Cart total($decimals = null, $decimalPoint = null, $thousandSeperator = null)
 * @method static \MuratDemirel\Cart\Cart tax($decimals = null, $decimalPoint = null, $thousandSeperator = null)
 * @method static \MuratDemirel\Cart\Cart subtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
 * @method static \MuratDemirel\Cart\Cart search(\Closure $search)
 * @method static \MuratDemirel\Cart\Cart associate($rowId, $model)
 * @method static \MuratDemirel\Cart\Cart setTax($rowId, $taxRate)
 * @method static \MuratDemirel\Cart\Cart store($identifier)
 * @method static \MuratDemirel\Cart\Cart restore($identifier)
 * @method static \MuratDemirel\Cart\Cart __get($attribute)
 * 
 * @see \MuratDemirel\Cart\Cart
 */
class Cart
{
    /**
     * @param $method
     * @param $arguments
     * @return ShoppingCart
     */
    public static function __callStatic($method, $arguments)
    {
        return (new ShoppingCart())->$method(...$arguments);
    }
}