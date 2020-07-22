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
 * get parents of a specific (list of) category
 *
 * @param id $args['cid'] id of category to get parent for, or
 * @param array $args['cids'] array of category ids to get parent for
 * @param bool $args['return_itself'] return the cid itself (default true)
 * @return array of category info arrays, false on failure
 */
function categories_userapi_getparents($args)
{
    $return_itself = true;
    extract($args);

    if (!isset($cid) && !isset($cids)) {
       $msg = xarML('Bad arguments for API function');
       throw new EmptyParameterException($args, $msg);
    }
    $info = array();

    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    $categoriestable = $xartable['categories'];
    
    if (isset($cids) && is_array($cids) && count($cids) == 1) {
        $cid = $cids[0];
        unset($cids);
    }
    
    if (isset($cid) && !isset($cids)) {
        if (empty($cid) || !is_numeric($cid)) return $info; // Should we not raise an exception here instead? TODO: check all the calls.
        
        // TODO : evaluate alternative with 2 queries
        $SQLquery = "SELECT
                            P1.xar_cid,
                            P1.xar_name,
                            P1.xar_description,
                            P1.xar_image,
                            P1.xar_parent,
                            P1.xar_left,
                            P1.xar_right
                       FROM $categoriestable AS P1,
                            $categoriestable AS P2
                      WHERE P2.xar_cid = ? 
                        AND P2.xar_left >= P1.xar_left
                        AND P2.xar_left <= P1.xar_right";
        $SQLquery .= " ORDER BY P1.xar_left";

        $result = $dbconn->Execute($SQLquery,array($cid));
        if (!$result) return;
    
        while (!$result->EOF) {
            list($pid, $name, $description, $image, $parent, $left, $right) = $result->fields;
            if (!xarSecurityCheck('ViewCategories',0,'Category',"$name:$pid")) {
                 $result->MoveNext();
                 continue;
            }
    
            if ($return_itself || $cid != $pid) {
                $info[$pid] = Array(
                                    "cid"         => $pid,
                                    "name"        => $name,
                                    "description" => $description,
                                    "image"       => $image,
                                    "parent"      => $parent,
                                    "left"        => $left,
                                    "right"       => $right
                                    );
            }
            $result->MoveNext();
        }
    } else if (isset($cids) && is_array($cids)) {
        // TODO : evaluate alternative with 2 queries
        $SQLinCids = ' IN (' . implode(',', $cids) . ') ';
        $SQLquery = " SELECT
                            xar_cid,
                            xar_name,
                            xar_description,
                            xar_image,
                            xar_parent,
                            xar_left,
                            xar_right
                       FROM $categoriestable
                       WHERE xar_left <= (
                            SELECT MIN(xar_left)
                            FROM $categoriestable 
                            WHERE xar_cid $SQLinCids
                       )
                       AND xar_right >= (
                            SELECT MAX(xar_right)
                            FROM $categoriestable 
                            WHERE xar_cid $SQLinCids
                       ) 
                       ORDER BY xar_left";

        $result = $dbconn->Execute($SQLquery);
        if (!$result) return;
    
        while (!$result->EOF) {
            list($pid, $name, $description, $image, $parent, $left, $right) = $result->fields;
            if (!xarSecurityCheck('ViewCategories',0,'Category',"$name:$pid")) {
                 $result->MoveNext();
                 continue;
            }
            if (!in_array($pid, $cids) || $return_itself) {
                $info[$pid] = Array(
                                    "cid"         => $pid,
                                    "name"        => $name,
                                    "description" => $description,
                                    "image"       => $image,
                                    "parent"      => $parent,
                                    "left"        => $left,
                                    "right"       => $right
                                    );
            }
            $result->MoveNext();
        }
    
    }
    return $info;
}

?>
