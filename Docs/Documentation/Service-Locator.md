Service Locator
===============

The **service locator** is the main method used to resolve *dependencies* with the plugin. Each **service** (instance) is mapped internally to a *scope* and a dependency *name*.

The plugin also searches for a file named ```dependency.php``` in the ```app/Config``` directory. This helps keep your dependency configurations separate from your bootstrap settings.

Registering Services
--------------------

As an example, here we have a class which represents a book.

```php
class Book {

	public $title = 'Jungle Book';

}
```

To register the previous class as a dependency you would call the ```Di::add()``` method, with the *name* and *options*, for example:

```php
Di::add('JungleBook', array(
	'className' => 'Book',
	'classPath' => 'Lib'
));
```

The second argument of ```Di::add()``` can be an *object*, an *anonymous function* or a *closure* which returns the dependency, or a configuration *array*.

The configuration options available for the array are the following:

* **className:** The name of the class to load.
* **classPath:** The location of the class, using the same syntax as with ```App::uses()```.
* **implement:** Requires that the service class implement any of the interfaces defined in the array of class names.
* **extend:** Requires that the service class extend any of the classes defined in the array of class names.
* **params:** The **constructor** parameters for the instantiation of the dependency as a key => value array. The keys are the reflected names of the **constructor** arguments. Use ```Di::load()``` to lazy load dependencies as the value of an argument.
* **setters:** The **setters** to call after creating an instance as a key => value array. The key is the method name, while the value is a key => value array of reflected parameters, the same as with the "params" option.
* **scope:** The scope to store the dependency under, otherwise the current scope is used. You can change the current scope at any time with ```Di::scope('something')```. If the ```Di::GLOBAL_SCOPE``` is used the dependency will always resolve irrespective of the current scope.
* **fresh:** Setting this to *true* will force the dependency to always create a new instance. However, this will only take effect in dependency chains if the option is set along the chain, or, if the dependency is injected via a **setter**.

Configuration options can also be passed to the dependency factory or ```Di::get()``` at runtime when resolving an instance. However, it's important to note that, when overriding the configuration options, the stored instance (if one exists) is *not* modified. It's assumed that, if you're making configuration changes at runtime, you're expecting a new instance. To override the configuration options on dependencies which have already been registered use ```Di::set()```.

Resolving Services
------------------

To create an instance of the dependency you can now call it as a *static* method of the ```Di``` class, for example:

```php
$book = Di::JungleBook();

echo $book->title; // "Jungle Book"
```

You can also use the ```Di::get()``` method to return an instance explicitly.

```php
$book = Di::get('JungleBook');
```

Both ways accept an options array, which can override the dependency configuration at runtime.

Service Scope
-------------

Service scopes allow you to set contextual boundaries for dependencies. This allows the same service to have multiple setups, or for a collection of related dependencies to exist under a common scope.

```php
Di::scope('products');
```

When the service locator looks-up the service named "JungleBook", it searches under the current scope. If the scope hasn't been changed or set, it's the default scope, which is ```Di::DEFAULT_SCOPE```.

Services can also be registered globally, and therefore are available from any scope. To make a service global simply set the current scope to ```Di::GLOBAL_SCOPE``` before configuring the dependency, or use the "scope" option to explicitly make it global. To read the current scope call ```Di::scope()``` without an argument.

Additionally, there exists the option to *lock* and *unlock* scopes against new dependencies being registered.

```php
Di::lock('products');
```

This would lock the "products" scope for the registry of new services, throwing an exception if an attempt is made. To unlock the scope you would simply call ```Di::unlock("products")```.

