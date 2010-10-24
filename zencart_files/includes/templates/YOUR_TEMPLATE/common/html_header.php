<?php
/**
 * Common Template
 *
 * outputs the html header. i,e, everything that comes before the \</head\> tag <br />
 *
 * @package templateSystem
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: html_header.php 15761 2010-03-31 19:31:27Z drbyte $
 */
/**
 * load the module for generating page meta-tags
 */
require(DIR_WS_MODULES . zen_get_module_directory('meta_tags.php'));
/**
 * output main page HEAD tag and related headers/meta-tags, etc
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php echo HTML_PARAMS; ?>>
<head>
<title><?php echo META_TAG_TITLE; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
<meta name="keywords" content="<?php echo META_TAG_KEYWORDS; ?>" />
<meta name="description" content="<?php echo META_TAG_DESCRIPTION; ?>" />
<meta http-equiv="imagetoolbar" content="no" />
<meta name="author" content="The Zen Cart&trade; Team and others" />
<meta name="generator" content="shopping cart program by Zen Cart&trade;, http://www.zen-cart.com eCommerce" />
<?php if (defined('ROBOTS_PAGES_TO_SKIP') && in_array($current_page_base,explode(",",constant('ROBOTS_PAGES_TO_SKIP'))) || $current_page_base=='down_for_maintenance' || $robotsNoIndex === true) { ?>
<meta name="robots" content="noindex, nofollow" />
<?php } ?>
<?php if (defined('FAVICON')) { ?>
<link rel="icon" href="<?php echo FAVICON; ?>" type="image/x-icon" />
<link rel="shortcut icon" href="<?php echo FAVICON; ?>" type="image/x-icon" />
<?php } //endif FAVICON ?>

<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG ); ?>" />
<?php if (isset($canonicalLink) && $canonicalLink != '') { ?>
<link rel="canonical" href="<?php echo $canonicalLink; ?>" />
<?php } ?>

<?php
/**
* load the loader files
*/

if($RI_CJLoader->get('status')){
	$files = $RI_CJLoader->header();
	foreach($files['css'] as $file)
		if($file['include']) include($file['src']);
		else if(!$RI_CJLoader->get('minify') || $file['external']) echo "<link rel=\"stylesheet\" type=\"text/css\" href='{$file['src']}' />\n";
		else echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"min/?f={$file['src']}&amp;".$RI_CJLoader->getOptions('minify_time')."\" />\n";
		
	foreach($files['jscript'] as $file)
		if($file['include']) include($file['src']);
		else if(!$RI_CJLoader->get('minify') || $file['external']) echo "<script type='text/javascript' src='{$file['src']}'></script>\n";
		else echo "<script type=\"text/javascript\" src=\"min/?f={$file['src']}&amp;".$RI_CJLoader->getOptions('minify_time')."\"></script>\n";
}
//DEBUG: echo '<!-- I SEE cat: ' . $current_category_id . ' || vs cpath: ' . $cPath . ' || page: ' . $current_page . ' || template: ' . $current_template . ' || main = ' . ($this_is_home_page ? 'YES' : 'NO') . ' -->';
?>
</head>
<?php // NOTE: Blank line following is intended: ?>
