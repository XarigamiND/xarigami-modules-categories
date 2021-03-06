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
 * @link http://xarigami.com/project/xarigami_categories.html
 */
/**
 * View statistics about category links
 */
function categories_admin_stats()
{
    // Security Check
    if (!xarSecurityCheck('AdminCategories')) {
        $msg = xarML('You have no permission to administrate categories');
        return xarResponseForbidden($msg);
    }

    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('startnum', 'isset', $startnum,     1, XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('catid',    'isset', $catid,     NULL, XARVAR_DONT_SET)) return;

    $data = array();

    $modlist = xarModAPIFunc('categories', 'user', 'getmodules');

    if (empty($modid)) {
        $data['moditems'] = array();
        $data['numitems'] = 0;
        $data['numlinks'] = 0;
        foreach ($modlist as $modid => $itemtypes) {
            $modinfo = xarModGetInfo($modid);
            // Get the list of all item types for this module (if any)
            $mytypes = xarModAPIFunc($modinfo['name'], 'user', 'getitemtypes',
                                     // don't throw an exception if this function doesn't exist
                                     array(), 0);
            foreach ($itemtypes as $itemtype => $stats) {
                $moditem = array();
                $moditem['numitems'] = $stats['items'];
                $moditem['numcats'] = $stats['cats'];
                $moditem['numlinks'] = $stats['links'];
                if ($itemtype == 0) {
                    $moditem['name'] = ucwords($modinfo['displayname']);
                //    $moditem['link'] = xarModURL($modinfo['name'],'user','main');
                } else {
                    if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                        $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                    //    $moditem['link'] = $mytypes[$itemtype]['url'];
                    } else {
                        $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                    //    $moditem['link'] = xarModURL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
                    }
                }
                $moditem['link'] = xarModURL('categories', 'admin', 'stats',
                                             array('modid' => $modid,
                                                   'itemtype' => empty($itemtype) ? null : $itemtype));
                $moditem['delete'] = xarModURL('categories', 'admin', 'unlink',
                                               array('modid' => $modid,
                                                     'itemtype' => empty($itemtype) ? null : $itemtype));
                $data['moditems'][] = $moditem;
                $data['numitems'] += $moditem['numitems'];
                $data['numlinks'] += $moditem['numlinks'];
            }
        }
        $data['delete'] = xarModURL('categories', 'admin', 'unlink');
    } else {
        $modinfo = xarModGetInfo($modid);
        $data['module'] = $modinfo['name'];
        if (empty($itemtype)) {
            $data['itemtype'] = 0;
            $data['modname'] = ucwords($modinfo['displayname']);
            $itemtype = null;
            if (isset($modlist[$modid][0])) {
                $stats = $modlist[$modid][0];
            }
        } else {
            $data['itemtype'] = $itemtype;
            // Get the list of all item types for this module (if any)
            $mytypes = xarModAPIFunc($modinfo['name'], 'user', 'getitemtypes',
                                     // don't throw an exception if this function doesn't exist
                                     array(), 0);
            if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
            //    $data['modlink'] = $mytypes[$itemtype]['url'];
            } else {
                $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
            //    $data['modlink'] = xarModURL($modinfo['name'], 'user', 'view', array('itemtype' => $itemtype));
            }
            if (isset($modlist[$modid][$itemtype])) {
                $stats = $modlist[$modid][$itemtype];
            }
        }
        if (isset($stats)) {
            $data['numitems'] = $stats['items'];
            $data['numlinks'] = $stats['links'];
        } else {
            $data['numitems'] = 0;
            $data['numlinks'] = '';
        }
        $numstats = xarModGetVar('categories', 'numstats');
        if (empty($numstats)) {
            $numstats = 100;
        }
        if (!empty($catid)) {
            $data['numlinks'] = xarModAPIFunc('categories', 'user', 'countitems',
                                              array('modid' => $modid,
                                                    'itemtype' => $itemtype,
                                                    'catid' => $catid));
        }
        if ($numstats < $data['numlinks']) {
            $data['pager'] = xarTplGetPager($startnum,
                                            $data['numlinks'],
                                            xarModURL('categories', 'admin', 'stats',
                                                      array('modid' => $modid,
                                                            'itemtype' => $itemtype,
                                                            'catid' => $catid,
                                                            'sort' => $sort,
                                                            'startnum' => '%%')),
                                            $numstats);
        } else {
            $data['pager'] = '';
        }
        $data['modid'] = $modid;
        $getitems = xarModAPIFunc('categories', 'user', 'getlinks',
                                  array('modid' => $modid,
                                        'itemtype' => $itemtype,
                                        'reverse' => 1,
                                        'numitems' => $numstats,
                                        'startnum' => $startnum,
                                        'sort' => $sort,
                                        'catid' => $catid));
        $showtitle = xarModGetVar('categories', 'showtitle');
        if (!empty($getitems) && !empty($showtitle)) {
           $itemids = array_keys($getitems);
           $itemlinks = xarModAPIFunc($modinfo['name'], 'user', 'getitemlinks',
                                      array('itemtype' => $itemtype,
                                            'itemids' => $itemids),
                                      0); // don't throw an exception here
        } else {
           $itemlinks = array();
        }
        $seencid = array();
        $data['moditems'] = array();
        foreach ($getitems as $itemid => $cids) {
            $data['moditems'][$itemid] = array();
            $data['moditems'][$itemid]['numlinks'] = count($cids);
            $data['moditems'][$itemid]['cids'] = $cids;
            foreach ($cids as $cid) {
                $seencid[$cid] = 1;
            }
            $data['moditems'][$itemid]['delete'] = xarModURL('categories', 'admin', 'unlink',
                                                             array('modid' => $modid,
                                                                   'itemtype' => $itemtype,
                                                                   'itemid' => $itemid));
            if (isset($itemlinks[$itemid])) {
                $data['moditems'][$itemid]['link'] = $itemlinks[$itemid]['url'];
                $data['moditems'][$itemid]['title'] = $itemlinks[$itemid]['label'];
            }
        }
        unset($getitems);
        unset($itemlinks);
        if (!empty($seencid)) {
            $data['catinfo'] = xarModAPIFunc('categories', 'user', 'getcatinfo',
                                             array('cids' => array_keys($seencid)));
        } else {
            $data['catinfo'] = array();
        }
        $data['delete'] = xarModURL('categories', 'admin', 'unlink',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype));
        $data['sortlink'] = array();
        if (empty($sort) || $sort == 'itemid') {
             $data['sortlink']['itemid'] = '';
        } else {
             $data['sortlink']['itemid'] = xarModURL('categories', 'admin', 'stats',
                                                     array('modid' => $modid,
                                                           'itemtype' => $itemtype));
        }
        if (!empty($sort) && $sort == 'numlinks') {
             $data['sortlink']['numlinks'] = '';
        } else {
             $data['sortlink']['numlinks'] = xarModURL('categories', 'admin', 'stats',
                                                      array('modid' => $modid,
                                                            'itemtype' => $itemtype,
                                                            'sort' => 'numlinks'));
        }
        $data['catid'] = $catid;
    }
    $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');
    return $data;
}

?>
