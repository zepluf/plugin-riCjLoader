<?php
/**
 * Created by RubikIntegration Team.
 * Date: 9/27/12
 * Time: 6:39 AM
 * Question? Come to our website at http://rubikin.com
 */

namespace plugins\riCjLoader\Browser;

/**
 * this class acts as an interface so that we can use different Browser classes if we want by
 * simply switching via our settings
 */
interface BrowserInterface{

    /**
     * checks if the browser is IE, FF, Chrome etc
     * @string $browser_name
     * @return boolean
     */
    public function isBrowser($browser_name);

    /**
     * gets the version of the browser
     * @string $browser_name
     * @return string
     */
    public function getVersion();
}