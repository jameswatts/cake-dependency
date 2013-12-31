Examples
========

The following are a few basic examples for use with the **Dependency** plugin.

Register a Dependency
---------------------

Register a simple service.

```php
Di::add('Example', array(
	'className' => 'MyClass',
	'classPath' => 'Path/To/My/Class'
));
```

By convention, service names are *CamelCase*.

Register an Object
------------------

Register an instance as a service.

```php
Di::add('Example', new MyClass());
```

You can also register an existing instance of a class.

```php
Di::add('Example', $object);
```

Register a Callback
-------------------

Register an anonymous function which resolves a service.

```php
Di::add('Example', function() {
	return new MyClass();
});
```

You can also use a closure to capture variables used when creating the instance.

```php
Di::add('Example', function() use ($something) {
	if ($something) {
		return new MyClass();
	} else {
		return new OtherClass();
	}
});
```

Register a Callable
-------------------

Register a callable function which resolves a service.

```php
Di::add('Example', array($object, 'createExample'));
Di::add('Example', array('SomeClass', 'createExample'));
Di::add('Example', 'SomeClass::createExample');
```

Lazy Loading
------------

Lazy load a dependency.

```php
$callback = Di::load('Example');
```

Lazy loading is important to specify a service without immediately instantiating it.

Constructor Injection
---------------------

Register a service with constructor injection.

```php
Di::add('Example', array(
	'className' => 'MyClass',
	'classPath' => 'Path/To/My/Class',
	'params' => array(
		'foo' => Di::load('Bar')
	)
));
```

This will instantiate ```MyClass``` passing an instance of the "Bar" service to the *$foo* constructor argument, for example:

```php
class MyClass {

	public function __construct(SomeClass $foo) {
		// your code
	}

}
```

Internally this resolves as:

```php
$example = new MyClass(Di::get('Bar'));
```

Injected services can in turn have their own dependencies.

Setter Injection
----------------

Register a service with a setter injection.

```php
Di::add('Example', array(
	'className' => 'MyClass',
	'classPath' => 'Path/To/My/Class',
	'setters' => array(
		'setFoo' => array(
			'foo' => Di::load('Bar')
		)
	)
));
```

This will instantiate ```MyClass```, then call ```setFoo()```, passing it an instance of the "Bar" service, for example:

```php
$example = new MyClass();
$example->setFoo(Di::get('Bar'));
```

Injected services can in turn have their own dependencies.

Loading a Dependency
--------------------

Load a dependency on the fly.

```php
$example = Di::Example();
```

You can also explicitly get the dependency from the container.

```php
$example = Di::get('Example');
```

Checking for a Dependency
-------------------------

Check if a dependency has been registered.

```php
$exists = Di::has('Example');
```

If a scope is defined as the second argument of ```Di::has()``` the search will be limited to that scope. Otherwise all scopes will be searched.

Overriding Configuration
------------------------

Override configuration options at runtime.

```php
$example = Di::Example(array(
	'params' => array(
		'something' => 'Hello World'
	)
));
```

**IMPORTANT:** Configuration changes at runtime do not modify the stored instance if one exists.

Configuration options can also be modified previous to instantiation using ```Di::set()```.

```php
Di::set('Example', array(
	'params' => array(
		'something' => 'Hello World'
	)
));
```

The values of options, if an array, are merged or overwritten if they're already defined.

Changing Scope
--------------

You can change the scope of the dependency container at any time.

```php
Di::scope('something');
```

Dependencies will now be searched for under that scope. The default scope is ```Di::DEFAULT_SCOPE```.

You can also register a dependency in the ```Di::GLOBAL_SCOPE```, which will make it always available, irrespective of the current scope. To get the current scope you can call ```Di::scope()``` without an argument.

```php
$scope = Di::scope();
```

You can also specify a scope when loading a dependency. This will not modify the scope globally, only for the service requested.

```php
$example = Di::Example(array(
	'scope' => 'products'
));
```

If a dependency is requested from a scope where it does not exist an exception is thrown.

Registering an Observer
-----------------------

Register an observer for an interface or class.

```php
Di::observe('MyInterface', array(
	'setters' => array(
		'setFactory' => Di::load('Factory')
	)
));
```

Multiple interfaces or classes can also be observed at once.

```php
Di::observe(array(
	'MyInterface',
	'AbstractClass'
), array(
	'setters' => array(
		'setFactory' => Di::load('Factory')
	)
));
```

