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
 * @return string
 */
function categories_userapi_cid2name ($args)
{
    extract($args);
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $categoriestable = $xartable['categories'];

    if (empty($cid) || !is_numeric($cid)) {
        $cid = 1;
    }
    // for DMOZ-like URLs where the description contains the full path
    if (!empty($usedescr)) {
        $query = "SELECT xar_parent, xar_description FROM $categoriestable WHERE xar_cid = ?";
    } else {
        $query = "SELECT xar_parent, xar_name FROM $categoriestable WHERE xar_cid = ?";
    }
    $result = $dbconn->Execute($query, array($cid));
    if (!$result) throw new DataNotFoundException(array($query, $cid));

    list($parent,$name) = $result->fields;
    $result->Close();

    $name = rawurlencode($name);
    $name = preg_replace('/%2F/','/',$name);
    return $name;
}

?>
