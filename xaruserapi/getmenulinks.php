<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Categories Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects
 * @author Xarigami Team
 */

/**
 * Utility function pass individual menu items to the main menu
 * @return array containing the menulinks for the main menu items.
 */
function categories_userapi_getmenulinks()
{

    if (empty($menulinks)) {
        $menulinks = '';
    }
    /* The final thing that we need to do in this function is return the values back
     * to the main menu for display.
     */
    return $menulinks;
}
?>