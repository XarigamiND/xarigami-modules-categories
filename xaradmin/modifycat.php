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
 * Modify an existing category. If the category doesn't exist, a new one is created.
 *
 * @param bool creating Unknown. Not used in function
 * @param int cid OPTIONAL
 * @param int repeat OPTIONAL
 * @param string return_url OPTIONAL
 * @return mixed
 */
function categories_admin_modifycat()
{
    if (!xarVarFetch('creating', 'bool', $creating, true, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('cid', 'int::', $cid, NULL, XARVAR_DONT_SET)) return;
    if (empty($cid)) {
        if(!xarVarFetch('repeat', 'int:1:', $repeat, 1, XARVAR_NOT_REQUIRED)) return;
    } else {
        $repeat = 1;
    }
   
    xarVarFetch('return_url', 'str:1:100', $return_url, NULL, XARVAR_NOT_REQUIRED);

    $data = array();
    //common admin menu
    $data['menulinks'] = xarModAPIFunc('categories','admin','getmenulinks');
    $data['imageoptions'] = array();
    $data['imageoptions'][] = array('id' => '', 'name' => xarML('-- No image --'));
    $data['return_url'] = $return_url;
    $image_array = xarModAPIFunc('categories', 'visual', 'findimages');
    foreach ($image_array as $image) {
        $data['imageoptions'][] = array('id' => $image, 'name' => $image);
    }

    $data['repeat'] = $repeat;

    if (!empty($cid)) {
        // Editing an existing category

        // Security check
        if (!xarSecurityCheck('EditCategories', 1, 'Category' ,"All:$cid")) {
            $msg = xarML('You have no permission to edit #(1) item #(2)', 'Category', xarVarPrepForDisplay($cid));
            return xarResponseForbidden($msg);
        }

        // Setting up necessary data.
        $data['cid'] = $cid;
        $data['category'] = xarModAPIFunc('categories', 'user', 'getcatinfo', array('cid' => $cid));

        $categories = xarModAPIFunc('categories', 'user', 'getcat',
            array('cid' => false, 'eid' => $cid, 'getchildren' => true)
        );

        $data['func'] = 'modify';

        $catinfo = $data['category'];
        $catinfo['module'] = 'categories';
        $catinfo['itemtype'] = 0;
        $catinfo['itemid'] = $cid;
        $hooks = xarModCallHooks('item', 'modify', $cid, $catinfo);
        if (empty($hooks)) {
            $data['hooks'] = '';
        } else {
            $data['hooks'] = $hooks;
        }
    } else {
        // Adding a new Category

        if (!xarSecurityCheck('AddCategories')) {
            $msg = xarML('You have no permission to add categories');
            return xarResponseForbidden($msg);
        }
        // Setting up necessary data.
        $categories = xarModAPIFunc('categories', 'user', 'getcat',
            array('cid' => false, 'getchildren' => true)
        );

        $catinfo = array();
        $catinfo['module'] = 'categories';
        $catinfo['itemtype'] = 0;
        $catinfo['itemid'] = '';
        $hooks = xarModCallHooks('item', 'new', '', $catinfo);
        if (empty($hooks)) {
            $data['hooks'] = '';
        } else {
            $data['hooks'] = $hooks;
        }

        $data['category'] = Array('left'=>0, 'right'=>0, 'name'=>'', 'description'=>'');
        $data['func'] = 'create';
        $data['cid'] = NULL;
    }

    $category_Stack = array();

    foreach ($categories as $key => $category) {
        $categories[$key]['slash_separated'] = '';

        while ((count($category_Stack) > 0 ) &&
               ($category_Stack[count($category_Stack)-1]['indentation'] >= $category['indentation'])
        ) {
            array_pop($category_Stack);
        }

        foreach ($category_Stack as $stack_cat) {
            $categories[$key]['slash_separated'] .= $stack_cat['name'] . '&nbsp;/&nbsp;';
        }

        array_push($category_Stack, $category);
        $categories[$key]['slash_separated'] .= $category['name'];
    }

    $data['categories'] = $categories;

    // Return output
    return xarTplModule('categories', 'admin', 'editcat', $data);
}

?>