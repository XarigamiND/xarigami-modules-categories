<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2010 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 * @author Xarigami Team
 */
/**
 * Build array with visual tree of categories and eventually articles (&lt;ul&gt;&lt;li&gt;...&lt;/li&gt; style)
 * for use in view maps, menus etc.
 *
 * @param $args['cids'] The ID or array of ids of the root categories used for the tree
 * @param $args['showlinkitems'] Includes also the articles linked to the categories (default FALSE)
 * @return array of array('cid' => 123,
 *                        'name' => 'My Cat',
 *                        'beforetags' => '&lt;ul&gt;&lt;li&gt; ',
 *                        'aftertags' => ' &lt;/li&gt;&lt;/ul&gt;&lt;/ul&gt;')
 */
function categories_visualapi_menuarray ($args)
{
    // Load User API
    if (!xarModAPILoad('categories', 'user')) return;

    if (!isset($args['cids'])) return;
    if (!isset($args['modid']) || !isset($args['modname']) || !isset($args['type']) || !array_key_exists('itemtypes', $args) || !isset($args['func'])) return;
    $modid = $args['modid']; $modname = $args['modname']; $type = $args['type']; $itemtypes = $args['itemtypes']; $func = $args['func'];

    if (!is_array($args['cids']) && is_numeric($args['cids'])) {
        $cids[] = $args['cids'];
    } elseif (is_array($args['cids'])) {
        $cids = $args['cids'];
    } else {
        return;
    }

    $mainitemid = isset($args['blockidprefix']) ? $args['blockidprefix'] : 'catnavmenu';
    if (!isset($args['blockid']) || !is_numeric($args['blockid'])) return; // Bad parameter exception in 1.2
    $mainitemid .= $args['blockid'];

    if (isset($args['showlinkitems']) && $args['showlinkitems'] && xarModIsAvailable('articles')) {
        $showlinkitems = TRUE;
        $catcount = xarModAPIFunc('categories', 'user', 'groupcount', array('modid' => $modid, 'itemtype' => NULL));
        xarVarSetCached('Blocks.categories', 'catcount', $catcount);
    } else {
        $showlinkitems = FALSE;
        $catcount = array();
    }

    // Load Article User API if required

    $list_data = Array ();

    $currentcids = array();
    $currentcid = isset($args['current_cid']) ? $args['current_cid'] : NULL;
    if (isset($args['current_cids'])) { // Current cids are passed we don't compute relatives
        $currentcids = $args['current_cids'];
    } else { // Research relatives from current_cid arg
        if (!empty($args['current_css_class']) && !empty($currentcid)) {

            // Getting all cids relative to the currentcid (parents and itself)

            $currentrelatives = xarModAPIFunc('categories', 'user', 'getcat',
            array(  'eid' => FALSE,
                    'cid' => $currentcid,
                    'return_itself' => TRUE,
                    'getchildren' => FALSE,
                    'getparents' => TRUE,
                    'maximum_depth' => NULL,
                    'minimum_depth' => NULL));

            foreach ($currentrelatives as $relative) {
                $currentcids[] = $relative['cid'];
            }
        } else {
            $currentcssclass = '';
        }
    }

    $maincssclass = isset($args['main_css_class']) ? $args['main_css_class'] : 'sf-menu';
    $currentcssclass = isset($args['current_css_class']) ? $args['current_css_class'] : 'sf-current';

    // Further checks are made by getcat. However we need to know how many $cids root categories will be displayed to the user
    foreach ($cids as $key => $cid) {
        if (!xarSecurityCheck('ViewCategories', 0, 'Category', "All:$cid")) unset($cids[$key]);
    }
    $cids = array_values($cids); // reindex as we might have removed an item

    // xcat-000638 - If we have only a single remaining category in the $cids, we may prefer to display
    // directly its children as the first level in the menu.
    if (count($cids) === 1) {
        $children = xarModAPIFunc('categories','user','getchildren', array('cid' => $cids[0], 'return_itself' => FALSE));
        $newcids = array();
        $newitemtypes = array();
        foreach ($children as $cat) {
            $newcids[] = $cat['cid'];
            $newitemtypes[] = $itemtypes[0];
        }
        if (count($newcids) !== 0) {
            unset($cids,$itemtypes);
            $itemtypes = $newitemtypes;
            $cids = $newcids;
        }
    }

    $items = array();
    // Looping among the root cids
    foreach ($cids as $key => $cid) {
        // Getting categories Array
        $categories = xarModAPIFunc('categories', 'user', 'getcat',
            array(  'eid' => FALSE,
                    'cid' => $cid,
                    'return_itself' => TRUE,
                    'getchildren' => TRUE,
                    'getparents' => FALSE,
                    'maximum_depth' => isset($args['maximum_depth']) ? $args['maximum_depth'] : NULL,
                    'minimum_depth' => isset($args['minimum_depth']) ? $args['minimum_depth']: NULL));

        if ($categories === FALSE || empty($categories)) {
            // Sometimes a category has been deleted, causing to arrive here. Better not throw an exception and just skip the category.
            unset($cids[$key]);
            continue;
        }

        $firstcat = reset($categories);
        $startindent = $firstcat['indentation'];
        foreach ($categories as $category) {
            $items[] = array(   'islink' => FALSE,
                                'cid' => $category['cid'],
                                'name' => xarVarPrepForDisplay($category['name']),
                                'image' => $category['image'],
                                'description' => xarVarPrepForDisplay($category['description']),
                                'beforetags' => '',
                                'aftertags' => '',
                                'class' => '',
                                'indentation' => $category['indentation'] - $startindent,
                                'link' => xarModURL($modname, $type, $func, array('itemtype' => $itemtypes[$key], 'catid' => $category['cid'])));
            $haslinkitems = $showlinkitems && isset($catcount[$category['cid']]) && ($catcount[$category['cid']] !== 0);
            if ($haslinkitems) {
                // Determine the default sort to use
                if (!empty($itemtypes[$key])) {
                    $settings = unserialize(xarModGetVar('articles', 'settings.'.$itemtypes[$key]));
                } else {
                    $string = xarModGetVar('articles', 'settings');
                    if (!empty($string)) {
                        $settings = unserialize($string);
                    } else {
                        $settings = array();
                    }
                }
                if (empty($settings['defaultsort'])) {
                    $defaultsort = 'date';
                } else {
                    $defaultsort = $settings['defaultsort'];
                }
                

                $articles = xarModAPIFunc('articles', 'user', 'getall',
                    array('cids'=>array($category['cid']), 'ptid'=>$itemtypes[$key], 'fields'=>array('aid','title','summary', 'cid'), 'status' => array(2,3), 'numitems' => 25, 'sort' => $defaultsort,));
                $indent = $category['indentation'] - $startindent + 1;
                $cid = $category['cid'];
                $aid = xarVarGetCached('Blocks.articles','aid');
                foreach ($articles as $article) {
                    $linkargs = $itemtypes[$key] !== NULL && $itemtypes[$key] != 0 ?
                                    array('catid' => $cid, 'aid'=> $article['aid'], 'ptid'=>$itemtypes[$key])
                                  : array('catid' => $cid, 'aid'=> $article['aid']);
                    $class = $aid == $article['aid'] ? $currentcssclass : '';
                    $items[] = array('islink' => TRUE,
                                    'cid' => $cid,
                                    'aid' => $article['aid'],
                                    'name' => xarVarPrepForDisplay($article['title']),
                                    'image' => '',
                                    'description' => !empty($article['summary']) ? xarVarPrepForDisplay($article['summary']) : '',
                                    'beforetags' => '',
                                    'aftertags' => '',
                                    'class' => $class,
                                    'indentation' => $indent,
                                    'link' => xarModURL('articles', 'user','display', $linkargs));
                }
                unset($articles);
            }
        }
        unset($categories);
    }
    unset($cids);

    $oldindent = 0;
    $c = count($items);
    for ($k = 0; $k !== $c; $k++) {
        $item = &$items[$k];
        if ($item['indentation'] > $oldindent) {
            for ($i = $oldindent; $i !== $item['indentation']; $i++) {
                $item['beforetags'] .= '<ul>';
            }
        } elseif ($k !== $c-1 && $item['indentation'] < $oldindent) {
            for ($i = $item['indentation']; $i !== $oldindent; $i++) {
                $items[$k-1]['aftertags'] .= '</li></ul>';
            }
        } elseif ($k === $c-1) {
            for ($i = 0; $i !== $oldindent; $i++) {
                $items[$item['indentation'] !== 0 ? $k : $k-1]['aftertags'] .= '</li></ul>';
            }
        }

        // Identify current selected items if necessary
        if (!empty($currentcssclass) && in_array($item['cid'], $currentcids) && !$item['islink']) {
            $item['beforetags'] .= '<li class="' . $currentcssclass . '">';
            $item['class'] = $currentcssclass;
        } else {
            $item['beforetags'] .= '<li>';
        }
        if ($item['indentation'] <= $oldindent && $k !== 0) {
            $items[$k-1]['aftertags'] .= '</li>';
        }
        $oldindent = $item['indentation'];
    }
    if ($k !== 0) {
        $items[0]['beforetags'] = '<ul id="' . $mainitemid . '"' . (!empty($maincssclass) ? ' class="'. $maincssclass . '"': '') . '>' . $items[0]['beforetags'];
        $items[$k-1]['aftertags'] .= '</li></ul>';
    }

    return $items;
}

?>