# PHP-Utils
[![Packgist](https://img.shields.io/packagist/v/carry0987/utils.svg?style=flat-square)](https://packagist.org/packages/carry0987/utils)  
This Utils library provides a collection of static methods designed to help in various common tasks encountered in PHP development. From manipulating arrays and strings to handling date-time conversions and file paths, this library aims to be a handy tool for PHP developers.

## Features
- Array manipulation and ordering
- String handling and sanitization
- Date-time formatting and conversions
- File and directory path normalization
- URL manipulation and redirection
- Custom HTTP headers setting
- Query parameter handling
- Input validation and filtering

## Installation
This library can be easily installed via Composer. Run the following command to add it to your project:

```bash
composer require carry0987/utils
```

## Usage
Utilize the static methods provided by the `Utils` class in your code without instantiating the class. Here's an example on how to use the array sorting feature:

```php
use carry0987\Utils\Utils;

$array = [
    ['id' => 3, 'name' => 'Alice'],
    ['id' => 1, 'name' => 'Charlie'],
    ['id' => 2, 'name' => 'Bob'],
];

$sortedArray = Utils::sortData($array, 'ASC', 'id');
print_r($sortedArray);
```

For more detailed information on each method, refer to the inline comments in the source code.

## Contributing
Contributions, issues, and feature requests are welcome. Feel free to check the issues page if you want to contribute.

## License
This project is open-sourced software licensed under the [MIT license](LICENSE).
