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
 * @return int count
 */
function categories_userapi_countitems_deprecated($args)
{
    // Get arguments from argument array
    extract($args);

    // Optional arguments
    if (!isset($cids)) {
        $cids = array();
    }

    // Security check
    if (!xarSecurityCheck('ViewCategoryLink')) {
        $msg = xarML('You have no permission to view category links');
        throw ForbiddenOperationException(null, $msg);
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    // Check if we have active CIDs
    $bindvars = array();
    if (count($cids) > 0) {
        // We do.  We just need to know how many articles there are in these
        // categories
        // Get number of links with those categories in cids
        // TODO: make sure this is SQL standard
        //$sql = "SELECT DISTINCT COUNT(xar_iid)
        if ($dbconn->databaseType == 'sqlite') {
        $sql = "SELECT COUNT(*)
                FROM (SELECT DISTINCT xar_iid  FROM $categorieslinkagetable "; //unbalanced
        }else{
        $sql = "SELECT COUNT(DISTINCT xar_iid)
                FROM $categorieslinkagetable ";
        }
        if (isset($table) && isset($field) && isset($where)) {
            $sql .= "LEFT JOIN $table ON $field = xar_iid;";
        }
        $sql .= "  WHERE ";

        $allcids = join(', ', $cids);
        $bindmarkers - '?' . str_repeat(',?', count($cids)-1);
        $bindvars = $cids;
        $sql .= "xar_cid IN ($bindmarkers) ";

        if (isset($table) && isset($field) && isset($where)) {
            $sql .= " AND $where ";
        }
        // Balance parentheses
       if ($dbconn->databaseType == 'sqlite') $sql .=')';
        $result = $dbconn->Execute($sql,$bindvars);
        if (!$result) new DataNotFoundException(array($sql, $bindvars));

        $num = $result->fields[0];

        $result->Close();


    } else {
        // Get total number of links
    // TODO: make sure this is SQL standard
        //$sql = "SELECT DISTINCT COUNT(xar_iid)
        $sql = "SELECT COUNT(DISTINCT xar_iid)
                FROM $categorieslinkagetable ";
        if (isset($table) && isset($field) && isset($where)) {
            $sql .= "LEFT JOIN $table
                     ON $field = xar_iid
                     WHERE $where ";
        }

        $result = $dbconn->Execute($sql);
        if (!$result) new DataNotFoundException($sql);

        $num = $result->fields[0];

        $result->Close();
    }

    return $num;
}
    // end of not-so-good idea

?>
