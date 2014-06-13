<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
	
	
Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));	// root
Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));		// pages
Router::connect('/register', array('controller' => 'users', 'action' => 'add'));		// user registration
Router::connect('/login', array('controller' => 'users', 'action' => 'login'));			// login
Router::connect('/logout', array('controller' => 'users', 'action' => 'logout'));		// logout
Router::connect('/drafts', array('controller' => 'drafts', 'action' => 'index'));		// drafts index
Router::connect('/play/*', array('controller' => 'tables', 'action' => 'play'));		// play
	
	
	
/**
 * Load all plugin routes. See the CakePlugin documentation on
 * how to customize the loading of plugin routes.
 */
	CakePlugin::routes();

/**
 * Load the CakePHP default routes. Only remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config' . DS . 'routes.php';
