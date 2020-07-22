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
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 */
/**
 * creates a category
 *
 *  -- INPUT --
 * @param $args['name'] the name of the category
 * @param $args['description'] the description of the category
 * @param $args['image'] the (optional) image for the category
 *
 * @param $args['catexists'] = 0 means there were no categories during insertion
 *
 * If catexists == 0 then these do not to be set:
 *
 *    @param $args['refcid'] the ID of the reference category
 *
 *    These two parameters are set in relationship with the reference category:
 *
 *       @param $args['inorout'] Where the new category should be: IN or OUT
 *       @param $args['rightorleft'] Where the new category should be: RIGHT or LEFT
 *
 *  -- OUTPUT --
 * @returns int
 * @return category ID on success, raise an exception on failure
 */
function categories_adminapi_createcat($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if ((!isset($name)) || (!isset($description))) {
        $msg = xarML('Invalid parameter count');
        throw new BadParameterException(null, $msg);
    }

    if (!isset($image)) {
        $image = '';
    }

    if (isset($catexists) && ($catexists != 0)) {
        if (!isset($refcid) || !isset($rightorleft) || !isset($inorout)) {
            $msg = xarML('Invalid parameter count');
            throw new BadParameterException($args, $msg);
        }
    }

    // Security check
    // Has to be redone later
    if (!xarSecurityCheck('AddCategories')) {
            // we should not arrive here from xardamin/updatecat.php
            $msg = xarML('You have no permission to add categories');
            throw new ForbiddenOperationException(null, $msg);
    }

    if (isset($catexists) && ($catexists == 0)) {

       $n = xarModAPIFunc('categories', 'user', 'countcats', Array());

       if ($n == 0) {
               // Editing database doesn't need to have a great performance
            // So the 2 extras updates are OK...
            return xarModAPIFunc('categories','admin','createcatdirectly',
                Array
                (
                    'point_of_insertion' => 1,
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                    'parent' => 0
                )
            );
       } else {
            $msg = xarML('That category already exists');
            throw new DuplicateException($args, $msg);
       }
    } else {

        // Obtain current information on the reference category
        $cat = xarModAPIFunc('categories', 'user', 'getcatinfo', Array('cid'=>$refcid));

        if ($cat == false) {
            $msg = xarML('Unable to find #(1) ID #(2)', 'category', xarVarPrepForDisplay($refcid));
            throw new IDNotFoundException(null, $msg);
        }

        $right = $cat['right'];
        $left = $cat['left'];

        /* Find out where you should put the new category in */
        $point_of_insertion = xarModAPIFunc('categories', 'admin', 'find_point_of_insertion',
                   Array('inorout' => $inorout, 'rightorleft' => $rightorleft,
                         'right' => $right, 'left' => $left));
        if (!$point_of_insertion) {
            $msg = xarML('Category point of insertion could not be found');
            throw new DataNotFoundException($args, $msg);
        }

        /* Find the right parent for this category */
        if (strtolower($inorout) == 'in') {
            $parent_id = $refcid;
        } else {
            $parent_id = $cat['parent'];
        }

        return xarModAPIFunc('categories', 'admin', 'createcatdirectly',
            Array (
                'point_of_insertion' => $point_of_insertion,
                'name' => $name,
                'description' => $description,
                'image' => $image,
                'parent' => $parent_id
            )
        );
    }
}

?>
