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

use Symfony\Component\Templating\Helper\Helper;

class LoaderHelper extends Helper
{


    protected $loaders = array();

    protected $files = array();

    protected $processed_files = array();

    protected $handlers = array();

    protected $options = array(
        'dirs' => array(),
        'loaders' => '*',
        'load_global' => true,
        'load_page' => true,
        'load_loaders' => true,
        'load_print' => true
    );

    protected $inline = array();

    protected $location = 1;

    protected $libs;

    protected $loaded_libs = array();

    /**
     * the browser handler
     *
     * @var
     */
    protected $browser;

    /**
     * @var
     */
    protected $fileUtility;

    /**
     * @var
     */
    protected $finder;

    /**
     * @var
     */
    protected $filters;

    public function __construct($settings, $browser, $finder)
    {
        $this->options = array_merge($this->options, $settings->get('plugins.ricjloader.settings'));

        $this->browser = $browser;

        $this->finder = $finder;

        $this->finder->setSupportedExternals($this->options["supported_externals"]);
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param $id
     * @param $handler
     */
    public function setHandler($id, $handler)
    {
        if(in_array($id, $this->options['handlers'])) {
            $this->handlers[$id] = $handler;

            $this->finder->setBaseDir($id, $handler->getTemplateBaseDir());
        }
    }

    /**
     * @param $id
     * @param $filter
     */
    public function setFilter($id, $filter)
    {
        if(isset($this->options['filters'][$id])) {
            $this->filters[$id] = array('filter' => $filter, 'options' => $this->options['filters'][$id]);;
        }
    }

    /**
     * returns the name of this helper
     *
     * @return string
     */
    public function getName()
    {
        return 'loader';
    }

    /**
     * @param $options
     */
    function set($options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param string $key
     * @param bool $default
     * @return array|bool
     */
    public function getOption($key = '', $default = false)
    {
        if (!empty($key)) {
            return isset($this->options[$key]) ? $this->options[$key] : $default;
        } else {
            return $this->options;
        }
    }

    /**
     * Load the file or set of files or libs
     *
     * @param array $file array(array('path' => 'path/to/file', 'type' => 'css'))
     * @param string $location allows loading the file at header/footer or current location
     */
    public function load($files, $location = '', $silent = false)
    {
        $files = (array)$files;

        // rather costly operation here but we need to determine the location
        if (empty($location)) {
            $location = ++$this->location;
            if (!$silent) {
                echo  '<!-- ricjloader: ' . $location . ' -->';
            }
        } // now we will have to echo out the string to be replaced here
        elseif ($location !== 'header' && $location !== 'footer' && $location != $this->location) {
            if (!$silent) {
                echo  '<!-- ricjloader: ' . $location . ' -->';
            }
        }

        foreach ($files as $file => $options) {
            if (!is_array($options)) {
                $file = $options;
                $options = array();
            }

            // only add this file if it has not been requested for the same position
            if (!isset($this->files[$location]) || !in_array($file, $this->files[$location])) {
                $options['ext'] = pathinfo($file, PATHINFO_EXTENSION);

                if (isset($options['inline']) && !empty($options['inline'])) {
                    $file = md5($options['inline']) . '.' . $options['ext'];
                }

                $this->files[$location][$file] = $options;
            }
        }
        return $location;
    }

    /**
     * @param string $type
     * @param string $location
     */
    public function startInline($type = 'js', $location = '')
    {
        if ($location !== 'header' && $location !== 'footer') {
            if (empty($location)) {
                $location = $this->location;
            }
        }

        $this->inline = array('type' => $type,
            'location' => $location);
        ob_start();
    }

    /**
     *
     */
    public function endInline()
    {
        $this->load(array('inline.' . $this->inline['type'] => array('inline' => ob_get_clean())), $this->inline['location']);
    }

    /**
     * @param $type
     * @return mixed
     */
    private function getHandler($type)
    {
        return $this->handlers[$type];
    }

    /**
     *
     * Inject the assets into the content of the page
     *
     * @param string $content
     */
    public function injectAssets($content)
    {
        if ($this->getOption('load_global')) {
            $this->loadGlobal();
        }

        if ($this->getOption('load_page')) {
            $this->loadPage($this);
        }

        if ($this->getOption('load_loaders')) {
            $this->loadLoaders();
        }

        $ordered_files = array();
        // scan the content to find out the real order of the loader
        preg_match_all("/(<!-- ricjloader:)(.*?)(-->)/", $content, $matches, PREG_SET_ORDER);

        $found_header = $found_footer = false;
        foreach ($matches as $val) {
            $val[2] = trim($val[2]);

            if (!$found_header && $val[2] == 'header') {
                $found_header = true;
            } elseif (!$found_footer && $val[2] == 'footer') {
                $found_footer = true;
            }

            if (isset($this->files[$val[2]])) {
                $ordered_files[$val[2]] = $this->files[$val[2]];
            }
        }

        if (!$found_header && isset($this->files['header'])) {
            $ordered_files['header'] = $this->files['header'];
        }

        if (!$found_footer && isset($this->files['footer'])) {
            $ordered_files['footer'] = $this->files['footer'];
        }

        $this->processFiles($ordered_files);

        foreach ($this->processed_files as $type => $locations) {
            foreach ($locations as $location => $files) {

                $inject_content = $this->getHandler($type)->process($files, $this->getOption("cache"), $this->finder, $this->filters);
                // inject
                switch ($location) {
                    case 'header':
                        if (!$found_header) {
                            $content = str_replace('</head>', $inject_content . '</head>', $content);
                        } else {
                            $content = str_replace('<!-- ricjloader: header -->', $inject_content . '<!-- ricjloader: header -->', $content);
                        }
                        break;
                    case 'footer':
                        if (!$found_footer) {
                            $content = str_replace('</body>', $inject_content . '</body>', $content);
                        } else {
                            $content = str_replace('<!-- ricjloader: footer -->', $inject_content . '<!-- ricjloader: footer -->', $content);
                        }
                        break;
                    default:
                        $content = str_replace('<!-- ricjloader: ' . $location . ' -->', $inject_content . '<!-- ricjloader: ' . $location . ' -->', $content);
                        break;
                }
            }
        }

        return $content;
    }

    /**
     * @return array
     */
    public function getAssetsArray()
    {
        if ($this->getOption('load_global')) {
            $this->loadGlobal();
        }

        if ($this->getOption('load_page')) {
            $this->loadPage();
        }

        if ($this->getOption('load_loaders')) {
            $this->loadLoaders();
        }

        $this->processFiles($this->files);

        $result = array();
        foreach ($this->processed_files as $type => $locations) {
            foreach ($locations as $location => $files) {

                // we may want to do some caching here
                $result[$location][$type] = $this->getHandler($type)->processArray($files, $type, $this);
            }
        }

        return $result;
    }

    /**
     * @param $ordered_files
     * @return array
     */
    public function processFiles($ordered_files)
    {
        // now we loop thru the $ordered_files to make sure each file is loaded only once
        $loaded_files = $to_load = array();
        foreach ($ordered_files as $location => $files) {
            $location_loaded_files = array();
            foreach ($files as $file => $options) {
                if (!array_key_exists($file, $loaded_files)) {
                    $loaded_files[$file] = $location;
                    $to_load[$location][$file] = $options;
                    $location_loaded_files[$file] = $options;
                } // if we encounter this file in the loaded list, it means that we will have to take all the loaded
                // files in this same location and put it IN FRONT OF this file location which is $loaded_files[$file]
                elseif (!empty($location_loaded_files)) {

                    $to_load[$location] = array_diff($to_load[$location], $location_loaded_files);

                    array_KSplice2($to_load[$loaded_files[$file]], $file, 0, $location_loaded_files);

                    $location_loaded_files = array();
                }
            }
        }

        // now we will have to process the list of files to put them in their real type to process later
        foreach ($to_load as $location => $files) {
            foreach ($files as $file => $options) {
                switch ($options['ext']) {
                    // lib? load the library
                    case 'lib':
                        // we need to try loading the config file
                        $lib = str_replace('.lib', '', $file);

                        if (!in_array($lib, $this->loaded_libs)) {
                            if (file_exists(__DIR__ . '/Resources/config/libs/' . $lib . '.php')) {
                                require (__DIR__ . '/Resources/config/libs/' . $lib . '.php');
                                $this->libs[$lib] = !isset($this->libs[$lib]) ? $libs[$lib] : array_merge($libs[$lib], $this->libs[$lib]);
                            }
                            $this->loaded_libs[] = $lib;
                        }

                        if (isset($this->libs[$lib])) {
                            $lib_versions = array_keys($this->libs[$lib]);

                            // if options are passed in
                            if (is_array($options)) {
                                if (isset($options['min']) && (($pos = array_search($options['min'], $lib_versions)) != 0)) {
                                    $lib_versions = array_slice($lib_versions, $pos);
                                }

                                if (isset($options['max']) && (($pos = array_search($options['max'], $lib_versions)) < count($lib_versions) - 1)) {
                                    array_splice($lib_versions, $pos + 1);
                                }
                            }

                            if (empty($lib_versions)) {
                                // houston we have a problem
                                // TODO: we need to somehow print out the error in this case
                            } else {
                                // we prefer the latest version
                                $lib_version = end($lib_versions);

                                // add the files
                                if (isset($this->libs[$lib][$lib_version]['css_files'])) {
                                    $options['type'] = 'css';

                                    foreach ($this->libs[$lib][$lib_version]['css_files'] as $css_file => $css_file_options) {
                                        if ($this->getOption('cdn') && isset($css_file_options['cdn'])) {
                                            $file = $this->request_type == 'NONSSL' ? $css_file_options['cdn']['http'] : $css_file_options['cdn']['https'];
                                            $this->_load($this->processed_files, $file, $location, array('type' => 'css', 'external' => true));
                                        } else {
                                            if (strpos($css_file_options['local'], ":") !== false) {
                                                $local = explode(":", $css_file_options['local']);

                                                if (empty($local[1])) {
                                                    $local[1] = $css_file;
                                                }

                                                $file = $this->finder->findAsset($local[0] . ':' . $lib . '/' . $lib_version . '/' . $local[1], $options);
                                            } else {
                                                $file = $this->finder->findAsset('riCjLoader:libs/' . $lib . '/' . $lib_version . '/' . (!empty($css_file_options['local']) ? $css_file_options['local'] : $css_file), $options);
                                            }

                                            $this->_load($this->processed_files, $file, $location, $options);
                                        }
                                    }
                                }
                                if (isset($this->libs[$lib][$lib_version]['jscript_files'])) {
                                    $options['type'] = 'js';

                                    foreach ($this->libs[$lib][$lib_version]['jscript_files'] as $jscript_file => $jscript_file_options) {
                                        if ($this->getOption('cdn') && isset($jscript_file_options['cdn'])) {
                                            $file = $this->request_type == 'NONSSL' ? $jscript_file_options['cdn']['http'] : $jscript_file_options['cdn']['https'];
                                            $this->_load($this->processed_files, $file, $location, array('type' => 'js', 'external' => true));
                                        } else {
                                            if (strpos($jscript_file_options['local'], ":") !== false) {
                                                $local = explode(":", $jscript_file_options['local']);
                                                if (empty($local[1])) $local[1] = $jscript_file;
                                                $file = $this->finder->findAsset($local[0] . ':' . $lib . '/' . $lib_version . '/' . $local[1], $options);
                                            } else {
                                                $file = $this->finder->findAsset('riCjLoader:libs/' . $lib . '/' . $lib_version . '/' . (!empty($jscript_file_options['local']) ? $jscript_file_options['local'] : $jscript_file), $options);
                                            }
                                            $this->_load($this->processed_files, $file, $location, $options);
                                        }
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

    /**
     *
     */
    public function loadGlobal()
    {
        $this->loadFiles($this->finder->findGlobalFiles($this->getOption('load_print')));
    }

    /**
     *
     */
    public function loadPage()
    {
        $this->loadFiles($this->finder->findPageFiles());
    }

    /**
     *
     */
    public function loadLoaders()
    {
        $loaders = $this->finder->findLoaders($this->getOption('loaders'));
        if (count($loaders) > 0) {
            $this->addLoaders($loaders, true);
        }
        $this->loadFiles($this->finder->findLoadersFiles($this->loaders));
    }

    /**
     * for backward compatibility
     *
     * @param $libs
     */
    public function addLibs($libs)
    {
        foreach ($libs as $lib => $versions) {
            foreach ($versions as $version => $options) {
                if (!isset($this->libs[$lib]))
                    $this->libs[$lib][$version] = $options;
            }
        }
    }

    /**
     * @param $loaders
     * @param bool $multi
     */
    public function addLoaders($loaders, $multi = false)
    {
        if ($multi) {
            $this->loaders = array_merge($this->loaders, $loaders);
        }
        else {
            $this->loaders[] = $loaders;
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
    public function isBrowser($browser_name)
    {
        return $this->browser->isBrowser($browser_name);
    }

    /**
     * @return mixed
     */
    public function getBrowserVersion()
    {
        return $this->browser->getVersion();
    }

    /**
     * @param $files
     * @param $file
     * @param $location
     * @param $options
     */
    private function _load(&$files, $file, $location, $options)
    {

        if (!isset($options['type'])) {
            $options['type'] = $options['ext'] == 'css' ? 'css' : 'js';
        }

        // for css, they MUST be loaded at header
        if ($options['type'] == 'css' && is_integer($location)) {
            $location = 'header';
        }

        $this->getHandler($options['type'])->load($files, $file, $location, $options);
    }

    /**
     * @param $files
     */
    private function loadFiles($files)
    {
        foreach ($files as $file) {
            $this->load($file["files"], $file["location"]);
        }
    }
}