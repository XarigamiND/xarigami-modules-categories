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
 * test function for DMOZ-style short URLs in xaruser.php
 */

function categories_userapi_name2cid($args)
{
    extract($args);
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $categoriestable = $xartable['categories'];

    if (empty($name) || !is_string($name)) {
        $name = 'Top';
    }
    // for DMOZ-like URLs where the description contains the full path
    if (!empty($usedescr)) {
        $query = "SELECT xar_parent, xar_cid FROM $categoriestable WHERE xar_description = ?";
    } else {
        $query = "SELECT xar_parent, xar_cid FROM $categoriestable WHERE xar_name = ?";
    }
    $result = $dbconn->Execute($query, array($name));
    if (!$result) throw new DataNotFoundException(array($query, $name));
    list($parent, $cid) = $result->fields;
    $result->Close();

    return $cid;
}

?>
