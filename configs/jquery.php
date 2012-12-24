<?php
// we use php file for now, we will later move to using yaml or another format
$libs['jquery'] = array(
	'1.7.1' => array(
		'jscript_files' => array(
			'jquery.js' => array(
				'local' => 'jquery.js', 
				'cdn' => array(
					'http' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js', 
					'https' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'
				)
			),
            'noconflict.js' => array(
                'local' => 'noconflict.js'
            )
		)
	),
    '1.7.2' => array(
        'jscript_files' => array(
            'jquery.js' => array(
                'local' => 'jquery.js',
                'cdn' => array(
                    'http' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js',
                    'https' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'
                )
            ),
            'noconflict.js' => array(
                'local' => 'noconflict.js'
            )
        )
    ),
    '1.8.1' => array(
        'jscript_files' => array(
            'jquery.js' => array(
                'local' => 'jquery.js',
                'cdn' => array(
                    'http' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js',
                    'https' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js'
                )
            ),
            'noconflict.js' => array(
                'local' => 'noconflict.js'
            )
        )
    )
);