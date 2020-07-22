<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_categories.html
 */

/**
 * Overview displays standard Overview page
 *
 * Only used if you actually supply an overview link in your adminapi menulink function
 * and used to call the template that provides display of the overview
 *
 * @returns array xarTplModule with $data containing template data
 * @return array containing the menulinks for the overview item on the main manu
 * @since 2 Oct 2005
 */
function categories_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminCategories')) {
        $msg = xarML('You have no permission to administrate categories');
        return xarResponseForbidden($msg);
    }

    $data = array();

    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */
    $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');
    return xarTplModule('categories', 'admin', 'main', $data, 'main');
}

?>