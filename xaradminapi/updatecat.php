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
 * update a category
 *
 *  -- INPUT --
 * @param $args['cid'] the ID of the category
 * @param $args['name'] the modified name of the category
 * @param $args['description'] the modified description of the category
 * @param $args['image'] the (optional) modified image for the category
 * @param $args['moving'] = 1 means the category can move around
 *
 * If $args['moving'] != 1 then these shouldn?t be set:
 *
 *    @param $args['refcid'] the ID of the reference category
 *
 *    These two parameters are set in relationship with the reference category:
 *
 *       @param $args['inorout'] Where the new category should be: IN or OUT
 *       @param $args['rightorleft'] Where the new category should be: RIGHT or LEFT
 *
 *  -- OUTPUT --
 * @return bool true on success, exceptions on failure

 */
function categories_adminapi_updatecat($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($cid) || !isset($name) || !isset($description) ||
        ($moving == 1) && (!isset($inorout) || !isset($rightorleft) || !isset($refcid))) {
        $msg = xarML('Bad Parameters for function #(1)', 'categories_adminapi_updatecat');
        throw new EmptyParameterException($args, $smg);
    }

    if (!isset($image)) {
        $image = '';
    }

    // Obtain current information on the category
    if(!xarModAPILoad('categories', 'user')) throw new FunctionNotFoundException();
    $cat = xarModAPIFunc('categories', 'user', 'getcatinfo', Array('cid'=>$cid));
    if ($cat == false) {
        $msg = xarML('Unable to find #(1) ID #(2)', 'category', xarVarPrepForDisplay($cid));
        throw new IDNotFoundException(null, $msg);
    }

    // Get datbase setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $categoriestable = $xartable['categories'];

    // Get inside if the category is supposed to move
    if ($moving == 1) {
        // Obtain current information on the reference category
        $refcat = xarModAPIFunc('categories', 'user', 'getcatinfo', Array('cid'=>$refcid));

        if ($refcat == false) {
           $msg = xarML('Unable to find #(1) ID #(2)', 'category', xarVarPrepForDisplay($refcid));
           throw new IDNotFoundException(null, $msg);
        }

        // Checking if the reference ID is of a child or itself
        if ( ($refcat['left'] >= $cat['left'])  &&
             ($refcat['left'] <= $cat['right'])) {
            $msg = xarML('Category references siblings.');
            throw new DataNotFoundException(null, $msg);
        }

       // Security check
        if(!xarSecurityCheck('EditCategories',1,'Category',"All:$cid")) {
            $msg = xarML('You have no permission to edit #(1) item #(2)', 'Category', xarVarPrepForDisplay($cid));
            // we should not arrive here from xaradmin/updatecat.php
            // throw an exception then
            throw new ForbiddenOperationException(null, $msg);
        }

       // Find the needed variables for moving things...
       $point_of_insertion =
                   xarModAPIFunc('categories','admin','find_point_of_insertion',
                       Array('inorout' => $inorout,
                               'rightorleft' => $rightorleft,
                               'right' => $refcat['right'],
                               'left' => $refcat['left']
                       )
                   );
       $size = $cat['right'] - $cat['left'] + 1;
       $distance = $point_of_insertion - $cat['left'];

       // If necessary to move then evaluate
       if ($distance != 0) { // It's Moving, baby!  Do the Evolution!
            if ($distance > 0) {
                // moving forward
                $distance = $point_of_insertion - $cat['right'] - 1;
                $deslocation_outside = -$size;
                $between_string = ($cat['right'] + 1)." AND ".($point_of_insertion - 1);
            }  else {
                // $distance < 0 (moving backward)
                $deslocation_outside = $size;
                $between_string = $point_of_insertion." AND ".($cat['left'] - 1);
            }

            // TODO: besided portability, also check performance here
            $SQLquery = "UPDATE $categoriestable SET
                    xar_left = CASE
                        WHEN xar_left BETWEEN ".$cat['left']." AND ".$cat['right']."
                           THEN xar_left + ($distance)
                        WHEN xar_left BETWEEN $between_string
                           THEN xar_left + ($deslocation_outside)
                        ELSE xar_left
                        END,
                    xar_right = CASE
                        WHEN xar_right BETWEEN ".$cat['left']." AND ".$cat['right']."
                           THEN xar_right + ($distance)
                        WHEN xar_right BETWEEN $between_string
                           THEN xar_right + ($deslocation_outside)
                        ELSE xar_right
                        END
                     ";
                     // This seems SQL-92 standard... Its a good test to see if
                     // the databases we are supporting are complying with it. This can be
                     // broken down in 3 simple UPDATES which shouldnt be a problem with any database

            $result = $dbconn->Execute($SQLquery);
            if (!$result) throw new DataNotFoundException($SQLquery);

            /* Find the right parent for this category */
            if (strtolower($inorout) == 'in') {
                $parent_id = $refcid;
            } else {
                $parent_id = $refcat['parent'];
            }
            // Update parent id
            $SQLquery = "UPDATE $categoriestable
                       SET xar_parent = ?
                       WHERE xar_cid = ?";
            $result = $dbconn->Execute($SQLquery,array($parent_id, $cid));
            if (!$result) throw new DataNotFoundException(array($SQLquery, $parent_id, $cid));

        } // else (distace == 0) not necessary to move
    }
    else {// (moving != 1)
        if (isset($inorout) || isset($rightorleft) || isset($refcid)) {
            // Show them that moving is not set, or else they wont know why it
            // is not working
/* no worries - cfr. bug 3809
            $msg = xarML('Bad Parameters for function #(1), moving not set, yet parameters for moving present', 'categories_adminapi_updatecat');
            throw new BadParameterException(null, $msg);
*/
        }
    }

    // Update name and description
    $SQLquery = "UPDATE $categoriestable
                 SET xar_name = ?, xar_description = ?, xar_image = ?
                 WHERE xar_cid = ?";
    $bindvars = array($name, $description, $image,$cid);
    $result = $dbconn->Execute($SQLquery,$bindvars);
    if (!$result) throw new DataNotFoundException(array($SQLquery, $bindvars));

    // Call update hooks
    $args['module'] = 'categories';
    $args['itemtype'] = 0;
    $args['itemid'] = $cid;
    xarModCallHooks('item', 'update', $cid, $args);

    return true;
}

?>
