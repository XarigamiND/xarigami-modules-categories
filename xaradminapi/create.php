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
 * creates a category using the parent model
 *
 *  -- INPUT --
 * @param $args['name'] the name of the category
 * @param $args['description'] the description of the category
 * @param $args['image'] the (optional) image for the category
 * @param $args['parent_id'] Parent Category ID (0 if root)
 *
 *  -- OUTPUT --
 * @return mixed category ID on success, false on failure
 */
function categories_adminapi_create ($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($name) || !isset($description) 
        || !isset($parent_id) || !is_numeric($parent_id)) {
        $msg = xarML('Invalid parameter count');
        throw new BadParameterException($args, $msg);
    }

    if (!isset($image)) {
        $image = '';
    }

    // Security check
    // Has to be redone later

    if (!xarSecurityCheck('AddCategories')) {
        $msg = xarML('You have no permission to add categories');
        return xarResponseForbidden($msg);
    }

    if ($parent_id != 0) {
        $cat = xarModAPIFunc('categories', 'user', 'getcatinfo', Array('cid'=>$parent_id));

        if ($cat == false) {
            $msg = xarML('Unable to find #(1) ID #(2)', 'category', xarVarPrepForDisplay($refcid));
            throw new IDNotFoundException(null, $msg);
        }
//      $point_of_insertion = $cat['left'] + 1;
        $point_of_insertion = $cat['right'];
    } else {
        $dbconn = xarDBGetConn();
        $xartable = xarDBGetTables();
        $categoriestable = $xartable['categories'];
        $query = "SELECT MAX(xar_right) FROM " . $categoriestable;
        $result = $dbconn->Execute($query);
        if (!$result) throw new DataNotFoundException($query);

        if (!$result->EOF) {
            list($max) = $result->fields;
            $point_of_insertion = $max + 1;
        } else {
            $point_of_insertion = 1;
        }
    }
    return xarModAPIFunc('categories','admin','createcatdirectly', Array(
                    'point_of_insertion' => $point_of_insertion,
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                    'parent' => $parent_id
                )
            );
}

?>