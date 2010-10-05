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

if(!defined('MINIFY_STATUS'))
	define('MINIFY_STATUS', 'false');
	
require_once(DIR_WS_CLASSES . 'class.cj_loader.php');
$RI_CJLoader = new RICJLoader();