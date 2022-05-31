# CodeIgniter4 Shopping Cart

Ported from https://github.com/agungsugiarto/codeigniter4-shoppingcart

A simple cart module for CodeIgniter4.



## Overview
Look at one of the following topics to learn more about CodeIgniter4 ShoppingCart

* [Installation](#installation)
* [Usage](#usage)
* [Collections](#collections)
* [Instances](#instances)
* [Exceptions](#exceptions)
* [Events](#events)
* [Example](#example)
* [License](#license)



## Installation

Install the package through [Composer](http://getcomposer.org/).

Run the Composer require command from the Terminal:

    composer require muratdemirel/codeigniter4-cart

To publish config file, run the command below from the terminal:

    php spark cart:publish

This will give you a `Cart.php` config file in which you can make the changes.

After check & update your config file, run the command below on your terminal to generate tables:

    php spark migrate -n 'MuratDemirel\Cart'

It's all for setting up.

##Usage

- [Initialize](#initialize)

###Initialize

```php
// Via services
$cart = \MuratDemirel\Cart\Config\Services::cart();
$cart->add();

// Traditional way
$cart = new \MuratDemirel\Cart\Cart();
$cart->add();

// Static call
use MuratDemirel\Cart\Facades\Cart;
Cart::add()
```

##Collections

Soon..

##Instances

Soon..

##Exceptions

Soon..

##Events

Soon..

##Example

Soon

## License

This package is free software distributed under the terms of the [MIT license](LICENSE.md).