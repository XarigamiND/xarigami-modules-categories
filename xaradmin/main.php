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
 * @link http://xarigami.com/project/xarigami_categories
 */
/**
 * the main administration function
 * This function redirects to the view categories function
 * @return bool true on success
 */
function categories_admin_main()
{
    // Security check
    if (!xarSecurityCheck('ViewCategories')) {
        $msg = xarML('You have no permission to view categories');
        return xarResponseForbidden($msg);
    }
    
    // Redirect
    xarResponseRedirect(xarModURL('categories', 'admin', 'viewcats'));
    return true;
}

?>