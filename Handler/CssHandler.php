<?php

namespace plugins\riCjLoader\Handler;

class CssHandler extends Handler
{
    protected $file_pattern = "<link rel=\"stylesheet\" type=\"text/css\" media=\"%s\" href=\"%s\" />\n";
    protected $extension = 'css';
    protected $template_base_dir = 'css';

    /**
     * (non-PHPdoc)
     * @see plugins\riCjLoader.Handler::load()
     */
    public function load(&$files, $file, $location, $options)
    {

        if (!isset($options['media'])) $options['media'] = 'screen';

        $files['css'][$location][$options['media']][$file] = $options;
    }

    /**
     * (non-PHPdoc)
     * @see plugins\riCjLoader.Handler::process()
     */
    public function process($files, $cache, $finder, $filters)
    {
        $router = $this->router;

        //ob_start();
        foreach ($files as $media => $_files) {
            $_files = $finder->findAssets($_files);
            $to_load = array();

            foreach ($_files as $file => $options) {

                // the file is external file or minify is off
                if ($options['external']) {
                    // if the inject content is not empty, we should push it into 1 file to cache
                    if (($cache_files = $this->cache($to_load, $cache, $finder, $filters)) !== false) {
                        foreach ($cache_files as $cache_file) {
                            printf($this->file_pattern, $media, $cache_file);
                        }
                    }
                    printf($this->file_pattern, $media, $file);
                } else {
                    // the file is php file and needs to be included
                    if ($options['ext'] == 'php') {
                        // print out all the current files in stack first
                        if (($cache_files = $this->cache($to_load, $cache, $finder, $filters)) !== false) {
                            foreach ($cache_files as $cache_file) {
                                printf($this->file_pattern, $media, $cache_file);
                            }
                        }
                        // include the file
                        include($file);
                    } elseif (isset($options['inline'])) {

                        if (($cache_files = $this->cache($to_load, $cache, $finder, $filters)) !== false) {
                            foreach ($cache_files as $cache_file) {
                                printf($this->file_pattern, $media, $cache_file);
                            }
                        }

                        echo $options['inline'];

                    } // minify
                    else {
                        $to_load[] = $file;
                    }
                }
            }

            if (($cache_files = $this->cache($to_load, $cache, $finder, $filters)) !== false) {
                foreach ($cache_files as $cache_file) {
                    printf($this->file_pattern, $media, $cache_file);
                }
            }
        }

        $result = ob_get_clean();

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see plugins\riCjLoader.Handler::processArray()
     */
    public function processArray($files, $finder)
    {
        $result = array();
        foreach ($files as $media => $_files) {
            $result[$media] = $finder->findAssets($_files);
        }
        return $result;
    }
}