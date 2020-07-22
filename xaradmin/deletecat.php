<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2010 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @subpackage Xarigami Categories
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 */
/**
 * Delete a category
 *
 * This function also shows a count on the number of child categories of the current category
 * @param id cid
 * @param str confirm OPTIONAL
 * @return bool
 */
function categories_admin_deletecat()
{
    if (!xarVarFetch('cid', 'int:1:', $cid)) return;
    if (!xarVarFetch('confirm', 'str:1:' ,$confirm, '', XARVAR_NOT_REQUIRED)) return;

    // Security check
    if (!xarSecurityCheck('DeleteCategories', 1, 'category', "All:$cid")) {
        $msg = xarML('You have no permission to delete #(1) item #(2)', 'Category', xarVarPrepForDisplay($cid));
        return xarResponseForbidden($msg);
    }

    // Check for confirmation
    if (empty($confirm)) {

        // Get category information
        $cat = xarModAPIFunc('categories', 'user', 'getcatinfo',
                              array('cid' => $cid));

        if ($cat == false) {
            $msg = xarML('The category to be deleted does not exist');
            throw new BadParameterException(null, $msg);
        }

        $data = Array('cid'=>$cid,'name'=>$cat['name']);
        $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');
        $data['nolabel'] = xarML('No');
        $data['yeslabel'] = xarML('Yes');
        $data['authkey'] = xarSecGenAuthKey();

        $data['numcats'] = xarModAPIFunc('categories', 'user', 'countcats', $cat);
        $data['numcats'] -= 1;
        $data['numitems'] = xarModAPIFunc('categories', 'user', 'countitems',
                                          array('cids' => array('_'.$cid),
                                                'modid' => 0));
        // Return output
        return xarTplModule('categories', 'admin', 'delete', $data);
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Category was not deleted as the request was not valid!');
        return xarResponseForbidden($msg);
    }

    // Pass to API
    // Errors handled by exceptions. No need to care about returned values now.
    xarModAPIFunc('categories', 'admin', 'deletecat', array('cid' => $cid));

    xarResponseRedirect(xarModURL('categories', 'admin', 'viewcats', array()));

    return true;
}

?>
