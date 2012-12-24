<?php

namespace plugins\riCjLoader;

/**
 * Required functions for the CSS/JS Loader
 *
 * @author yellow1912 (rubikin.com)
 * @author John William Robeson, Jr <johnny@localmomentum.net>
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License V2.0
 *
 * NOTES:
 * All .php files can be manipulated by PHP when they're called, and are copied in-full to the browser page
 */

use plugins\riPlugin\Plugin;

class Loader
{
    protected $template,
        $page_directory = '',
        $current_page = '',
        $request_type,
        $this_is_home_page,
        $cPath,
        $loaders = array(),
        $files = array(),
        $processed_files = array(),
        $handlers = array(),
        $options = array(
        'dirs' => array(),
        'loaders' => '*',
        'load_global' => true,
        'load_page' => true,
        'load_loaders' => true,
        'load_print' => true
    ),
        $inline = array(),
        $location = 1,
        $libs, $loaded_libs = array();

    public function __construct()
    {
        $this->options = array_merge($this->options, Plugin::get('settings')->get('riCjLoader'));
    }

    public function setGlobalVariables(){
        global $page_directory, $request_type, $template, $this_is_home_page, $cPath, $current_page;

        $this->template = $template;
        $this->page_directory = $page_directory;
        $this->request_type = $request_type;
        $this->this_is_home_page = $this_is_home_page;
        $this->cPath = $cPath;
        $this->current_page = $current_page;
    }

    function set($options){
        $this->options = array_merge($this->options, $options);
    }

    function getOption($key = '', $default = false){
        if(!empty($key))
            return isset($this->options[$key]) ? $this->options[$key] : $default;
        else return $this->options;
    }

    /**
     *
     * Load the file or set of files or libs
     * @param array $file array(array('path' => 'path/to/file', 'type' => 'css'))
     * @param string $location allows loading the file at header/footer or current location
     */
    function load($files, $location ='', $silent = false){

        $files = (array)$files;

        $previous_files = array();
        // rather costly operation here but we need to determine the location
        if(empty($location)){
            $location = ++$this->location;
            if(!$silent) echo  '<!-- ricjloader: ' . $location . ' -->';
        }
        // now we will have to echo out the string to be replaced here
        elseif($location !== 'header' && $location !== 'footer' && $location != $this->location)
            if(!$silent) echo  '<!-- ricjloader: ' . $location . ' -->';

        foreach($files as $file => $options){
            if(!is_array($options)) {
                $file = $options;
                $options = array();
            }

            // only add this file if it has not been requested for the same position
            if(!isset($this->files[$location]) || !in_array($file, $this->files[$location])){
                $options['ext'] = pathinfo($file, PATHINFO_EXTENSION);

                if(isset($options['inline']) && !empty($options['inline'])) {
                    $file = md5($options['inline']) . '.' . $options['ext'];
                }

                $this->files[$location][$file] = $options;
            }
        }
        return $location;
    }

    private function _load(&$files, $file, $location, $options){

        if(!isset($options['type'])) $options['type'] = $options['ext'] == 'css' ? 'css' : 'js';

        // for css, they MUST be loaded at header
        if($options['type'] == 'css' && is_integer($location)) {$location = 'header';}

        $this->getHandler($options['type'])->load($files, $file, $location, $options);
    }

    public function startInline($type = 'js', $location = ''){
        if($location !== 'header' && $location !== 'footer'){
            if(empty($location)) $location = $this->location;
            //if(!isset($this->files[$location]))
            //    echo  '<!-- ricjloader2:' . $location++ . ' -->';
        }

        $this->inline = array('type' => $type, 'location' => $location);
        ob_start();
    }

    public function endInline(){
        $this->load(array('inline.' . $this->inline['type'] => array('inline' => ob_get_clean())), $this->inline['location']);
    }

    private function getHandler($handler){
        if(!isset($this->handlers[$handler])){
            $this->handlers[$handler] = Plugin::get('riCjLoader.' . ucfirst($handler) . 'Handler');
        }
        return $this->handlers[$handler];
    }
    /**
     *
     * Inject the assets into the content of the page
     * @param string $content
     */
    public function injectAssets($content){

        // set the correct base
        $this->setCurrentPage();

        if($this->getOption('load_global')) $this->loadGlobal();

        if($this->getOption('load_page')) $this->loadPage();

        if($this->getOption('load_loaders')) $this->loadLoaders();

        $ordered_files = array();
        // scan the content to find out the real order of the loader
        preg_match_all("/(<!-- ricjloader:)(.*?)(-->)/", $content, $matches, PREG_SET_ORDER);

        $found_header = $found_footer = false;
        foreach ($matches as $val) {
            $val[2] = trim($val[2]);

            if(!$found_header && $val[2] == 'header') $found_header = true;
            elseif(!$found_footer && $val[2] == 'footer') $found_footer = true;

            if(isset($this->files[$val[2]]))
                $ordered_files[$val[2]] = $this->files[$val[2]];
        }

        if(!$found_header && isset($this->files['header']))
            $ordered_files['header'] = $this->files['header'];

        if(!$found_footer && isset($this->files['footer']))
            $ordered_files['footer'] = $this->files['footer'];

        $this->processFiles($ordered_files);

        foreach ($this->processed_files as $type => $locations){
            foreach($locations as $location => $files){

                $inject_content = $this->getHandler($type)->process($files, $this);
                // inject
                switch($location){
                    case 'header':
                        if(!$found_header)
                            $content = str_replace('</head>', $inject_content . '</head>', $content);
                        else
                            $content = str_replace('<!-- ricjloader: header -->', $inject_content . '<!-- ricjloader: header -->', $content);
                        break;
                    case 'footer':
                        if(!$found_footer)
                            $content = str_replace('</body>', $inject_content . '</body>', $content);
                        else
                            $content = str_replace('<!-- ricjloader: footer -->', $inject_content . '<!-- ricjloader: footer -->', $content);
                        break;
                    default:
                        $content = str_replace('<!-- ricjloader: ' . $location . ' -->', $inject_content . '<!-- ricjloader: ' . $location . ' -->', $content);
                        break;
                }
            }
        }
        return $content;
    }

    public function getAssetsArray(){
        // set the correct base
        $this->setCurrentPage();

        if($this->getOption('load_global')) $this->loadGlobal();

        if($this->getOption('load_page')) $this->loadPage();

        if($this->getOption('load_loaders')) $this->loadLoaders();

        $this->processFiles($this->files);

        $result = array();
        foreach ($this->processed_files as $type => $locations){
            foreach($locations as $location => $files){

                // we may want to do some caching here
                $result[$location][$type] = $this->getHandler($type)->processArray($files, $type, $this);
            }
        }

        return $result;
    }

    public function processFiles($ordered_files){

        //if(!empty($this->processed_files)) return $this->processed_files;

        // now we loop thru the $ordered_files to make sure each file is loaded only once
        $loaded_files = $to_load = array();
        foreach ($ordered_files as $location => $files){
            $location_loaded_files = array();
            foreach($files as $file => $options){
                if(!array_key_exists($file, $loaded_files)){
                    $loaded_files[$file] = $location;
                    $to_load[$location][$file] = $options;
                    $location_loaded_files[$file] = $options;
                }
                // if we encounter this file in the loaded list, it means that we will have to take all the loaded
                // files in this same location and put it IN FRONT OF this file location which is $loaded_files[$file]
                elseif(!empty($location_loaded_files)){

                    $to_load[$location] = array_diff($to_load[$location], $location_loaded_files);

                    array_KSplice2($to_load[$loaded_files[$file]], $file, 0, $location_loaded_files);

                    $location_loaded_files = array();
                }
            }
        }

        // now we will have to process the list of files to put them in their real type to process later
        foreach($to_load as $location => $files){
            foreach($files as $file => $options){
                switch($options['ext']){
                    // lib? load the library
                    case 'lib':
                        // we need to try loading the config file
                        $lib = str_replace('.lib', '', $file);

                        if(!in_array($lib, $this->loaded_libs)){
                            if (file_exists(__DIR__ . '/../configs/' . $lib . '.php'))
                            {
                                require (__DIR__ . '/../configs/' . $lib . '.php');
                                $this->libs[$lib] = !isset($this->libs[$lib]) ? $libs[$lib]: array_merge($libs[$lib], $this->libs[$lib]);
                            }
                            $this->loaded_libs[] = $lib;
                        }

                        if (isset($this->libs[$lib]))
                        {
                            $lib_versions = array_keys($this->libs[$lib]);

                            // if options are passed in
                            if(is_array($options)){
                                if (isset($options['min']) && (($pos = array_search($options['min'], $lib_versions)) != 0))
                                {
                                    $lib_versions = array_slice($lib_versions, $pos);
                                }

                                if (isset($options['max']) && (($pos = array_search($options['max'], $lib_versions)) < count($lib_versions)-1))
                                {
                                    array_splice($lib_versions, $pos+1);
                                }
                            }

                            if (empty($lib_versions))
                            {
                                // houston we have a problem
                                // TODO: we need to somehow print out the error in this case
                            }
                            else
                            {
                                // we prefer the latest version
                                $lib_version = end($lib_versions);

                                // add the files
                                if (isset($this->libs[$lib][$lib_version]['css_files']))
                                    foreach ($this->libs[$lib][$lib_version]['css_files'] as $css_file => $css_file_options)
                                    {
                                        if($this->getOption('cdn') && isset($css_file_options['cdn'])){
                                            $file = $this->request_type == 'NONSSL' ? $css_file_options['cdn']['http'] : $css_file_options['cdn']['https'];
                                            $this->_load($this->processed_files, $file, $location, array('type' => 'css'));
                                        }
                                        else
                                        {
                                            if(strpos($css_file_options['local'], "::") !== false){
                                                $local = explode("::", $css_file_options['local']);
                                                if(empty($local[1])) $local[1] = $css_file;
                                                $file = $this->findAsset($local[0] . '::' . $lib . '/' . $lib_version . '/' . $local[1]);
                                            }
                                            else
                                                $file = $this->findAsset('riCjLoader::' . $lib . '/' . $lib_version . '/' . (!empty($css_file_options['local']) ? $css_file_options['local'] : $css_file));
                                            $this->_load($this->processed_files, $file, $location, array('type' => 'css'));
                                        }
                                    }

                                if (isset($this->libs[$lib][$lib_version]['jscript_files']))
                                    foreach ($this->libs[$lib][$lib_version]['jscript_files'] as $jscript_file => $jscript_file_options)
                                    {
                                        if($this->getOption('cdn') && isset($jscript_file_options['cdn'])){
                                            $file = $this->request_type == 'NONSSL' ? $jscript_file_options['cdn']['http'] : $jscript_file_options['cdn']['https'];
                                            $this->_load($this->processed_files, $file, $location, array('type' => 'js'));
                                        }
                                        else
                                        {
                                            if(strpos($jscript_file_options['local'], "::") !== false){
                                                $local = explode("::", $jscript_file_options['local']);
                                                if(empty($local[1])) $local[1] = $jscript_file;
                                                $file = $this->findAsset($local[0] . '::' . $lib . '/' . $lib_version . '/' . $local[1]);
                                            }
                                            else
                                                $file = $this->findAsset('riCjLoader::' . $lib . '/' . $lib_version . '/' . (!empty($jscript_file_options['local']) ? $jscript_file_options['local'] : $jscript_file));
                                            $this->_load($this->processed_files, $file, $location, array('type' => 'js'));
                                        }
                                    }
                            }
                        }
                        break;
                    default:
                        $this->_load($this->processed_files, $file, $location, $options);
                        break;
                }
            }
        }

        return $this->processed_files;
    }

    private function strposArray($haystack, $needles) {
        $pos = false;
        if ( is_array($needles) ) {
            foreach ($needles as $str) {
                if ( is_array($str) ) {
                    $pos = $this->strposArray($haystack, $str);
                } else {
                    $pos = strpos($haystack, $str);
                }
                if ($pos !== FALSE) {
                    break;
                }
            }
        } else {
            $pos = strpos($haystack, $needles);
        }
        return $pos;
    }

    public function loadGlobal(){
        /**
         * load all template-specific stylesheets, named like "style*.css", alphabetically
         */
        $files = $this->findAssetsByPattern('.css', 'css', 'css', '/^style/');
        $this->load($files, 'header');

        /**
         * load all template-specific stylesheets, named like "style*.php", alphabetically
         */
        $files = $this->findAssetsByPattern('.php', 'css', 'css', '/^style/');
        $this->load($files, 'header');

        /**
         * load all site-wide jscript_*.js files from includes/templates/YOURTEMPLATE/jscript, alphabetically
         */
        $files = $this->findAssetsByPattern('.js', 'jscript', 'js', '/^jscript_/');
        $this->load($files, 'footer');

        /**
         * include content from all site-wide jscript_*.php files from includes/templates/YOURTEMPLATE/jscript, alphabetically.
         */
        $files = $this->findAssetsByPattern('.php', 'jscript', 'js', '/^jscript_/');
        $this->load($files, 'footer');

        /**
         * load printer-friendly stylesheets -- named like "print*.css", alphabetically
         */
        if($this->getOption('load_print')) {
            $directory_array = $this->findAssetsByPattern('.css', 'css', 'css', '/^print/');
            // TODO: custom processing this
            foreach ($directory_array as $key => $value) {
                $this->load(array($key => array('type' => 'css', 'media' => 'print')), 'header');
            }
        }

        /*
           if (file_exists(DIR_FS_CATALOG . 'plugins/riCjLoader/lib/browser.php') && floatval(phpversion()) > 5) {
           include(DIR_FS_CATALOG . 'plugins/riCjLoader/lib/browser.php');
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
           $this->addAssets($files, 'jscript');

           // get the browser version specific files
           $files = $this->findAssets('.css', 'css', "/^{$browser_name}{$browser_version}-/", -100);
           $this->addAssets($files, 'css');

           $directory_array = $this->findAssets('.js', 'jscript', "/^{$browser_name}{$browser_version}-/", -500);
           $this->addAssets($files, 'jscript');
           }
           */

    }

    public function loadPage(){
        /**
         * TODO: we shouldn't use $_GET here, it breaks the encapsulation
         * load stylesheets on a per-page/per-language/per-product/per-manufacturer/per-category basis. Concept by Juxi Zoza.
         */
        $manufacturers_id = (isset($_GET['manufacturers_id'])) ? $_GET['manufacturers_id'] : '';
        $tmp_products_id = (isset($_GET['products_id'])) ? (int)$_GET['products_id'] : '';
        $tmp_pagename = ($this->this_is_home_page) ? 'index_home' : $this->current_page;
        $sheets_array = array('/' . $_SESSION['language'] . '_stylesheet',
            '/' . $tmp_pagename,
            '/' . $_SESSION['language'] . '_' . $tmp_pagename,
            '/c_' . $this->cPath,
            '/' . $_SESSION['language'] . '_c_' . $this->cPath,
            '/m_' . $manufacturers_id,
            '/' . $_SESSION['language'] . '_m_' . (int)$manufacturers_id,
            '/p_' . $tmp_products_id,
            '/' . $_SESSION['language'] . '_p_' . $tmp_products_id
        );

        foreach ($sheets_array as $key => $value) {
            $perpagefile = $this->getAssetDir('.css', 'css') . $value . '.css';
            if (file_exists($perpagefile)) $this->load(array($perpagefile => array('type' => 'css')), 'header');

            $perpagefile = $this->getAssetDir('.php', 'css') . $value . '.php';
            if (file_exists($perpagefile)) $this->load(array($perpagefile => array('type' => 'css')), 'header');

            $perpagefile = $this->getAssetDir('.js', 'jscript') . $value . '.js';
            if (file_exists($perpagefile)) $this->load(array($perpagefile => array('type' => 'js')), 'footer');

            $perpagefile = $this->getAssetDir('.php', 'jscript') . $value . '.php';
            if (file_exists($perpagefile)) $this->load(array($perpagefile => array('type' => 'js')), 'footer');

        }

        /**
         * load all page-specific jscript_*.js files from includes/modules/pages/PAGENAME, alphabetically
         */
        $files = $this->template->get_template_part($this->page_directory, '/^jscript_/', '.js');
        foreach ($files as $key => $value) {
            $this->load(array("$this->page_directory/$value" => array('type' => 'js')), 'footer');
        }

        /**
         * include content from all page-specific jscript_*.php files from includes/modules/pages/PAGENAME, alphabetically.
         */
        $files = $this->template->get_template_part($this->page_directory, '/^jscript_/', '.php');
        foreach ($files as $key => $value) {
            $this->load(array("$this->page_directory/$value" => array('type' => 'js')), 'footer');
        }
    }

    /**
     * Get asset directory
     */
    function getAssetDir($extension, $directory, $template = DIR_WS_TEMPLATE)
    {
        return $this->template->get_template_dir($extension, $template, $this->current_page, $directory);
    }

    /**
     * Find asset files in a template directory
     *
     * @param string extension - file extension to look for
     * @param directory - subdirectory of the template containing the assets
     */
    function findAssetsByPattern($extension, $directory, $type, $file_pattern = '')
    {
        $this->templateDir = $this->getAssetDir($extension, $directory, DIR_WS_TEMPLATE);
        $allFiles = $this->template->get_template_part($this->templateDir, $file_pattern, $extension);

        if($this->getOption('inheritance') != ''){
            $defaultDir = $this->getAssetDir($extension, $directory, DIR_WS_TEMPLATES. $this->getOption('inheritance'));
            $allFiles = array_unique(array_merge($this->template->get_template_part($defaultDir, $file_pattern, $extension),$allFiles));
        }

        $files = array();
        foreach ($allFiles as $file) {
            // case 1: file is in server but full path not passed, assuming it is under corresponding template css/js folder
            if(file_exists(DIR_FS_CATALOG.DIR_WS_TEMPLATE.$directory.'/'.$file)){
                $files[DIR_WS_TEMPLATE.$directory.'/'.$file] = array('type' => $type);
            }
            elseif ($this->getOption('inheritance') != '' && file_exists(DIR_FS_CATALOG.DIR_WS_TEMPLATES.$this->getOption('inheritance').'/'.$directory.'/'.$file)){
                $files[DIR_WS_TEMPLATES.$this->getOption('inheritance').'/'.$directory.'/'.$file] = array('type' => $directory);
            }
        }

        return $files;
    }

    public function findAssets($files){
        if(!is_array($files)) $files = array($files => null);

        $list = array();
        foreach ($files as $file => $options) {
            $path = $this->findAsset($file, $options);
            if(!empty($path)) $list[$path] =  $options;
        }
        return $list;
    }

    public function findAsset($file, &$options = array()){
        $error = false; $external = false; $path = '';

        // plugin?
        if(strpos($file, '::') !== false){
            $file = explode('::', $file);

            if(!file_exists($path = DIR_FS_CATALOG . DIR_WS_TEMPLATE . 'plugins/' . $file[0] . '/content/resources/' . $file[1]))
                if(!file_exists($path = DIR_FS_CATALOG . 'plugins/' . $file[0] . '/content/resources/' . $file[1]))
                    $error = true;
        }
        // inline?
        elseif(!empty($options['inline'])){
            $path = $file;
        }
        else{
            // external?
            if($this->strposArray($file, $this->options['supported_externals']) !== false){
                $path = $file;
                $external = true;
            }
            else{
                $error = true;
                // can we find the path?
                foreach($this->getOption('dirs') as $dir){
                    $path = str_replace('%type%', $this->getHandler($options['type'])->getTemplateBaseDir(), $dir) . $file;
                    if(file_exists(DIR_FS_CATALOG . $path)){
                        $error = false;
                        break;
                    }
                }

                //
                if($error && file_exists($path = $file)) $error = false;
            }
        }


        if(!$error){
            $options['external'] = $external;
            return $path;
        }
        else
        {
            return '';// some kind of error logging here
        }
    }

    /**
     * @param array $files
     * @return array
     */
    public function get($files){
        $list = $this->findAssets($files);
        $result = array();
        foreach($list as $file => $options){
            $result[] = array(
                'path' => Plugin::get('riUtility.File')->getRelativePath(DIR_FS_CATALOG, $file),
                'options' => $options
            );
        }

        return $result;
    }

    // for backward compatibility
    function addLibs ($libs){
        foreach ($libs as $lib => $versions)
        {
            foreach ($versions as $version => $options){
                if(!isset($this->libs[$lib]))
                    $this->libs[$lib][$version] = $options;
            }
        }
    }

    function setCurrentPage(){
        if(!$this->getOption('admin')){

            // set current page
            if($this->this_is_home_page)
                $this->current_page = 'index_home';
            elseif($this->current_page == 'index'){
                if(isset($_GET['cPath']))
                    $this->current_page = 'index_category';
                elseif(isset($_GET['manufacturers_id']))
                    $this->current_page = 'index_manufacturer';
            }
        }
        else{
            $this->current_page = preg_replace('/\.php/','',substr(strrchr($_SERVER['PHP_SELF'],'/'),1),1);
        }
    }

    function addLoaders($loaders, $multi = false){
        if($multi)
            $this->loaders = array_merge($this->loaders, $loaders);
        else
            $this->loaders[] = $loaders;
    }

    public function loadLoaders()
    {
        $this->template = $this->template;
        $this->page_directory = $this->page_directory;;

        if($this->getOption('loaders') == '*')
        {
            $directory_array = $this->template->get_template_part(DIR_WS_TEMPLATE.'auto_loaders', '/^loader_/', '.php');
            while(list ($key, $value) = each($directory_array)) {
                /**
                 * include content from all site-wide loader_*.php files from includes/templates/YOURTEMPLATE/jscript/auto_loaders, alphabetically.
                 */
                require(DIR_WS_TEMPLATE.'auto_loaders'. '/' . $value);
            }
        }
        elseif(count($this->getOption('loaders')) > 0)
        {
            foreach($this->getOption('loaders') as $loader)
                if(file_exists($path = DIR_WS_TEMPLATE.'auto_loaders'. '/loader_' . $loader .'.php')) require($path);
        }
        else
            return;
        if(count($loaders) > 0)	$this->addLoaders($loaders, true);

        /**
         * load the loader files
         */
        if((is_array($this->loaders)) && count($this->loaders) > 0)	{
            foreach($this->loaders as $loader){
                $load = false;
                if(isset($loader['conditions']['pages']) && (in_array('*', $loader['conditions']['pages']) || in_array($this->current_page, $loader['conditions']['pages']))){
                    $load = true;
                }
                else{
                    if(isset($loader['conditions']['call_backs']))
                        foreach($loader['conditions']['call_backs'] as $function){
                            $f = explode(',',$function);
                            if(count($f) == 2){
                                $load = call_user_func(array($f[0], $f[1]));
                            }
                            else $load = $function();
                        }
                }

                // do we satistfy al the conditions to load?
                if($load){
                    $files = array();
                    if(isset($loader['libs'])){
                        foreach ($loader['libs'] as $key => $value) {
                            $files[$key . '.lib'] = $value;
                        }
                    }
                    if(isset($loader['jscript_files'])){
                        asort($loader['jscript_files']);
                        foreach ($loader['jscript_files'] as $key => $value) {
                            $files[$key] = array('type' => 'js');
                        }
                    }
                    if(isset($loader['css_files'])){
                        asort($loader['css_files']);
                        foreach ($loader['css_files'] as $key => $value) {
                            $files[$key] = array('type' => 'css');
                        }
                    }
                    $this->load($files, 'footer');
                }
            }
        }
    }

    /**
     * we put the browser methods here because we want to users to be able to easily access
     * it within templates
     */

    /**
     * @param $browser_name
     * @return boolean
     */
    public function isBrowser($browser_name){
        return Plugin::get('riCjLoader.' . $this->options['browser_handler'])->isBrowser($browser_name);
    }

    /**
     * @return mixed
     */
    public function getBrowserVersion(){
        return Plugin::get('riCjLoader.' . $this->options['browser_handler'])->getVersion();
    }
}
