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
 */
/**
 * delete all category links for a module - hook for ('module','remove','API')
 * // TODO: remove per itemtype ?
 *
 * @param $args['objectid'] ID of the object (must be the module name here !!)
 * @param $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function categories_adminapi_removehook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, we should get the real module name from objectid
    // here, because the current module is probably going to be 'modules' !!!
    if (!isset($objectid) || !is_string($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID (= module name)', 'admin', 'removehook', 'categories');
        throw new BadParameterException(null, $msg);
    }

    $modid = xarModGetIDFromName($objectid);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module ID', 'admin', 'removehook', 'categories');
        throw new EmptyParameterException(null, $msg);
    }

    if(!xarSecurityCheck('DeleteCategoryLink',1,'Link',"$modid:All:All:All")) {
        $msg = xarML('You have no permission to delete #(1) item for module id #(2), module type #(3)',
                'Category Link', xarVarPrepForDisplay($modid), 'All');
        throw new ForbiddenOperation("$modid:All:All:All", $msg);
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    // Delete the link
    $sql = "DELETE FROM $categorieslinkagetable
            WHERE xar_modid = ?";
    $dbconn->Execute($sql, array($modid));

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)','admin', 'removehook', 'categories');
        throw new DataNotFoundException(array($sql, $modid), $msg);
    }

    // Return the extra info
    return $extrainfo;
}


?>
