riCjLoader is a product of RubikIntegration.com. The plugin/module is meant for Zencart ecommerce framework

**Features**
- Allowing different plugins to minimize javascript conflicts
- Speeding up the site performance by minifying and combining css/javascript files
- Loading files that are located outside of the root folder
- Loading the files from right within the template files
- Loading javascript files as late as possible (to speed up page rendering)

**Installation**

*prerequisites* 

you MUST HAVE our Zencart's Plugin Framework (ZePLUF) installed before you can use this plugin:
https://github.com/yellow1912/ZenCartPluginFramework

If you don't have ZePLUF, please use the master branch of this module which is meant for those who do not have ZePLUF

Installation is extremely easy:
- Simply drop this plugin into the plugins/ folder (which you should have if you installed ZePLUF
- Open plugins/settings.yaml and add riCjLoader into the frontend pre-load list (we are working to make this step even easier)
- Open includes/templates/your-current-templates/common/html_header.php file. If you don't have it, copy from the template_default/ folder.

Look for: 

    /**
    * load all template-specific stylesheets, named like "style*.css", alphabetically
    */

Delete everything below until you find:
`</head>`

*For help and professional installation* please contact us at http://helpdesk.rubikintegration.com

**Usage**

*Loading from within any template file*

`$riview->get('loader')->load(array($array_of_files_to_load), $location, $silent)`

`$location` is optional, you can pass in "head", "footer", or nothing at all. Note that CSS files will ALWAYS be loaded at head.

`$silent` is optional, you can pass in boolean value, the default value is false. This parameter allows you to tell cjloader to not print out the loader holder at that specific location, more on this will be explained later.

*The correct format of the $array_of_files_to_load*

`array('filename1.php' => array('type' => 'css'), 'filename3.css', 'filename2.js')`

The array after each filename is optional, usually only needed if you want to load a php file, then you must specify which type the file is

Notes:
- Files may not get loaded at the exact location, the loader may decide to load the files at a position further down if possible.
- Files will be loaded in the EXACT order given in the array. For example, if you pass in array('abc.js', 'def.js') then the abc.js will be loaded BEFORE def.js.
- If a file is asked to be loaded more than once by multiple plugins/template files it will only be loaded ONCE at the earliest possible location to make sure it's available for others to use.
- If, at one location, you need to load a file say my_file.js and this file REQUIRES abc.lib js run then you should ALWAYS put abc.js in the load list as well (even if you may know that jquery.js has been requested before). Don't worry, the loader will do the hard work and decide the location to load for you, and it will only load a file ONCE.

*Loading inline css/js*

You can load inline CSS and JS easily with our loader, there are 2 methods:

Assuming we need to load inline js here which also makes use of jquery:
`$riview->get('loader')->load(array('jquery.lib', 'inline.js' => array('inline' => '$("#test".html("test"))')))`

Note that in the above sample code we put inline.js in the filename, you can use anyname but you MUST use the right extension (js or css). Don't worry if you use the same name inline.js at many load locations, they will all be loaded.

*Loading inline css/js the second way*

Using the above example:

    $riview->get('loader')->load(array('jquery.lib'));
    $riview->get('loader')->startInline();
    ?>
      $("#test".html("test"))
    <?php
    $riview->get('loader')->endInline();

*Libraries*

If you look at the above examples, you may have noticed that sometimes I used jquery.lib, the extension .lib will tell the loader to load the jquery library instead (all libraries are currently defined in riCjLoader/configs/ folder. A library allows you to specify multiple versions as well as multiple files (including css and js). For example, asking for jquery.ui.lib will load BOTH the css and js files for you.
