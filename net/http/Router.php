<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_rest\net\http;

class Router extends \lithium\net\http\Router {

	/**
	 * Classes used by `Router`.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'route' => 'lithium\net\http\Route',
		'resource' => 'li3_rest\net\http\Resource'
	);

	public static function resource($resource, $options = array()) {
		$class = static::$_classes['resource'];


		$configs = $class::bind($resource, $options);
		foreach ($configs as $config) {
			static::connect($config['template'], $config['params'], $options);

		}
	}
}




?>


