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
 * @copyright (C) 2009-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 * @author Xarigami Team
 */

/**
 * initialise block
 */
function categories_navigationblock_init()
{
    return array(
        'layout' => 1,
        'showcatcount' => 0,
        'showchildren' => 0,
        'showempty' => FALSE,
        'showlinkitems' => FALSE,
        'startmodule' => '',
        'dynamictitle' => FALSE,
        'nocache' => 0, // cache by default
        'pageshared' => 0, // don't share across pages
        'usershared' => 1, // share across group members
        'cacheexpire' => NULL,
        'menutype' => 0,
        'multiselect' => 2,
        'highlightparent' => 0
    );
}

/**
 * get information on block
 */
function categories_navigationblock_info()
{
    // Values
    return array(
        'text_type' => 'Navigation',
        'module' => 'categories',
        'text_type_long' => 'Show navigation',
        'allow_multiple' => TRUE,
        'form_content' => FALSE,
        'form_refresh' => FALSE,
        'show_preview' => TRUE
    );
}

/**
 * display block
 * @param array blockinfo
 * @param int layout
 * @return array of blockinfo with the appropriate template
 */
function categories_navigationblock_display($blockinfo)
{
    // Security Check
    //if (!xarSecurityCheck('ReadCategoryBlock', 0, 'Block', "All:$blockinfo[title]:All")) return;

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    if (!empty($vars)) extract($vars);

    // Get requested layout
    if (empty($layout)) $layout = 1; // default tree here

    if (!empty($startmodule)) {
        // static behaviour
        list($module, $itemtype, $rootcid) = explode('.', $startmodule);
        if (empty($rootcid)) {
            $rootcids = null;
        } elseif (strpos($rootcid,' ')) {
            $rootcids = explode(' ', $rootcid);
        } elseif (strpos($rootcid,'+')) {
            $rootcids = explode('+', $rootcid);
        } else {
            $rootcids = explode('-', $rootcid);
        }
    }

// TODO: for multi-module pages, we'll need some other reference point(s)
//       (e.g. cross-module categories defined in categories admin ?)
    // Get current module
    if (empty($module)) {
        if (xarVarIsCached('Blocks.categories', 'module')) {
           $modname = xarVarGetCached('Blocks.categories', 'module');
        }
        if (empty($modname)) {
            $modname = xarModGetName();
        }
    } else {
        $modname = $module;
    }
    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module name', 'blocks', 'navigation', 'categories');
        throw new BadParameterException(null, $msg);
    }

    // Get current item type (if any)
    if (!isset($itemtype)) {
        if (xarVarIsCached('Blocks.categories', 'itemtype')) {
            $itemtype = xarVarGetCached('Blocks.categories', 'itemtype');
        } else {
            // try to get itemtype from input
            xarVarFetch('itemtype', 'id', $itemtype, NULL, XARVAR_DONT_SET);
        }
    }
    if (empty($itemtype)) $itemtype = null;

    // Get current item id (if any)
    if (!isset($itemid)) {
        if (xarVarIsCached('Blocks.categories','itemid')) {
            $itemid = xarVarGetCached('Blocks.categories','itemid');
        } else {
            // try to get itemid from input
            xarVarFetch('itemid', 'id', $itemid, NULL, XARVAR_DONT_SET);
        }
    }
    if (empty($itemid)) $itemid = null;

    if (isset($rootcids)) {
        $mastercids = array_values($rootcids);
        foreach($rootcids as $cid) {
            $itemtypes[] = $itemtype;
            // @TODO: find a better way to get itemtypes here.
        }
    } else {
        // Get number of categories for this module + item type

        $numcats = xarModAPIfunc('categories', 'user', 'countcatbases',
            array(
                'module'   => $modname,
                'itemtype' => (empty($itemtype) ? NULL : $itemtype)
            ));

        if (empty($numcats)) {
            // no categories to show here -> return empty output
            return;
        }

        // Get master cids for this module + item type


        $catbases = xarModAPIfunc('categories', 'user', 'getallcatbases',
            array(
                'module'            => $modname,
                'format'            => 'flat',
                'order'             => 'none',  // order is set 'none' to get the itemtype / natural tree order
                'itemtype'          => (empty($itemtype) ? NULL : $itemtype),
                'nodefaultitemtype' => TRUE
            ));
        
        $mastercids = array();
        $itemptypecidmap = array();
        foreach($catbases as $catbase) {
            $_cid = (int) $catbase['cid'];
            $_itemtype = $catbase['itemtype'];
            if (!isset($itemptypecidmap[$_cid]) || !in_array($_itemtype, $itemptypecidmap)) {
                $itemptypecidmap[$_cid][] = $_itemtype;
            }
            $mastercids[] = $_cid;
        }

        if (empty($mastercids)) {
            // no categories to show here -> return empty output
            return;
        }

        // Merge the multiple combinaisons cids/itemtypes
        $mastercids = array_unique($mastercids);
        $itemtypes = array();
        // Sort out when there are several itemtypes per cid
        foreach($mastercids as $mastercid) {
            $itemtypes[] = count($itemptypecidmap[$mastercid]) === 1 ? $itemptypecidmap[$mastercid][0] : NULL;
        }

        if (!empty($startmodule)) {
            $rootcids = $mastercids;
        }
    }

    // See if we need to show a count per category
    if (!isset($showcatcount)) {
        $showcatcount = 0;
    }

    // See if we need to show the children of current categories
    if (!isset($showchildren)) {
        $showchildren = 1;
    }

    // Get current category counts (optional array of cid => count)
    if (empty($showcatcount)) {
        $catcount = array();
    }
    if (empty($showempty) || !empty($showcatcount)) {
        // A 'deep count' sums the totals at each node with the totals of all descendants.
        if (xarVarIsCached('Blocks.categories', 'deepcount') && empty($startmodule)) {
            $deepcount = xarVarGetCached('Blocks.categories', 'deepcount');
        } else {
            $deepcount = xarModAPIFunc('categories', 'user', 'deepcount',
                array('modid' => $modid, 'itemtype' => $itemtype));
            xarVarSetCached('Blocks.categories','deepcount', $deepcount);
        }
    }

    if (!empty($showcatcount)) {
        if (xarVarIsCached('Blocks.categories', 'catcount') && empty($startmodule)) {
            $catcount = xarVarGetCached('Blocks.categories', 'catcount');
        } else {
            // Get number of items per category (for this module).
            // If showcatcount == 2 then add in all descendants too.

            if ($showcatcount == 1) {
                // We want to display only children category counts.
                $catcount = xarModAPIFunc('categories', 'user', 'groupcount',
                    array('modid' => $modid, 'itemtype' => $itemtype));
            } else {
                // We want to display the deep counts.
                $catcount =& $deepcount;
            }

            xarVarSetCached('Blocks.categories', 'catcount', $catcount);
        }
    }

    // Specify type=... & func = ... arguments for xarModURL()
    if (empty($type)) {
        if (xarVarIsCached('Blocks.categories', 'type')) {
            $type = xarVarGetCached('Blocks.categories', 'type');
        }
        if (empty($type)) {
            $type = 'user';
        }
    }
    if (empty($func)) {
        if (xarVarIsCached('Blocks.categories', 'func')) {
            $func = xarVarGetCached('Blocks.categories', 'func');
        }
        if (empty($func)) {
            $func = 'view';
        }
    }

    // Get current categories
    if (xarVarIsCached('Blocks.categories', 'catid')) {
       $catid = xarVarGetCached('Blocks.categories', 'catid');
    }
    if (empty($catid)) {
        // try to get catid from input
        xarVarFetch('catid', 'str', $catid, NULL, XARVAR_DONT_SET);
    }
    // turn $catid into $cids array (and set $andcids flag)
    $istree = 0;
    if (!empty($catid)) {
        // if we're viewing all items below a certain category, i.e. catid = _NN
        if (strstr($catid,'_')) {
             $catid = preg_replace('/_/','',$catid);
             $istree = 1;
        }
        if (strpos($catid,' ')) {
            $cids = explode(' ',$catid);
            $andcids = true;
        } elseif (strpos($catid,'+')) {
            $cids = explode('+',$catid);
            $andcids = true;
        } else {
            $cids = explode('-',$catid);
            $andcids = false;
        }
    } elseif (empty($cids)) {
        if (xarVarIsCached('Blocks.categories', 'cids')) {
            $cids = xarVarGetCached('Blocks.categories', 'cids');
        }
        if (xarVarIsCached('Blocks.categories', 'andcids')) {
            $andcids = xarVarGetCached('Blocks.categories', 'andcids');
        }
        if (empty($cids)) {
            // try to get cids from input
            xarVarFetch('cids',    'isset', $cids,    NULL,  XARVAR_DONT_SET);
            xarVarFetch('andcids', 'isset', $andcids, false, XARVAR_NOT_REQUIRED);

            if (empty($cids)) {
                $cids = array();
                if ((empty($module) || $module == $modname) && !empty($itemid)) {
                    $links = xarModAPIFunc('categories', 'user', 'getlinks',
                                          array('modid' => $modid,
                                                'itemtype' => $itemtype,
                                                'iids' => array($itemid)));
                    if (!empty($links) && count($links) > 0) {
                        $cids = array_keys($links);
                    }
                }
            }
        }
    }
    if (count($cids) > 0) {
        $seencid = array();
        foreach ($cids as $cid) {
            if (empty($cid) || ! is_numeric($cid)) {
                continue;
            }
            $seencid[$cid] = 1;
        }
        $cids = array_keys($seencid);
    }

    $data = array();
    $data['cids'] = $cids;
    // pass information about current module, item type and item id (if any) to template
    $data['module'] = $modname;
    $data['itemtype'] = $itemtype;
    $data['itemid'] = $itemid;
    // pass information about current function to template
    $data['type'] = $type;
    $data['func'] = $func;

    $blockinfo['content'] = '';

    // Generate output
    switch ($layout) {

        case 3: // prev/next category
            $template = 'prevnext';
            if (empty($cids) || count($cids) != 1 || in_array($cids[0], $mastercids)) {
                // nothing to show here
                return;
            } else {
                // See if we need to show anything
                if (empty($showprevnext)) {
                    if (xarVarIsCached('Blocks.categories', 'showprevnext')) {
                        $showprevnext = xarVarGetCached('Blocks.categories', 'showprevnext');
                        if (empty($showprevnext)) {
                            return;
                        }
                    }
                }
                $cat = xarModAPIFunc('categories','user','getcatinfo',
                                array('cid' => $cids[0]));
                if (empty($cat)) {
                    return;
                }
                $neighbours = xarModAPIFunc('categories','user','getneighbours', $cat);
                if (empty($neighbours) || count($neighbours) == 0) {
                    return;
                }
                foreach ($neighbours as $neighbour) {
//                    if ($neighbour['link'] == 'parent') {
//                        $data['uplabel'] = $neighbour['name'];
//                        $data['upcid'] = $neighbour['cid'];
//                        $data['uplink'] = xarModURL($modname,$type,$func,
//                                                   array('itemtype' => $itemtype,
//                                                         'catid' => $neighbour['cid']));
//                    } elseif ($neighbour['link'] == 'previous') {
                    if ($neighbour['link'] == 'previous') {
                        $data['prevlabel'] = $neighbour['name'];
                        $data['prevcid'] = $neighbour['cid'];
                        $data['prevlink'] = xarModURL($modname, $type, $func,
                                                     array('itemtype' => $itemtype,
                                                           'catid'    => $neighbour['cid']));
                    } elseif ($neighbour['link'] == 'next') {
                        $data['nextlabel'] = $neighbour['name'];
                        $data['nextcid'] = $neighbour['cid'];
                        $data['nextlink'] = xarModURL($modname, $type, $func,
                                                     array('itemtype' => $itemtype,
                                                           'catid'    => $neighbour['cid']));
                    }
                }
                if (!isset($data['nextlabel']) && !isset($data['prevlabel'])) {
                    return;
                }
//                if (!isset($data['uplabel'])) {
//                    $data['uplabel'] = '&nbsp;';
//                }
            }
            break;

        case 2: // crumbtrails
            $template = 'trails';
            $data['cattitle'] = xarML('Browse in:');
            if (empty($cids) || count($cids) == 0) {
                $template = 'rootcats';

                $data['catitems'] = array();

                // Get root categories
                $catlist = xarModAPIFunc('categories', 'user', 'getcatinfo',
                                        array('cids' => $mastercids));
                $join = '';
                if (empty($catlist) || !is_array($catlist)) {
                    return;
                }
                foreach ($catlist as $cat) {
                // TODO: now this is a tricky part...
                    $link = xarModURL($modname,$type,$func,
                                     array('itemtype' => $itemtype,
                                           'catid' => $cat['cid']));
                    $label = xarVarPrepForDisplay($cat['name']);
                    $desc = xarVarPrepForDisplay($cat['description']);
                    $data['catitems'][] = array('catlabel' => $label,
                                                'catid' => $cat['cid'],
                                                'catlink' => $link,
                                                'catjoin' => $join);
                    $join = ' | ';
                }
            } else {
                $template = 'trails';
                $data['cattrails'] = array();

                $descriptions = array();
    // TODO: stop at root categories
                foreach ($cids as $cid) {
                    // Get category information
                    $parents = xarModAPIFunc('categories', 'user', 'getparents',
                                            array('cid' => $cid));
                    if (empty($parents)) {
                        continue;
                    }
                    $catitems = array();
                    $curcount = 0;
                // TODO: now this is a tricky part...
                    $label = xarML('All');
                    $link = xarModURL($modname, $type, $func,
                                     array('itemtype' => $itemtype));
                    $join = '';
                    $catitems[] = array('catlabel' => $label,
                                        'catid' => $cid,
                                        'catlink' => $link,
                                        'catjoin' => $join);
                    $join = ' &gt; ';
                    foreach ($parents as $cat) {
                        $label = xarVarPrepForDisplay($cat['name']);
                        if ($cat['cid'] == $cid && empty($itemid) && empty($andcids)) {
                            $link = '';
                        } else {
                        // TODO: now this is a tricky part...
                            $link = xarModURL($modname, $type, $func,
                                             array('itemtype' => $itemtype,
                                                   'catid' => $cat['cid']));
                        }
                        if ($cat['cid'] == $cid) {
                            // show optional count
                            if (isset($catcount[$cat['cid']])) {
                                $curcount = $catcount[$cat['cid']];
                            }
                            if (!empty($cat['description'])) {
                                $descriptions[] = xarVarPrepHTMLDisplay($cat['description']);
                            } else {
                                $descriptions[] = xarVarPrepForDisplay($cat['name']);
                            }
                            // save current category info for icon etc.
                            if (count($cids) == 1) {
                                $curcat = $cat;
                            }
                        }
                        $catitems[] = array('catlabel' => $label,
                                            'catid' => $cat['cid'],
                                            'catlink' => $link,
                                            'catjoin' => $join);
                    }
                    $data['cattrails'][] = array('catitems' => $catitems,
                                                 'catcount' => $curcount);
                }

                // Add filters to select on all categories or any categories
                if (count($cids) > 1) {
                    $catitems = array();
                    if (!empty($itemid) || !empty($andcids)) {
                        $label = xarML('Any of these categories');
                        $link = xarModURL($modname, $type, $func,
                                          array('itemtype' => $itemtype,
                                                'catid' => join('-',$cids)));
                        $join = '';
                        $catitems[] = array('catlabel' => $label,
                                            'catid' => join('-',$cids),
                                            'catlink' => $link,
                                            'catjoin' => $join);
                    }
                    if (empty($andcids)) {
                        $label = xarML('All of these categories');
                        $link = xarModURL($modname,$type,$func,
                                          array('itemtype' => $itemtype,
                                                'catid' => join('+',$cids)));
                        if (!empty($itemid)) {
                            $join = '-';
                        } else {
                            $join = '';
                        }
                        $catitems[] = array('catlabel' => $label,
                                            'catid' => join('+',$cids),
                                            'catlink' => $link,
                                            'catjoin' => $join);
                    }
                    $curcount = 0;
                    $data['cattrails'][] = array('catitems' => $catitems,
                                                 'catcount' => $curcount);
                }

            // TODO: move off to nav-trails template ?
                // Build category description
                if (!empty($itemid)) {
                    $data['catdescr'] = join(' + ', $descriptions);
                } elseif (!empty($andcids)) {
                    $data['catdescr'] = join(' ' . xarML('and') . ' ', $descriptions);
                } else {
                    $data['catdescr'] = join(' ' . xarML('or') . ' ', $descriptions);
                }

                if (count($cids) != 1) {
                    break;
                }

                if (!empty($curcat)) {
/*
                    $curcat['module'] = 'categories';
                    $curcat['itemtype'] = 0;
                    $curcat['itemid'] = $cids[0];
                    $curcat['returnurl'] = xarModURL($modname,$type,$func,
                                                     array('itemtype' => $itemtype,
                                                           'catid' => $cids[0]));
                    // calling item display hooks *for the categories module* here !
                    $data['cathooks'] = xarModCallHooks('item','display',$cid,$curcat,'categories');
*/
                    // saving the current cat id for use e.g. with DD tags (<xar:data-display module="categories" itemid="$catid" />)
                    $data['catid'] = $curcat['cid'];
                }
/*
                // set the page title to the current module + category if no item is displayed
                if (empty($itemid)) {
                    // Get current title
                    if (empty($title)) {
                        if (xarVarIsCached('Blocks.categories','title')) {
                            $title = xarVarGetCached('Blocks.categories','title');
                        }
                    }
                    if (!empty($curcat['name'])) {
                        $title = xarVarPrepForDisplay($curcat['name']);
                    }
                    xarTplSetPageTitle($title);
                }
*/
            // TODO: don't show icons when displaying items ?
                if (!empty($curcat['image'])) {
                    // find the image in categories (we need to specify the module here)
                    $data['catimage'] = xarTplGetImage($curcat['image'],'categories');
                    $data['catname'] = xarVarPrepForDisplay($curcat['name']);
                }
                if ($showchildren == 2) {
                    // Get child categories (all sub-levels)
                    $childlist = xarModAPIFunc('categories', 'visual', 'listarray',
                                              array('cid' => $cids[0]));
                    if (empty($childlist) || count($childlist) == 0) {
                        break;
                    }
                    foreach ($childlist as $info) {
                        if ($info['id'] == $cids[0]) {
                            continue;
                        }
                        $label = xarVarPrepForDisplay($info['name']);
                    // TODO: now this is a tricky part...
                        $link = xarModURL($modname,$type,$func,
                                         array('itemtype' => $itemtype,
                                               'catid' => $info['id']));
                        if (!empty($catcount[$info['id']])) {
                            $count = $catcount[$info['id']];
                        } else {
                            $count = 0;
                        }
    /* don't show descriptions in (potentially) multi-level trees
                        if (!empty($info['description'])) {
                            $descr = xarVarPrepHTMLDisplay($info['description']);
                        } else {
                            $descr = '';
                        }
    */
                        $data['catlines'][] = array('catlabel' => $label,
                                                    'catid' => $info['id'],
                                                    'catlink' => $link,
                                                  //  'catdescr' => $descr,
                                                    'catdescr' => '',
                                                    'catcount' => $count,
                                                    'beforetags' => $info['beforetags'],
                                                    'aftertags' => $info['aftertags']);

                    }
                    unset($childlist);
                } elseif ($showchildren == 1) {
                    // Get child categories (1 level only)
                    $children = xarModAPIFunc('categories', 'user', 'getchildren',
                                             array('cid' => $cids[0]));
                    if (empty($children) || count($children) == 0) {
                        break;
                    }
                    $data['catlines'] = array();
                // TODO: don't show icons when displaying items ?
                    $data['caticons'] = array();
                    $numicons = 0;
                    foreach ($children as $cat) {
                    // TODO: now this is a tricky part...
                        $label = xarVarPrepForDisplay($cat['name']);
                        $link = xarModURL($modname, $type, $func,
                                         array('itemtype' => $itemtype,
                                               'catid' => $cat['cid']));
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            $count = 0;
                        }
                        if (!empty($cat['image'])) {
                            // find the image in categories (we need to specify the module here)
                            $image = xarTplGetImage($cat['image'],'categories');
                            $numicons++;
                            $data['caticons'][] = array('catlabel' => $label,
                                                        'catid' => $cat['cid'],
                                                        'catlink' => $link,
                                                        'catimage' => $image,
                                                        'catcount' => $count,
                                                        'catnum' => $numicons);
                        } else {
                            if (!empty($cat['description']) && $cat['description'] != $cat['name']) {
                                $descr = xarVarPrepHTMLDisplay($cat['description']);
                            } else {
                                $descr = '';
                            }
                            $beforetags = '<li>';
                            $aftertags = '</li>';
                            $data['catlines'][] = array('catlabel' => $label,
                                                        'catid' => $cat['cid'],
                                                        'catlink' => $link,
                                                        'catdescr' => $descr,
                                                        'catcount' => $count,
                                                        'beforetags' => $beforetags,
                                                        'aftertags' => $aftertags);
                        }
                    }
                    unset($children);
                    if (count($data['catlines']) > 0) {
                        $numitems = count($data['catlines']);
                        // add leading <ul> tag
                        $data['catlines'][0]['beforetags'] = '<ul>' .
                                                   $data['catlines'][0]['beforetags'];
                        // add trailing </ul> tag
                        $data['catlines'][$numitems - 1]['aftertags'] .= '</ul>';
                        // add new column
                        if ($numitems > 7) {
                            $miditem = round(($numitems + 0.5) / 2) - 1;
                            $data['catlines'][$miditem]['aftertags'] .=
                                                   '</ul></td><td valign="top"><ul>';
                        }
                    }
                }
            }
            break;

        case 4: // menu
            $template= 'menu';
            if (empty($multiselect)) $multiselect = 0;         // Multi-selection disallowed
            if (empty($menutype)) $menutype = 0;               // Horizontal menu
            if (empty($highlightparent)) $highlightparent = 0; // None

            // Overrides $highlightparent modes in the case of a navbar menu type
            // Navbar works correctly only if current css classes are present in the parents
            if ($menutype == 2) $highlightparent = 2;

             if (!empty($dynamictitle)) {
                if (empty($title) && empty($module)) {
                    if (xarVarIsCached('Blocks.categories','title')) {
                        $title = xarVarGetCached('Blocks.categories','title');
                    }
                }
                if (empty($title) && !empty($itemtype)) {
                    // Get the list of all item types for this module (if any)
                    $mytypes = xarModAPIFunc($modname,'user','getitemtypes', array(), 0); // don't throw an exception if this function doesn't exist
                    if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                        $title = $mytypes[$itemtype]['label'];
                    }
                }
                if (empty($title)) {
                    $modinfo = xarModGetInfo($modid);
                    $title = ucwords($modinfo['displayname']);
                }
                $blockinfo['title'] = xarML('Browse in #(1)', $title);
            }

            $loopcids = $mastercids;

            // TODO: we need to study how navigation works.
            /*
            if (empty($cids) || count($cids) == 0) {
               $loopcids = $mastercids;
            } else {
                $loopcids = $cids;
            }
            */

            $selectedcids = $cids;
            $c = count($selectedcids);
            if ($c > 0) {
                // We need to check whether the selection passed intersects with the categories branches to navigate.
                for ($i = 0; $i++; $i < $c) {
                    if (!xarModAPIfunc('categories', 'user', 'isdescendant',
                        array('cid' => $selectedcids[$i], 'branches' => $loopcids, 'include_root' => true))) {
                        unset($selectedcids[$i]); // We remove it as it doesn't exist in the navigation.
                        // TODO: we should also take account of the depth desired here.
                    }
                }
                $c = count($selectedcids); // Might need to be updated
            }
            if ($c > 1) { // We still have some multi-selection to handle
                switch($multiselect) {
                    case 0: // we want single selection. No multiple ones. Unselect things.
                        $selectedcids = array(); // Nothing
                        $parentcids = array();
                        break;
                    case 1: // use arbitrary the first element of a multi-selection
                        $firstcid = $selectedcids[0];
                        $selectedcids = array($firstcid);
                        break;
                    case 2: // find the nearest common parent, including a category itself
                        $arrparent = array_keys(xarModAPIfunc('categories', 'user', 'getparents', array('cids' => $selectedcids, 'return_itself' => true)));
                        $countparents = count($arrparent);
                        $parentcids = array(); // To let the end of the code know that we already processed it right below
                        // We might need to check whether they intersect with the categories branches to navigate.
                        for ($i = 0; $i++; $i < $countparents) {
                            if (!xarModAPIfunc('categories', 'user', 'isdescendant',
                                array('cid' => $arrparent[$i], 'branches' => $loopcids, 'include_root' => true))) {
                                unset($arrparent[$i]); // We remove it as it doesn't exist in the navigation.
                            }
                        }
                        $countparents = count($arrparent);
                        if ($countparents) {
                            $selectedcids = array($arrparent[$countparents-1]); // Takes the last element
                            switch ($highlightparent) {
                                case 0:  // no parent highlighting
                                    break;
                                case 1:
                                    if ($countparents > 1) {
                                        $parentcids = array($arrparent[$countparents-2]);
                                    }
                                    break;
                                case 2:
                                    if ($countparents > 1) {
                                        unset($arrparent[$countparents-1]);
                                        $parentcids = $arrparent;
                                    }
                                    break;
                            }
                        }
                        break;
                    case 9: // Allows true multi-selection without restriction
                        // Nothing to do.
                        break;
                }
            }
            if (!isset($parentcids)) { // We have not computed parents yet.
                if ($c == 0 || !$highlightparent) {
                    $parentcids = array();
                } else {
                    $parentcids = array();
                    foreach ($selectedcids as $cid) {
                        $arrparent = array_keys(xarModAPIfunc('categories', 'user', 'getparents', array('cid' => $cid, 'return_itself' => false)));
                        $countparents = count($arrparent);
                        if ($countparents) {
                            switch ($highlightparent) {
                                case 0:  // no parent highlighting => should never pass here
                                    break;
                                case 1:
                                    if ($countparents > 1)
                                        $parentcids[] = $arrparent[$countparents-1];
                                    else
                                        $parentcids[] = $arrparent[0];
                                    break;
                                case 2:
                                    if ($countparents > 0) $parentcids = array_merge($parentcids, $arrparent);
                                    break;
                            }
                        }
                    }
                }
            }

            if (!xarModAPILoad('categories', 'visual')) return;

            // For now we merge both parents and real selection. We might want some different style for the true selected items from its relatives.
            $currentcids = array_unique(array_merge($selectedcids, $parentcids));
            $blockidprefix = 'catnavmenu';
            $typecssclass = array('sf-menu', 'sf-vertical', 'sf-navbar');
            $maincssclass = $menutype ? $typecssclass[0] . ' ' . $typecssclass[$menutype] : $typecssclass[0];
            $currentcssclass = 'sf-current';

            $cssfiles = $menutype ? $typecssclass[0] . ',' . $typecssclass[$menutype] : $typecssclass[$menutype];
            // Unused for now as we cannot pass a var to the <xar:base-include-javascript> tab

            if (!isset($showlinkitems)) $showlinkitems = FALSE;

            $menuarray = xarModAPIFunc('categories', 'visual', 'menuarray',
                            array('cids' => $loopcids, 'current_cids' => $currentcids, 'main_css_class' => $maincssclass,
                                  'blockidprefix' => 'catnavmenu', 'blockid' => $blockinfo['bid'],
                                  'modid' => $modid, 'modname' => $modname, 'type' => $type, 'func' => $func, 'itemtypes' => $itemtypes,
                                  'current_css_class' => $currentcssclass, 'showlinkitems' => $showlinkitems));

            $data['menutype'] = $menutype;
            $data['cssfiles'] = $cssfiles;
            $data['blockidprefix'] = $blockidprefix;
            $data['catmenuitems'] = array('items' => $menuarray);

            // The Superfish script only requires the pathclass parameter passed for the navbar type.
            if ($menutype == 2) {
                $data['currentpathclass'] = $currentcssclass;
            } else {
                $data['currentpathclass'] = '';
            }

            break;

        case 1: // tree
        default:

            $template = 'tree';
            // Get current title (if dynamic)
            if (!empty($dynamictitle)) {
                if (empty($title) && empty($module)) {
                    if (xarVarIsCached('Blocks.categories', 'title')) {
                        $title = xarVarGetCached('Blocks.categories','title');
                    }
                }
                if (empty($title) && !empty($itemtype)) {
                    // Get the list of all item types for this module (if any)
                    $mytypes = xarModAPIFunc($modname,'user','getitemtypes',
                                             // don't throw an exception if this function doesn't exist
                                             array(), 0);
                    if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                        $title = $mytypes[$itemtype]['label'];
                    }
                }
                if (empty($title)) {
                    $modinfo = xarModGetInfo($modid);
                    $title = ucwords($modinfo['displayname']);
                }
                $blockinfo['title'] = xarML('Browse in #(1)', $title);
            }

            $data['cattrees'] = array();

            if (empty($cids) || count($cids) == 0) {
                $loopcids = $mastercids;
            }
            elseif (isset($rootcids) && count($rootcids) > 0) {
                $loopcids = $rootcids;
            }
            if (isset($loopcids)) {
                foreach ($loopcids as $cid) {
                    $catparents = array();
                    $catitems = array();
                    // Get child categories
                    $children = xarModAPIFunc('categories', 'user', 'getchildren',
                                             array('cid' => $cid,
                                                   'return_itself' => true));
                    foreach ($children as $cat) {
                        // TODO: now this is a tricky part...
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            $count = 0;

                            if (empty($showempty) && empty($deepcount[$cat['cid']])) {
                                // We want to hide empty categories - so skip this loop.
                                continue;
                            }
                        }

                        $link = xarModURL($modname, $type, $func,
                                         array('itemtype' => $itemtype,
                                               'catid' => $cat['cid']));

                        $label = xarVarPrepForDisplay($cat['name']);
                        $descr = xarVarPrepForDisplay($cat['description']);
                        if ($cat['cid'] == $cid) {
                            $catparents[] = array('catlabel' => $label,
                                                  'catdescr' => $descr,
                                                  'catid' => $cat['cid'],
                                                  'catlink' => $link,
                                                  'catcount' => $count);
                        } else {
                            $catitems[] = array('catlabel' => $label,
                                                'catdescr' => $descr,
                                                'catid' => $cat['cid'],
                                                'catlink' => $link,
                                                'catcount' => $count);
                        }
                    }
                    $data['cattrees'][] = array('catitems' => $catitems,
                                                'catparents' => $catparents);
                }

            } else {
                foreach ($cids as $cid) {
                    $catparents = array();
                    $catitems = array();
                    // Get category information
                    $parents = xarModAPIFunc('categories', 'user', 'getparents',
                                            array('cid' => $cid));
                    if (empty($parents)) {
                        continue;
                    }
                // TODO: do something with parents
                    $root = '';
                    $parentid = 0;
                    foreach ($parents as $id => $info) {
                        if (empty($root)) {
                            $root = xarVarPrepForDisplay($info['name']);
                        }
                        if ($id == $cid) {
                            $parentid = $info['parent'];
                        }
                    }
                    // yes, this excludes the top-level categories too :-)
                    if (empty($parentid) || empty($root)) {
                        $parentid = $cid;
                //        return;
                    }
                    if (!empty($parents[$parentid])) {
                        $cat = $parents[$parentid];
                        $label = xarVarPrepForDisplay($cat['name']);
                        $descr = xarVarPrepForDisplay($cat['description']);
                        $link = xarModURL($modname,$type,$func,
                                         array('itemtype' => $itemtype,
                                               'catid' => $cat['cid']));
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            $count = 0;
                        }
                        $catparents[] = array('catlabel' => $label,
                                              'catdescr' => $descr,
                                              'catid' => $cat['cid'],
                                              'catlink' => $link,
                                              'catcount' => $count);
                    }

                    // Get sibling categories
                    $siblings = xarModAPIFunc('categories', 'user', 'getchildren',
                                             array('cid' => $parentid));
                    if ($showchildren && $parentid != $cid) {
                        // Get child categories
                        $children = xarModAPIFunc('categories' ,'user', 'getchildren',
                                                 array('cid' => $cid));
                    }

                    // Generate list of sibling categories
                    foreach ($siblings as $cat) {
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            $count = 0;

                            // Note: when hiding empty categories, check the deep count
                            // as a child category may be empty, but it could still have
                            // descendants with items.

                            if (empty($showempty) && empty($deepcount[$cat['cid']])) {
                                // We want to hide empty categories - so skip this loop.
                                continue;
                            }
                        }

                        $label = xarVarPrepForDisplay($cat['name']);
                        $descr = xarVarPrepForDisplay($cat['description']);
                        $link = xarModURL($modname, $type, $func,
                            array('itemtype' => $itemtype,
                                'catid' => $cat['cid']));

                        $savecid = $cat['cid'];
                        $catchildren = array();
                        if ($cat['cid'] == $cid) {
                            if (empty($itemid) && empty($andcids)) {
                                $link = '';
                            }
                            if ($showchildren && !empty($children) && count($children) > 0) {
                                foreach ($children as $cat) {
                                    $clabel = xarVarPrepForDisplay($cat['name']);
                                    $cdescr = xarVarPrepForDisplay($cat['description']);
                                // TODO: now this is a tricky part...
                                    $clink = xarModURL($modname,$type,$func,
                                                      array('itemtype' => $itemtype,
                                                            'catid' => $cat['cid']));
                                    if (!empty($catcount[$cat['cid']])) {
                                        $ccount = $catcount[$cat['cid']];
                                    } else {
                                        $ccount = 0;
                                    }
                                    $catchildren[] = array('clabel' => $clabel,
                                                           'cdescr' => $cdescr,
                                                           'cid' => $cat['cid'],
                                                           'clink' => $clink,
                                                           'ccount' => $ccount);
                                }
                            }
                        }
                        $catitems[] = array('catlabel' => $label,
                                            'catdescr' => $descr,
                                            'catid' => $savecid,
                                            'catlink' => $link,
                                            'catcount' => $count,
                                            'catchildren' => $catchildren);
                    }
                    $data['cattrees'][] = array('catitems' => $catitems,
                                                'catparents' => $catparents);
                }
            }
            break;
    }
    $data['blockid'] = $blockinfo['bid'];
    // Populate block info for passing back to theme.

    // The template base is set by this block if not already provided.
    // The base is 'nav-tree', 'nav-trails' or 'nav-prevnext', but allow
    // the admin to override this completely.
    $blockinfo['_bl_template_base'] = 'nav-' . $template;

    // Return data, not rendered content.
    $blockinfo['content'] = $data;
    if (!empty($blockinfo['content'])) {
        return $blockinfo;
    }
 echo '<pre>';print_r($data);echo '</pre>'; die('fu');
    return;
}

?>
