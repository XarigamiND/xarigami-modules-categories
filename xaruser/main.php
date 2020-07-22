<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 * @author Xarigami Team
 */
/**
 * The main user function
 * Show a listing of categories
 *
 * @param int catid The category id in the form of a single category id.
 * @return array with data for the template
 */
function categories_user_main($args)
{
    // Extract the supplied arguments. This allows for the insertion of a catid into here
    // MichelV: any reason this wasn't here before?
    extract($args);

    if (!xarVarFetch('catid', 'isset', $catid, NULL, XARVAR_DONT_SET)) return;
    // Why first not set it, and then check for integer and create a 0??
    if (empty($catid) || !is_numeric($catid)) {
        // for DMOZ-like URLs
        // xarModSetVar('categories', 'SupportShortURLs' ,1);
        // replace with DMOZ top cid
        $catid = 0;
    }

    if (!xarModAPILoad('categories', 'user')) throw new FunctionNotFoundException();
    $data = array();
    $catcount = array();
    $out = '';
/*
    $catcount = xarModAPIFunc('categories','user','deepcount',
                              array('groupby' => 'category',
                                    // get the (approximate) count for all categories below $catid
                                    'cids' => array('_'.$catid)));
*/

    $parents = xarModAPIFunc('categories', 'user', 'getparents',
                             array('cid' => $catid));
    $data['parents'] = array();
    $data['hooks'] = '';
    $title = '';
    if (count($parents) > 0) {
        foreach ($parents as $id => $info) {
            $info['name'] = preg_replace('/_/',' ',$info['name']);
            $title .= $info['name'];
            if ($id == $catid) {
                $info['module'] = 'categories';
                $info['itemtype'] = 0;
                $info['itemid'] = $catid;
                $info['returnurl'] = xarModUrl('categories', 'user', 'main', array('catid' => $catid));
                $hooks = xarModCallHooks('item','display',$catid,$info);
                if (!empty($hooks) && is_array($hooks)) {
                // TODO: do something specific with pubsub, hitcount, comments etc.
                    $data['hooks'] = join('',$hooks);
                }
                if (isset($catcount[$id])) {
                    $count = $catcount[$id];
                } else {
                    $count = 0;
                }
                $data['parents'][] = array('catid' => $catid, 'name' => $info['name'], 'link' => '', 'count' => $count);
            } else {
                $link = xarModURL('categories','user','main',array('catid' => $id));
                $data['parents'][] = array('catid' => $info['cid'], 'name' => $info['name'], 'link' => $link);
                $title .= ' > ';
            }
        }
    }

    // set the page title to the current category
    if (!empty($title)) {
        xarTplSetPageTitle(xarVarPrepForDisplay($title));
    }

    $children = xarModAPIFunc('categories', 'user', 'getchildren',
                              array('cid' => $catid));
    $category = array();
    $letter = array();
    foreach ($children as $id => $info) {
        if (strlen($info['name']) == 1) {
            $letter[$id] = $info['name'];
        } else {
            $category[$id] = $info['name'];
        }
    }

/* test only - requires *_categories_symlinks table for symbolic links :
    $xartable = xarDBGetTables();
    if (empty($xartable['categories_symlinks'])) {
        $xartable['categories_symlinks'] = xarDBGetSiteTablePrefix() . '_categories_symlinks';
    }
    // created by DMOZ import script
//    $query = "CREATE TABLE $xartable[categories_symlinks] (
//              xar_cid int(11) NOT NULL default 0,
//              xar_name varchar(64) NOT NULL,
//              xar_parent int(11) NOT NULL default 0,
//              PRIMARY KEY (xar_parent, xar_cid)
//              )";

    // Symbolic links
    $dbconn = xarDBGetConn();

    $query = "SELECT xar_cid, xar_name FROM $xartable[categories_symlinks] WHERE xar_parent = '$catid'";
    $result = $dbconn->Execute($query);
    if (!$result) return;
    for (; !$result->EOF; $result->MoveNext()) {
        list($id,$name) = $result->fields;
        $category[$id] = $name . '@';
        }

    $result->Close();
*/

    $data['letters'] = array();
    if (count($letter) > 0) {
        asort($letter);
        reset($letter);
        foreach ($letter as $id => $name) {
            $link = xarModURL('categories', 'user', 'main', array('catid' => $id));
            if (isset($catcount[$id])) {
                $count = $catcount[$id];
            } else {
                $count = 0;
            }
            $data['letters'][] = array('catid' => $id, 'name' => $name, 'link' => $link, 'count' => $count);
        }
    }
    $data['categories'] = array();
    if (count($category) > 0) {
        asort($category);
        reset($category);
        foreach ($category as $id => $name) {
            $name = preg_replace('/_/',' ',$name);
            $link = xarModURL('categories', 'user', 'main', array('catid' => $id));
            if (isset($catcount[$id])) {
                $count = $catcount[$id];
            } else {
                $count = 0;
            }
            $data['categories'][] = array('catid' => $id, 'name' => $name, 'link' => $link, 'count' => $count);
        }
    }

    $data['moditems'] = array();
    if (empty($catid)) {
        return $data;
    }

    $modlist = xarModAPIFunc('categories', 'user', 'getmodules',
                             array('cid' => $catid));
    if (count($modlist) > 0) {
        foreach ($modlist as $modid => $itemtypes) {
            $modinfo = xarModGetInfo($modid);
            if (empty($modinfo)) {
                continue;
            }
            // Get the list of all item types for this module (if any)
            $mytypes = xarModAPIFunc($modinfo['name'], 'user', 'getitemtypes',
                                     // don't throw an exception if this function doesn't exist
                                     array(), 0);
            foreach ($itemtypes as $itemtype => $stats) {
                $moditem = array();
                $moditem['modname'] = $modinfo['name'];
                $moditem['modid'] = $modid;
                $moditem['itemtype'] = $itemtype;
                if ($itemtype == 0) {
                    $moditem['name'] = ucwords($modinfo['displayname']);
                    $moditem['link'] = xarModURL($modinfo['name'],'user','main');
                } else {
                    if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                        $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                        $moditem['link'] = $mytypes[$itemtype]['url'];
                    } else {
                        $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                        $moditem['link'] = xarModURL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
                    }
                }
                $moditem['numitems'] = $stats['items'];
                $moditem['numcats'] = $stats['cats'];
                $moditem['numlinks'] = $stats['links'];

                $links = xarModAPIFunc('categories', 'user', 'getlinks',
                                       array('modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'cids' => array($catid)));
                $moditem['items'] = array();
                if (!empty($links[$catid])) {
                    $itemlinks = xarModAPIFunc($modinfo['name'], 'user', 'getitemlinks',
                                               array('itemtype' => $itemtype,
                                                     'itemids' => $links[$catid]),
                                               // don't throw an exception if this function doesn't exist
                                               0);
                    if (!empty($itemlinks)) {
                        $moditem['items'] = $itemlinks;
                    } else {
                    // we're dealing with unknown items - skip this if you prefer
                        foreach ($links[$catid] as $iid) {
                            $moditem['items'][$iid] = array('url'   => '',
                                                            'title' => xarML('Display Item'),
                                                            'label' => xarML('item #(1)', $iid));
                        }
                    }
                }
                $data['moditems'][] = $moditem;
            }
        }
    }
    return $data;
}

?>