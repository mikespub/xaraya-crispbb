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
 *//**
 * Do something
 *
 * Standard function
 *
 * @author crisp <crisp@crispcreations.co.uk>
 * @return array
 * @throws none
 */
sys::import('modules.base.class.pager');
function crispbb_admin_posters($args)
{
    extract($args);
    if (!xarSecurityCheck('AdminCrispBB')) return;

    if (!xarVarFetch('ip', 'str:1', $ip, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uid', 'id', $uid, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('startnum', 'int', $startnum, NULL, XARVAR_NOT_REQUIRED)) return;

    $numitems = 20;
    $now = time();

    if (!empty($uid)) {
        $posters = xarMod::apiFunc('crispbb', 'user', 'getipsbyposter',
            array(
                'uid' => $uid,
                'startnum' => $startnum,
                'numitems' => $numitems,
                'showstatus' => true
            ));
    } else {
        $posters = xarMod::apiFunc('crispbb', 'user', 'getposters',
            array(
                'ip' => $ip,
                'startnum' => $startnum,
                'numitems' => $numitems,
                'showstatus' => true
            ));
    }

    $totalposters = xarMod::apiFunc('crispbb', 'user', 'countposters',
        array(
            'ip' => $ip,
            'powner' => $uid
        ));

    $data['items'] = $posters;

    $data['ip'] = $ip;
    $data['startnum'] = $startnum;
    $data['uid'] = $uid;
    if (!empty($uid)) {
        $data['name'] = xarUser::getVar('name', $uid);
    }
    sys::import('modules.base.class.pager');
    $data['pager'] = xarTplPager::getPager($startnum,
        $totalposters,
        xarModURL('crispbb', 'admin', 'posters', array('ip' => $ip, 'uid' => $uid, 'startnum' => '%%')),
        $numitems);
    $pageTitle = xarML('Forum Posters');
    $data['pageTitle'] = $pageTitle;
    xarTpl::setPageTitle(xarVarPrepForDisplay($pageTitle));

    $data['menulinks'] = xarMod::apiFunc('crispbb', 'admin', 'getmenulinks',
        array(
            'current_module' => 'crispbb',
            'current_type' => 'admin',
            'current_func' => 'posters',
            'current_sublink' => NULL
        ));
    return $data;
}
?>