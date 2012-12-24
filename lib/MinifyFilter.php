<?php

namespace plugins\riCjLoader;

// setup Minify
set_include_path(__DIR__ . '/../vendor/minify/min/lib/' . get_include_path());
require 'Minify.php';
require 'Minify/Cache/File.php';

class MinifyFilter{
    public function filter($sources){

    	// handle request
    	return \Minify::combine($sources);

    }
}