<?php
// we use php file for now, we will later move to using yaml or another format
$libs['jquery.validation'] = array(
	'1.7' => array(
		'jscript_files' => array(
			'validation.js' => array(
				'local' => 'validation.js', 
				'cdn' => array(
					'http' => 'http://ajax.microsoft.com/ajax/jquery.validate/1.7/jquery.validate.min.js', 
					'https' => 'https://ajax.microsoft.com/ajax/jquery.validate/1.7/jquery.validate.min.js'
				)
			)
		)
	)
);