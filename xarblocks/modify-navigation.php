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
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 * @author Xarigami Team
 */
/**
 * modify block settings
 */
function categories_navigationblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['layout']))          $vars['layout'] = 1;
    if (empty($vars['showcatcount']))    $vars['showcatcount'] = 0;
    if (empty($vars['showchildren']))    $vars['showchildren'] = 0;
    if (empty($vars['menutype']))        $vars['menutype'] = 0;
    if (empty($vars['startmodule']))     $vars['startmodule'] = '';
    if (empty($vars['showempty']))       $vars['showempty'] = 0;
    if (empty($vars['dynamictitle']))    $vars['dynamictitle'] = 0;
    if (empty($vars['multiselect']))     $vars['multiselect'] = 0;
    if (empty($vars['highlightparent'])) $vars['highlightparent'] = 0;

    $vars['catcounts'] = array(array('id' => 0, 'name' => xarML('None')),
                               array('id' => 1, 'name' => xarML('Simple count')),
                               array('id' => 2, 'name' => xarML('Cascading count')));

    $vars['layouts'] = array(array('id' => 1, 'name' => xarML('Tree (Side Block)')),
                             array('id' => 2, 'name' => xarML('Crumbtrail (Top Block)')),
                             array('id' => 3, 'name' => xarML('Prev/Next (Bottom Block)')),
                             array('id' => 4, 'name' => xarML('Menu (Top or Side Block)')));

    $vars['children'] = array(array('id' => 0, 'name' => xarML('None')),
                              array('id' => 1, 'name' => xarML('Direct children only')),
                              array('id' => 2, 'name' => xarML('All children')));

    $vars['menutypes'] = array(array('id' => 0, 'name' => xarML('Horizontal menu')),
                               array('id' => 1, 'name' => xarML('Vertical menu')),
                               array('id' => 2, 'name' => xarML('Horizontal nav-bar')));

    $vars['multiselects'] =  array(array('id' => 0, 'name' => xarML('Single selection only, ignores any multi-selections (all unselected)')),
                                   array('id' => 1, 'name' => xarML('Single selection, use the first element of the multi-selection')),
                                   array('id' => 2, 'name' => xarML('Single selection, find the nearest common parent of the multi-selection')),
                                   // room for more modes.
                                   array('id' => 9, 'name' => xarML('Multi-selection allowed')));

    $vars['highlightparents'] = array(array('id' => 0, 'name' => xarML('Do not highlight any parents')),
                                      array('id' => 1, 'name' => xarML('Highlight the nearest parent')),
                                      array('id' => 2, 'name' => xarML('Highlight every parents')));

    $vars['modules'] = array();
    $vars['modules'][] = array('id' => '', 'name' => xarML('Adapt dynamically to current page'));

    // List contains:
    // 0. option group for the module
    // 1. module [base1|base2]
    // 2.    module [base1]    (for itemtype 0)
    //       module [base2]
    // 3.    module:itemtype [base3|base4]
    // 4.       itemtype [base3]
    //          itemtype [base4]

    $allcatbases = xarModAPIfunc('categories', 'user', 'getallcatbases',
        array('order'=>'module', 'format'=>'tree'));

    $vars['modules'][] = array('id' => '.0.0', 'name' => xarML('All modules'));
    foreach($allcatbases as $modulecatbases) {
        // Module label for the option group in the list.
        $modlabel = xarML('#(1)', ucwords($modulecatbases['module']));

        $vars['modules'][] = array(
            'label' => $modlabel, 'id' => $modulecatbases['module'] . '.0.0', 'name' => $modlabel . ' [ ' . xarML('All item types') . ' ]'
        );

        $indent = '&nbsp;&nbsp;&nbsp;';

        foreach($modulecatbases['itemtypes'] as $thisitemtype => $itemtypecatbase) {
            if (!empty($itemtypecatbase['catbases'])) {
                $catlist = '[';
                $join = '';
                foreach($itemtypecatbase['catbases'] as $itemtypecatbases) {
                    $catlist .= $join . $itemtypecatbases['category']['name'];
                    $join = ' | ';
                }
                $catlist .= ']';

                //if (empty($itemtypecatbase['itemtype']['label'])) {
                if ($thisitemtype == 0) {
                    // Default module cats at top level.
                    $indent_level = 0;
                    $itemtypelabel = '';
                } else {
                    // Item types at one level deeper
                    $indent_level = 1;
                    $itemtypelabel = ' -&gt; ' . xarML('#(1)', $itemtypecatbase['itemtype']['label']);
                }

                // Module-Itemtype [all cats]
                $vars['modules'][] = array(
                    'id' => $modulecatbases['module'] . '.' . $thisitemtype . '.0',
                    'name' => str_repeat($indent, $indent_level) . $modlabel . $itemtypelabel . ' ' . $catlist
                );

                // Individual categories a level deeper.
                $indent_level += 1;

                // Individual base categories where there are more than one.
                if (count($itemtypecatbase['catbases']) > 1) {
                    foreach($itemtypecatbase['catbases'] as $itemtypecatbases) {
                        $catlist = '[' . $itemtypecatbases['category']['name'] . ']';
                        if ($thisitemtype == 0) {$itemtypelabel = $modlabel;}
                        $vars['modules'][] = array(
                            'id' => $modulecatbases['module'] . '.' . $thisitemtype . '.' . $itemtypecatbases['category']['cid'],
                            'name' => str_repeat($indent, $indent_level) . $itemtypelabel . ' ' . $catlist
                        );
                    }
                }
            }
        }
    }

    $vars['blockid'] = $blockinfo['bid'];
    // Return output
    return xarTplBlock('categories', 'nav-admin', $vars);
}

/**
 * update block settings
 */
function categories_navigationblock_update($blockinfo)
{
    $vars = array();
    if (!xarVarFetch('layout',          'isset',    $vars['layout'],          NULL,   XARVAR_DONT_SET)) return;
    if (!xarVarFetch('menutype',        'isset',    $vars['menutype'],        NULL,   XARVAR_DONT_SET)) return;
    if (!xarVarFetch('multiselect',     'isset',    $vars['multiselect'],     NULL,   XARVAR_DONT_SET)) return;
    if (!xarVarFetch('showlinkitems',   'checkbox', $vars['showlinkitems'],   FALSE,  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('highlightparent', 'isset',    $vars['highlightparent'], NULL,   XARVAR_DONT_SET)) return;
    if (!xarVarFetch('showcatcount',    'isset',    $vars['showcatcount'],    NULL,   XARVAR_DONT_SET)) return;
    if (!xarVarFetch('showchildren',    'isset',    $vars['showchildren'],    NULL,   XARVAR_DONT_SET)) return;
    if (!xarVarFetch('showempty',       'checkbox', $vars['showempty'],       FALSE,  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('startmodule',     'isset',    $vars['startmodule'],     NULL,   XARVAR_DONT_SET)) return;
    if (!xarVarFetch('dynamictitle',    'checkbox', $vars['dynamictitle'],    FALSE,  XARVAR_DONT_SET)) return;
    
    $blockinfo['content'] = $vars;

    return $blockinfo;
}

/**
 * built-in block help/information system.
 */
function categories_navigationblock_help()
{
    return '';
}

?>
