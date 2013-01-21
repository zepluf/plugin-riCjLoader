<?php

namespace plugins\riCjLoader\Filter;

// setup Minify
set_include_path(__DIR__ . '/../vendor/minify/min/lib/' . PATH_SEPARATOR . get_include_path());
require 'Minify.php';
require 'Minify/Cache/File.php';

class MinifyFilter
{

    protected $cache_path = '';

    /**
     * @var
     */
    protected $cache;

    /**
     * @var
     */
    protected $webPath;

    /**
     * @var
     */
    protected $fileUtility;

    /**
     * @param $cache
     * @param $cachePath
     * @param $fileUtility
     */
    public function __construct($cache, $cachePath, $webPath, $fileUtility)
    {
        $this->cache_path = $cachePath;
        $this->cache = $cache;
        $this->webPath = $webPath;
        $this->fileUtility = $fileUtility;
    }

    /**
     * @param $sources
     * @param $extension
     * @param $use_cache
     * @param $options
     * @return array
     */
    public function filter($sources, $extension, $use_cache, $options)
    {

        $files = array();

        // handle request
        if (!$options['combine']) {
            foreach ($sources as $file) {
                $cache_filename = basename($file) . '.' . md5($file) . '.' . $extension;

                $destination_file = $this->cache_path . $cache_filename;
                if (!file_exists($destination_file) || !$use_cache) {
                    $cache_file = $this->cache->write($destination_file, \Minify::combine($file, array('minifiers' => array('application/x-javascript' => ''))));

                    // TODO: remove DIR_FS_CATALOG
                    if ($cache_file !== false) {
                        $files[] = $this->host . $this->fileUtility->getRelativePath($this->webPath, $destination_file);
                    }
                }
            }
        } else {
            $cache_filename = md5(serialize($sources)) . '.' . $extension;

            $destination_file = $this->cache_path . $cache_filename;
            if (($cache_file = $this->cache->exists($destination_file)) === false || !$use_cache) {
                // Todo: what to do if we do not turn on the minify?
                $cache_file = $this->cache->write($destination_file, \Minify::combine($sources, $options));
            }

            if ($cache_file !== false) {
                $files[] = $this->fileUtility->getRelativePath($this->webPath, $destination_file);
            }
        }

        return $files;

    }
}