# li3_rest: RESTful support for the Lithium framework

## Introduction
This plugin lets you define one or more `resources`, which map automatically to their appropriate 
controller actions. The plugin provides a common set of default settings, which should work for 
the common cases (nonetheless it is possible to customize every part of the plugin easily). Read on 
for a hands-on guide on how to install and use the plugin.

## Installation
To install and activate the plugin, you have to perform three easy steps.

1: Download or clone the plugin into your `libraries` directory.

	cd app/libraries
	git clone git://github.com/daschl/li3_rest.git
	

2: Enable the plugin at the bottom of your bootstrap file (`app/config/bootstrap/libraries.php`).

	/**
	 * Add some plugins:
	 */
	Libraries::add('li3_rest');

3: Use the extended `Router` class instead of the default one (at the top of `app/config/routes.php`).

	// use lithium\net\http\Router;
	use li3_rest\net\http\Router;

## Basic Usage
If you want to add a `resource`, you have to call the `Router::resource()` method with one or more params. 
The first param is the name of the `resource` (which has great impact on the routes generated), the second 
one is an array of options that will optionally override the default settings.

It's important to remember that any singular resource name is converted to plural so use plural resource names for your controller names.

If you want to add a `Posts` resource, add the following to `app/config/routes.php`:

	Router::resource('Posts');

This will generate a bunch of routes. If you want to list them, you can use the `li3 route` command:

	/posts(/v{:version:\d+(\.\d+)?})?(.{:type:\w+})*        {"controller":"posts","action":"index"}
	/posts(/v{:version:\d+(\.\d+)?})?/{:id:[0-9a-zA-Z\-_\.]+}(.{:type:\w+})* {controller":"posts","action":"show"}
	/posts(/v{:version:\d+(\.\d+)?})?/add                   {"controller":"posts","action":"add"}
	/posts(/v{:version:\d+(\.\d+)?})?(.{:type:\w+})*        {"controller":"posts","action":"create"}
	/posts(/v{:version:\d+(\.\d+)?})?/{:id:[0-9a-zA-Z\-_\.]+}/edit        {"controller":"posts","action":"edit"}
	/posts(/v{:version:\d+(\.\d+)?})?/{:id:[0-9a-zA-Z\-_\.]+}(.{:type:\w+})* {"controller":"posts","action":"update"}
	/posts(/v{:version:\d+(\.\d+)?})?/{:id:[0-9a-zA-Z\-_\.]+}(.{:type:\w+})* {"controller":"posts","action":"delete"}
	/posts(/v{:version:\d+(\.\d+)?})?/bulk(.{:type:\w+})* {"controller":"posts","action":"bulk"}

 
This routes look complex in the first place, but they try to be as flexible as possible. You can pass 
all default ids (both MongoDB and for relational databases) and always an optional type (like `json`).  If you want, you can set the `type_support` through the options for the resource in your routes file to false to not create the routes with the optional regex.

With the default resource activated, you can use the following URIs.

	GET /posts or /posts.json => Show a list of available posts
	GET /posts/1234 or /posts/1234.json => Show the post with the ID 1234
	GET /posts/add => Add a new post (maybe a HTML form)
	POST /posts or /posts.json => Add a new post (has the form data attached)
	GET /posts/1234/edit => Edit the post with the ID 1234 (maybe a HTML form)
	PUT /posts/1234 or /posts/1234.json => Edit the post with the ID 1234 (has the form data attached)
	DELETE /posts/1234 or /posts/1234.json => Deletes the post with the ID 1234
	POST /posts/bulk or /posts/bulk.json => Useful for bulk operations also an example on adding more routes
	
 


## Advanced usage

### Versioning

Using versioning in the request:
You can request using v1 or v1.0 right after the resource name and it will pass the number as the parameter version

	GET /posts/v1 or /posts/v1.0 or /posts/v1.json or /posts/v1.0.json => Show a list of available posts
	
The versions are dynamically converted to matching action names in your controller.  For example: 
	
	GET /posts/v1 or /posts/v1.0 or /posts/v1.json or /posts/v1.0.json => index_1
	GET  /posts/v1.0  or /posts/v1.0.json => index_1_0
	
If the version is not passed and you don't version any methods, it will default to normal action/method name.

If version is ommitted from the resource request, it will parse all the method/action names and execute the latest version (highest version number). 

### Linked models

If you want to create routes using linked models. You can add the following to `app/config/routes.php`:
	
	Router::resource('Post/Comment') 
	
You will get a route like this:

	/post/{:post_id:[0-9a-f]{24}|[0-9]+}/comment/{:id:[0-9a-f]{24}|[0-9]+}/edit  {"controller":"comment","action":"edit"}
	
Note that the above route passes the variable post_id in addition to id to the "edit" action of the "comment" controller.

The equivalent of the above call with versioning is:

	/post/{:post_id:[0-9a-f]{24}|[0-9]+}/comment/v1/{:id:[0-9a-f]{24}|[0-9]+}/edit  {"controller":"comment","action":"edit_1"}

The thing to remember is that you're versioning the action in the controller (which controls the interface of the call) so the first parameter (post_id) does not need versioning.  This is there for readability ease and nothing more since the passing of linked data can be accomplished in the data passed into the call.

### Scoping

If you want to create routes using a scope. You can add the following to `app/config/routes.php`:
	
	Router::resource('Post/Comment',array('scope'=>'Articles')) 

Will generate a route that lookes like this:

	/articles/post/{:post_id:[0-9a-f]{24}|[0-9]+}/comment/{:id:[0-9a-f]{24}|[0-9]+}/edit  {"controller":"comment","action":"edit"}
	
The scope is passed into the action as a parameter.  You can use this to further define the template you want to use or anything else you want.  It helps with readability much like the linked models feature. This is lazy loaded so the computational expense of the regex is not in play when the scope option is not added.  Also, the value of scope is always converted to lower case.

### Limiting Created Routes

There will be cases where you don't want all the routes created.  You can either exclude certain routes or when easier, create only certain routes.

If you want to only generate the add and show routes, you can add the following to `app/config/routes.php`:
	
	Router::resource('Post/Comment',array('only'=>array('add','show'))) 
	
If you want to exclude the add and show routes, you can add the following to `app/config/routes.php`:
	
	Router::resource('Post/Comment',array('except'=>array('add','show'))) 

### Using Types

The types for the each resource are optional if the type is supported, you will always get two routes created. One with regex for the type and one without.  By default, the optional type regex route is created since its the simplest implemantation. 

If you want to disable passing in the types, because you will always return a certain data type (e.g. json) or you want to be hard core and use auto negotiation (see below), you have two options:

1. You can use the options for the resource (recommended). You can add the following to `app/config/routes.php`:
	
	`Router::resource('Post/Comment',array('type_support'=>false)) `
	
2. You can disable the type for all routes. You can change the `type_support` in `li3_rest/net/http/Resource.php` to `false`.  If you do, you can overwrite the default behavior by using method 1 and setting `type_support` to `true`. 


## Contributing
Feel free to fork the plugin and send in pull requests. If you find any bugs or need a feature that is not implemented, open a ticket.


## Autonegotiation content (REQUEST type == RESPOND type)
If you need serve content by recognize headers params you must set negotiate = true params

class PostsController extends \lithium\action\Controller {

    protected function _init() {
		$this->_render['negotiate'] = true;
		parent::_init();
	}

after that, when client send request to our service for eg. `DELETE /posts/1234` witch headers like that
    
    `Accept: application/json`
    `X-Requested-With: XMLHttpRequest`

li3 serve json view, but you should configure Media class (in /app/config/bootstrap/media/php) like this

    /**
     * Json media response for ExtJs specifid format Request must have 2 important flags in headers:
     * `Accept application/json`
     * `X-Requested-With XMLHttpRequest`
     * @link http://www.sencha.com/learn/Manual:RESTful_Web_Services#HTTP_Status_Codes
     * @link http://json.org/JSONRequest.html
     */
    Media::type('extjs', array('application/json', 'application/jsonrequest'), array(
        'view' => 'lithium\template\View',
    	'layout' => false,
    	'conditions' => array('ajax' => true),
    	'encode' => function($data, $handler, &$response) {
    		if (!isset($data['success'])) {
    			$data['success'] = true;
    		}
    		
    		if (!isset($data['errors']) && $data['success'] === true) {
    			$data['errors'] = array();
    			if (!isset($data['data'][0]) && !is_string(key($data['data']))) {
    				// reorder array index (from 0 to n)
    				$data['data'] = array_values($data['data']);
    			}
    		}
    
    		if (is_array($data['data']) && count($data['errors'])) {
    			$errors = array();
    			foreach ($data['errors'] as $id => $val) {
    				if (is_array($val)) {
    					$errors[$id] = implode(' ', $val);
    				}
    			}
    			$data['errors'] = $errors;
    		}
    		return json_encode($data);
    	},
    	'decode' => function($data, $handler) {
    		return json_decode($data);
    	}
    ));

this eg. works for ExtJs REST interface.
