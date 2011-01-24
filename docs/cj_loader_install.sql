SET @t4=0;
SELECT (@t4:=configuration_group_id) as t4 
FROM configuration_group
WHERE configuration_group_title= 'CSS/JS Loader';
DELETE FROM configuration WHERE configuration_group_id = @t4;
DELETE FROM configuration_group WHERE configuration_group_id = @t4;

INSERT INTO configuration_group (`configuration_group_title`,`configuration_group_description`,`sort_order`,`visible`) VALUES ('CSS/JS Loader', 'Set CSS/JS Loader Options', '1', '1');
UPDATE configuration_group SET sort_order = last_insert_id() WHERE configuration_group_id = last_insert_id();

SET @t4=0;
SELECT (@t4:=configuration_group_id) as t4 
FROM configuration_group
WHERE configuration_group_title= 'CSS/JS Loader';

INSERT INTO configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES 
('Enable Minify', 'MINIFY_STATUS', 'true', 'Minifying will speed up your site\'s loading speed by combining and compressing css/js files.', @t4, 1, NOW(), NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Max URL Length', 'MINIFY_MAX_URL_LENGHT', '500', 'On some server the maximum lenght of any POST/GET request URL is limited. If this is the case for your server, you can change the setting here', @t4, 2, NOW(), NOW(), NULL, NULL),
('Minify Cache Time', 'MINIFY_CACHE_TIME_LENGHT', '31536000', 'Set minify cache time (in second). Default is 1 year (31536000)', @t4, 3, NOW(), NOW(), NULL, NULL),
('Latest Cache Time', 'MINIFY_CACHE_TIME_LATEST', '0', 'Normally you don\'t have to set this, but if you have just made changes to your js/css files and want to make sure they are reloaded right away, you can reset this to 0.', @t4, 4, NOW(), NOW(), NULL, NULL);