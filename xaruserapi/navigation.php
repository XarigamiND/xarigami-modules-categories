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
 * @author Xarigami Team
 */
/**
 * show some categories navigation in a template
 * @TODO: clean up all those ways to get parameters + better templating
 *
 * @param $args['module'] string module that you want to navigate in (default current module)
 * @param $args['itemtype'] integer item type of the module items (default none)
 * @param $args['itemid'] integer item id of the current module item (default none)
 * @param $args['catid'] string current category/categories we're navigating in, or
 * @param $args['cids'] array current category/categories we're navigating in
 * @param $args['showcatcount'] integer show a count per category (0 = no, 1 = local count, 2 = deep count)
 * @param $args['showchildren'] integer show children of the current category (0 = no, 1 = immediate children, 2 = all descendants)
 * @param $args['showempty'] integer show empty categories (0 = no, 1 = yes)
 * @param $args['urlmodule'] string module name to use in URLs (default $module)
 * @param $args['type'] string type to use in URLs (default 'user')
 * @param $args['func'] string function to use in URLs (default 'view')
 * @param $args['urlparam'] string extra parameter name to use in URLs (default 'itemtype')
 * @param $args['urlvalue'] string extra parameter value to use in URLs (default $itemtype)
 * @param $args['urlextra'] array extra arguments to use in URLs (default none)
 * @param $args['layout'] string layout to use for the navigation (prevnext, trails or tree - default trails)
 * @param $args['template'] string override the template that corresponds to this layout (prevnext, rootcats/trails or tree)
 * @param $args['tplmodule'] string override the module where this template is located (default 'categories')
 * @return string containing the HTML (or other) text to output in the BL template
 */
function categories_userapi_navigation($args)
{
    extract($args);

    // Allow the template to the over-ridden.
    // This allows different category browsing formats in different places.
    if (!empty($template)) {
        $template_override = $template;
    }

    // Get requested layout
    if (empty($layout)) {
        $layout = 2; // breadcrumb trails
    } elseif (!is_numeric($layout)) {
        switch ($layout) {
            case 'prevnext':
                $layout = 3;
                break;
            case 'trails':
                $layout = 2;
                break;
            case 'tree':
            default:
                $layout = 1;
                break;
         }
    }

    // Get root cids for tree layout (if any)
    if ($layout == 1) {
        if (empty($catid) && empty($cids)) {
            $rootcids = null;
        } elseif (!empty($cids)) {
            $rootcids = $cids;
        } elseif (strpos($catid,' ')) {
            $rootcids = explode(' ',$catid);
        } elseif (strpos($catid,'+')) {
            $rootcids = explode('+',$catid);
        } else {
            $rootcids = explode('-',$catid);
        }
        $catid = null;
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
        return xarML('Undefined module in categories navigation');
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
    if (empty($itemtype)) {
        $itemtype = null;
    }

    // Get current item id (if any)
    if (!isset($itemid)) {
        if (xarVarIsCached('Blocks.categories', 'itemid')) {
            $itemid = xarVarGetCached('Blocks.categories', 'itemid');
        } else {
            // try to get itemid from input
            xarVarFetch('itemid', 'id', $itemid, NULL, XARVAR_DONT_SET);
        }
    }
    if (empty($itemid)) {
        $itemid = null;
    }

    // Get number of categories for this module + item type
    if (!empty($itemtype)) {
        $numcats = (int) xarModGetVar($modname, 'number_of_categories.'.$itemtype);
    } else {
        $numcats = (int) xarModGetVar($modname, 'number_of_categories');
    }
    if (empty($numcats) || !is_numeric($numcats)) {
        // no categories to show here -> return empty output
        return '';
    }

    // Get master cids for this module + item type
    if (!empty($itemtype)) {
        $cidlist = xarModGetVar($modname,'mastercids.'.$itemtype);
    } else {
        $cidlist = xarModGetVar($modname,'mastercids');
    }
    if (empty($cidlist)) {
        // no categories to show here -> return empty output
        return '';
    } else {
        $mastercids = explode(';',$cidlist);
        // preserve order of root categories if possible
        //sort($mastercids,SORT_NUMERIC);
    }

    // See if we need to show a count per category
    if (!isset($showcatcount)) {
        $showcatcount = 0;
    }

    // See if we need to show the children of current categories
    if (!isset($showchildren)) {
        $showchildren = 1;
    }

    // See if we need to show empty categories
    if (!isset($showempty) && empty($showcatcount)) {
        $showempty = 1; // default yes here (otherwise you never see anything by default - duh)
    }

    // Get current category counts (optional array of cid => count)
    if (empty($showcatcount)) {
        $catcount = array();
    } elseif (empty($catcount)) {
        // A 'deep count' sums the totals at each node with the totals of all descendants.
        if ($showcatcount > 1 || empty($showempty)) {
            if (xarVarIsCached('Blocks.categories', 'deepcount')) {
                $deepcount = xarVarGetCached('Blocks.categories', 'deepcount');
            } else {
                $deepcount = xarModAPIFunc(
                    'categories', 'user', 'deepcount',
                    array('modid' => $modid, 'itemtype' => $itemtype)
                );
                xarVarSetCached('Blocks.categories','deepcount', $deepcount);
            }
        }

        if (xarVarIsCached('Blocks.categories', 'catcount')) {
            $catcount = xarVarGetCached('Blocks.categories', 'catcount');
        } else {
            // Get number of items per category (for this module).
            // If showcatcount == 2 then add in all descendants too.

            if ($showcatcount == 1) {
                // We want to display only children category counts.
                $catcount = xarModAPIFunc(
                    'categories','user', 'groupcount',
                    array('modid' => $modid, 'itemtype' => $itemtype)
                );
            } else {
                // We want to display the deep counts.
                $catcount =& $deepcount;
            }

            xarVarSetCached('Blocks.categories', 'catcount', $catcount);
        }
    }

    // Specify type=... & func = ... arguments for xarModURL()
    if (empty($type)) {
        if (xarVarIsCached('Blocks.categories','type')) {
            $type = xarVarGetCached('Blocks.categories','type');
        }
        if (empty($type)) {
            $type = 'user';
        }
    }
    if (empty($func)) {
        if (xarVarIsCached('Blocks.categories','func')) {
            $func = xarVarGetCached('Blocks.categories','func');
        }
        if (empty($func)) {
            $func = 'view';
        }
    }

    // Specify the module to use as argument for xarModURL()
    if (empty($urlmodule)) {
        $urlmodule = $modname;
    }
    // Specify the URL parameter to use as argument for xarModURL()
    if (empty($urlparam)) {
        $urlparam = 'itemtype';
    }
    // Specify the URL value to use as argument for xarModURL()
    if (empty($urlvalue)) {
        $urlvalue = $itemtype;
    }
    // Specify additional arguments for xarModURL()
    if (empty($urlextra)) {
        $urlextra = array();
        $urlargs = array();
    } else {
        $urlargs = $urlextra;
    }
    // By default, include itemtype=N in URLs (if not NULL)
    $urlargs[$urlparam] = $urlvalue;

    // Get current categories
    if (xarVarIsCached('Blocks.categories','catid')) {
       $catid = xarVarGetCached('Blocks.categories','catid');
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
        if (xarVarIsCached('Blocks.categories','cids')) {
            $cids = xarVarGetCached('Blocks.categories','cids');
        }
        if (xarVarIsCached('Blocks.categories','andcids')) {
            $andcids = xarVarGetCached('Blocks.categories','andcids');
        }
        if (empty($cids)) {
            // try to get cids from input
            xarVarFetch('cids',    'isset', $cids,    NULL,  XARVAR_DONT_SET);
            xarVarFetch('andcids', 'isset', $andcids, false, XARVAR_NOT_REQUIRED);
            // for preview of hooked new/modified items
            xarVarFetch('new_cids',    'isset', $newcids,    NULL,  XARVAR_DONT_SET);
            xarVarFetch('modify_cids', 'isset', $modifycids, NULL,  XARVAR_DONT_SET);

            if (!empty($cids)) {
                // found some cids
            } elseif (!empty($newcids)) {
                $cids = $newcids;
            } elseif (!empty($modifycids)) {
                $cids = $modifycids;
            } else {
                $cids = array();
                if ((empty($module) || $module == $modname) && !empty($itemid)) {
                    $links = xarModAPIFunc('categories','user','getlinks',
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
            if (empty($cid) || !is_numeric($cid)) {
                continue;
            }
            $seencid[$cid] = 1;
        }
        $cids = array_keys($seencid);
    }

    $data = array();
    $data['cids'] = $cids;
    $data['istree'] = $istree;
    // pass information about current module, item type and item id (if any) to template
    $data['module'] = $modname;
    $data['itemtype'] = $itemtype;
    $data['itemid'] = $itemid;
    // pass information about current function to template
    $data['urlmodule'] = $urlmodule;
    $data['type'] = $type;
    $data['func'] = $func;
    $data['urlparam'] = $urlparam;
    $data['urlvalue'] = $urlvalue;
    $data['urlextra'] = $urlextra;
    $data['urlargs'] = $urlargs;

    switch ($layout) {

        case 3: // prev/next category
            $template = 'prevnext';
            if (empty($cids) || count($cids) != 1 || in_array($cids[0], $mastercids)) {
                // nothing to show here
                return '';
            } else {
                // See if we need to show anything
                if (empty($showprevnext)) {
                    if (xarVarIsCached('Blocks.categories','showprevnext')) {
                        $showprevnext = xarVarGetCached('Blocks.categories','showprevnext');
                        if (empty($showprevnext)) {
                            return '';
                        }
                    }
                }
                $cat = xarModAPIFunc('categories','user','getcatinfo',
                                array('cid' => $cids[0]));
                if (empty($cat)) {
                    return '';
                }
                $neighbours = xarModAPIFunc('categories','user','getneighbours',
                                           $cat);
                if (empty($neighbours) || !is_array($neighbours) == 0) {
                    return '';
                }
                foreach ($neighbours as $neighbour) {
//                    if ($neighbour['link'] == 'parent') {
//                        $data['uplabel'] = $neighbour['name'];
//                        $data['upcid'] = $neighbour['cid'];
//                        $urlargs['catid'] = $neighbour['cid'];
//                        $data['uplink'] = xarModURL($urlmodule,$type,$func,
//                                                    $urlargs);
//                    } elseif ($neighbour['link'] == 'previous') {
                    if ($neighbour['link'] == 'previous') {
                        $data['prevlabel'] = $neighbour['name'];
                        $data['prevcid'] = $neighbour['cid'];
                        $urlargs['catid'] = $neighbour['cid'];
                        $data['prevlink'] = xarModURL($urlmodule,$type,$func,
                                                      $urlargs);
                    } elseif ($neighbour['link'] == 'next') {
                        $data['nextlabel'] = $neighbour['name'];
                        $data['nextcid'] = $neighbour['cid'];
                        $urlargs['catid'] = $neighbour['cid'];
                        $data['nextlink'] = xarModURL($urlmodule,$type,$func,
                                                      $urlargs);
                    }
                }
                if (!isset($data['nextlabel']) &&
                    !isset($data['prevlabel'])) {
                    return '';
                }
//                if (!isset($data['uplabel'])) {
//                    $data['uplabel'] = '&nbsp;';
//                }
            }
            break;

        case 2: // crumbtrails
            $data['cattitle'] = xarML('Browse in:');
            if (empty($cids) || count($cids) == 0) {
                $template = 'rootcats';
                $data['catitems'] = array();

                // Get root categories
                $catlist = xarModAPIFunc(
                    'categories','user','getcatinfo',
                    array('cids' => $mastercids)
                );
                $join = '';

                if (empty($catlist) || !is_array($catlist)) {return '';}

                // preserve order of base categories if possible
                foreach ($mastercids as $cid) {
                    if (!isset($catlist[$cid])) continue;
                    $cat = $catlist[$cid];
                    // TODO: now this is a tricky part...
                    $urlargs['catid'] = $cat['cid'];
                    $link = xarModURL(
                        $urlmodule,$type,$func,
                        $urlargs
                    );
                    $label = xarVarPrepForDisplay($cat['name']);
                    $data['catitems'][] = array(
                        'catlabel' => $label,
                        'catid' => $cat['cid'],
                        'catlink' => $link,
                        'catjoin' => $join
                    );
                    $join = ' | ';
                }
            } else {
                $template = 'trails';

                $data['cattrails'] = array();
                $descriptions = array();

                // Loop for each category assigned to the item.
                // A separate trail will be created for each assigned.
                foreach ($cids as $cid) {
                    // Get category information.
                    $parents = xarModAPIFunc(
                        'categories', 'user', 'getancestors',
                        array('cid' => $cid, 'self' => true)
                    );

                    // Some kind of error; skip this category.
                    // The ancestors list should never be empty, as it
                    // includes 'self'.
                    if (empty($parents)) {continue;}

                    $catleft = 0;
                    $baseorder = 0;
                    $catitems = array();
                    $curcount = 0;

                    // Create the top-level link.
                    // 'baseflag' = 0 for 'All'; 1 for cids below the 'base';
                    // 2 at the base and 3 above, with 4 for the current cid.
                    // Also 5 for the pseudo-trails: 'All Cats' and 'Any Cats'.
                    //
                    // Explanation of how the 'baseflag' works:-
                    // Each category item in the trail has a 'baseflag' set, with a value
                    // that indicates its position in the trail. The template can use the
                    // flag to decide whether each item should be displayed. How the template
                    // uses the flag will depend in the effect the site designer is trying
                    // to achieve.
                    // A typical trail will look like this:
                    // All > Cat1 > Cat1.1 > Cat1.1.1 > Cat1.1.1.1 > CatCurrent
                    // Supposing Cat1.1 is a base category for the current selection, then
                    // the baseflag values will be set like this:
                    // 0 > 1 > 2 > 3 > 3 > 4
                    // Here: 0 is the top level; 1 is below the base; 2 is the base and 3 for
                    // the two items below the base. By checking the flag in the template, you
                    // can decide which levels to display (e.g. everything, stop at the base,
                    // only items above the base etc).
                    // Flag values are assigned in order, from 'All' to the current category,
                    // with a higher value always taking precendence.

                    // Initialise variables for a single trail.
                    $label = xarML('All');
                    unset($urlargs['catid']);
                    $link = xarModURL(
                        $urlmodule,$type,$func,
                        $urlargs
                    );
                    $join = '';
                    $baseflag = 0;
                    $trailbasecid = 0;

                    $catitems[] = array(
                        'catlabel' => $label,
                        'catid' => $cid,
                        'catlink' => $link,
                        'catjoin' => $join,
                        'baseflag' => $baseflag
                    );

                    // TODO: The join value only makes sense if the complete trail is
                    // displayed. If only a partial trail is displayed, then the join
                    // value will be wrong. To alleviate this, the join string value
                    // should be calculated entirely within the template.
                    $join = ' &#187; ';
                    $baseflag = 1;

                    // Loop for each ancestor and create an entry.
                    foreach ($parents as $cat) {
                        if ($baseflag == 2) {$baseflag = 3;}

                        // Is this cid a base cid?
                        if ($baseflag == 1 && in_array($cat['cid'], $mastercids)) {
                            // This is a base cid.
                            $baseflag = 2;
                            // Set the base cid for this trail (only set the first base we come across).
                            $trailbasecid = ($trailbasecid ? $trailbasecid : $cat['cid']);
                            if (empty($baseorder)) {
                                // return the index in the mastercids
                                $baseorder = array_search($cat['cid'], $mastercids);
                                if ($baseorder === false) {
                                     $baseorder = 0;
                                } else {
                                     $baseorder++;
                                }
                            }
                        }

                        // TODO: move the prep to the template.
                        $label = xarVarPrepForDisplay($cat['name']);
                        // TODO: make the link always available to the template, but make the
                        // template use the baseflag to determine whether to display the link
                        // or not.
                        if ($cat['cid'] == $cid && empty($itemid) && empty($andcids) && empty($istree)) {
                            $link = '';
                            // The end of the trail is flagged as level 4.
                            $baseflag = 4;
                        } else {
                            $urlargs['catid'] = $cat['cid'];
                            $link = xarModURL(
                                $urlmodule, $type, $func,
                                $urlargs
                            );
                        }

                        if ($cat['cid'] == $cid) {
                            $catleft = $cat['left'];
                            // show optional count
                            if (isset($catcount[$cat['cid']])) {
                                $curcount = $catcount[$cat['cid']];
                            }
                            // TODO: the preps should be in the template, as not everyone will
                            // want the descriptions prepped (they may contain required HTML).
                            // Normally the the description will go into a 'title' attribute,
                            // but not always. As it is, the HTML display prep is the wrong one
                            // to use for an attribute anyway.
                            if (!empty($cat['description'])) {
                                $descriptions[$cid] = xarVarPrepHTMLDisplay($cat['description']);
                            } else {
                                $descriptions[$cid] = xarVarPrepForDisplay($cat['name']);
                            }
                            // Save current category info for icon etc.
                            if (count($cids) == 1) {
                                $curcat = $cat;
                            }
                        }

                        $catitems[] = array(
                            'catlabel' => $label,
                            'catid' => $cat['cid'],
                            'catlink' => $link,
                            'catjoin' => $join,
                            'baseflag' => $baseflag
                        );
                    }

                    // TODO: move to template.
                    if (!empty($istree)) {
                        $viewall = '';
                    } else {
                        $urlargs['catid'] = '_' . $cid;
                        $viewall = xarModURL(
                            $urlmodule, $type, $func,
                            $urlargs
                        );
                    }
                    $data['cattrails'][] = array(
                        'catitems' => $catitems,
                        'catcount' => $curcount,
                        'viewall' => $viewall,
                        'catid' => $cid,
                        'catleft' => $catleft,
                        'baseorder' => $baseorder,
                        'basecatid' => $trailbasecid
                    );
                }
                // sort navigation trails by base category order, then by Celko tree
                uasort($data['cattrails'], 'categories_navigation_sortbyorder');
                // re-order the list of cids and descriptions accordingly
                $sortcids = array();
                $sortdescr = array();
                foreach ($data['cattrails'] as $trail) {
                    $sortcids[] = $trail['catid'];
                    if (isset($descriptions[$trail['catid']])) {
                        $sortdescr[] = $descriptions[$trail['catid']];
                    }
                }

                // Add filters to select on 'all categories' or 'any categories'
                if (count($cids) > 1) {
                    $catitems = array();
                    if (!empty($itemid) || !empty($andcids)) {
                        $label = xarML('Any of these categories');
                        $urlargs['catid'] = join('-', $sortcids);
                        $link = xarModURL(
                            $urlmodule,$type,$func,
                            $urlargs
                        );
                        $join = '';
                        $catitems[] = array(
                            'catlabel' => $label,
                            'catid' => join('-', $sortcids),
                            'catlink' => $link,
                            'catjoin' => $join,
                            'baseflag' => 5
                        );
                    }
                    if (empty($andcids)) {
                        $label = xarML('All of these categories');
                        $urlargs['catid'] = join('+', $sortcids);
                        $link = xarModURL(
                            $urlmodule, $type, $func,
                            $urlargs
                        );
                        if (!empty($itemid)) {
                            $join = '-';
                        } else {
                            $join = '';
                        }
                        $catitems[] = array(
                            'catlabel' => $label,
                            'catid' => join('+', $sortcids),
                            'catlink' => $link,
                            'catjoin' => $join,
                            'baseflag' => 5
                        );
                    }
                    $curcount = 0;
                    $data['cattrails'][] = array(
                        'catitems' => $catitems,
                        'catcount' => $curcount
                    );
                    // add a hit for the categories we're viewing here
                    if (empty($itemid) && xarModIsHooked('hitcount','categories')) {
                        foreach ($cids as $cid) {
                            if (empty($cid)) {
                                continue;
                            }
                            // if we're viewing all items below a certain category, i.e. catid = _NN
                            $cid = str_replace('_', '', $cid);
                            // FIXME: if this fails, an exception will be set, so it needs to be cleared?
                            xarModAPIFunc('hitcount','admin','update',
                                          array('modname' => 'categories',
                                                'objectid' => $cid));
                        }
                    }
                }

                // TODO: move off to nav-trails template ?
                // Build category description
                if (!empty($itemid)) {
                    $data['catdescr'] = join(' + ', $sortdescr);
                } elseif (!empty($andcids)) {
                    $data['catdescr'] = join(' ' . xarML('and') . ' ', $sortdescr);
                } else {
                    $data['catdescr'] = join(' ' . xarML('or') . ' ', $sortdescr);
                }

                if (count($cids) != 1) {
                    break;
                }

                if (!empty($curcat)) {
                    $curcat['module'] = 'categories';
                    $curcat['itemtype'] = 0;
                    $curcat['itemid'] = $cids[0];
                    $urlargs['catid'] = $cids[0];
                    $curcat['returnurl'] = xarModURL(
                        $urlmodule, $type, $func,
                        $urlargs
                    );
                    // pass along the current module & itemtype for pubsub (urgh)
                    $curcat['current_module'] = $modname;
                    $curcat['current_itemtype'] = $itemtype;
                    // calling item display hooks *for the categories module* here !
                // FIXME: if hitcount is hooked to categories, this will also increase the hitcount
                //        of the category when displaying an article that belongs to that single category
                // Possible solution : extend xarVarIsCached('Hooks.hitcount','nocount') mechanism to take
                // into account the module ???
                    $data['cathooks'] = xarModCallHooks('item','display',$cids[0],$curcat,'categories');
                    // saving the current cat id for use e.g. with DD tags (<xar:data-display module="categories" itemid="$catid" />)
                    $data['catid'] = $curcat['cid'];
                }

                // set the page title to the current module + category if no item is displayed
                if (empty($itemid)) {
                    // Get current title
                    if (empty($title)) {
                        if (xarVarIsCached('Blocks.categories', 'title')) {
                            $title = xarVarGetCached('Blocks.categories', 'title');
                        }
                    }
                    if (!empty($curcat['name'])) {
                        $title = xarVarPrepForDisplay($curcat['name']);
                    }
                    if (!empty($title)) {
                        xarTplSetPageTitle($title);
                    }
                }

                // TODO: don't show icons when displaying items?
                if (!empty($curcat['image'])) {
                    // find the image in categories (we need to specify the module here)
                    $data['catimage'] = xarTplGetImage($curcat['image'], 'categories');
                    $data['catname'] = xarVarPrepForDisplay($curcat['name']);
                }
                if ($showchildren == 2) {
                    // Get child categories (all sub-levels)
                    $childlist = xarModAPIFunc(
                        'categories', 'visual', 'listarray',
                        array('cid' => $cids[0])
                    );
                    if (empty($childlist) || count($childlist) == 0) {
                        break;
                    }
                    foreach ($childlist as $info) {
                        if ($info['id'] == $cids[0]) {
                            continue;
                        }
                        $label = xarVarPrepForDisplay($info['name']);
                        $urlargs['catid'] = $info['id'];
                        $link = xarModURL(
                            $urlmodule, $type, $func,
                            $urlargs
                        );
                        if (!empty($catcount[$info['id']])) {
                            $count = $catcount[$info['id']];
                        } else {
                            $count = 0;
                        }
                        $data['catlines'][] = array(
                            'catlabel' => $label,
                            'catid' => $info['id'],
                            'catlink' => $link,
                            'catdescr' => '',
                            'catcount' => $count,
                            'beforetags' => $info['beforetags'],
                            'aftertags' => $info['aftertags']
                        );
                    }
                    unset($childlist);
                } elseif ($showchildren == 1) {
                    // Get child categories (1 level only)
                    $children = xarModAPIFunc(
                        'categories', 'user', 'getchildren',
                        array('cid' => $cids[0])
                    );
                    if (empty($children) || count($children) == 0) {
                        break;
                    }
                    $data['catlines'] = array();

                    // TODO: don't show icons when displaying items?
                    // TODO: move the HTML to the template.
                    $data['caticons'] = array();
                    $numicons = 0;
                    foreach ($children as $cat) {
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            // Note: when hiding empty categories, check the deep count
                            // as a child category may be empty, but it could still have
                            // descendants with items.
                            if (!empty($showempty) || !empty($deepcount[$cat['cid']])) {
                                // We are not hiding empty categories - set count to zero.
                                $count = 0;
                            } else {
                                // We want to hide empty categories - so skip this loop iteration.
                                continue;
                            }
                        }

                        $label = xarVarPrepForDisplay($cat['name']);
                        $urlargs['catid'] = $cat['cid'];
                        $link = xarModURL(
                            $urlmodule, $type, $func,
                            $urlargs
                        );
                        if (!empty($cat['description']) && $cat['description'] != $cat['name']) {
                                $descr = xarVarPrepHTMLDisplay($cat['description']);
                            } else {
                                $descr = '';
                            }
                        if (!empty($cat['image'])) {
                            // find the image in categories (we need to specify the module here)
                            $image = xarTplGetImage($cat['image'], 'categories');
                            $numicons++;
                            $data['caticons'][] = array(
                                'catlabel' => $label,
                                'catid' => $cat['cid'],
                                'catlink' => $link,
                                'catdescr' => $descr,
                                'catimage' => $image,
                                'catcount' => $count,
                                'catnum' => $numicons
                            );
                        } else {
                            $beforetags = '<li>';
                            $aftertags = '</li>';
                            $data['catlines'][] = array(
                                'catlabel' => $label,
                                'catid' => $cat['cid'],
                                'catlink' => $link,
                                'catdescr' => $descr,
                                'catcount' => $count,
                                'beforetags' => $beforetags,
                                'aftertags' => $aftertags
                            );
                        }
                    }
                    unset($children);
                    if (count($data['catlines']) > 0) {
                        $numitems = count($data['catlines']);
                        // add leading <ul> tag
                        $data['catlines'][0]['beforetags'] = '<ul>' . $data['catlines'][0]['beforetags'];
                        // add trailing </ul> tag
                        $data['catlines'][$numitems - 1]['aftertags'] .= '</ul>';
                        // add new column
                        if ($numitems > 7) {
                            $miditem = round(($numitems + 0.5) / 2) - 1;
                            $data['catlines'][$miditem]['aftertags'] .= '</ul></td><td valign="top"><ul>';
                        }
                    }
                }
            }
            break;

        case 1: // tree
        default:
            $template = 'tree';
            $data['cattrees'] = array();

            if (empty($cids) || count($cids) == 0) {
                foreach ($mastercids as $cid) {
                    $catparents = array();
                    $catitems = array();
                    // Get child categories
                    $children = xarModAPIFunc('categories','user','getchildren',
                                             array('cid' => $cid,
                                                   'return_itself' => true));

                    foreach ($children as $cat) {
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            $count = 0;

                            // TODO: check! When does this section get executed?
// <mikespub> this is used in the dynamic case, to show the base categories for a module+itemtype
//            when no categories are currently selected
// See also the navigation block, which was supposed to stay in sync with this code, except
// for returning null instead of '', and adding some block title at the end of the code...
                            // TODO: how much duplication is there in these three loops?
                            // Note: when hiding empty categories, check the deep count
                            // as a child category may be empty, but it could still have
                            // descendants with items.

                            if (!empty($showempty) || !empty($deepcount[$cat['cid']])) {
                                // We are not hiding empty categories - set count to zero.
                                $count = 0;
                            } else {
                                // We want to hide empty categories - so skip this loop.
                                continue;
                            }
                        }

                        $label = xarVarPrepForDisplay($cat['name']);
                    // TODO: now this is a tricky part...
                        $urlargs['catid'] = $cat['cid'];
                        $link = xarModURL($urlmodule,$type,$func,
                                          $urlargs);

                        if ($cat['cid'] == $cid) {
                            $catparents[] = array('catlabel' => $label,
                                                  'catid' => $cat['cid'],
                                                  'catlink' => $link,
                                                  'catcount' => $count);
                        } else {
                            $catitems[] = array('catlabel' => $label,
                                                'catid' => $cat['cid'],
                                                'catlink' => $link,
                                                'catcount' => $count);
                        }
                    }
                    $data['cattrees'][] = array('catitems' => $catitems,
                                                'catparents' => $catparents);
                }
            } elseif (isset($rootcids) && count($rootcids) > 0) {
                foreach ($rootcids as $cid) {
                    $catparents = array();
                    $catitems = array();
                    // Get child categories
                    $children = xarModAPIFunc('categories','user','getchildren',
                                             array('cid' => $cid,
                                                   'return_itself' => true));
                    foreach ($children as $cat) {
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            $count = 0;

                            // Note: when hiding empty categories, check the deep count
                            // as a child category may be empty, but it could still have
                            // descendants with items.

                            if (!empty($showempty) || !empty($deepcount[$cat['cid']])) {
                                // We are not hiding empty categories - set count to zero.
                                $count = 0;
                            } else {
                                // We want to hide empty categories - so skip this loop.
                                continue;
                            }
                        }

                        $label = xarVarPrepForDisplay($cat['name']);
                    // TODO: now this is a tricky part...
                        $urlargs['catid'] = $cat['cid'];
                        $link = xarModURL($urlmodule,$type,$func,
                                          $urlargs);
                        if ($cat['cid'] == $cid) {
                            $catparents[] = array('catlabel' => $label,
                                                  'catid' => $cat['cid'],
                                                  'catlink' => $link,
                                                  'catcount' => $count);
                        } else {
                            $catitems[] = array('catlabel' => $label,
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
                    $parents = xarModAPIFunc('categories','user','getancestors',
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
                        if ($id = $cid) {
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
                        $urlargs['catid'] = $cat['cid'];
                        $link = xarModURL($urlmodule,$type,$func,
                                          $urlargs);
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            // JJ: TODO: check hiding.
                            $count = 0;
                        }
                        $catparents[] = array('catlabel' => $label,
                                              'catid' => $cat['cid'],
                                              'catlink' => $link,
                                              'catcount' => $count);
                    }
                    // Get sibling categories
                    $siblings = xarModAPIFunc('categories','user','getchildren',
                                             array('cid' => $parentid));
                    if ($showchildren && $parentid != $cid) {
                        // Get child categories
                        $children = xarModAPIFunc('categories','user','getchildren',
                                                 array('cid' => $cid));
                    }

                    // Generate list of sibling categories
                    foreach ($siblings as $cat) {
                        $label = xarVarPrepForDisplay($cat['name']);
                        $urlargs['catid'] = $cat['cid'];
                        $link = xarModURL($urlmodule,$type,$func,
                                          $urlargs);
                        if (!empty($catcount[$cat['cid']])) {
                            $count = $catcount[$cat['cid']];
                        } else {
                            // JJ: TODO: check hiding.
                            $count = 0;
                        }
                        $savecid = $cat['cid'];
                        $catchildren = array();
                        if ($cat['cid'] == $cid) {
                            if (empty($itemid) && empty($andcids)) {
                                $link = '';
                            }
                            if ($showchildren && !empty($children) && count($children) > 0) {
                                foreach ($children as $cat) {
                                    $clabel = xarVarPrepForDisplay($cat['name']);
                                // TODO: now this is a tricky part...
                                    $urlargs['catid'] = $cat['cid'];
                                    $clink = xarModURL($urlmodule,$type,$func,
                                                       $urlargs);
                                    if (!empty($catcount[$cat['cid']])) {
                                        $ccount = $catcount[$cat['cid']];
                                    } else {
                                        $ccount = 0;
                                    }
                                    $catchildren[] = array('clabel' => $clabel,
                                                           'cid' => $cat['cid'],
                                                           'clink' => $clink,
                                                           'ccount' => $ccount);
                                }
                            }
                        }
                        $catitems[] = array('catlabel' => $label,
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

    // Specify the module where the templates are located
    if (empty($tplmodule)) {
        $tplmodule = 'categories';
    }
    // Do template override.
    if (!empty($template_override)) {
        $template = $template_override;
    }
    return xarTplModule($tplmodule, 'user', 'navigation', $data, $template);
}

/**
 * sort navigation trails by base category order, then by Celko tree
 */
function categories_navigation_sortbyorder ($a,$b)
{
    if ($a['baseorder'] == $b['baseorder']) {
        if ($a['catleft'] == $b['catleft']) return 0;
        return ($a['catleft'] > $b['catleft']) ? 1 : -1;
    }
    return ($a['baseorder'] > $b['baseorder']) ? 1 : -1;
}

?>
