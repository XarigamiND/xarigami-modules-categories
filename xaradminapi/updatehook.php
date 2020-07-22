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
 * update linkage for an item - hook for ('item','update','API')
 * Needs $extrainfo['cids'] from arguments, or 'cids' from input
 *
 * @param int $args['objectid'] ID of the object
 * @param array $args['extrainfo'] extra information
 * @return array extrainfo
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function categories_adminapi_updatehook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'createhook', 'categories');
        throw new BadParameterException(null, $msg);
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','module name', 'admin', 'createhook', 'categories');
        throw new BadParameterException($modname, $msg);
    }

    if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    // see what we have to do here (might be empty => we need to unlink)
    if (empty($extrainfo['cids'])) {
        if (!empty($extrainfo['modify_cids'])) {
            $extrainfo['cids'] =& $extrainfo['modify_cids'];
        } else {
            // try to get cids from input
            xarVarFetch('modify_cids', 'list:int:1:', $cids, NULL, XARVAR_NOT_REQUIRED);
            if (empty($cids) || !is_array($cids)) {
                $extrainfo['cids'] = array();
            } else {
                $extrainfo['cids'] =& $cids;
            }
        }
    }
    // get all valid cids for this item
    // Note : an item may *not* belong to the same cid twice
    $seencid = array();
    foreach ($extrainfo['cids'] as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        $seencid[$cid] = 1;
    }
    $cids = array_keys($seencid);

    // errors are handled by exceptions in admin API: returned values are not considered
    if (count($cids) == 0) {
        xarModAPIFunc('categories', 'admin', 'unlink',
                          array('iid' => $objectid,
                                'itemtype' => $itemtype,
                                'modid' => $modid)); 
    } else {
        xarModAPIFunc('categories', 'admin', 'linkcat',
                            array('cids'  => $cids,
                                  'iids'  => array($objectid),
                                  'itemtype' => $itemtype,
                                  'modid' => $modid,
                                  'clean_first' => true));
    }

    // Return the extra info
    return $extrainfo;
}

?>
