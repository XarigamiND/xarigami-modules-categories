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
 * Checks whether one or more cid is a descendant of one or more category
 * tree branches. Returns true if any cid is a descendant of any branch.
 * Common use: within a template to determine if the visitor is browsing
 * within a region of the website - the 'region' being defined by one or
 * more branches.
 *
 * @author Jason Judge <judgej@xaraya.com>
 * @param $args['cid'] id of category to test; or
 * @param $args['cids'] array of category ids to test; defaults to query parameter 'catid'
 * @param $args['branch'] id of the category branch; or
 * @param $args['branches'] id of the category branches
 * @param $args['include_root'] flag to indicate whether a branch root is included in the check [false]
 * @return bool true if one or more cids is a descendant of one or more of the branch roots
 */
function categories_userapi_isdescendant($args)
{
    extract($args);

    // TODO: proper error handling.
    if (empty($cid) && empty($cids)) {
        // TODO: try the query parameter 'catid'
        $msg = xarML('Bad arguments for API function');
        throw new EmptyParameterException($args, $msg);
    }

    if (empty($cids)) {$cids = array($cid);}
    if (empty($branches)) {$branches = array($branch);}

    // If there is just one cid, then it may have a prefix to be stripped.
    if (count($cids) == 1) {$cids[0] = str_replace('_', '', $cids[0]);}

    $cids = array_filter($cids, 'is_numeric');
    $branches = array_filter($branches, 'is_numeric');

    if (empty($cids) || empty($branches)) return false;

    if (empty($include_root)) {$include_root = false;}

    // Simple check first (not involving the database).
    if ($include_root && array_intersect($cids, $branches)) {
        // One or more of the cids is equal to one or more of the branch roots.
        return true;
    }

    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    $categoriestable = $xartable['categories'];

    $query = '
        SELECT  P1.xar_cid
        FROM    '.$categoriestable.' AS P1,
                '.$categoriestable.' AS P2
        WHERE   P2.xar_left >= P1.xar_left
        AND     P2.xar_left <= P1.xar_right
        AND     P2.xar_cid in(' . implode(',', $cids) . ')
        AND     P1.xar_cid in(' . implode(',', $branches) . ')
        AND     P1.xar_cid not in(' . implode(',', $cids) . ')';

    $result = $dbconn->SelectLimit($query, 1);
    if (!$result) throw new DataNotFoundException($query);

    if (!$result->EOF) {
        return true;
    } else {
        return false;
    }
}

?>