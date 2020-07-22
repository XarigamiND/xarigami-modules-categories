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
 * @copyright (C) 2010-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 * @author Xarigami Team
 */
/**
 * Get a list of images from the modules/categories/xarimages directory
 * (may be overridden by versions in themes/<theme>/modules/categories/images)
 * jojo - the problem is the image has to be in the system category folder to start with :(
 *      - we may need them for multiple sites but atm it's not convenient for a single site
 *      Try: 1. Check the category theme dir for images
 *           2. Fall back to Category system dir to find them
 *           3. Add option (todo) to signify any other directory to also look
 */
function categories_visualapi_findimages()
{
    // theme *overrides* are possible, but the original must reside here
    $basedir = sys::code().'modules/categories/xarimages';
    $themedir = xarTplGetThemeDir().'/modules/categories/images';

    $basedir = realpath($basedir);
    $themedir = realpath($themedir);
    $dirarray = array($basedir,$themedir);
    $filetype = '(png|gif|jpg|jpeg)';
    $filelist = array();

    $todo = array();
    array_push($todo, $basedir);
    while (count($todo) > 0) {
        $curdir = array_shift($todo);
        if ($dir = @opendir($curdir)) {
            while(($file = @readdir($dir)) !== false) {
                $curfile = $curdir . '/' . $file;
                if (preg_match("/\.$filetype$/",$file) && is_file($curfile) && filesize($curfile) > 0) {
                    $curfile = preg_replace('#'.preg_quote($basedir,'#').'/#','',$curfile);
                    $filelist[] = $curfile;
                } elseif ($file != '.' && $file != '..' && is_dir($curfile)) {
                    array_push($todo, $curfile);
                }
            }
            closedir($dir);
        }
    }
   // natsort($filelist);
    //we have the category list
    //add the theme images that are not in the category list (which could be overridden in theme as normal)
    $todo = array();
    array_push($todo, $themedir);
    $filelist2 = array();
    while (count($todo) > 0) {
        $curdir = array_shift($todo);
        if ($dir = @opendir($curdir)) {
            while(($file = @readdir($dir)) !== false) {
                $curfile = $curdir . '/' . $file;
                if (preg_match("/\.$filetype$/",$file) && is_file($curfile) && filesize($curfile) > 0) {
                    $curfile = preg_replace('#'.preg_quote($themedir,'#').'/#','',$curfile);
                    $filelist2[] = $curfile;
                } elseif ($file != '.' && $file != '..' && is_dir($curfile)) {
                    array_push($todo, $curfile);
                }
            }
            closedir($dir);
        }
    }
   // natsort($filelist2);
    $filelist = array_merge($filelist2,$filelist);
    $filelist = array_unique($filelist);
    natsort($filelist);
    return $filelist;
}

?>
