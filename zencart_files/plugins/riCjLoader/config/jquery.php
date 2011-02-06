<?php
// we use php file for now, we will later move to using yaml or another format
$libs['jquery'] = array(
	'1.4.2' => array(
		'jscript_files' => array(
			'jquery.js' => array(
				'local' => 'jquery.js', //if not set, we will use the key name '1.4.2.js' 
				'cdn' => array(
					'http' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js', 
					'https' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'
				)
			)
		)
	), 
	'1.4.4' => array(
		'jscript_files' => array(
			'jquery.js' => array(
				'local' => 'jquery.js', 
				'cdn' => array(
					'http' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js', 
					'https' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js'
				)
			)
		)
	),  
	'1.5.0' => array(
		'jscript_files' => array(
			'jquery.js' => array(
				'local' => 'jquery.js', 
				'cdn' => array(
					'http' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.min.js', 
					'https' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.min.js'
				)
			)
		)
	)
);