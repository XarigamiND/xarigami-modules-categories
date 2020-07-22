<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Categories module development team
 * @throws none
 * @return array containing the menulinks for the main menu items.
 */
function categories_adminapi_getmenulinks()
{
    $menulinks = array();
// Security Check

    if (xarSecurityCheck('EditCategories', 0)) {
        $menulinks[] = Array('url'   => xarModURL('categories', 'admin', 'viewcats'),
                              'title' => xarML('View and Edit Categories'),
                              'label' => xarML('View Categories'),
                              'active'=>array('viewcats'),
                              );
    }

    if (xarSecurityCheck('AddCategories', 0)) {
        $menulinks[] = Array('url'   => xarModURL('categories', 'admin', 'modifycat'),
                              'title' => xarML('Add a new Category into the system'),
                              'label' => xarML('Add Category'),
                              'active'=>array('modifycat')
                              );
    }

    if (xarSecurityCheck('AdminCategories', 0)) {
        $menulinks[] = Array('url'   => xarModURL('categories', 'admin', 'stats'),
                              'title' => xarML('View category statistics per module'),
                              'label' => xarML('View Statistics'),
                              'active'=>array('stats')
                              );
        $menulinks[] = Array('url'   => xarModURL('categories', 'admin', 'hooks'),
                              'title' => xarML('Set category hooks'),
                              'label' => xarML('Configure hooks'),
                              'active'=>array('hooks'));

        $menulinks[] = Array('url'   => xarModURL('categories', 'admin', 'checklinks'),
                              'title' => xarML('Check for orphaned category assignments'),
                              'label' => xarML('Check Links'),
                              'active'=>array('checklinks'));

        $menulinks[] = Array('url'   => xarModURL('categories', 'admin', 'modifyconfig'),
                              'title' => xarML('Config the Categories module'),
                              'label' => xarML('Modify Config'),
                              'active'=>array('modifyconfig'));
    }

    return $menulinks;
}


?>
