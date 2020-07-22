<?php
/**
 * Categories module
 *
 * @package Xarigami modules
 * @copyright (C) 2008-2012 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Categories Module
 * @link http://xarigami.com/project/xarigami_categories
 * @author Categories module development team
 */
/**
 * Hooks shows the configuration of hooks for other modules
 *
 * @return array $data containing template data
 */
function categories_admin_hooks()
{
    // Security check
    if(!xarSecurityCheck('ViewCategories')) return;

    $data = array();

    return $data;
}

?>
