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
 * Modify the category base
 * @return array $data
 */
function categories_admin_modifycatbase()
{
    if (!xarVarFetch('bid', 'id', $bid, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('modid', 'id', $modid, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype', 'id', $itemtype, NULL, XARVAR_NOT_REQUIRED)) return;

    $data = array();
    //common admin menu
    $data['menulinks'] = xarModAPIFunc('categories','admin','getmenulinks');
    if (!empty($bid)) {
        // Editing an existing category base.

        // Security check
        // TODO: category links - what security check is needed here? AdminCategoryLink? Check for base id?
        if (!empty($itemtype)) {
            $modtype = $itemtype;
        } else {
            $modtype = 'All';
        }
        if (!xarSecurityCheck('DeleteCategoryLink', 1, 'Link', "$modid:$modtype:All:All")) {
            $msg = xarML('You have no permission to delete #(1) item for module id #(2), module type #(3)',
                'Category Link', xarVarPrepForDisplay($modid), xarVarPrepForDisplay($modtype));
            return xarResponseForbidden($msg);
        }

        $data['catbase'] = xarModAPIFunc(
            'categories', 'user', 'getcatbase',
            array(
                'bid' => $bid,
                'modid' => $modid, // temporary
                'itemtype' => $itemtype // temporary
            )
        );

        // Form item for choosing the base category.
        $data['cidselect'] = xarModAPIFunc(
            'categories', 'visual', 'makeselect',
            array(
                'values' => array($data['catbase']['cid'] => 1),
                'multiple' => false
            )
        );

        $data['func'] = 'modify';

        $data['bid'] = $bid;
        $data['modid'] = $modid;
        $data['itemtype'] = $itemtype;

        if (empty($module) && !empty($modid) && is_numeric($modid)) {
            $modinfo = xarModGetInfo($modid);
            $module = $modinfo['name'];
        }
        $data['module'] = $module;

        // TODO: could do with this in the template, but there is no way to add it yet.
        xarModAPIfunc('base', 'javascript', 'moduleinline',
            array(
                'position' => 'head',
                'code' => 'xar_base_reorder_warn = \'' . xarML('You must select the category base to move.') . '\''
            )
        );

        // Get count of category bases in this group (for module/itemtype)
        $data['groupcount'] = xarModAPIfunc(
            'categories', 'user', 'countcatbases',
            array('modid' => $modid, 'itemtype' => $itemtype)
        );

        // Get the list of cat bases for the order list.
        $data['catbases'] = xarModAPIfunc(
            'categories', 'user', 'getallcatbases',
            array('modid' => $modid, 'itemtype' => $itemtype, 'order' => 'order')
        );

        // TODO: config hooks for the category base and modify hooks for the category base item

    } 
/*    
    else {

        // Adding a new Category Base
        // TODO...

        if(!xarSecurityCheck('AddCategoryLink')) return;
    }
*/

    // Return output
    return $data;
}

?>