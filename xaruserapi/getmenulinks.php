<?php
/**
 * crispBB Forum Module
 *
 * @package modules
 * @copyright (C) 2008-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage crispBB Forum Module
 * @link http://xaraya.com/index.php/release/970.html
 * @author crisp <crisp@crispcreations.co.uk>
 */
/**
* Utility function to pass individual menu items to whoever
*
* @author crisp <crisp@crispcreations.co.uk>
* @return array
*/
function crispbb_userapi_getmenulinks()
{
    $secLevel = xarMod::apiFunc('crispbb', 'user', 'getseclevel');
    if (empty($secLevel)) {
        return [];
    }
    static $menulinks = [];

    if (empty($menulinks)) {
        $menulinks = xarMod::apiFunc('crispbb', 'user', 'getitemlinks');
    }
    return $menulinks;
}
