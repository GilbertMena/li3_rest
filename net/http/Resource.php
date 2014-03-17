<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_rest\net\http;

use lithium\util\Inflector;
use lithium\util\String;
use lithium\action\Dispatcher;
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
 * /posts                                   {"controller":"posts","action":"index"}
 * /posts/{:id:[0-9a-f]{24}|[0-9]+}         {"controller":"posts","action":"show"}
 * /posts/add                               {"controller":"posts","action":"add"}
 * /posts                                   {"controller":"posts","action":"create"}
 * /posts/{:id:[0-9a-f]{24}|[0-9]+}/edit    {"controller":"posts","action":"edit"}
 * /posts/{:id:[0-9a-f]{24}|[0-9]+}         {"controller":"posts","action":"update"}
 * /posts/{:id:[0-9a-f]{24}|[0-9]+}         {"controller":"posts","action":"delete"}
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
 * Restricting the Routes Created
 * By default, connecting a resource creates routes for the seven default actions (index, show, add, create, edit, update, and delete)
 * for every RESTful route in your application. You can use the `only` and `except` options to fine-tune this behavior.
 * The `only` option tells Lithium to create only the specified routes:
 *
 * {{{
 * Resource::connect('posts', array('only' => array('index', 'show')));
 * }}}
 *
 * Now, a GET request to /posts would succeed, but a POST request to /posts (which would ordinarily be routed to the create action) will fail.
 *
 * The `except` option specifies a route or list of routes that Lithium should not create:
 *
 * {{{
 * Resource::connect('posts', array('except' => 'delete'));
 * }}}
 *
 * In this case, Lithium will create all of the normal routes except the route for delete (a DELETE request to /posts/:id).
 *
 */
class Resource extends \lithium\core\StaticObject {

	/**
	 * Classes used by `Resource`.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'route' => 'lithium\net\http\Route'
	);

	/**
	 * Default resource types to connect.
	 *
	 * @var array
	 */
	protected static $_types = array(
		'index' => array(
			'template' => '/{:resource}(/v{:version:\d+(\.\d+)?})?(.{:type:\w+})?', //
			'params' => array('http:method' => 'GET')
		),
		'show' => array(
			'template' => '/{:resource}(/v{:version:\d+(\.\d+)?})?/{:id:[0-9a-f]{24}|[0-9]+}(.{:type:\w+})?',
			'params' => array('http:method' => 'GET')
		),
		'add' => array(
			'template' => '/{:resource}(/v{:version:\d+(\.\d+)?})?/add(.{:type:\w+})?',
			'params' => array('http:method' => 'GET')
		),
		'create' => array(
			'template' => '/{:resource}(/v{:version:\d+(\.\d+)?})?(.{:type:\w+})?',
			'params' => array('http:method' => 'POST')
		),
		'edit' => array(
			'template' => '/{:resource}(/v{:version:\d+(\.\d+)?})?/{:id:[0-9a-f]{24}|[0-9]+}/edit(.{:type:\w+})?',
			'params' => array('http:method' => 'GET')
		),
		'update' => array(
			'template' => '/{:resource}(/v{:version:\d+(\.\d+)?})?/{:id:[0-9a-f]{24}|[0-9]+}(.{:type:\w+})?',
			'params' => array('http:method' => 'PUT')
		),
		'delete' => array(
			'template' => '/{:resource}(/v{:version:\d+(\.\d+)?})?/{:id:[0-9a-f]{24}|[0-9]+}(.{:type:\w+})?',
			'params' => array('http:method' => 'DELETE')
		)
	);

	/**
	 * Configure the class params like classes or types.
	 *
	 * @param array $config Optional configuration params.
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
	 *
	 * @param string $resource The name of the resource
	 * @param array $options
	 */
	public static function connect($resource, $options = array()) {
		$resource = Inflector::tableize($resource);
		$types = static::$_types;

		if (isset($options['types'])) {
			$types = $options['types'] + $types;
		}

		if (isset($options['except'])) {
			foreach (array_intersect((array) $options['except'], array_keys($types)) as $k) {
				unset($types[$k]);
			}
		}

		if (isset($options['only'])) {
			foreach (array_keys($types) as $k) {
				if (!in_array($k, (array) $options['only'])) {
					unset($types[$k]);
				}
			}
		}

		$configs = array();
		foreach ($types as $action => $params) {
			$config = array(
				'template' => String::insert($params['template'], array('resource' => $resource)),
				'params' => $params['params'] + array('controller' => $resource, 'action' => $action)
			);
			$configs[] = $config;
		}

		return $configs;
	}
}

Dispatcher::applyFilter('_callable', function($self, $params, $chain) {

    $params['params']['originalAction'] = $params['params']['action'];
    
    
    $ctrl    = $chain->next($self, $params, $chain);
    //print_r($ctrl);exit;
    $methodNames = get_class_methods($ctrl);
    $a = preg_grep("/".$params['params']['originalAction']."(_d+)?/",$methodNames);
    //print_r($a);exit;
    if(!isset($params['request']->params['version']))
    {
        foreach($a as $key => $version)
        {
            $underPosition = strpos($version,'_');
            if($underPosition!==false)
            {
                $underPosition++;
            }else
            {
                $a[$key] = 0;
                continue;
            }
            $a[$key] = str_replace('_','.',substr($version,$underPosition));
        }
    
        $latestVersion = max($a);

        $ctrl->request->params['version'] = $latestVersion;
    }else
    {
        if(!in_array($params['params']['action'],$methodNames))
        {
            //version requested does not exist
            //TODO: return error response
        }
    }

   return $ctrl;
   
});

Dispatcher::applyFilter('_call', function($self, $params, $chain) {

     if(!isset($params['params']['version']))
    {
         if(isset($params['callable']->request->params['version']))
        {
            if(is_numeric($params['callable']->request->params['version']))
            {
                $params['params']['version'] = $params['callable']->request->params['version'];
            }
         }
    }
    if(isset($params['params']['version']))
    {
        if(is_numeric($params['params']['version']))
        {
            $params['params']['action'] .= '_'.str_replace('.','_',$params['params']['version']);
        }
    }
    
    $ctrl    = $chain->next($self, $params, $chain);
   return $ctrl;
   
});

?>