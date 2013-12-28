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
 * Calls a dependency via the facotry interface.
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
 */
	public static function add($name, array $options = array()) {
		self::$_registry[(isset($options['scope']))? $options['scope'] : self::$_scope][$name] = $options;
	}

/**
 * Updates an existing dependency.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param array $options The dependency options.
 */
	public static function set($name, array $options = array()) {
		$current = null;
		$scope = (isset($options['scope']))? $options['scope'] : self::$_scope;
		if (isset(self::$_registry[$scope][$name])) {
			$current = self::$_registry[$scope][$name];
		}
		self::$_registry[$scope][$name] = (is_array($current))? array_replace_recursive($current, $options) : $options;
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
 * Resolves a previously registered dependency.
 *
 * @static
 * @param string $name The name of the dependency.
 * @param array $options The dependency options.
 * @return mixed
 */
	public static function get($name, array $options = array()) {
		$scope = self::$_scope;
		if (isset($options['scope'])) {
			$scope = $options['scope'];
		}
		if (!array_key_exists($name, self::$_container[$scope]) && !array_key_exists($name, self::$_container[self::GLOBAL_SCOPE])) {
			self::_create($name, $options);
		}
		if ($scope === self::GLOBAL_SCOPE) {
			$container = self::$_container[$scope];
		} else {
			$container = array_replace_recursive(self::$_container[self::GLOBAL_SCOPE], self::$_container[$scope]);
		}
		$data = $container[$name]['data'];
		switch ($container[$name]['type']) {
			case self::TYPE_OBJECT:
				if ($data instanceof Closure) {
					return (self::$_container[$scope][$name]['instance'] = $data(array_merge($data, $options)));
				}
				return (self::$_container[$scope][$name]['instance'] = $data);
			case self::TYPE_CALLBACK:
				return (self::$_container[$scope][$name]['instance'] = call_user_func_array($data, array(array_merge($data, $options))));
			default:
				$class = $data['className'];
				if (!class_exists($data['className'])) {
					throw new CakeException('Dependency class is not defined: ' . $data['className']);
				}
				$interfaces = class_implements($class);
				$parents = class_parents($class);
				$data = array_replace_recursive($data, self::_observers(array_merge(array($class), $interfaces, $parents), $scope));
				if (!$container[$name]['instance'] || (isset($options['params']) && is_array($options['params'])) || (isset($data['fresh']) && $data['fresh'])) {
					if (isset($data['implement'])) {
						if (is_array($data['implement'])) {
							if (count(array_merge($data['implement'], $interfaces)) === (count($data['implement'])+count($interfaces))) {
								throw new CakeException('Dependency "%s" does not implement a required interface: %s', $name, implode(', ', $interfaces));
							}
						} else if (!in_array($data['implement'], $interfaces)) {
							throw new CakeException('Dependency "%s" does not implement a required interface: %s', $name, $data['implement']);
						}
					}
					if (isset($data['extend'])) {
						if (is_array($data['extend'])) {
							if (count(array_merge($data['extend'], $parents)) === (count($data['extend'])+count($parents))) {
								throw new CakeException('Dependency does not extend a required class: ' . implode(', ', $parents));
							}
						} else if (!in_array($data['extend'], $parents)) {
							throw new CakeException('Dependency "%s" does not extend a required class: %s', $name, $data['extend']);
						}
					}
					if ((isset($data['params']) && is_array($data['params'])) || (isset($options['params']) && is_array($options['params']))) {
						$reflection = new ReflectionMethod($class, '__construct');
						$arguments = array();
						foreach ($reflection->getParameters() as $param) {
							$paramName = $param->getName();
							if (isset($options['params'][$paramName])) {
								$arguments[] = $options['params'][$paramName];
							} else if (isset($data['params'][$paramName])) {
								if (is_object($data['params'][$paramName]) && $data['params'][$paramName] instanceof Closure) {
									$arguments[] = $data['params'][$paramName]();
								} else {
									$arguments[] = $data['params'][$paramName];
								}
							} else if ($param->isOptional()) {
								$arguments[] = $param->getDefaultValue();
							} else {
								$arguments[] = null;
							}
						}
						$reflection = new ReflectionClass($class);
						self::$_container[$scope][$name]['instance'] = $reflection->newInstanceArgs($arguments);
					} else {
						self::$_container[$scope][$name]['instance'] = new $class();
					}
				}
				if ((isset($data['setters']) && is_array($data['setters'])) || (isset($options['setters']) && is_array($options['setters']))) {
					$setters = array_replace_recursive((isset($data['setters']))? $data['setters'] : array(), (isset($options['setters']))? $options['setters'] : array());
					foreach ($setters as $setter => $params) {
						$reflection = new ReflectionMethod(self::$_container[$scope][$name]['instance'], $setter);
						$arguments = array();
						foreach ($reflection->getParameters() as $param) {
							$paramName = $param->getName();
							if (isset($params[$paramName])) {
								if (is_object($params[$paramName]) && $params[$paramName] instanceof Closure) {
									$arguments[] = $params[$paramName]();
								} else {
									$arguments[] = $params[$paramName];
								}
							} else if ($param->isOptional()) {
								$arguments[] = $param->getDefaultValue();
							} else {
								$arguments[] = null;
							}
						}
						$reflection->invokeArgs(self::$_container[$scope][$name]['instance'], $arguments);
					}
				}
				return self::$_container[$scope][$name]['instance'];
		}
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
 * Attaches an observer for a specifc class or set of classes.
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
 * applies the options for the observed classes.
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
					throw new CakeException('Dependency "%s" missing option: className', $name);
				}
				if (!isset($data['classPath'])) {
					throw new CakeException('Dependency "%s" missing option: classPath', $name);
				}
				App::uses($data['className'], $data['classPath']);
				self::$_container[$scope][$name] = array(
					'type' => self::TYPE_CONFIG,
					'data' => self::$_registry[$scope][$name],
					'instance' => null
				);
			}
		} else {
			throw new CakeException('Dependency not found: ' . $name);
		}
	}

}

