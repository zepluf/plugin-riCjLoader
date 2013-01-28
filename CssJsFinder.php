<?php
/**
 * Created by RubikIntegration Team.
 * Date: 1/23/13
 * Time: 2:45 PM
 * Question? Come to our website at http://rubikintegration.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace plugins\riCjLoader;

use Zepluf\Bundle\StoreBundle\AssetFinder;

class CssJsFinder extends AssetFinder
{
    /**
     *
     */
    function setCurrentPage()
    {
        if ($this->kernel->getContainer()->get("environment")->getSubEnvironment() == "frontend") {

            // set current page
            if ($this->this_is_home_page) {
                $this->current_page = 'index_home';
            } elseif ($this->current_page == 'index') {
                if (isset($_GET['cPath'])) {
                    $this->current_page = 'index_category';
                } elseif (isset($_GET['manufacturers_id'])) {
                    $this->current_page = 'index_manufacturer';
                }
            }
        } else {
            $this->current_page = preg_replace('/\.php/', '', substr(strrchr($_SERVER['PHP_SELF'], '/'), 1), 1);
        }
    }

    public function setGlobalVariables()
    {
        global $page_directory, $request_type, $template, $this_is_home_page, $cPath, $current_page;

        $this->template = $template;
        $this->page_directory = $page_directory;
        $this->request_type = $request_type;
        $this->this_is_home_page = $this_is_home_page;
        $this->cPath = $cPath;
        $this->current_page = $current_page;
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
        $templateDir = $this->getAssetDir($extension, $directory, $this->templatesDir . '/' . $this->currentTemplate  );
        $allFiles = $this->template->get_template_part($templateDir, $file_pattern, $extension);

        $files = array();
        foreach ($allFiles as $file) {
            // file is in server but full path not passed, assuming it is under corresponding template css/js folder
            if (file_exists($this->templatesDir . '/' . $this->currentTemplate   . '/' . $directory . '/' . $file)) {
                $files[DIR_WS_TEMPLATE . $directory . '/' . $file] = array('type' => $type);
            }
        }

        return $files;
    }

    /**
     *
     */
    public function findLoaders($loaders_list)
    {
        $loaders = array();
        if ($loaders_list == '*') {
            $directory_array = $this->template->get_template_part($this->templatesDir . '/' . $this->currentTemplate   . '/auto_loaders', '/^loader_/', '.php');
            while (list ($key, $value) = each($directory_array)) {
                /**
                 * include content from all site-wide loader_*.php files from includes/templates/YOURTEMPLATE/jscript/auto_loaders, alphabetically.
                 */
                require($this->templatesDir . '/' . $this->currentTemplate   . '/auto_loaders' . '/' . $value);
            }
        } elseif (is_array($loaders_list) && count($loaders_list) > 0) {
            foreach ($this->getOption('loaders') as $loader) {
                if (file_exists($path = $this->templatesDir . '/' . $this->currentTemplate   . '/auto_loaders' . '/loader_' . $loader . '.php')) {
                    require($path);
                }
            }
        } else {
            return $loaders;
        }

        return $loaders;
    }

    /**
     * @param $loaders
     * @return array
     */
    public function findLoadersFiles($loaders)
    {
        $files_array = array();

        /**
         * load the loader files
         */
        if ((is_array($loaders)) && count($loaders) > 0) {
            foreach ($loaders as $loader) {
                $load = false;
                if (isset($loader['conditions']['pages']) && (in_array('*', $loader['conditions']['pages']) || in_array($this->current_page, $loader['conditions']['pages']))) {
                    $load = true;
                } else {
                    if (isset($loader['conditions']['call_backs'])) {
                        foreach ($loader['conditions']['call_backs'] as $function) {
                            $f = explode(',', $function);
                            if (count($f) == 2) {
                                $load = call_user_func(array($f[0], $f[1]));
                            } else $load = $function();
                        }
                    }
                }

                // do we satistfy al the conditions to load?
                if ($load) {
                    $files = array();
                    if (isset($loader['libs'])) {
                        foreach ($loader['libs'] as $key => $value) {
                            $files[$key . '.lib'] = $value;
                        }
                    }
                    if (isset($loader['jscript_files'])) {
                        asort($loader['jscript_files']);
                        foreach ($loader['jscript_files'] as $key => $value) {
                            $files[$key] = array('type' => 'js');
                        }
                    }
                    if (isset($loader['css_files'])) {
                        asort($loader['css_files']);
                        foreach ($loader['css_files'] as $key => $value) {
                            $files[$key] = array('type' => 'css');
                        }
                    }
                    $files_array[] = array("files" => $files, "location" => 'footer');
                }
            }
        }

        return $files_array;
    }

    /**
     * @return array
     */
    public function findGlobalFiles($loadPrint)
    {
        $files_array = array();
        /**
         * load all template-specific stylesheets, named like "style*.css", alphabetically
         */
        $files = $this->findAssetsByPattern('.css', 'css', 'css', '/^style/');
        if (!empty($files)) {
            $files_array[] = array("files" => $files, "location" => 'header');
        }

        /**
         * load all template-specific stylesheets, named like "style*.php", alphabetically
         */
        $files = $this->findAssetsByPattern('.php', 'css', 'css', '/^style/');
        if (!empty($files)) {
            $files_array[] = array("files" => $files, "location" => 'header');
        }

        /**
         * load all site-wide jscript_*.js files from includes/templates/YOURTEMPLATE/jscript, alphabetically
         */
        $files = $this->findAssetsByPattern('.js', 'jscript', 'js', '/^jscript_/');
        if (!empty($files)) {
            $files_array[] = array("files" => $files, "location" => 'footer');
        }

        /**
         * include content from all site-wide jscript_*.php files from includes/templates/YOURTEMPLATE/jscript, alphabetically.
         */
        $files = $this->findAssetsByPattern('.php', 'jscript', 'js', '/^jscript_/');
        if (!empty($files)) {
            $files_array[] = array("files" => $files, "location" => 'header');
        }

        /**
         * load printer-friendly stylesheets -- named like "print*.css", alphabetically
         */
        if ($loadPrint) {
            $directory_array = $this->findAssetsByPattern('.css', 'css', 'css', '/^print/');
            // TODO: custom processing this
            foreach ($directory_array as $key => $value) {
                $files_array[] = array("files" => array($key => array('type' => 'css', 'media' => 'print')), "location" => 'header');
            }
        }

        return $files_array;
    }

    /**
     * @param $loader
     * @return array
     */
    public function findPageFiles()
    {
        $files_array = array();
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
            if (file_exists($perpagefile)) {
                $files_array[] = array("files" => array($perpagefile => array('type' => 'css')), "location" => 'header');
            }

            $perpagefile = $this->getAssetDir('.php', 'css') . $value . '.php';
            if (file_exists($perpagefile)) {
                $files_array[] = array("files" => array($perpagefile => array('type' => 'css')), "location" => 'header');
            }

            $perpagefile = $this->getAssetDir('.js', 'jscript') . $value . '.js';
            if (file_exists($perpagefile)) {
                $files_array[] = array("files" => array($perpagefile => array('type' => 'js')), "location" => 'footer');
            }

            $perpagefile = $this->getAssetDir('.php', 'jscript') . $value . '.php';
            if (file_exists($perpagefile)) {
                $files_array[] = array("files" => array($perpagefile => array('type' => 'js')), "location" => 'footer');
            }
        }

        /**
         * load all page-specific jscript_*.js files from includes/modules/pages/PAGENAME, alphabetically
         */
        $files = $this->template->get_template_part($this->page_directory, '/^jscript_/', '.js');
        foreach ($files as $key => $value) {
            $files_array[] = array("files" => array("$this->page_directory/$value" => array('type' => 'js')), "location" => 'footer');
        }

        /**
         * include content from all page-specific jscript_*.php files from includes/modules/pages/PAGENAME, alphabetically.
         */
        $files = $this->template->get_template_part($this->page_directory, '/^jscript_/', '.php');
        foreach ($files as $key => $value) {
            $files_array[] = array("files" => array("$this->page_directory/$value" => array('type' => 'js')), "location" => 'footer');
        }

        return $files_array;
    }
}