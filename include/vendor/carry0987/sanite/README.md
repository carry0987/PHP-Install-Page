# Sanite
[![Packgist](https://img.shields.io/packagist/v/carry0987/sanite.svg?style=flat-square)](https://packagist.org/packages/carry0987/sanite) 
![CI](https://github.com/carry0987/Sanite/actions/workflows/php.yml/badge.svg)  
Sanite is a PHP library that provide base CRUD structure and methods, using PDO.

## Getting Started
Make sure you have `Sanite` installed. If not, you can install it with the following command:

```bash
composer require carry0987/sanite
```

After installation, you can include `Sanite` in your project and start using it.

## Establishing a Database Connection

Use `Sanite` to establish a database connection:

```php
use carry0987\Sanite\Sanite;

// Database connection settings
$config = array(
    'host' => 'mariadb',
    'database' => 'dev_sanite',
    'username' => 'test_user',
    'password' => 'test1234',
    'port' => 3306, // Optional
    'charset' => 'utf8mb4' // Optional
);

// Create a database connection
$sanite = new Sanite($config);
```

## Using a Data Model

Create your own data models to perform CRUD operations. Here's an example of using `UserModel` to retrieve user data.

First, ensure your model extends `DataReadModel` (or corresponding `DataCreateModel`, `DataDeleteModel`, `DataUpdateModel`):

```php
namespace carry0987\Sanite\Example;

use carry0987\Sanite\Models\DataReadModel;

class UserModel extends DataReadModel
{
    // Implement your methods, for example:
    public function getUserById(int $userId)
    {
        $queryArray = [
            'query' => 'SELECT * FROM user WHERE uid = ? LIMIT 1',
            'bind'  => 'i',  // This value needs to be relative when using DBUtil::getPDOType
        ];
        $dataArray = [$userId];

        return $this->getSingleData($queryArray, $dataArray);
    }

    public function getAllUsers()
    {
        $queryArray = [
            'query' => 'SELECT * FROM user'
        ];

        return $this->getMultipleData($queryArray);
    }
}
```

Then, you can use your model like so:

```php
use carry0987\Sanite\Example\UserModel;

// Instantiate UserModel
$userModel = new UserModel($sanite);

// Retrieve user information for user with ID 1
$user = $userModel->getUserById(1);
$users = $userModel->getAllUsers();

print_r($user);
print_r($users);
```

## Exception Handling

`Sanite` defines a specific exception class `DatabaseException`. Capture and handle it appropriately in your code:

```php
try {
    // ... attempt some database operations ...
} catch (\carry0987\Sanite\Exceptions\DatabaseException $e) {
    // ... handle database exception ...
    echo "Error: " . $e->getMessage();
}
```
