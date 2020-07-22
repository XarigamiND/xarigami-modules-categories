<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2007-2013 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 */

$modversion['name']         = 'categories';
$modversion['directory']    = 'categories';
$modversion['id']           = '147';
$modversion['version']      = '2.5.5';
$modversion['displayname']  = 'Categories';
$modversion['description']  = 'Categorised data utility';
$modversion['credits']      = 'xardocs/credits.txt';
$modversion['help']         = 'xardocs/help.txt';
$modversion['changelog']    = 'xardocs/changelog.txt';
$modversion['license']      = 'xardocs/license.txt';
$modversion['official']     = 1;
$modversion['author']       = 'Original author Jim McDonald';
$modversion['contact']      = 'http://xarigami.comm/';
$modversion['homepage']     = 'http://xarigami.com/project/xarigami_categories/';
$modversion['admin']        = 1;
$modversion['user']         = 0;
$modversion['class']        = 'Utility';
$modversion['category']     = 'Content';
$modversion['dependencyinfo']   = array(
                                    0 => array(
                                            'name' => 'core',
                                            'version_ge' => '1.5.3' //for jquery 1.9.1 and treeTable 3.0
                                         )
                                );
if (false) {
    xarML('Categories');
    xarMl('Categorised data utility');
}
?>
