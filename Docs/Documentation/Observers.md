Observers
=========

Using **observers** allows you to modify services and their dependencies based upon interfaces they implement or classes they extend. An observer *watches* the service instances for certain classes, and if found, applies modifications to the dependency configuration on the fly.

```php
Di::observe('MyClass' array(
	'params' => array(
		'example' => Di::load('Something')
	)
));
```

The previous observer would define the param "example" for any service of which it's instance which uses, implements or extends ```MyClass```.

You can also observe multiple interfaces and classes at once, which would apply the changes for any match.

```php
Di::observe(array(
	'MyInterface',
	'AbstractClass'
), array(
	'params' => array(
		'example' => Di::load('Something')
	)
));
```

Using observers is a clean way to inject dependencies on lower level abstractions, especially for classes which require common dependencies, as this removes the requirement to explicitly set the configuration for each service.

**IMPORTANT:** Keep in mind that **observers** are aware of scope. They will apply configuration changes to any matches found within the scope set when the **observer** was defined. To apply changes globally, the scope should be changed to ```Di::GLOBAL_SCOPE``` using the ```Di:scope()``` method *before* defining the **observer**.

