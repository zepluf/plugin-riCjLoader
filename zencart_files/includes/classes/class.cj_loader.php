<?php
/**
 * Required functions for the CSS/JS Loader
 *
 * @author yellow1912 (RubikIntegration.com)
 * @author John William Robeson, Jr <johnny@localmomentum.net>
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License V2.0
 *
 * NOTES:
 * All .php files can be manipulated by PHP when they're called, and are copied in-full to the browser page
 */

class RICJLoader
{
	protected $js = array();
	protected $css = array();
	protected $template;
	protected $page_directory = '';
	protected $current_page_base = '';
	protected $request_type;
	protected $loaders = array();
	protected $options = array('admin' => false, 'loaders' => '*', 'status' => true, 'ajax' => false, 'load_global' => true, 'load_print' => true, 'minify' => false, 'minify_time' => 0);
	
	function __construct()
	{
		global $current_page_base, $page_directory, $request_type, $template;
		if (MINIFY_STATUS === 'true') {
			global $db;
			$this->options['minify'] = true;
			$now = time();
			$this->options['minify_time'] = (int)MINIFY_CACHE_TIME_LATEST;
			if($now - $this->options['minify_time'] > (int)MINIFY_CACHE_TIME_LENGHT){
				$db->Execute("UPDATE ".TABLE_CONFIGURATION." SET configuration_value = $now WHERE configuration_key = 'MINIFY_CACHE_TIME_LATEST'");
				$this->options['minify_time'] = $now;
			}
		}

		$this->template = $template;
		$this->page_directory = $page_directory;
		$this->request_type = $request_type;
	}

	function setOptions($options){
		$this->options = array_merge($this->options, $options);
	}
	
	function getOptions($key = ''){
		if(!empty($key))
			return $this->options[$key];
		else return $this->options;
	}

	/**
	 * Get asset directory
	 */
	function getAssetDir($extension, $directory, $template = DIR_WS_TEMPLATE)
	{
		return $this->template->get_template_dir($extension, $template, $this->current_page_base, $directory);
	}

	/**
	 * Find asset files in a template directory
	 *
	 * @param string extension - file extension to look for
	 * @param directory - subdirectory of the template containing the assets
	 */
	function findAssets($extension, $directory, $file_pattern = '', $order = 0, $inheritance = '')
	{
		$relative_path = $this->request_type == 'NONSSL' ? DIR_WS_CATALOG : DIR_WS_HTTPS_CATALOG;
		$templateDir = $this->getAssetDir($extension, $directory, DIR_WS_TEMPLATE);
		$allFiles = $this->template->get_template_part($templateDir, $file_pattern, $extension);

		if(!empty($inheritance)){
			$defaultDir = $this->getAssetDir($extension, $directory, DIR_WS_TEMPLATES. $$inheritance);
			$allFiles = array_unique(array_merge($this->template->get_template_part($defaultDir, $file_pattern, $extension),$allFiles));
		}
		
		$files = array();
		foreach ($allFiles as $file) {
		// case 1: file is in server but full path not passed, assuming it is under corresponding template css/js folder
      if(file_exists(DIR_FS_CATALOG.DIR_WS_TEMPLATE.$directory. '/' .$file)){
        $files[$relative_path.DIR_WS_TEMPLATE.$directory. '/' .$file] = $order++;
      }
      elseif ($inheritance != '' && file_exists(DIR_FS_CATALOG.DIR_WS_TEMPLATES.$inheritance.$directory.'/'.$file)){
        $files[$relative_path.DIR_WS_TEMPLATES.$inheritance.$directory.'/'.$file] = $order++;
      }
		}

		return $files;
	}
	

	function addAssets($files, $type)
	{
		foreach ($files as $file => $order) {
			$this->{$type}[] = array($file => $order);
		}
	}

	function loadFiles($files){
		$result = array();
		foreach($files as $_files){
			foreach($_files as $file=>$order)
			if(!isset($result[$file]) || $result[$file] > $order) $result[$file] = $order;		
		}
		
		if(count($result) > 0)
			asort($result);	
		return $result;
	}
	
	function addLoaders($loader, $multi = false){
		if($multi)
			$this->loaders = array_merge($this->loaders, $loaders);
		else
			$this->loaders[] = $loader;
	}
	
	function setCurrentPageBase(){
		if(!$this->getOptions('admin')){
			global $current_page_base, $this_is_home_page;
			// set current page
			if($this_is_home_page)
				$this->current_page_base = 'index_home';
			elseif($current_page_base == 'index'){
				if(isset($_GET['cPath']))
					$this->current_page_base = 'index_category';
				elseif(isset($_GET['manufacturers_id']))
					$this->current_page_base = 'index_manufacturer';
			}
			else 
				$this->current_page_base = $current_page_base;
		}
		else{
			$this->current_page_base = preg_replace('/\.php/','',substr(strrchr($_SERVER['PHP_SELF'],'/'),1),1);
		}
	}
	
	function loadCssJsFiles()
	{
		global $this_is_home_page, $cPath;
		$template = $this->template;
		$page_directory = $this->page_directory;
		
		// set the correct base
		$this->setCurrentPageBase();
		
		/**
		 * load the loader files
		 */
		if((is_array($this->loaders)) && count($this->loaders) > 0)	{
			foreach($this->loaders as $loader){
				if(in_array('*', $loader['conditions']['pages']) || in_array($this->current_page_base, $loader['conditions']['pages'])){
					if(isset($loader['jscript_files']))
						$this->addAssets($loader['jscript_files'], 'js');
					if(isset($loader['css_files']))
						$this->addAssets($loader['css_files'], 'css');
				}
				else{
					$load = false;	
					if(isset($loader['conditions']['call_backs']))
					foreach($loader['conditions']['call_backs'] as $function){
						$f = explode(',',$function);
						if(count($f) == 2){
							$load = call_user_func(array($f[0], $f[1]));
						}
						else $load = $function();
						
						if($load){
							if(isset($loader['jscript_files']))
								$this->addAssets($loader['jscript_files'], 'js');
							if(isset($loader['css_files']))
								$this->addAssets($loader['css_files'], 'css');
							break;
						}
					}
				}
			}
		}
			
		if($this->getOptions('load_global')) {
		/**
		 * load all template-specific stylesheets, named like "style*.css", alphabetically
		 */
			$files = $this->findAssets('.css', 'css', '/^style/', -300); 
			$this->addAssets($files, 'css');
		
			/**
	    * load all template-specific stylesheets, named like "style*.php", alphabetically
	    */
			$files = $this->findAssets('.php', 'css', '/^style/', -250);
			$this->addAssets($files, 'css');
			
			/**
			 * load all site-wide jscript_*.js files from includes/templates/YOURTEMPLATE/jscript, alphabetically
			 */
			$files = $this->findAssets('.js', 'jscript', '/^jscript_/', -400);
			$this->addAssets($files, 'js');
			
			/**
	    * include content from all site-wide jscript_*.php files from includes/templates/YOURTEMPLATE/jscript, alphabetically.
	    */
			$files = $this->findAssets('.php', 'jscript', '/^jscript_/', -200);
			$this->addAssets($files, 'js');
		}
		
		/**
		 * TODO: we shouldn't use $_GET here, it breaks the encapsulation
		 * load stylesheets on a per-page/per-language/per-product/per-manufacturer/per-category basis. Concept by Juxi Zoza.
		 */
		$manufacturers_id = (isset($_GET['manufacturers_id'])) ? $_GET['manufacturers_id'] : '';
		$tmp_products_id = (isset($_GET['products_id'])) ? (int)$_GET['products_id'] : '';
		$tmp_pagename = ($this_is_home_page) ? 'index_home' : $this->current_page_base;
		$sheets_array = array('/' . $_SESSION['language'] . '_stylesheet', 
								'/' . $tmp_pagename, 
								'/' . $_SESSION['language'] . '_' . $tmp_pagename, 
	                        '/c_' . $cPath,
	                        '/' . $_SESSION['language'] . '_c_' . $cPath,
	                        '/m_' . $manufacturers_id,
	                        '/' . $_SESSION['language'] . '_m_' . (int)$manufacturers_id, 
	                        '/p_' . $tmp_products_id,
	                        '/' . $_SESSION['language'] . '_p_' . $tmp_products_id
	                        );
		$load_order = -200;
		foreach ($sheets_array as $key => $value) {
			$perpagefile = $this->getAssetDir('.css', 'css') . $value . '.css';
			if (file_exists($perpagefile)) $this->addAssets(array(array(trim($value, '/') . '.css' => $load_order++)), 'css');	
			
			$perpagefile = $this->getAssetDir('.php', 'css') . $value . '.php';
			if (file_exists($perpagefile)) $this->addAssets(array(array(trim($value, '/') . '.php' => $load_order++)), 'css');	
	    
			$perpagefile = $this->getAssetDir('.js', 'jscript') . $value . '.js';
			if (file_exists($perpagefile)) $this->addAssets(array(array(trim($value, '/') . '.js' => $load_order++)), 'js');	
	    
			$perpagefile = $this->getAssetDir('.php', 'jscript') . $value . '.php';
			if (file_exists($perpagefile)) $this->addAssets(array(array(trim($value, '/') . '.php' => $load_order++)), 'js');	
	
		}
	
		/**
		 * load printer-friendly stylesheets -- named like "print*.css", alphabetically
		 */
		if($this->getOptions('load_print')) {
			$directory_array = $this->findAssets('.css','css', '/^print/');;
			sort($directory_array);
			// TODO: don't output link tags directly from here
			foreach ($directory_array as $key => $value) {
				echo '<link rel="stylesheet" type="text/css" media="print" href="' . $key . '" />'."\n";
			}
		}

		if (file_exists(DIR_WS_CLASSES . 'browser.php') && floatval(phpversion()) > 5) {
			include(DIR_WS_CLASSES . 'browser.php');
			$browser = new _Browser();
			$browser_name = preg_replace("/[^a-zA-Z0-9s]/", "-", strtolower($browser->getBrowser()));
			$browser_version = floor($browser->getVersion());
	    
			// this is to make it compatible with the other ie css hack
			if ($browser->getBrowser() == $browser->BROWSER_IE) {
				$browser_name = 'ie';
			}

			// get the browser specific files
			$files = $this->findAssets('.css', 'css', "/^{$browser_name}-/", -100);
			$this->addAssets($files, 'css');

			$files = $this->findAssets('.js', 'jscript', "/^{$browser_name}-/", -500);
			$this->addAssets($files, 'js');

			// get the browser version specific files
			$files = $this->findAssets('.css', 'css', "/^{$browser_name}{$browser_version}-/", -100);
			$this->addAssets($files, 'css');

			$directory_array = $this->findAssets('.js', 'jscript', "/^{$browser_name}{$browser_version}-/", -500);
			$this->addAssets($files, 'js');
		}
		
	
		/**
		 * load all page-specific jscript_*.js files from includes/modules/pages/PAGENAME, alphabetically
		 */
		$files = $this->template->get_template_part($page_directory, '/^jscript_/', '.js');
		$load_order = -300;
		foreach ($files as $key => $value) {
	    $this->addAssets(array(array("$page_directory/$value" => $load_order++)), 'js');	
		}
		
		/**
		 * include content from all page-specific jscript_*.php files from includes/modules/pages/PAGENAME, alphabetically.
		 */
		$load_order = -100;
		$files = $this->template->get_template_part($page_directory, '/^jscript_/', '.php');
		foreach ($files as $key => $value) {
			$this->addAssets(array(array("$page_directory/$value" => $load_order++)), 'js');	
		}  
		return true;
	}
	
	function processCssJsFiles()
	{
		$css_files = $this->loadFiles($this->css);
		$js_files = $this->loadFiles($this->js);
		
		$files['js'] = $this->getFiles($js_files);
		$files['css'] = $this->getFiles($css_files);	
		return $files;
	}
		
	function getPath($file, $type = 'jscript'){
		$path_info = pathinfo($file);
		return array('extension' => $path_info['extension'], 'path' => DIR_WS_TEMPLATE.$type.'/'.$path_info['dirname'].$file_name);
	}
	
		
	function getFiles($files)
	{
		$files_paths = '';
		$result = array();
		foreach ($files as $file => $order) {
			$file_absolute_path = DIR_FS_CATALOG.$file; 
			$file_relative_path = $file; 
			
			if (substr($file, 0, 4) == 'http') {
				// TODO: do the outputting formatting all in one place
        $result[] = array('src' => $file, 'include' => false, 'external' => true);
				continue;
			}
			
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			// if we encounter php, unfortunately we will have to include it for now
			// another solution is to put everything into 1 file, but we will have to solve @import
			if ($ext == 'php') {
				$result[] = array('src' => $file_absolute_path, 'include' => true);
				continue;
			} elseif ($this->getOptions('minify')) {
				if (strlen($files_paths) > ((int)MINIFY_MAX_URL_LENGHT - 20)) {
					//$result[] = array('string' => sprintf($request_string, trim($files_paths, ',')), 'include' => false);	
					$files_paths = $file_relative_path.',';		
				} else {
					//$result[] = array('string' => sprintf($request_string, $file_relative_path), 'include' => false);
					$files_paths .= $file_relative_path.',';
				}
			} else {
				$result[] = array('src' => $file_relative_path, 'include' => false, 'external' => false);
			}
		}
		// one last time
		if (!empty($files_paths) && $this->getOptions('minify')) {
			$result[] = array('src' => trim($files_paths, ','), 'include' => false, 'external' => false);
		}
		return $result;
	}
}