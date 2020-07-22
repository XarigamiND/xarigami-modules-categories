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
 * count number of items
 * @param $args['cids'] optional array of cids we're counting for (OR/AND)
 * @param $args['andcids'] true means AND-ing categories listed in cids
 * @param $args['modid'] module?s ID
 * @param $args['itemtype'] item type
 * @return int number of items
 */
function categories_userapi_countitems($args)
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

    // Get the field names and LEFT JOIN ... ON ... parts from categories
    // By passing on the $args, we can let leftjoin() create the WHERE for
    // the categories-specific columns too now
    $categoriesdef = xarModAPIFunc('categories','user','leftjoin',$args);

    if ($dbconn->databaseType == 'sqlite') {
        $sql = 'SELECT COUNT(*)
                FROM (SELECT DISTINCT ' . $categoriesdef['iid'];
    } else {
        $sql = 'SELECT COUNT(DISTINCT ' . $categoriesdef['iid'] . ')';
    }
    $sql .= ' FROM ' . $categoriesdef['table'];
    $sql .= $categoriesdef['more'];
    if (!empty($categoriesdef['where'])) {
        $sql .= ' WHERE ' . $categoriesdef['where'];
    }
    if ($dbconn->databaseType == 'sqlite') {
        $sql .= ')';
    }

    $result = $dbconn->Execute($sql);
    if (!$result) throw new DataNotFoundException($sql);

    $num = $result->fields[0];

    $result->Close();

    return $num;
}

?>
