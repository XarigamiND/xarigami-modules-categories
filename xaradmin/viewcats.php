<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 */
/**
 * View the categories in the system
 *
 * @param pagerstart
 * @param catsperpage
 * @param useJSdisplay
 */
function categories_admin_viewcats()
{
    // Get parameters
    if(!xarVarFetch('pagerstart',   'isset', $pagerstart,    NULL, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('catsperpage',  'isset', $catsperpage,   NULL, XARVAR_DONT_SET)) return;
    // Security check
    if(!xarSecurityCheck('EditCategories')) {
        $msg = xarML('You have no permission to edit categories');
        return xarResponseForbidden($msg);
    }

    $data = array();
    //common admin menu
    $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');

    $data['reloadlabel'] = xarML('Reload');

    // Add pager
    if (empty($pagerstart)) {
        $data['pagerstart'] = 1;
    } else {
        $data['pagerstart'] = intval($pagerstart);
    }

    if (empty($catsperpage)) {
        $data['catsperpage'] = xarModGetVar('categories', 'catsperpage');
    } else {
        $data['catsperpage'] = intval($catsperpage);
    }

    $data['pagertotal'] = xarModAPIFunc('categories', 'user', 'countcats', array());

    $categories = xarModAPIFunc('categories', 'user', 'getcat',
                                array('start' => $data['pagerstart'],
                                      'count' => $data['catsperpage'],
                                      'cid' => false,
                                      'getchildren' => true));

    if (empty($categories)) {
        return xarTplModule('categories','admin','viewcats-nocats',$data);
    }


    //$useJSdisplay = $data['useJSdisplay'] = xarModGetVar('categories', 'useJSdisplay');
    $data['allowdragdrop'] = xarModGetVar('categories', 'allowdragdrop');

        xarModLoad('categories', 'renderer');

        foreach ($categories as $category) {
            $category['xar_pid'] = $category['parent'];
            $category['xar_cid'] = $category['cid'];
            /*
            // Note : extending category information with other fields is possible via DD,
            // so getcatinfo() should be able to retrieve that for you in the future
                // there are no 'category' 'display' hooks in use at the moment, and if they
                // were, they should probably be used when individual categories are displayed
                $category['hooks'] = xarModCallHooks('category',
                                                     'display',
                                                     $category['cid'],
                                                     array('returnurl' => xarModURL('categories',
                                                                                    'admin',
                                                                                    'viewcats',
                                                                                    array())));
                if (isset($category['hooks']) && is_array($category['hooks'])) {
                    $category['hooks'] = join('',$category['hooks']);
                }
            */
            $cats[] = $category;
        }
        $categories = $cats;

        categories_renderer_array_markdepths_bypid($categories);
        $categories = categories_renderer_array_maptree($categories);

        $data['categories'] = $categories;

        return xarTplModule('categories', 'admin', 'viewcats-render', $data);

}

?>
