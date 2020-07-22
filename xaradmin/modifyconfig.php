<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Categories
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_categories
 */
/**
 * modify and update configuration
 * @param string phase
 * @return mixed true on success of update, or array with template to show
 */
function categories_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminCategories')) {
        $msg = xarML('You have no permission to administrate categories');
        return xarResponseForbidden($msg);
    }

    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default:
            $catsperpage = xarModGetVar('categories', 'catsperpage');
            if (!$catsperpage) {
                $catsperpage = 10;
            }
            /* jojo - deprecated already?
            $useJSdisplay = xarModGetVar('categories', 'useJSdisplay');
            if (!$useJSdisplay) {
                $useJSdisplay = false;
            }
            */
            $extrainfo = array();
            $extrainfo['module'] = 'categories';
            $hooks = xarModCallHooks('module', 'modifyconfig', 'categories', $extrainfo);

            if (empty($hooks)) {
                $hooks = '';
            }

            $data = array ('catsperpage'   => $catsperpage,
                //             'useJSdisplay'  => $useJSdisplay,
                           'hooks'         => $hooks);
            $data['submitlabel'] = xarML('Submit');

            $data['numstats'] = xarModGetVar('categories', 'numstats');
            if (empty($data['numstats'])) {
                $data['numstats'] = 100;
            }
            $data['showtitle'] = xarModGetVar('categories', 'showtitle');
            if (!empty($data['showtitle'])) {
                $data['showtitle'] = 1;
            }
            $data['inputsize'] = xarModGetVar('categories', 'inputsize');
            if (!is_numeric($data['inputsize'])) {
                $data['inputsize'] = 5;
            }
            $data['singleinput'] = xarModGetVar('categories', 'singleinput');
            if (!is_numeric($data['singleinput'])) {
                $data['singleinput'] = FALSE;
            }
            $data['usenameinstead'] = xarModGetVar('categories', 'usename');
            $data['allowdragdrop'] = xarModGetVar('categories', 'allowdragdrop');
            // xcat-000554
            //jojo - keep this alwyays true for now until cat/articles privs reviewed
            //$testall = xarModGetVar('categories', 'securitytestall');
             $data['sectestall'] = TRUE; //xarModGetVar('categories', 'securitytestall');
            //common admin menu
            $data['menulinks'] = xarModAPIFunc('categories', 'admin', 'getmenulinks');

            return xarTplModule('categories', 'admin', 'config',$data);
            break;

        case 'update':
            if (!xarSecConfirmAuthKey()) return;
            if (!xarVarFetch('catsperpage', 'int:1:1000', $catsperpage, 10, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('usenameinstead', 'checkbox', $usenameinstead,false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('allowdragdrop', 'checkbox', $allowdragdrop,false, XARVAR_NOT_REQUIRED)) return;
            xarModSetVar('categories', 'allowdragdrop', $allowdragdrop);
            xarModSetVar('categories', 'catsperpage', $catsperpage);
            xarModSetVar('categories', 'usename', $usenameinstead);

            if (!xarVarFetch('numstats', 'int', $numstats, 100, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showtitle', 'checkbox', $showtitle, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('inputsize', 'int:0:', $inputsize, 5, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('singleinput', 'checkbox', $singleinput, false, XARVAR_NOT_REQUIRED)) return;
            xarModSetVar('categories', 'numstats', $numstats);
            xarModSetVar('categories', 'showtitle', $showtitle);
            xarModSetVar('categories', 'inputsize', $inputsize);
            xarModSetVar('categories', 'singleinput', $singleinput);

            if (!xarVarFetch('sectestall', 'checkbox', $sectestall, true, XARVAR_NOT_REQUIRED)) return;
            xarModSetVar('categories', 'securitytestall', $sectestall);

            // Call update config hooks
            xarModCallHooks('module','updateconfig','categories', array('module' => 'categories'));
            $msg = xarML('Categories configuration was successfully updated.');
            xarTplSetMessage($msg,'status');
            xarResponseRedirect(xarModUrl('categories','admin','modifyconfig',array()));

            break;
    }

    return true;
}

?>