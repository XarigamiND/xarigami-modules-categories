<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_categories.html
 */
/**
 * update the base category from categories_admin_modifycatbase
 * @return bool true
 */
function categories_admin_updatecatbase()
{
    // Get parameters

    //Checkbox work for submit buttons too
    if (!xarVarFetch('bid',         'id', $pbid, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('name',        'str:1:100', $name, '', XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('modid',       'id', $modid, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'int:0', $itemtype, false, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('multiple',    'checkbox', $multiple, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('display',     'checkbox', $display, false, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('orderresult', 'str', $order, 'x', XARVAR_NOT_REQUIRED)) return;

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('The system failed to confirm your categories update request!');
        return xarResponseForbidden($msg);
    }

    if (empty($pbid)) {
        // Creating a new category base.
    } else {
        // Updating an existing category base.

        // If $order is set, then set the ordering of the category bases.
        if (!empty($order)) {
            xarModAPIfunc(
                'categories', 'admin', 'ordercatbases',
                array(
                    'modid' => $modid,
                    'itemtype' => $itemtype,
                    'order' => explode(';', $order)
                )
            );
        }

        // Update the details for this category base.
        xarModAPIfunc(
            'categories', 'admin', 'updatecatbase',
            array(
                'bid' => $pbid,
                'modid' => $modid, // temporary
                'itemtype' => $itemtype, // temporary
                'name' => $name,
                'mutiple' => $multiple,
                'display' => $display
            )
        );
    }

    if (empty($pbid)) {
        // TODO: direct to the currect URLs.
        xarResponseRedirect(xarModUrl('categories', 'admin', 'modifycatbase', array()));
    } else {
        xarResponseRedirect(xarModUrl('categories', 'admin', 'viewcatbases', array()));
    }

    return true;
}

?>