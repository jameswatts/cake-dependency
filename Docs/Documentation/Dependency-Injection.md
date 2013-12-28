Dependency Injection
====================

Using dependency injection allows you to specify dependencies separate from the instantiation logic, favoring composition from a configuration external to the class's hierarchy.

As a more elborate example, here we have a class which relies upon another dependency. For this, we'll create a class which will serve as the look-up for the book's "title" based upon it's ISBN number.

```php
class Library {

	protected $_books = array(
		'1613823312' => 'Jungle Book'
	);

	public function find($isbn) {
		return $this->_books[$isbn];
	}

}
```

This dependency will be registered so it can be resolved by the calling class.

```php
Di::add('Finder' array(
	'className' => 'Library',
	'classPath' => 'Lib'
));
```

There are now *2* ways to inject dependencies, using the **constructor** or by using a **setter**.

Constructor Injection
---------------------

This first method injects dependencies via the class **constructor**, namely the ```__construct()``` method.

Here, the class accepts the ```Library``` class as a dependency, and saves it as a reference in the internal *$_Library* property of the object.

```php
class Book {

	protected $_Library = null;

	public $esbn = '1613823312';

	public function __construct(Library $library) {
		$this->_Library = $library;
	}

	public function getTitle() {
		$this->_Library->find($this->esbn);
	}

}
```

The dependency can now be injected into the ```Book``` class via the "params" array, where the key is the reflected name of the method's argument, which in this case is *$library*.

```php
Di::add('JungleBook' array(
	'className' => 'Book',
	'classPath' => 'Lib'
	'params' => array(
		'library' => Di::load('Finder')
	)
));
```

Here the ```Di::load()``` method is used to lazy load the dependency, as it shouldn't be instantiated until required.

Now, when the "JungleBook" service is loaded, it's dependency will be resolved automatically, by injecting it via the class's **constructor**.

```php
$book = Di::JungleBook();

echo $book->getTitle(); // "Jungle Book"
```

Setter Injection
----------------

The other method of injecting dependencies is via a **setter**. This is a method designated to add a dependency to the object *after* creating an instance.

Here we'll create a ```setLibrary()``` method which sets up the dependency.

```php
class Book {

	protected $_Library = null;

	public $esbn = '1613823312';

	public function setLibrary(Library $library) {
		$this->_Library = $library;
	}

	public function getTitle() {
		$this->_Library->find($this->esbn);
	}

}
```

Then, form the service, we'll inject the dependency using the **setter** method and defining the *$library* argument to use the dependency, for example:

```php
Di::add('JungleBook' array(
	'className' => 'Book',
	'classPath' => 'Lib'
	'setters' => array(
		'setLibrary' => array(
			'library' => Di::load('Finder')
		)
	)
));
```

Here the ```Di::load()``` method is also used to lazy load the dependency, as it shouldn't be instantiated until required.

The additional benefit of using a **setter** is that the dependency can be switched later on by overloading the configuration options when requesting the service, by using ```Di::set()``` to modify the options, or by using an **observer** to watch for the class.

