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

require_once(DIR_FS_CATALOG . 'plugins/riCjLoader/RiCjLoaderPlugin.php');
$RI_CJLoader = new RiCjLoaderPlugin();
