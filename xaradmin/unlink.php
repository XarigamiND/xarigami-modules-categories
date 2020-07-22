<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2010 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/project/xarigami_categories.html
 */
/**
 * Delete category links of module items
 * @param modid
 * @param itemtype
 * @param itemid
 * @param string confirm
 * @return bool True on success of redirect
 */
function categories_admin_unlink()
{
    // Security Check
    if (!xarSecurityCheck('AdminCategories')) {
        $msg = xarML('You have no permission to administrate categories');
        return xarResponseForbidden($msg);
    }

    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) return;

    // Check for confirmation.
    if (empty($confirm)) {
        $data = array();
        $data['modid'] = $modid;
        $data['itemtype'] = $itemtype;
        $data['itemid'] = $itemid;

        $what = '';
        if (!empty($modid)) {
            $modinfo = xarModGetInfo($modid);
            if (empty($itemtype)) {
                $data['modname'] = ucwords($modinfo['displayname']);
            } else {
                // Get the list of all item types for this module (if any)
                $mytypes = xarModAPIFunc($modinfo['name'], 'user', 'getitemtypes',
                                         // don't throw an exception if this function doesn't exist
                                         array(), 0);
                if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                    $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                } else {
                    $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                }
            }
        }
        $data['confirmbutton'] = xarML('Confirm');
        $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');        
        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSecGenAuthKey();
        // Return the template variables defined in this function
        return $data;
    }

    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('The system failed to confirm your category unlink request!');  
        return xarResponseForbidden($msg);
    }
   
    // API throws exception for errors.
    xarModAPIFunc('categories', 'admin', 'unlink',
                       array('modid' => $modid,
                             'itemtype' => $itemtype,
                             'iid' => $itemid,
                             'confirm' => $confirm));
                             
    xarResponseRedirect(xarModURL('categories', 'admin', 'stats'));
    return true;
}

?>
