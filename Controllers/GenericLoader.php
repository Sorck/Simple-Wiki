<?php

/**
 * This does most of the wiki page handling...
 * @todo redirect when there is no page
 */

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Storage\PageFactory;

class GenericLoader extends AbstractWikiController
{
	/**
	 * @var type The namespace under which this page is located
	 */
	protected $_namespace;
	/**
	 * @var string The page name (without namespace)
	 */
	protected $_page_name;
	
	/**
	 * @const string default_namespace
	 */
	const default_namespace = 'View';
	/**
	 * @const string default_page
	 */
	const default_page = 'Main_Page';
	
	public function LoadNoNamespace()
	{
		// this is run if there is no namespace or it's invalid
		// so run with a modified value
		$this->MainMethod('' . substr($this->_app['request']->getPath(), 6));
	}

	public function MainMethod()
	{
		// try and get the wiki Path
		$path = substr($this->_app['request']->getPath(), 5);
		// do we have a namespace?
		if(false !== $pos = strpos($path, ':'))
		{
			// the namespace is the first bit
			$namespace = ucfirst(strtolower(substr($path, 0, $pos) ?: self::default_namespace));
			// is it a valid namespace?
			if(preg_match('/^[A-Za-z]+\z/', $namespace) === 1)
			{
				# nothing here...
			}
			else
			{
				// invalid eh? Show an error then
				// @todo
			}
			// and the latter is the page name
			$page_name = substr($path, $pos + 1);
			// does the namespace exist?
			#if(class_exists(''))
		}
		else
		{
			$page_name = $path;
			$namespace = ucfirst(strtolower(self::default_namespace));
		}
		// try and get the page
		try
		{
			$factory = new PageFactory($this->_app);
			$this->_page = $factory->load($page_name, $namespace);
			
			$crumbs = $this->_page['crumbs'];foreach($crumbs as $k => $v){throw new \Exception;}
			$crumbs[] = array(
					'name' => $page_name, // @todo make this use the real name, not the urlname. Not sure how we'll do this.
					'href' => array('wiki', $page_name),
					'active' => $namespace === self::default_namespace,
			);

			// breadcrumb time
			if($namespace !== self::default_namespace)
			{
				$crumbs[] = array(
						'name' => $namespace,
						'href' => array('wiki', $namespace . ':' . $page_name),
						'active' => $namespace !== self::default_namespace,
				);
			}
			$this->_page['crumbs'] = $crumbs;
			// @todo namespace/page combo
			return $this->module->render('Namespaces/Wiki' . $namespace, array('wiki' => $this->_page));
		}
		catch(Exception $error_string)
		{
			// @todo some actual excpetion handling
			throw $error_string;
		}
	}
	
	public function NoPage()
	{
		$this->_app['response']->redirect('/wiki/Main_Page');
	}
}