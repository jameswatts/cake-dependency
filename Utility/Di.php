<?php
/**
 * Dependency Injection Container
 *
 * Contains and delegates the registered dependencies.
 *
 * Cake Dependency (http://github.com/jameswatts/cake-dependency)
 * Copyright 2013, James Watts (http://github.com/jameswatts)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2013, James Watts (http://github.com/jameswatts)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Dependency.Utility
 * @since         CakePHP(tm) v 2.4.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Object', 'Core');

/**
 * Dependency Injection Container
 *
 * Contains and delegates the registered dependencies.
 *
 * @package       Dependency.Utility
 */
abstract class Di extends Object {

/**
 * The global container scope.
 */
	const GLOBAL_SCOPE = '*';

/**
 * The default container scope.
 */
	const DEFAULT_SCOPE = 'default';

/**
 * Defines a dependency object.
 */
	const TYPE_OBJECT = 1;

/**
 * Defines a dependency callback.
 */
	const TYPE_CALLBACK = 2;

/**
 * Defines a dependency configuration.
 */
	const TYPE_CONFIG = 3;

/**
 * The current container scope.
 *
 * @var string
 */
	protected static $_scope = self::DEFAULT_SCOPE;

/**
 * The locked scopes.
 *
 * @var array
 */
	protected static $_lock = array();

/**
 * The central registry for dependencies.
 *
 * @var array
 */
	protected static $_registry = array(
		self::GLOBAL_SCOPE => array(),
		self::DEFAULT_SCOPE => array()
	);

/**
 * The dependency injection container.
 *
 * @var array
 */
	protected static $_container = array(
		self::GLOBAL_SCOPE => array(),
		self::DEFAULT_SCOPE => array()
	);

/**
 * The registered class observers.
 *
 * @var array
 */
	protected static $_observers = array(
		self::GLOBAL_SCOPE => array(),
		self::DEFAULT_SCOPE => array()
	);

/**
 * Calls a dependency via the factory interface. Only the first argument is 
 * accepted as a configuration array, which if passed at runtime upon resolving 
 * a dependency the original instance will not be modified.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param array $arguments The dependency options.
 * @return mixed
 */
	public static function __callStatic($name, $arguments) {
		return self::get($name, (isset($arguments[0]))? $arguments[0] : array());
	}

/**
 * Changes or returns the current scope.
 *
 * @static
 * @param string $scope The new dependency scope.
 * @return string
 */
	public static function scope($scope = null) {
		if (isset($scope)) {
			self::$_scope = $scope;
		} else {
			return self::$_scope;
		}
	}

/**
 * Registers a new dependency.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param mixed $options The dependency options. Can be an object, a closure, a 
 * valid callable, or an array of options. If an object, this will be assumed 
 * as the dependency. Both closures and callables will always be evaluated upon 
 * every request.
 * @throws CakeException if the scope is currently locked.
 */
	public static function add($name, array $options = array()) {
		$scope = (isset($options['scope']))? $options['scope'] : self::$_scope;
		if (isset(self::$_lock[$scope])) {
			throw new CakeException(sprintf('Cannot register dependency "%s", scope locked: %s', $name, $scope));
		}
		self::$_registry[$scope][$name] = $options;
	}

/**
 * Updates an existing dependency.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param mixed $options The dependency options.
 * @throws CakeException if the scope is currently locked.
 */
	public static function set($name, $options = array()) {
		$current = null;
		$scope = (is_array($options) && isset($options['scope']))? $options['scope'] : self::$_scope;
		if (isset(self::$_lock[$scope])) {
			throw new CakeException(sprintf('Cannot register dependency "%s", scope locked: %s', $name, $scope));
		}
		if (isset(self::$_registry[$scope][$name])) {
			$current = self::$_registry[$scope][$name];
		}
		self::$_registry[$scope][$name] = (is_array($current) && is_array($options)) ? array_replace_recursive($current, $options) : $options;
	}

/**
 * Determines if a dependency has been registered.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param string $scope The optional scope to search, otherwise all.
 * @return boolean
 */
	public static function has($name, $scope = null) {
		if (isset($scope)) {
			return array_key_exists($name, self::$_registry[$scope]);
		} else {
			foreach (self::$_registry as $scope) {
				if (array_key_exists($name, self::$_registry[$scope])) {
					return true;
				}
			}
		}
		return false;
	}

/**
 * Resolves a previously registered dependency. If configuration options are 
 * passed at runtime upon resolving a dependency the original instance will not 
 * be modified.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param array $options The dependency options.
 * @return mixed
 */
	public static function get($name, array $options = array()) {
		$scope = (isset($options['scope']))? $options['scope'] : self::$_scope;
		if (!array_key_exists($name, self::$_container[$scope]) && !array_key_exists($name, self::$_container[self::GLOBAL_SCOPE])) {
			self::_create($name, $options);
		}
		$container = ($scope === self::GLOBAL_SCOPE)? self::$_container[$scope] : array_replace_recursive(self::$_container[self::GLOBAL_SCOPE], self::$_container[$scope]);
		return self::_resolve($container[$name]['type'], $container, $scope, $name, $container[$name]['data'], $options);
	}

/**
 * Lazy loads a dependency as the argument of another.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param array $options The dependency options.
 * @return mixed
 */
	public static function load($name, array $options = array()) {
		return function() use ($name, $options) {
			return Di::get($name, $options);
		};
	}

/**
 * Destroys the instance for a dependency, forcing reinstantiating upon the 
 * next request.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param string $scope The optional dependency scope, defaults to DEFAULT_SCOPE.
 */
	public static function clear($name, $scope = self::DEFAULT_SCOPE) {
		if (isset(self::$_container[$scope][$name])) {
			self::$_container[$scope][$name]['instance'] = null;
		}
	}

/**
 * Attaches an observer for a specif class or set of classes.
 *
 * @static
 * @param mixed $class The class name or array of class names.
 * @param array $options The dependency options.
 * @return mixed
 */
	public static function observe($class, array $options = array()) {
		if (is_array($class)) {
			foreach ($class as $name) {
				self::$_observers[self::$_scope][$name] = $options;
			}
		} else {
			self::$_observers[self::$_scope][$class] = $options;
		}
	}

/**
 * Locks the container for a certain scope.
 *
 * @static
 * @param string $scope The optional dependency scope, defaults to DEFAULT_SCOPE.
 * @return void
 */
	public static function lock($scope = self::DEFAULT_SCOPE) {
		self::$_lock[$scope] = true;
	}

/**
 * Unlocks the container for a certain scope.
 *
 * @static
 * @param string $scope The optional dependency scope, defaults to DEFAULT_SCOPE.
 * @return void
 */
	public static function unlock($scope = self::DEFAULT_SCOPE) {
		unset(self::$_lock[$scope]);
	}

/**
 * Applies the options for the observed classes.
 *
 * @static
 * @param array $classes The classes to observe.
 * @param string $scope The optional dependency scope, defaults to current scope.
 * @return array
 */
	protected static function _observers(array $classes = array(), $scope = null) {
		if (!isset($scope)) {
			$scope = self::$_scope;
		}
		$options = array();
		if ($scope === self::GLOBAL_SCOPE) {
			$observers = self::$_observers[$scope];
		} else {
			$observers = array_replace_recursive(self::$_observers[self::GLOBAL_SCOPE], self::$_observers[$scope]);
		}
		foreach ($classes as $class) {
			if (isset($observers[$class])) {
				$options = array_replace_recursive($options, $observers[$class]);
			}
		}
		return $options;
	}

/**
 * Creates the dependency object.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param array $arguments The dependency options.
 * @throws CakeException if "className" or "classPath" are missing, or if the dependency has not been defined.
 * @return void
 */
	protected static function _create($name, array $options = array()) {
		$scope = self::$_scope;
		if (isset($options['scope'])) {
			$scope = $options['scope'];
		}
		if (isset(self::$_registry[$scope][$name])) {
			if (is_object(self::$_registry[$scope][$name])) {
				self::$_container[$scope][$name] = array(
					'type' => self::TYPE_OBJECT,
					'data' => self::$_registry[$scope][$name],
					'instance' => null
				);
			} else if (is_callable(self::$_registry[$scope][$name])) {
				self::$_container[$scope][$name] = array(
					'type' => self::TYPE_CALLBACK,
					'data' => self::$_registry[$scope][$name],
					'instance' => null
				);
			} else {
				$data = array_replace_recursive(self::$_registry[$scope][$name], $options);
				if (!isset($data['className'])) {
					throw new CakeException(sprintf('Dependency "%s" missing option: className', $name));
				}
				if (!isset($data['classPath'])) {
					throw new CakeException(sprintf('Dependency "%s" missing option: classPath', $name));
				}
				App::uses($data['className'], $data['classPath']);
				self::$_container[$scope][$name] = array(
					'type' => self::TYPE_CONFIG,
					'data' => self::$_registry[$scope][$name],
					'instance' => null
				);
			}
		} else {
			throw new CakeException(sprintf('Dependency not found: %s', $name));
		}
	}

/**
 * Resolves a dependency at runtime.
 *
 * @static
 * @param integer $type The dependency type.
 * @param array $container The dependency container.
 * @param string $scope The dependency scope.
 * @param string $name The name of the dependency.
 * @param array $data The dependency options.
 * @param array $options The runtime options passed.
 * @throws CakeException if an unknown dependency type is specified.
 * @return mixed
 */
	protected static function _resolve($type, $container, $scope, $name, $data, $options) {
		switch ($type) {
			case self::TYPE_OBJECT:
				return self::_resolveObject($container, $scope, $name, $data, $options);
			case self::TYPE_CALLBACK:
				return self::_resolveCallback($container, $scope, $name, $data, $options);
			case self::TYPE_CONFIG:
				return self::_resolveConfig($container, $scope, $name, $data, $options);
			default:
				throw new CakeException(sprintf('Unknown dependency type: %s', $type));
		}
	}

/**
 * Resolves the dependency as an object.
 *
 * @static
 * @param array $container The dependency container.
 * @param string $scope The dependency scope.
 * @param string $name The name of the dependency.
 * @param array $data The dependency options.
 * @param array $options The runtime options passed.
 * @return mixed
 */
	protected static function _resolveObject($container, $scope, $name, $data, $options) {
		if ($data instanceof Closure) {
			return (self::$_container[$scope][$name]['instance'] = $data(array_merge($data, $options)));
		}
		return (self::$_container[$scope][$name]['instance'] = $data);
	}

/**
 * Resolves the dependency as a callback.
 *
 * @static
 * @param array $container The dependency container.
 * @param string $scope The dependency scope.
 * @param string $name The name of the dependency.
 * @param array $data The dependency options.
 * @param array $options The runtime options passed.
 * @return mixed
 */
	protected static function _resolveCallback($container, $scope, $name, $data, $options) {
		return (self::$_container[$scope][$name]['instance'] = call_user_func_array($data, array(array_merge($data, $options))));
	}

/**
 * Resolves the dependency as a configuration. If configuration options are 
 * passed at runtime upon resolving a dependency the original instance will not 
 * be modified.
 *
 * @static
 * @param array $container The dependency container.
 * @param string $scope The dependency scope.
 * @param string $name The name of the dependency.
 * @param array $data The dependency options.
 * @param array $options The runtime options passed.
 * @throws CakeException if the class for the dependency does not exist.
 * @return mixed
 */
	protected static function _resolveConfig($container, $scope, $name, $data, $options) {
		if (isset($options['scope'])) {
			unset($options['scope']);
		}
		if (isset($options['className'])) {
			$class = $options['className'];
			App::uses($class, ($options['classPath'])? $options['classPath'] : $data['classPath']);
		} else {
			$class = $data['className'];
		}
		if (!class_exists($class)) {
			throw new CakeException(sprintf('Dependency class is not defined: %s', $class));
		}
		$interfaces = class_implements($class);
		$parents = class_parents($class);
		$data = array_replace_recursive($data, self::_observers(array_merge(array($class), $interfaces, $parents), $scope));
		$instance = (!count($options))? $container[$name]['instance'] : null;
		if (!$instance || (isset($data['fresh']) && $data['fresh'])) {
			if (isset($data['implement'])) {
				self::_implements($name, $data['implement'], $interfaces);
			}
			if (isset($data['extend'])) {
				self::_extends($name, $data['extend'], $parents);
			}
			if (isset($data['params']) || isset($options['params'])) {
				$reflection = new ReflectionMethod($class, '__construct');
				$arguments = array();
				foreach ($reflection->getParameters() as $param) {
					$paramName = $param->getName();
					if (isset($options['params'][$paramName])) {
						$arguments[] = $options['params'][$paramName];
					} else if (isset($data['params'][$paramName])) {
						$arguments[] = (is_object($data['params'][$paramName]) && $data['params'][$paramName] instanceof Closure)? $data['params'][$paramName]() : $data['params'][$paramName];
					} else {
						$arguments[] = ($param->isOptional())? $param->getDefaultValue() : null;
					}
				}
				$reflection = new ReflectionClass($class);
				$instance = $reflection->newInstanceArgs($arguments);
			} else {
				$instance = new $class();
			}
		}
		if (isset($data['setters']) || isset($options['setters'])) {
			$setters = array_replace_recursive((isset($data['setters']))? $data['setters'] : array(), (isset($options['setters']))? $options['setters'] : array());
			foreach ($setters as $setter => $params) {
				$reflection = new ReflectionMethod($instance, $setter);
				$arguments = array();
				foreach ($reflection->getParameters() as $param) {
					$paramName = $param->getName();
					if (isset($params[$paramName])) {
						$arguments[] = (is_object($params[$paramName]) && $params[$paramName] instanceof Closure)? $params[$paramName]() : $params[$paramName];
					} else {
						$arguments[] = ($param->isOptional())? $param->getDefaultValue() : null;
					}
				}
				$reflection->invokeArgs($instance, $arguments);
			}
		}
		return (count($options) > 0)? $instance : (self::$_container[$scope][$name]['instance'] = $instance);
	}

/**
 * Checks that the dependency implements a required interface.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param mixed $required The required interface as a string, or an array of strings.
 * @param array $interfaces The interfaces the dependency implements.
 * @throws CakeException if the dependency doesn't implement a required interface.
 * @return void
 */
	protected static function _implements($name, $required, array $interfaces = array()) {
		if (is_array($required)) {
			if (count(array_merge($required, $interfaces)) === (count($required)+count($interfaces))) {
				throw new CakeException(sprintf('Dependency "%s" does not implement a required interface: %s', $name, implode(', ', $interfaces)));
			}
		} else if (!in_array($required, $interfaces)) {
			throw new CakeException(sprintf('Dependency "%s" does not implement a required interface: %s', $name, $required));
		}
	}

/**
 * Checks that the dependency extends a required class.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param mixed $required The required class as a string, or an array of strings.
 * @param array $classes The classes the dependency extends.
 * @throws CakeException if the dependency doesn't extend a required class.
 * @return void
 */
	protected static function _extends($name, $required, array $classes = array()) {
		if (is_array($required)) {
			if (count(array_merge($required, $classes)) === (count($required)+count($classes))) {
				throw new CakeException(sprintf('Dependency does not extend a required class: %s', implode(', ', $classes)));
			}
		} else if (!in_array($required, $classes)) {
			throw new CakeException(sprintf('Dependency "%s" does not extend a required class: %s', $name, $required));
		}
	}

}

