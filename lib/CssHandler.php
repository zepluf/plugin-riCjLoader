<?php

namespace plugins\riCjLoader;

use plugins\riPlugin\Plugin;

class CssHandler extends Handler{
    protected 
        $file_pattern = "<link rel=\"stylesheet\" type=\"text/css\" media=\"%s\" href=\"%s\" />\n",
        $extension = 'css',
        $template_base_dir = 'css';
    /**
     * (non-PHPdoc)
     * @see plugins\riCjLoader.Handler::load()
     */
    public function load(&$files, $file, $location, $options){

        if(!isset($options['media'])) $options['media'] = 'screen';
                 
        $files['css'][$location][$options['media']][$file] = $options;                  
    }

    /**
     * (non-PHPdoc)
     * @see plugins\riCjLoader.Handler::process()
     */
    public function process($files, $loader){
                       
        ob_start();
        foreach ($files as $media => $_files){
            $_files = $loader->findAssets($_files, $type);  
            $to_load = array();   
                     
            foreach($_files as $file => $options){                
                 
                // the file is external file or minify is off
                if($options['external']){
                    // if the inject content is not empty, we should push it into 1 file to cache
                    if(($cache_files = $this->cache($to_load, $loader)) !== false){
                        foreach($cache_files as $cache_file)
                            printf($this->file_pattern, $media, $cache_file);
                    } 
                    printf($this->file_pattern, $media, $file);                                            
                }
                else{                                        
                    // the file is php file and needs to be included
                    if($options['ext'] == 'php') {
                        if(($cache_files = $this->cache($to_load, $loader)) !== false){                                      
                            foreach($cache_files as $cache_file)
                                printf($this->file_pattern, $media, $cache_file);                            
                        }
                        include($file);  
                    }
                    elseif(isset($options['inline'])){
                        
                        if(($cache_files = $this->cache($to_load, $loader)) !== false){                                      
                            foreach($cache_files as $cache_file)
                                printf($this->file_pattern, $media, $cache_file);                            
                        }
                        
                        echo $options['inline'];
                    
                    }
                    // minify
                    else {
                        $to_load[] = $file;        
                    }
                }                                    
            }

            if(($cache_files = $this->cache($to_load, $loader)) !== false){                                      
                foreach($cache_files as $cache_file)
                    printf($this->file_pattern, $media, $cache_file);                            
            }                                    
        }
        
        $result = ob_get_clean();
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see plugins\riCjLoader.Handler::processArray()
     */
    public function processArray($files, $type, $loader){    
        $result = array();
        foreach ($files as $media => $_files){
            $result[$media] = $loader->findAssets($_files, $type);  
        }    
        return $result;                               
    }
}