<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2009-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 */
/**
 * Check category links for orphans
 * @param int modid The module ID
 * @param int itemtype The id for the itemtype
 * @return array returns the data for the template
 */
function categories_admin_checklinks()
{
    // Security Check
    if (!xarSecurityCheck('AdminCategories')) {
        $msg = xarML('You have no permission to administrate categories');
        return xarResponseForbidden($msg);
    }
    
    if(!xarVarFetch('modid',    'isset',  $modid,    NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('itemtype', 'isset',  $itemtype, NULL, XARVAR_DONT_SET)) return;

    $data = array();
    $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');
    $modlist = xarModAPIFunc('categories', 'user', 'getmodules');
    $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');
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
                $moditem['link'] = xarModURL('categories', 'admin', 'checklinks',
                                             array('modid' => $modid,
                                                   'itemtype' => empty($itemtype) ? null : $itemtype));
                $data['moditems'][] = $moditem;
                $data['numitems'] += $moditem['numitems'];
                $data['numlinks'] += $moditem['numlinks'];
            }
        }
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
            $mytypes = xarModAPIFunc($modinfo['name'],'user','getitemtypes',
                                     // don't throw an exception if this function doesn't exist
                                     array(), 0);
            if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
            //    $data['modlink'] = $mytypes[$itemtype]['url'];
            } else {
                $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
            //    $data['modlink'] = xarModURL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
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
        $data['pager'] = '';
        $data['modid'] = $modid;
        $getitems = xarModAPIFunc('categories', 'user', 'getorphanlinks',
                                  array('modid' => $modid,
                                        'itemtype' => $itemtype));
        $data['numorphans'] = count($getitems);
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

        if(!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) return;
        if (!empty($seencid) && !empty($confirm)) {
            if (!xarSecConfirmAuthKey()) {
                 $msg = xarML('The system failed to confirm your category checklinks request!');
                 return xarResponseForbidden($msg);  
            } 
            if (!xarModAPIFunc('categories', 'admin', 'unlinkcids',
                               array('modid' => $modid,
                                     'itemtype' => $itemtype,
                                     'cids' => array_keys($seencid)))) {
                $msg = xarML('The system failed to confirm your category checklinks request!');
                return xarResponseNotFound($msg);
            }
            xarResponseRedirect(xarModURL('categories', 'admin', 'checklinks'));
            return true;
        }

        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSecGenAuthKey();
    }

    return $data;
}

?>
