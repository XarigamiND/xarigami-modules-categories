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
 * create item from xarModFunc('categories', 'admin', 'viewcat')
 * @return array
 */
function categories_admin_viewcatbases()
{
    // Get parameters
    // TODO: add pager
    if (!xarVarFetch('modid', 'id', $modid,  NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype', 'int:0', $itemtype,  NULL, XARVAR_NOT_REQUIRED)) return;

    // Security check
    if (!xarSecurityCheck('ReadCategories')) {
        $msg = xarML('You have no permission to read categories');
        return xarResponseForbidden($msg);
    }
    // These two variables define the scope of this screen.
    $data = array(
        'modid' => $modid,
        'itemtype' => $itemtype
    );

    // TODO: add pager
    $data['catbases'] = xarModAPIFunc(
        'categories', 'user', 'getallcatbases',
        array(
            'modid' => $modid,
            'itemtype' => $itemtype,
            'format' => 'flat',
            'order' => 'module,itemtype'
        )
    );

    // Get itemtype names for all modules selected (where available).
    $itemtypes = array();
    if (!empty($data['catbases'])) {
        foreach ($data['catbases'] as $itemtypekey => $catbase) {
            if (empty($itemtypes[$catbase['modid']])) {
                $itemtypes[$catbase['modid']] = xarModAPIFunc(
                    $catbase['module'], 'user', 'getitemtypes',
                    array(), 0
                );
            }

            if (!empty($itemtypes[$catbase['modid']][$catbase['itemtype']])) {
                $data['catbases'][$itemtypekey]['itemtypename'] =  $itemtypes[$catbase['modid']][$catbase['itemtype']]['label'];
            }
        }
    }
    $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');
    return $data;
}

?>
