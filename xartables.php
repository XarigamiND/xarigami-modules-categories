<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Categories Module
 * @link http://xaraya.com/index.php/release/147.html
 * @author Categories module development team
 */

/**
 * specifies module tables namees
 *
 * @author  Jim McDonald, Fl?vio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
 * @access  public
 * @param   none
 * @return  $xartable array
 * @throws  no exceptions
 * @todo    nothing
 */
function categories_xartables()
{
    // Initialise table array
    $xartable = array();

    // Name for categories
    $categories =  xarDBGetSiteTablePrefix() . '_categories';

    // CATEGORIES TABLE
    // Table name
    $xartable['categories'] = $categories;

    // Column names
    $xartable['categories_column'] = array('cid'        => $categories . '.xar_cid',
                                          'name'        => $categories . '.xar_name',
                                          'description' => $categories . '.xar_description',
                                          'image'       => $categories . '.xar_image',
                                          'parent'      => $categories . '.xar_parent',
                                          'left'        => $categories . '.xar_left',
                                          'right'       => $categories . '.xar_right');

    // Clean names, necessarry for self-join statements
    $xartable['categories_column_clean'] = array('cid'        => 'xar_cid',
                                                'name'        => 'xar_name',
                                                'description' => 'xar_description',
                                                'image'       => 'xar_image',
                                                'parent'      => 'xar_parent',
                                                'left'        => 'xar_left',
                                                'right'       => 'xar_right');

    // CATEGORIES LINKAGE TABLE
    // Name for linkage
    $categories_linkage =  $categories . '_linkage';

    // Table name
    $xartable['categories_linkage'] = $categories_linkage;

    // Column names
    $xartable['categories_linkage_column'] = array('cid'   => $categories_linkage . '.xar_cid',
                                                   'iid'   => $categories_linkage . '.xar_iid',
                                                   'modid' => $categories_linkage . '.xar_modid');

    // EXTENDED PRIVILEGES TABLE
    // Name for privileges
    $categories_ext_privileges =  $categories . '_ext_privileges';

    // Table name
    $xartable['categories_ext_privileges'] = $categories_ext_privileges;
    
    // Column names
    $xartable['categories_ext_privileges_column'] = 
                                    array('pid'                 => $categories_ext_privileges . '.xar_pid',
                                          'name'                => $categories_ext_privileges . '.xar_name', 
                                          'cid'                 => $categories_ext_privileges . '.xar_cid',
                                          'forced_deny'         => $categories_ext_privileges . '.xar_forced_deny',
                                          'forced_allow'        => $categories_ext_privileges . '.xar_forced_allow',
                                          'forbid_receive'      => $categories_ext_privileges . '.xar_forbid_receive',
                                          'multi_level'         => $categories_ext_privileges . '.xar_multi_level',
                                          'to_children'         => $categories_ext_privileges . '.xar_to_children',
                                          'to_parents'          => $categories_ext_privileges . '.xar_to_parents',
                                          'to_siblings'         => $categories_ext_privileges . '.xar_to_siblings',
                                          'not_itself'          => $categories_ext_privileges . '.xar_not_itself',
                                          'override_others'     => $categories_ext_privileges . '.xar_override_others',
                                          'inheritance_depth'   => $categories_ext_privileges . '.xar_inheritance_depth',
                                          'deactivated'         => $categories_ext_privileges . '.xar_deactivated',    // for future use
                                          'comment'             => $categories_ext_privileges . '.xar_comment'
                                          );
                                          
    // Clean names, necessarry for self-join statements
    $xartable['categories_ext_privileges_column_clean'] = 
                                    array('pid'                 => 'xar_pid',
                                          'name'                => 'xar_name',
                                          'cid'                 => 'xar_cid',
                                          'forced_deny'         => 'xar_forced_deny',
                                          'forced_allow'        => 'xar_forced_allow',
                                          'forbid_receive'      => 'xar_forbid_receive',
                                          'multi_level'         => 'xar_multi_level',
                                          'to_children'         => 'xar_to_children',
                                          'to_parents'          => 'xar_to_parents',
                                          'to_siblings'         => 'xar_to_siblings',
                                          'not_itself'          => 'xar_not_itself',
                                          'override_others'     => 'xar_override_others',
                                          'inheritance_depth'   => 'xar_inheritance_depth',
                                          'deactivated'         => 'xar_deactivated',    // for future use
                                          'comment'             => 'xar_comment'
                                          );
    // Return table information
    return $xartable;
}

?>