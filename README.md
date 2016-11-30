# Laravel 5 Conoha Object Handler

Laravel 5 Package to use [Conoha](https://www.conoha.jp/en/) Object Storage.

## Installation

Install the package via [Composer](http://getcomposer.org).

```sh
composer require okashoi/laravel5-conoha-object-handler
```

To use the package, register the service provider in `config/app.php`.

```php
    'providers' => [
        // ...
        Okashoi\Laravel5ConohaObjectHandler\ConohaObjectServiceProvider::class,
    ]
```

To configure your connection settings, execute the following command.

```sh
php artisan vendor:publish --provider="Okashoi\Laravel5ConohaObjectHandler\ConohaObjectServiceProvider"
```

Then set the following environment variables in `.env` file.

```
CONOHA_TENANT_ID
CONOHA_USERNAME
CONOHA_PASSWORD
```

## Usage

### Create the Instance

First of all you have to create an `ObjectHandler` instance.

```php
use Okashoi\Laravel5ConohaObjectHandler\ObjectHandler;
 
$handler = new ObjectHandler();
```

Optionally, you can cache the auth token by specifying the cache key.
(It is recommended. By default, the instance gets a new auth token per a request.)

```php
use Okashoi\Laravel5ConohaObjectHandler\ObjectHandler;
 
// cache the auth token with key 'conoha_token'
$handler = new ObjectHandler('conoha_token');
```

Caching is implemented using [Laravel Cache API](https://laravel.com/docs/5.3/cache).

### Get a List of Objects

Example

```php  
$objects = $handler->getList('container_name');
```


### Upload an Object

Example

```php  
$handler->upload('container_name', 'object_name.txt', '/path/to/file/to/upload.txt', 'text/plain');
```

### Download an Object

The method `download()` will return [GuzzleHttp](http://docs.guzzlephp.org/en/latest/) response.

You can access the file content by `getBody()` method.

```php  
$response = $handler->download('container_name', 'object_name.txt');

echo $response->getBody();
```

Or you can make download response as follows.

```php      
$response = $handler->download('container_name', 'object_name.txt');
 
return reponse($response->getBody())->header('Content-Type', $response->getHeader('Content-Type'));
```

### Delete an Object

Example

```php
$handler->delete('container_name', 'object_name.txt');
```
