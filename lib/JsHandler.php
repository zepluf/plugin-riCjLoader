<?php

namespace plugins\riCjLoader;

class JsHandler extends Handler{
    protected 
        $file_pattern = "<script type=\"text/javascript\" src=\"%s\"></script>\n",        
        $extension = 'js',
        $template_base_dir = 'jscript';
    
    protected function processInline($content){
        if(strpos($content, '<script') === false)
            return sprintf("<script type='text/javascript'><!-- \n\n %s \n\n //--></script>", $content);
        return $content;
    }
}