<?php
/**
 * Created by RubikIntegration Team.
 * Date: 12/28/12
 * Time: 2:28 PM
 * Question? Come to our website at http://rubikintegration.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace plugins\riCjLoader;

class Finder
{
    protected $kernel;

    protected $template;

    protected $page_directory = '';

    protected $dirs;

    protected $baseDirs = array();

    protected $current_page = '';

    protected $request_type;

    protected $this_is_home_page;

    protected $cPath;

    /**
     * @var array
     */
    protected $supportedExternals = array();

    /**
     * @var
     */
    protected $webDir;
    /**
     * @var
     */
    protected $currentTemplateDir;

    public function __construct($kernel)
    {
        $this->kernel = $kernel;
        $this->currentTemplateDir = $kernel->getContainer()->getParameter('store.template_dir') . '/' . $kernel->getContainer()->getParameter('store.current_template');

        if($kernel->getContainer()->get('environment')->getSubEnvironment() == "frontend") {
            $this->setDirs(array(
                $this->currentTemplateDir . '/%type%/',
                $kernel->getContainer()->getParameter('store.template_dir') . '/template_default/%type%/'
            ));
        }
        else {
            $this->setDirs(array(
                DIR_WS_ADMIN
            ));
        }

        $this->webDir = $this->kernel->getContainer()->getParameter('web_dir');
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
        $templateDir = $this->getAssetDir($extension, $directory, $this->currentTemplateDir);
        $allFiles = $this->template->get_template_part($templateDir, $file_pattern, $extension);

        $files = array();
        foreach ($allFiles as $file) {
            // file is in server but full path not passed, assuming it is under corresponding template css/js folder
            if (file_exists($this->currentTemplateDir . '/' . $directory . '/' . $file)) {
                $files[DIR_WS_TEMPLATE . $directory . '/' . $file] = array('type' => $type);
            }
        }

        return $files;
    }

    public function findAssets($files)
    {
        if (!is_array($files)) {
            $files = array($files => null);
        }

        $list = array();
        foreach ($files as $file => $options) {
            $path = $this->findAsset($file, $options);
            if (!empty($path)) {
                $list[$path] = $options;
            }
        }
        return $list;
    }

    public function findAsset($file, &$options = array())
    {
        $error = false;
        $external = false;
        $path = '';

        // external?
        if (!empty($options['inline'])) {
            $path = $file;
        } elseif ($options["external"] || $this->strposArray($file, $this->supportedExternals) !== false) {
            $path = $file;
            $external = true;
        } // absolute?
        elseif (file_exists($path = $file)) {

        } // plugin or bundle?
        elseif (strpos($file, ':') !== false) {
            $file = explode(':', $file);
            //plugin
            if (substr($file[0], -6) !== 'Bundle') {
                if (!file_exists($path = sprintf($this->currentTemplateDir . "/plugins/%s/Resources/public/%s", $file[0], $file[1]))) {
                    if (!file_exists($path = sprintf($this->kernel->getContainer()->getParameter('plugins.root_dir') . "/%s/Resources/public/%s", $file[0], $file[1]))) {
                        $error = true;
                    }
                }
            } else {
                // bundle
                // TODO: make a parser to parse
                if (!file_exists($path = sprintf($this->kernel->getBundle($file[0])->getPath() . "/Resources/public/%s", $file[1]))) {
                    $error = true;
                }
            }
        } else {
            $error = true;
            // can we find the path?
            foreach ($this->dirs as $dir) {
                $path = str_replace('%type%', $this->baseDirs[$options['type']], $dir) . $file;
                if (file_exists($path)) {
                    $error = false;
                    break;
                }
            }
            //
            if ($error && file_exists($path = $file)) {
                $error = false;
            }
        }

        if (!$error) {
            $options['external'] = $external;
            return $path;
        } else {
            return ''; // some kind of error logging here
        }
    }

    /**
     * @param array $files
     * @return array
     */
    public function get($files)
    {
        $list = $this->findAssets($files);
        $result = array();
        foreach ($list as $file => $options) {
            $result[] = array(
                'path' => $this->kernel->getContainer()->get("utility.file")->getRelativePath($this->webDir, $file),
                'options' => $options
            );
        }

        return $result;
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
     *
     */
    public function findLoaders($loaders_list)
    {
        $loaders = array();
        if ($loaders_list == '*') {
            $directory_array = $this->template->get_template_part($this->currentTemplateDir . '/auto_loaders', '/^loader_/', '.php');
            while (list ($key, $value) = each($directory_array)) {
                /**
                 * include content from all site-wide loader_*.php files from includes/templates/YOURTEMPLATE/jscript/auto_loaders, alphabetically.
                 */
                require($this->currentTemplateDir . '/auto_loaders' . '/' . $value);
            }
        } elseif (is_array($loaders_list) && count($loaders_list) > 0) {
            foreach ($this->getOption('loaders') as $loader) {
                if (file_exists($path = $this->currentTemplateDir . '/auto_loaders' . '/loader_' . $loader . '.php')) {
                    require($path);
                }
            }
        } else {
            return $loaders;
        }

        return $loaders;
    }

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

    public function setSupportedExternals($supportedExternals)
    {
        $this->supportedExternals = $supportedExternals;
    }

    public function setDirs($dirs)
    {
        $this->dirs = $dirs;
    }

    /**
     * @param $handler
     * @param $dir
     */
    public function setBaseDir($handler, $dir)
    {
        $this->baseDirs[$handler] = $dir;
    }

    /**
     * @param $haystack
     * @param $needles
     * @return bool|int
     */
    private function strposArray($haystack, $needles)
    {
        $pos = false;
        if (is_array($needles)) {
            foreach ($needles as $str) {
                if (is_array($str)) {
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
}
