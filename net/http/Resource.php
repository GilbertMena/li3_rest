<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_rest\net\http;

use lithium\util\Inflector;
use lithium\util\String;

/**
 * The `Resource` class enables RESTful routing in Lithium.
 * 
 * The `Resource` class acts as a more high-level interface to the `Route` class 
 * and takes care of instantiating the appropriate routes for a given resource.
 * 
 * 
 * In your `routes.php` file you can connect a resource in its simplest form like this:
 * 
 * {{{
 * Router::resource('Posts');
 * }}}
 * 
 * This will automatically generate this CRUD routes for you (output similar to `li3 route`):
 * 
 * {{{
 * /posts(.{:type:\w+})*                               	    {"controller":"posts","action":"index"}
 * /posts/{:id:[0-9a-zA-Z\-_\.]+}(.{:type:\w+})*        	{"controller":"posts","action":"show"}
 * /posts/add                          	                    {"controller":"posts","action":"add"}
 * /posts(.{:type:\w+})*                            	    {"controller":"posts","action":"create"}
 * /posts/{:id:[0-9a-zA-Z\-_\.]+}/edit	                {"controller":"posts","action":"edit"}
 * /posts/{:id:[0-9a-zA-Z\-_\.]+}(.{:type:\w+})*       	{"controller":"posts","action":"update"}
 * /posts/{:id:[0-9a-zA-Z\-_\.]+}(.{:type:\w+})*       	{"controller":"posts","action":"delete"}
 * }}}
 * 
 * This routes look complex in the first place, but they try to be as flexible as possible. You can pass 
 * all default ids (both MongoDB and for relational databases) and always an optional type (like `json`).
 * With the default resource activated, you can use the following URIs.
 * 
 * {{{
 * GET /posts or /posts.json => Show a list of available posts
 * GET /posts/1234 or /posts/1234.json => Show the post with the ID 1234
 * GET /posts/add => Add a new post (maybe a HTML form)
 * POST /posts or /posts.json => Add a new post (has the form data attached)
 * GET /posts/1234/edit => Edit the post with the ID 1234 (maybe a HTML form)
 * PUT /posts/1234 or /posts/1234.json => Edit the post with the ID 1234 (has the form data attached)
 * DELETE /posts/1234 or /posts/1234.json => Deletes the post with the ID 1234
 * }}}
 * 
 */
class Resource extends \lithium\core\Object {

	/**
	 * Classes used by `Resource`.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'route' => 'lithium\net\http\Route',
	);

	/**
	 * Default resource types to connect.
	 *
	 * @var array
	 */
	protected static $_types = array(
		'index' => array(
			'template' => '/{:resource}',
			'params' => array('http:method' => 'GET'),
			            'type_support' => true
		),
		'show' => array(
			'template' => '/{:resource}/{:id:[0-9a-zA-Z\-_\.]+}',
			'params' => array('http:method' => 'GET'),
			            'type_support' => true
		),
		'add' => array(
			'template' => '/{:resource}/add',
			'params' => array('http:method' => 'GET')
		),
		'create' => array(
			'template' => '/{:resource}',
			'params' => array('http:method' => 'POST'),
                    'type_support' => true
		),

		'edit' => array(
			'template' => '/{:resource}/{:id:[0-9a-zA-Z\-_\.]+}/edit',
			'params' => array('http:method' => 'GET')
		),
		'update' => array(
			'template' => '/{:resource}/{:id:[0-9a-zA-Z\-_\.]+}',
			'params' => array('http:method' => 'PUT') ,
			            'type_support' => true
		),
		'delete' => array(
			'template' => '/{:resource}/{:id:[0-9a-zA-Z\-_\.]+}',
			'params' => array('http:method' => 'DELETE'),
			            'type_support' => true
		)
	);

	/**
	 * Configure the class params like classes or types.
	 */
	public static function config($config = array()) {
		if (!$config) {
			return array('classes' => static::$_classes, 'types' => static::$_types);
		}
		if (isset($config['classes'])) {
			static::$_classes = $config['classes'] + static::$_classes;
		}
		if (isset($config['types'])) {
			static::$_types = $config['types'] + static::$_types;
		}	
	}

	/**
	 * Connect a resource to the `Router`.
	 */
	public static function connect($resource, $options = array()) {
        $ctrl = $resource;
        $resource = Inflector::tableize($resource);
        $class = static::$_classes['route'];
        $scope  = isset($options['scope']) ? $options['scope'] : '';

		$types = static::$_types;
		if(isset($options['types'])) {
			$types = $options['types'] + $types;
		}

        if(isset($options['except'])) {
            foreach(array_intersect($options['except'],array_keys($types)) as $k) {
                unset($types[$k]);
            }
        }

        if(isset($options['only'])) {
            foreach(array_keys($types) as $k) {
                if (!in_array($k, $options['only'])) unset($types[$k]);
            }
        }

		$routes = array();
		foreach($types as $action => $params) {
			$config = array(
				'template' => $scope.String::insert($params['template'], array('resource' => $resource)),
				'params' => $params['params'] + array('controller' => $ctrl, 'action' => isset($params['action']) ? $params['action'] : $action),
			);
			$routes[] = new $class($config);
            if (isset($params['type_support']) && $params['type_support']) {
                $config = array(
                    'template' => $scope.String::insert($params['template'].'.{:type}', array('resource' => $resource)),
                    'params' => $params['params'] + array('controller' => $ctrl, 'action' => isset($params['action']) ? $params['action'] : $action),
                );
                $routes[] = new $class($config);
            }
		}

		return $routes;
	}

}

?>