<?php
// TODO: find a better way to deal with it
$container->get('ricjLoader.loader_helper')->set(array(
    'dirs' => IS_ADMIN_FLAG ? array(DIR_WS_ADMIN) : array(DIR_WS_TEMPLATE . '%type%' . '/', 'includes/templates/template_default/%type%/')
));

$container->get('ricjLoader.loader_helper')->setGlobalVariables();