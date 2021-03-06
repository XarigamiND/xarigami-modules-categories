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
 * @link http://xarigami.com/project/xarigami_categories.html
 */
/**
 * Manage definition of instances for privileges (unfinished)
 * @return array
 */
function categories_admin_privileges($args)
{
    // Security Check
    if (!xarSecurityCheck('AdminCategories')) {
        $msg = xarML('You have no permission to administrate categories');
        return xarResponseForbidden($msg);
    }

    extract($args);

    // fixed params
    if (!xarVarFetch('cid',          'isset', $cid,          NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('cids',         'isset', $cids,         NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('moduleid',     'isset', $moduleid,     NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('itemtype',     'isset', $itemtype,     NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('itemid',       'isset', $itemid,       NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('apply',        'isset', $apply,        NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('extpid',       'isset', $extpid,       NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('extname',      'isset', $extname,      NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('extrealm',     'isset', $extrealm,     NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('extmodule',    'isset', $extmodule,    NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('extcomponent', 'isset', $extcomponent, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('extinstance',  'isset', $extinstance,  NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('extlevel',     'isset', $extlevel,     NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('pparentid',    'isset', $pparentid,    NULL, XARVAR_DONT_SET)) return;

    // 'Category' component = All:cid (catname is unused)
    if (!empty($extcomponent) && $extcomponent == 'Category') {

        // check the current instance
        if (!empty($extinstance)) {
            $parts = explode(':',$extinstance);
            if (count($parts) > 0 && !empty($parts[0])) $catname = $parts[0];
            if (count($parts) > 1 && !empty($parts[1])) $cid = $parts[1];
        }

        // check the selected category
// TODO: figure out how to handle more than 1 category in instances
        if (empty($cid) || $cid == 'All' || !is_numeric($cid)) {
            $cid = 0;
        }
        if (empty($cid) && isset($cids) && is_array($cids)) {
            foreach ($cids as $catid) {
                if (!empty($catid) && is_numeric($catid)) {
                    $cid = $catid;
                    // bail out for now
                    break;
                }
            }
        }

        // define the new instance
        $newinstance = array();
        if (empty($cid)) {
            $newinstance[] = 'All';
            $newinstance[] = 'All';
        } else {
            $catinfo = xarModAPIFunc('categories', 'user', 'getcatinfo',
                                     array('cid' => $cid));
            if (empty($catinfo)) {
                $cid = 0;
                $newinstance[] = 'All';
                $newinstance[] = 'All';
            } else {
                $newinstance[] = 'All';
                $newinstance[] = $cid;
            }
        }

    // TODO: add option to apply this privilege for all child categories too
    //       (once privileges supports this)

        if (!empty($apply)) {
            // create/update the privilege
            $pid = xarReturnPrivilege($extpid, $extname, $extrealm, $extmodule, $extcomponent,
                                      $newinstance, $extlevel, $pparentid);
            if (empty($pid)) {
                return; // throw back
            }

            // redirect to the privilege
            xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyprivilege',
                                          array('pid' => $pid)));
            return true;
        }

        $data = array(
                      'cid'          => $cid,
                      'extpid'       => $extpid,
                      'extname'      => $extname,
                      'extrealm'     => $extrealm,
                      'extmodule'    => $extmodule,
                      'extcomponent' => $extcomponent,
                      'extlevel'     => $extlevel,
                      'extinstance'  => xarVarPrepForDisplay(join(':', $newinstance)),
                      'pparentid'    => $pparentid,
                     );

        $seencid = array();
        if (!empty($cid)) {
            $seencid[$cid] = 1;
        }
        $data['cats'] = array();
        $data['cats'][] = xarModAPIFunc('categories',
                                        'visual',
                                        'makeselect',
                                        array('values' => &$seencid,
                                              'multiple' => 0));

        $data['refreshlabel'] = xarML('Refresh');
        $data['applylabel'] = xarML('Finish and Apply to Privilege');

        return $data;
    }

    // 'Link' component = moduleid:itemtype:itemid:cid
    if (!empty($extinstance)) {
        $parts = explode(':',$extinstance);
        if (count($parts) > 0 && !empty($parts[0])) $moduleid = $parts[0];
        if (count($parts) > 1 && !empty($parts[1])) $itemtype = $parts[1];
        if (count($parts) > 2 && !empty($parts[2])) $itemid = $parts[2];
        if (count($parts) > 3 && !empty($parts[3])) $cid = $parts[3];
    }

    // Get the list of all modules currently hooked to categories
    $hookedmodlist = xarModAPIFunc('modules', 'admin', 'gethookedmodules',
                                   array('hookModName' => 'categories'));
    if (!isset($hookedmodlist)) {
        $hookedmodlist = array();
    }
    $modlist = array();
    $typelist = array();
    foreach ($hookedmodlist as $modname => $value) {
        if (empty($modname)) continue;
        $modid = xarModGetIDFromName($modname);
        if (empty($modid)) continue;
        $modinfo = xarModGetInfo($modid);
        $modlist[$modid] = $modinfo['displayname'];
        if (!empty($moduleid) && $moduleid == $modid) {
            // Get the list of all item types for this module (if any)
            $mytypes = xarModAPIFunc($modname, 'user', 'getitemtypes',
                                     // don't throw an exception if this function doesn't exist
                                     array(), 0);
            if (empty($mytypes)) {
                $mytypes = array();
            }
            if (!empty($value[0])) {
                foreach ($mytypes as $id => $type) {
                    $typelist[$id] = $type['label'];
                }
            } else {
                foreach ($value as $id => $val) {
                    if (isset($mytypes[$id])) {
                        $type = $mytypes[$id]['label'];
                    } else {
                        $type = xarML('type #(1)',$id);
                    }
                    $typelist[$id] = $type;
                }
            }
        }
    }

    if (empty($moduleid) || $moduleid == 'All' || !is_numeric($moduleid)) {
        $moduleid = 0;
    }
    if (empty($itemtype) || $itemtype == 'All' || !is_numeric($itemtype)) {
        $itemtype = 0;
    }
    if (empty($itemid) || $itemid == 'All' || !is_numeric($itemid)) {
        $itemid = 0;
    }
// TODO: figure out how to handle more than 1 category in instances
    if (empty($cid) || $cid == 'All' || !is_numeric($cid)) {
        $cid = 0;
    }
    if (empty($cid) && isset($cids) && is_array($cids)) {
        foreach ($cids as $catid) {
            if (!empty($catid) && is_numeric($catid)) {
                $cid = $catid;
                // bail out for now
                break;
            }
        }
    }

    // define the new instance
    $newinstance = array();
    $newinstance[] = empty($moduleid) ? 'All' : $moduleid;
    $newinstance[] = empty($itemtype) ? 'All' : $itemtype;
    $newinstance[] = empty($itemid) ? 'All' : $itemid;
    $newinstance[] = empty($cid) ? 'All' : $cid;

    if (!empty($apply)) {
        // create/update the privilege
        $pid = xarReturnPrivilege($extpid, $extname, $extrealm, $extmodule, $extcomponent,
                                  $newinstance, $extlevel, $pparentid);
        if (empty($pid)) {
            return; // throw back
        }

        // redirect to the privilege
        xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyprivilege',
                                      array('pid' => $pid)));
        return true;
    }

    if (!empty($moduleid)) {
        $numitems = xarModAPIFunc('categories', 'user', 'countitems',
                                  array('modid' => $moduleid,
                                        'itemtype' => $itemtype,
                                        'cids'  => (empty($cid) ? null : array($cid))
                                       ));
    } else {
        $numitems = xarML('probably');
    }

    $data = array(
                  'cid'          => $cid,
                  'moduleid'     => $moduleid,
                  'itemtype'     => $itemtype,
                  'itemid'       => $itemid,
                  'modlist'      => $modlist,
                  'typelist'     => $typelist,
                  'numitems'     => $numitems,
                  'extpid'       => $extpid,
                  'extname'      => $extname,
                  'extrealm'     => $extrealm,
                  'extmodule'    => $extmodule,
                  'extcomponent' => $extcomponent,
                  'extlevel'     => $extlevel,
                  'extinstance'  => xarVarPrepForDisplay(join(':',$newinstance)),
                  'pparentid'    => $pparentid
                 );

    $catlist = array();
    if (!empty($moduleid)) {
        $modinfo = xarModGetInfo($moduleid);
        $modname = $modinfo['name'];
        if (!empty($itemtype)) {
            $cidstring = xarModGetVar($modname, 'mastercids.' . $itemtype);
            if (!empty($cidstring)) {
                $rootcats = explode (';', $cidstring);
                foreach ($rootcats as $catid) {
                    $catlist[$catid] = 1;
                }
            }
        } else {
            $cidstring = xarModGetVar($modname, 'mastercids');
            if (!empty($cidstring)) {
                $rootcats = explode (';', $cidstring);
                foreach ($rootcats as $catid) {
                    $catlist[$catid] = 1;
                }
            }
        }
    } else {
        // something with categories
    }

    $seencid = array();
    if (!empty($cid)) {
        $seencid[$cid] = 1;
/*
        $data['catinfo'] = xarModAPIFunc('categories',
                                         'user',
                                         'getcatinfo',
                                         array('cid' => $cid));
*/
    }

    $data['cats'] = array();
    $data['cats'][] = xarModAPIFunc('categories', 'visual', 'makeselect',
                                array('cids' => array_keys($catlist),
                                      'return_itself' => true,
                                      'values' => &$seencid,
                                      'multiple' => 0,
                                      'size' => 'auto',
                                      'javascript' => 'onchange="submit()"'));

    $data['refreshlabel'] = xarML('Refresh');
    $data['applylabel'] = xarML('Finish and Apply to Privilege');
    $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');
    return $data;
}

?>
