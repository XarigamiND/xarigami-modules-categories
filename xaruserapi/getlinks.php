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
 * get links for one or more categories
 * @param $args['cids'] array of ids of categories to get linkage for (OR/AND)
 * @param $args['iids'] array of ids of items to get linkage for
 * @param $args['modid'] module's ID
 * @param $args['itemtype'] item type (if any)
 * @param $args['numitems'] optional number of items to return
 * @param $args['startnum'] optional start at this number (1-based)
 * @param $args['sort'] optional sort by itemid (default) or numlinks
 * @param $args['reverse'] if set to 1 the return will have as keys the 'iids'
 *                         else the keys are the 'cids'
 * @param $args['andcids'] true means AND-ing categories listed in cids
 * @param $args['groupcids'] the number of categories you want items grouped by
 * @return array item array, or false on failure
 */
function categories_userapi_getlinks($args)
{
    // Get arguments from argument array
    extract($args);

    if (empty($reverse)) {
        $reverse = 0;
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
    $categoriesdef = xarModAPIFunc('categories', 'user', 'leftjoin', $args);

    // Get item IDs
    $sql = 'SELECT ' . $categoriesdef['cid'] . ', ' . $categoriesdef['iid'];
    $sql .= ' FROM ' . $categoriesdef['table'];
    $sql .= $categoriesdef['more'];
    if (!empty($categoriesdef['where'])) {
        $sql .= ' WHERE ' . $categoriesdef['where'];
    }

    if (!empty($sort)) {
        if ($sort == 'itemid') {
            $sql .= " ORDER BY " . $categoriesdef['iid'] . " ASC";
        } else {
            // no way to sort by number of links in the query itself
        }
    }

    if (!empty($numitems)) {
        if (empty($startnum)) {
            $startnum = 1;
        }
        $result = $dbconn->SelectLimit($sql, $numitems, $startnum - 1);
    } else {
        $result = $dbconn->Execute($sql);
    }
    if (!$result) return;

    // Makes the linkages array to be returned
    $answer = array();

    for(; !$result->EOF; $result->MoveNext())
    {
        $fields = $result->fields;
        $iid = array_pop($fields);
        if ($reverse == 1) {
            // the list of categories is in the N first fields here
            if (isset($cids) && count($cids) > 1 && $andcids) {
                $answer[$iid] = $fields;
            } elseif (isset($groupcids) && $groupcids > 1) {
                $answer[$iid] = $fields;
            // we get 1 category per record here
            } else {
                $answer[$iid][] = $fields[0];
            }
        } else {
// TODO: use multi-level array for multi-category grouping ?
            $cid = join('+',$fields);
            $answer[$cid][] = $iid;
        }
    }

    $result->Close();

    if (!empty($sort) && $sort == 'numlinks' && count($answer) > 0) {
    // TODO: find some way to sort first on count, and then on itemid
        uasort($answer, 'categories_userapi_getlinks_sortbycount');
    }

    // Return Array with linkage
    return $answer;
}

function categories_userapi_getlinks_sortbycount($a, $b)
{
    $ca = count($a);
    $cb = count($b);
    if ($ca == $cb) return 0;
    return ($ca > $cb ? 1 : -1);
}

?>
