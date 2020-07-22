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
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * @author the Categories module development team
 * @param $params array containing the different elements of the virtual path
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function categories_userapi_decode_shorturl($params)
{
    // Initialise the argument list we will return
    $args = array();

    // Analyse the different parts of the virtual path
    // $params[1] contains the first part after index.php/example

    // In general, you should be strict in encoding URLs, but as liberal
    // as possible in trying to decode them...

    if (empty($params[1])) {
        // nothing specified -> we'll go to the main function
        return array('main', $args);

    } elseif (preg_match('/^(\d+)/',$params[1],$matches)) {
        // something that starts with a number must be for the display function
        // Note : make sure your encoding/decoding is consistent ! :-)
        $catid = $matches[1];
        $args['catid'] = $catid;
        return array('main', $args);

    } elseif (preg_match('/^(\w+)/',$params[1],$matches)) {
        $list = $params;
        array_shift($list);
        $name = join('/',$list);
        $catid = xarModAPIFunc('categories', 'user', 'name2cid',
                               array('name' => $name,
                                     // for DMOZ-like URLs with the description field containing
                                     // the full path, use 1
                                     'usedescr' => 0));
        if (!empty($catid)) {
            $args['catid'] = $catid;
        }
        return array('main', $args);

    } else {
        // the first part might be something variable like a category name
        // In order to match that, you'll have to retrieve all relevant
        // categories for this module, and compare against them...
        // $catid = xarModGetVar('example','cids');
        // if (xarModAPILoad('categories','user')) {
        //     $cats = xarModAPIFunc('categories',
        //                          'user',
        //                          'getcat',
        //                          array('cid' => $catid,
        //                                'return_itself' => true,
        //                                'getchildren' => true));
        //     // lower-case for fanciful search engines/people
        //     $params[1] = strtolower($params[1]);
        //     $foundcid = 0;
        //     foreach ($cats as $cat) {
        //         if ($params[1] == strtolower($cat['name'])) {
        //             $foundcid = $cat['cid'];
        //             break;
        //         }
        //     }
        //     // check if we found a matching category
        //     if (!empty($foundcid)) {
        //         $args['catid'] = $foundcid;
        //         // TODO: now analyse $params[2] for index, list, \d+ etc.
        //         // and return array('whatever', $args);
        //     }
        // }

        // we have no idea what this virtual path could be, so we'll just
        // forget about trying to decode this thing

        // you *could* return the main function here if you want to
        // return array('main', $args);
    }

    // default : return nothing -> no short URL decoded
}

?>
