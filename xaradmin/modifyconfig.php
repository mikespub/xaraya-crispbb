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
 * Modify module's configuration
 *
 * This is a standard function to modify the configuration parameters of the
 * module
 *
 * @author crisp <crisp@crispcreations.co.uk>
 * @return mixed, true on update success, or array of form data
 */
function crispbb_admin_modifyconfig()
{

    // Admin only function
    if (!xarSecurityCheck('AdminCrispBB')) return;
    $data = array();
    if (!xarVarFetch('sublink', 'str:1:', $sublink, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'enum:form:update', $phase, 'form', XARVAR_NOT_REQUIRED)) return;
    // allow return url to be over-ridden
    if (!xarVarFetch('return_url', 'str:1:', $data['return_url'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('checkupdate', 'checkbox', $checkupdate, false, XARVAR_NOT_REQUIRED)) return;

    $now = time();

    // Check the version of this module
    $modid = xarMod::getRegID('crispbb');
    $modinfo = xarMod::getInfo($modid);
    if ($checkupdate) {
        $phase = 'form';
        $hasupdate = xarMod::apiFunc('crispbb', 'user', 'checkupdate',
            array('version' => $modinfo['version']));
        // set for waiting content
        if ($hasupdate) {
            xarModVars::set('crispbb', 'latestversion', $hasupdate);
        }
    }

    // Add the standard module settings
    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'crispbb'));
    $data['module_settings']->setFieldList('use_module_alias, module_alias_name, enable_short_urls');
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls','frontend_page, backend_page');
    $data['module_settings']->getItem();
    $invalid = array();

    switch (strtolower($phase)) {
        case 'modify':
        default:
            break;

        case 'update':
        $sessionTimeout = xarConfigVars::get(null, 'Site.Session.InactivityTimeout');
        if (!xarVarFetch('visit_timeout', "int:1:$sessionTimeout", $visit_timeout, $sessionTimeout, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showuserpanel', 'checkbox', $showuserpanel, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showsearchbox', 'checkbox', $showsearchbox, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showforumjump', 'checkbox', $showforumjump, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showtopicjump', 'checkbox', $showtopicjump, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showquickreply', 'checkbox', $showquickreply, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showpermissions', 'checkbox', $showpermissions, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showsortbox', 'checkbox', $showsortbox, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('editor', 'str', $editor, xarModVars::get('crispbb', 'editor'), XARVAR_NOT_REQUIRED)) return;

        $isvalid = $data['module_settings']->checkInput();
        if ($isvalid) {
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }
            $currentalias = xarModVars::get('crispbb','module_alias_name');
            
            // Update the module settings
            $itemid = $data['module_settings']->updateItem();
            
            $module = 'crispbb';
            $newalias = trim(xarModVars::get('crispbb', 'module_alias_name'));
            if (empty($newalias)) {
                xarModVars::set('crispbb', 'use_module_alias', 0);
                $usealias = false;
            } else {
                $usealias = xarModVars::get('crispbb', 'use_module_alias');
            }
            if ($usealias && $newalias != $currentalias) {
                if (!empty($currentalias)) {
                    $hasalias = xarModAlias::resolve($currentalias);
                    if (!empty($hasalias) && $hasalias == $module) {
                        xarModAlias::delete($currentalias, $module);
                    }
                }
                if ( strpos($newalias,'_') === FALSE ) {
                    $newalias = str_replace(' ','_',$newalias);
                    xarModVars::set($module, 'module_alias_name', $newalias);
                }
                xarModAlias::set($newalias, $module);
            } elseif (!$usealias && !empty($currentalias)) {
                $hasalias = xarModAlias::resolve($currentalias);
                if (!empty($hasalias) && $hasalias == $module) {
                    xarModAlias::delete($currentalias, $module);
                }
            }

            xarModVars::set('crispbb', 'visit_timeout', $visit_timeout);
            xarModVars::set('crispbb', 'showuserpanel', $showuserpanel);
            xarModVars::set('crispbb', 'showsearchbox', $showsearchbox);
            xarModVars::set('crispbb', 'showforumjump', $showforumjump);
            xarModVars::set('crispbb', 'showtopicjump', $showtopicjump);
            xarModVars::set('crispbb', 'showquickreply', $showquickreply);
            xarModVars::set('crispbb', 'showpermissions', $showpermissions);
            xarModVars::set('crispbb', 'showsortbox',   $showsortbox);
            xarModVars::set('crispbb', 'editor',        $editor);

            xarModCallHooks('module','updateconfig','crispbb',
                           array('module' => 'crispbb'));
            // update the status message
            xarSessionSetVar('crispbb_statusmsg', xarML('Module configuration updated'));
            // if no returnurl specified, return to the modify function for the newly created forum
            if (empty($data['return_url'])) {
                $data['return_url'] = xarModURL('crispbb', 'admin', 'modifyconfig');
            }
            xarController::redirect($data['return_url']);
            return true;
        }

    }
    $pageTitle = xarML('Modify Module Configuration');
    $data['menulinks'] = xarMod::apiFunc('crispbb', 'admin', 'getmenulinks',
        array(
            'current_module' => 'crispbb',
            'current_type' => 'admin',
            'current_func' => 'modifyconfig',
            'current_sublink' => $sublink
        ));

    // Update the display controls
    $data['visit_timeout'] = xarModVars::get('crispbb', 'visit_timeout');
    $data['showuserpanel'] = xarModVars::get('crispbb', 'showuserpanel');
    $data['showsearchbox'] = xarModVars::get('crispbb', 'showsearchbox');
    $data['showforumjump'] = xarModVars::get('crispbb', 'showforumjump');
    $data['showtopicjump'] = xarModVars::get('crispbb', 'showtopicjump');
    $data['showquickreply'] = xarModVars::get('crispbb', 'showquickreply');
    $data['showpermissions'] = xarModVars::get('crispbb', 'showpermissions');
    $data['showsortbox'] = xarModVars::get('crispbb', 'showsortbox');
    
    // uUpdate he version check
    $data['version'] = $modinfo['version'];
    $data['newversion'] = !empty($hasupdate) ? $hasupdate : NULL;
    $data['checkupdate'] = $checkupdate;

    // store function name for use by admin-main as an entry point
    xarSessionSetVar('crispbb_adminstartpage', 'modifyconfig');
    /* Return the template variables defined in this function */
    xarTpl::setPageTitle(xarVarPrepForDisplay($pageTitle));
    return $data;
}
?>
