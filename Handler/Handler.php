<?php
namespace plugins\riCjLoader\Handler;

abstract class Handler
{
    protected $file_pattern = '';

    protected $extension = '';

    protected $template_base_dir = '';

    protected $host = '';

    protected $router;

    /**
     *
     * This function is responsible for loading the files into the array for later parsing
     *
     * @param array $files
     * @param array $loaded_files
     * @param array $previous_files
     * @param array $type
     * @param string $file
     * @param string $location
     * @param array $options
     */
    public function __construct($router)
    {
        global $request_type;
        if ($request_type == 'SSL') {
            $this->host = HTTPS_SERVER . DIR_WS_HTTPS_CATALOG;
        }
        else {
            $this->host = HTTP_SERVER . DIR_WS_CATALOG;
        }

        $this->router = $router;
    }

    /**
     * @param $files
     * @param $file
     * @param $location
     * @param $options
     */
    public function load(&$files, $file, $location, $options)
    {
        $files[$options['type']][$location][$file] = $options;
    }

    public function getTemplateBaseDir()
    {
        return $this->template_base_dir;
    }

    /**
     *
     * This function is responsible for outputing the files (and also doing combining, minifying etc if needed)
     *
     * @param array $files
     * @param string $type
     * @param object Loader $finder
     */
    public function process($files, $cache, $finder, $filters)
    {
        $router = $this->router;

        $files = $finder->findAssets($files);

        $to_load = array();

        ob_start();
        foreach ($files as $file => $options) {
            // the file is external file or minify is off
            if ($options['external']) {
                // if the inject content is not empty, we should push it into 1 file to cache
                if (($cache_files = $this->cache($to_load, $cache, $filters)) !== false) {
                    foreach ($cache_files as $cache_file)
                        printf($this->file_pattern, $cache_file);
                }

                printf($this->file_pattern, $file);
            } else {
                // the file is php file and needs to be included
                if ($options['ext'] == 'php') {
                    if (($cache_files = $this->cache($to_load, $cache, $filters)) !== false) {
                        foreach ($cache_files as $cache_file) {
                            printf($this->file_pattern, $cache_file);
                        }
                    }
                    include($file);
                } elseif (isset($options['inline'])) {

                    if (($cache_files = $this->cache($to_load, $cache, $filters)) !== false) {
                        foreach ($cache_files as $cache_file) {
                            printf($this->file_pattern, $cache_file);
                        }
                    }
                    echo $this->processInline($options['inline']);
                } // minify
                else {
                    $to_load[] = $file;
                }
            }
        }

        if (($cache_files = $this->cache($to_load, $cache, $filters)) !== false) {
            foreach ($cache_files as $cache_file)
                printf($this->file_pattern, $cache_file);
        }

        $result = ob_get_clean();

        return $result;
    }

    /**
     *
     * Outputing as array
     *
     * @param array $files
     * @param string $type
     * @param object Loader $finder
     */
    public function processArray($files, $finder)
    {
        return $finder->findAssets($files);
    }

    /**
     *
     * This function assits in caching the loaded content into a file to be able to serve from content different than
     * the file original location
     *
     * @param string $inject_content
     * @param string $filesrcs
     * @param string $type
     */
    protected function cache(&$to_load, $cache, $filters)
    {
        $cache_files = array();
        if (!empty($to_load)) {
            foreach ($filters as $filter) {
                $cache_files = $filter['filter']->filter($to_load, $this->extension, $cache, $filter['options']);
            }

            foreach ($cache_files as $key => $value) {
                $cache_files[$key] = $this->host . $value;
            }
            $to_load = array();
        }
        return !empty($cache_files) ? $cache_files : false;
    }

    protected function processInline($content)
    {
        return $content;
    }
}