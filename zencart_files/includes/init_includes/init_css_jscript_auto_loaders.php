<?php
/**
 * init jscript auto_loaders
 *
 * @author yellow1912 (RubikIntegration.com)
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

require_once(DIR_WS_CLASSES . 'class.cj_loader.php');
$RI_CJLoader = new RICJLoader();
$directory_array = $template->get_template_part(DIR_WS_TEMPLATE.'auto_loaders', '/^loader_/', '.php');
	
$loaders_check = $RI_CJLoader->get('loaders');
if($loaders_check == '*' || count($loaders_check) > 0){
	while(list ($key, $value) = each($directory_array)) {
	/**
	* include content from all site-wide loader_*.php files from includes/templates/YOURTEMPLATE/jscript/auto_loaders, alphabetically.
	*/
		if($loaders_check == '*' || in_array($value, $loaders_check))
			require(DIR_WS_TEMPLATE.'auto_loaders'. '/' . $value);
	}
}

if(count($loaders) > 0)	$RI_CJLoader->addLoaders($loaders, true);
$RI_CJLoader->loadCssJsFiles();