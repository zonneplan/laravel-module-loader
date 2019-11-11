# Module loader inside your Laravel app

The `zonneplan/laravel-module-loader` package provides an easy to use module loader 
which can be used to modulize your project.

## How to use

First install the package, see the installation section.

#### Creating a new module:

- Create a folder, for example: `Modules` in the app directory.
- After that create another one, for example: `User`.
- In the root of that folder insert a `UserServiceProvider` which extends our abstract `Module` class.
- Implement the function `getModuleNamespace()` like:
``` php
namespace Modules\User;

use Zonneplan/ModuleLoader/Module;

class UserServiceProvider extends Module
{
    public function getModuleNamespace(): string
    {
        return 'user';
    }
}
```
- Register the `UserServiceProvider` in the `config/app.php` file.
``` php
'providers' => [
    Modules\User\UserServiceProvider::class
]
```

#### Structure within the module:
The expected structure is seen below. Most of it is optional.

```$xslt
app
├── Modules
    ├──MyModule
       ├──Config
       ├──Console
       ├──Database
          ├──Factories
          ├──Migrations
       ├──Exceptions
       ├──Http
          ├──Controllers
          ├──Middleware
          ├──Requests
          ├──Resources
       ├──Resources
          ├──lang
          ├──views
       ├──Routes
          ├──web.php            
          ├──api.php            
          ├──channels.php       
          ├──console.php        
       ├──MyModuleServiceProvider.php
       ├──tests
```

#### Access a view from the module:
To access a view from a module it will look like `'my-module::path.to.view'`

For example:
``` php
// In a controller
view('user::index');

// In a blade file
@include('user::index');
@include('user::partials.form');
````
 
 #### Registering Policies:
 To register policies overwrite the `$policies` variable in the ServiceProvider of your module
 
 For example:
``` php
protected $policies = [
    MyModel::class => MyModelPolicy::class,
];
``` 

#### Registering Middleware:
 To register middleware overwrite the `$middleware` variable in the ServiceProvider of your module
 
 For example:
``` php
protected $middleware = [
    'my-middleware' => MyMiddleware::class,
];
```

#### Registering Events & Listeners:
 To register events & listeners overwrite the `$listen` variable in the ServiceProvider of your module
 
 For example:
``` php
protected $listen = [
    MyEvent::class => [
        MyListener::class
    ],
];
```

#### Registering Event Subscribers:
 To register event subscribers overwrite the `$subscribe` variable in the ServiceProvider of your module
 
 For example:
``` php
protected $subscribe = [
    MySubscriber::class
];
```

## Requirements

This package requires at least Laravel 5.8 or higher, PHP 7.2 or higher 

## Installation

`composer require zonneplan/laravel-module-loader`

The package will automatically register itself.

Register the namespace: `"Modules\\": "app/Modules"` in `composer.json` like:
```$xslt
 "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "app/Modules"
        },
        ...
``` 

In the following files: `web.php`, `api.php`, `channels.php`, `console.php`  insert respectively:
``` php
Route::modules('web');
Route::modules('api');
Route::modules('channels');
Route::modules('console');
```

Routes will then be searched in the `Routes` folder of every module, but don't necessarily have to exist: 

```$xslt
├──MyModule
   ├──Routes
      ├──web.php      (optional)
      ├──api.php      (optional)
      ├──channels.php (optional)
      ├──console.php  (optional)
```

There is also the option to insert:
``` php
Route::modules();
```
Routes will then be searched in `routes.php` at the root of the module 

## Authors

* **Aron Rotteveel**
* **Dennis Stolmeijer** 
* **Wout Hoeve** 
* **Johnny Borg** 
* **Rick Gout**
* **Thijs Nijholt** 
