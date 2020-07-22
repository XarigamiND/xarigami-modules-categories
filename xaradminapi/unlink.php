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
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 */
/**
 * Delete all links for a specific Item ID
 * @param $args['iid'] the ID of the item
 * @param $args['modid'] ID of the module
 * @param $args['itemtype'] item type
 * @param $args['confirm'] from delete GUI
 */
function categories_adminapi_unlink($args)
{
    // Get arguments from argument array
    extract($args);

    if (!empty($confirm)) {
        if (!xarSecurityCheck('AdminCategories',0)) {
            $msg = xarML('You have no permission to administrate categories');
            throw new ForbiddenOperationException(null, $msg);
        }
    } else {
        // Argument check
        if (empty($modid) || !is_numeric($modid) || empty($iid) || !is_numeric($iid)) {
            $msg = xarML('Invalid Parameter Count', '', 'admin', 'linkcat', 'categories');
            throw new BadParameterException($args, $msg);
        }

        if (!isset($itemtype) || !is_numeric($itemtype)) {
            $itemtype = 0;
        }

        // Confirm linkage exists
        $childiids = xarModAPIFunc('categories',
                                  'user',
                                  'getlinks',
                                  array('iids' => array($iid),
                                        'itemtype' => $itemtype,
                                        'modid' => $modid,
                                        'reverse' => 0));

        // Note : this is a feature, not a bug in this case :-)
        // If Link doesn't exist then
        if ($childiids == Array()) {
            return true;
        }

        if (!empty($itemtype)) {
            $modtype = $itemtype;
        } else {
            $modtype = 'All';
        }

        // Note : yes, edit is enough here (cfr. updatehook)
        $cids = array_keys($childiids);
        foreach ($cids as $cid) {
            if(!xarSecurityCheck('EditCategoryLink',1,'Link',"$modid:$modtype:$iid:$cid")) {
                $msg = xarML('You have no permission to edit #(1)', 'Category Links');
                throw new ForbiddenOperationException("$modid:$modtype:$iid:$cid", $msg);
            }
        }
    }

    // Get datbase setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    // Delete the link
    $bindvars = array();
    $query = "DELETE FROM $categorieslinkagetable";

    if (!empty($modid)) {
        if (!is_numeric($modid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                         'module id', 'admin', 'unlink', 'categories');
            throw new BadParameterException($modid, $msg);
        }
        if (empty($itemtype) || !is_numeric($itemtype)) {
            $itemtype = 0;
        }
        $query .= " WHERE xar_modid = ? AND xar_itemtype = ?";
        $bindvars[] = $modid; $bindvars[] = $itemtype;
        if (!empty($iid)) {
            $query .= " AND xar_iid = ?";
            $bindvars[] =  $iid;
        }
    }

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) throw new DataNotFoundException(array($query, $bindvards));

    return true;
}

?>
