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
function crispbb_user_newtopic($args)
{

    if (!xarVarFetch('fid', 'id', $fid)) return;

    $data = xarMod::apiFunc('crispbb', 'user', 'getforum', array('fid' => $fid, 'privcheck' => true));

    if ($data == 'NO_PRIVILEGES' || empty($data['newtopicurl'])) {
        return xarTplModule('privileges','user','errors',array('layout' => 'no_privileges'));
    }

    $forumLevel = $data['forumLevel'];
    $privs = $data['privs'];
    $uid = xarUser::getVar('id');
    $errorMsg = array();
    $invalid = array();
    $now = time();

    if (!empty($data['floodcontrol']) && empty($errorMsg)) {
        $lastpost = xarMod::apiFunc('crispbb', 'user', 'getposts',
            array(
                'fid' => $fid,
                'powner' => $uid,
                'sort' => 'ptime',
                'order' => 'DESC',
                'numitems' => 1
            ));
        if (!empty($lastpost)) {
            $lastpost = reset($lastpost);
            if ($lastpost['ptime'] > $now-$data['floodcontrol']) {
                $errorMsg = $data;
                $errorMsg['message'] = xarML('This forum requires that you wait at least #(1) seconds between posts.', $data['floodcontrol']);
                $errorMsg['return_url'] = xarModURL('crispbb', 'user', 'view', array('fid' => $fid));
                $errorMsg['type'] = 'FLOOD_CONTROL';
                $errorMsg['pageTitle'] = xarML('Flood Control');
                xarTpl::setPageTitle(xarVarPrepForDisplay($errorMsg['pageTitle']));
                return xarTPLModule('crispbb', 'user', 'error', $errorMsg);
            }
        }
    }

    if (!xarVarFetch('ttitle', 'str:1:100', $ttitle, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pdesc', 'str', $pdesc, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ptext', 'str', $ptext, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tstatus', 'int:0:10', $tstatus, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ttype', 'int:0:10', $ttype, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('topicicon', 'str', $topicicon, NULL,XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('phase', 'enum:form:update', $phase, 'form', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('preview', 'checkbox', $preview, false, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('htmldeny', 'checkbox', $htmldeny, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('bbcodedeny', 'checkbox', $bbcodedeny, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('smiliesdeny', 'checkbox', $smiliesdeny, false, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('approvereplies', 'checkbox', $approvereplies, false, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('return_url', 'str:1', $return_url, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('modname', 'str:1', $modname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype', 'id', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'id', $itemid, NULL, XARVAR_NOT_REQUIRED)) return;

    $tracker = unserialize(xarModUserVars::get('crispbb', 'tracker_object'));
    $data['userpanel'] = $tracker->getUserPanelInfo();

    $categories[$data['catid']] = xarMod::apiFunc('categories', 'user', 'getcatinfo',
            array('cid' => $data['catid']));

    $data['categories'] = $categories;

    $topicstype = xarMod::apiFunc('crispbb', 'user', 'getitemtype',
        array('fid' => $fid, 'component' => 'topics'));

    $presets = xarMod::apiFunc('crispbb', 'user', 'getpresets',
        array('preset' => 'privactionlabels,privleveloptions,tstatusoptions,ttypeoptions'));
    $ttypeoptions = array();
    $ttypeoptions[] = $presets['ttypeoptions'][0];
    if (!empty($privs['stickies'])) {
        $ttypeoptions[1] = $presets['ttypeoptions'][1];
    } elseif ($ttype == 1) {
        $invalid['ttype'] = xarML('You can not post sticky topics');
    }
    if (!empty($privs['announcements'])) {
        $ttypeoptions[2] = $presets['ttypeoptions'][2];
    } elseif ($ttype == 2) {
        $invalid['ttype'] = xarML('You can not post announcements');
    }
    if (!empty($privs['faqs'])) {
        $ttypeoptions[3] = $presets['ttypeoptions'][3];
    } elseif ($ttype == 3) {
        $invalid['ttype'] = xarML('You can not post FAQs');
    }
    $data['ttypeoptions'] = $ttypeoptions;
    $tstatusoptions = array();
    if (empty($data['topicapproval'])) {
        $tstatusoptions[0] = $presets['tstatusoptions'][0];
        if (!empty($privs['closetopics'])) {
            $tstatusoptions[1] = $presets['tstatusoptions'][1];
        } elseif ($tstatus == 1) {
            $invalid['tstatus'] = xarML('You can not post closed topics');
        }
        if (!empty($privs['approvetopics'])) {
            $tstatusoptions[2] = $presets['tstatusoptions'][2];
        } elseif ($tstatus == 2) {
            $invalid['tstatus'] = xarML('Topics do not require approval');
        }
        if (!empty($privs['locktopics'])) {
            $tstatusoptions[4] = $presets['tstatusoptions'][4];
        } elseif ($tstatus == 4) {
            $invalid['tstatus'] = xarML('You can not post locked topics');
        }
    // topics require approval
    } else {
        if (!empty($privs['approvetopics'])) {
            $tstatusoptions[0] = $presets['tstatusoptions'][0];
            if (!empty($privs['closetopics'])) {
                $tstatusoptions[1] = $presets['tstatusoptions'][1];
            } elseif ($tstatus == 1) {
                $invalid['tstatus'] = xarML('You can not post closed topics');
            }
            $tstatusoptions[2] = $presets['tstatusoptions'][2];
            if (!empty($privs['locktopics'])) {
                $tstatusoptions[4] = $presets['tstatusoptions'][4];
            } elseif ($tstatus == 4) {
                $invalid['tstatus'] = xarML('You can not post locked topics');
            }
        } else {
            $tstatus = 2;
        }
    }
    $data['tstatusoptions'] = $tstatusoptions;

    if (!empty($privs['approvereplies'])) {
        $data['approvereplies'] = $approvereplies;
    } else {
        $data['approvereplies'] = $data['replyapproval'];
    }

    // transforms
    $hasbbcode = xarModIsHooked('bbcode', 'crispbb', $topicstype);
    $hassmilies = xarModIsHooked('smilies', 'crispbb', $topicstype);

    // always $hashtml
    if (!empty($privs['html'])) { // user has privs to use html
        // user can disable html?
        $htmldeny = !empty($privs['htmldeny']) ? $htmldeny : false;
        $hashtml = true;
    } else { // no privs, no html
        $hashtml = false;
    }
    // TODO: present html option for fields in modify forum
    // for now this is always empty, so we'll skip it
    /*
    if ($hashtml) { // check if we're transforming any fields
        if (empty($data['ttransforms']['ttitle']['html']) && empty($data['ttransforms']['tdesc']['html']) && empty($data['ttransforms']['ttext']['html'])) { // no fields, no html
            $hashtml = false;
        }
    }
    */
    if ($hashtml) { // still got html, check if it's been disabled
        if ($htmldeny) {
            $hashtml = false;
        }
    }


    if ($hasbbcode) { // BBCode is available
        if (!empty($privs['bbcode'])) { // user has privs to use bbcode
            // user can disable bbcode?
            $bbcodedeny = !empty($privs['bbcodedeny']) ? $bbcodedeny : false;
        } else { // no privs, no bbcode
            $hasbbcode = false;
        }
        if ($hasbbcode) { // check if we're transforming any fields
            if (empty($data['ttransforms']['ttitle']['bbcode']) && empty($data['ttransforms']['tdesc']['bbcode']) && empty($data['ttransforms']['ttext']['bbcode'])) { // no fields, no bbcode
                $hasbbcode = false;
            }
        }
        if ($hasbbcode) { // still got bbcode, check if it's been disabled
            if ($bbcodedeny) {
                $hasbbcode = false;
            }
        }
    }


    if ($hassmilies) { // Smilies are available
        if (!empty($privs['smilies'])) { // user has privs to use smilies
            // user can disable smilies?
            $smiliesdeny = !empty($privs['smiliesdeny']) ? $smiliesdeny : false;
        } else { // no privs, no smilies
            $hassmilies = false;
        }
        if ($hassmilies) { // check if we're transforming any fields
            if (empty($data['ttransforms']['ttitle']['smilies']) && empty($data['ttransforms']['tdesc']['smilies']) && empty($data['ttransforms']['ttext']['smilies'])) { // no fields, no smilies
                $hassmilies = false;
            }
        }
        if ($hassmilies) { // still got smilies, check if it's been disabled
            if ($smiliesdeny) {
                $hassmilies = false;
            }
        }
    }

    // called by hooks
    if (!empty($modname)) {
        $modid = xarMod::getRegID($modname);
        if (empty($modid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = array('module name', 'user', 'newtopic', 'crispBB');
            throw new BadParameterException($vars, $msg);
            return;
        }
        if (empty($itemid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = array('item id', 'user', 'newtopic', 'crispBB');
            throw new BadParameterException($vars, $msg);
            return;
        }
        $var_to_look_for = $modname;
        if (!empty($itemtype)) {
            $var_to_look_for .= '_' . $itemtype;
        }
        $var_to_look_for .= '_hooks';
        $string = xarModVars::get('crispbb', $var_to_look_for);
        if (empty($string) || !is_string($string)) {
            $string = xarModVars::get('crispbb', 'crispbb_hooks');
        }
        $settings = !empty($string) && is_string($string) ? unserialize($string) : array();
        if (empty($settings['fid']) || $settings['fid'] != $fid) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = array('fid', 'user', 'newtopic', 'crispBB');
            throw new BadParameterException($vars, $msg);
            return;
        }
        $itemlinks = xarMod::apiFunc($modname, 'user', 'getitemlinks', array('itemids' => array($itemid), ), 0);
        if (!empty($itemlinks[$itemid])) {
            $ttitle = $itemlinks[$itemid]['label'];
            $linkurl = $itemlinks[$itemid]['url'];
        } else {
            $modinfo = xarMod::getInfo($modid);
            $ttitle = $modinfo['displayname'];
            if (!empty($itemtype)) {
                $ttitle .= ' ';
                $mytypes = xarMod::apiFunc($modname, 'user', 'getitemtypes', array(), 0);
                $ttitle .= !empty($mytypes[$itemtype]['label']) ? $mytypes[$itemtype]['label'] : $itemtype;
            }
            $ttitle .= ' ' . $itemid;
            $linkurl = xarModURL($modname, 'user', 'display', array('itemtype' => $itemtype, 'itemid' => $itemid));
        }
        $ptext = xarML('This topic is a discussion of');
        if ($hasbbcode) {
            $ptext .= ' [url=' . $linkurl . ']' . $ttitle . '[/url]';
        } elseif ($hashtml) {
            $ptext .= ' <a href="' . $linkurl . '">' . $ttitle . '</a>';
        } else {
            $ptext .= ' ' . $ttitle . ' - ' . $linkurl;
        }
        // set phase to update, so we can skip straight to newreply from here
        $phase = 'update';
    }

    $transargs = array();
    $transargs['itemtype'] = $topicstype;
    $transargs['transforms'] = $data['ttransforms'];
    $transargs['ttitle'] = $ttitle;
    $transargs['tdesc'] = $pdesc;
    $transargs['ttext'] = $ptext;
    $ignore = array();
    if (!$hashtml) $ignore['html'] = 1;
    if (!$hasbbcode) $ignore['bbcode'] = 1;
    if (!$hassmilies) $ignore['smilies'] = 1;
    $transargs['ignore'] = $ignore;

    $transformed = xarMod::apiFunc('crispbb', 'user', 'dotransforms', $transargs);

    if ($phase == 'update' || $preview) {
        $ttlen = strlen(strip_tags($transformed['ttitle']));
        if ($ttlen < $data['topictitlemin']) {
            $invalid['ttitle'] = xarML('Title must be at least #(1) characters', $data['topictitlemin']);
        } elseif ($ttlen > $data['topictitlemax']) {
            $invalid['ttitle'] = xarML('Title can not be more than #(1) characters', $data['topictitlemax']);
        }

        if ((!empty($data['topicdescmin']) || !empty($data['topicdescmax'])) && (empty($modname))) {
            $tdlen = strlen(strip_tags($transformed['tdesc']));
            if ($tdlen < $data['topicdescmin']) {
                $invalid['pdesc'] = xarML('Description must be at least #(1) characters', $data['topicdescmin']);
            } elseif ($tdlen > $data['topicdescmax']) {
                $invalid['pdesc'] = xarML('Description can not be more than #(1) characters', $data['topicdescmax']);
            }
        }

        $ptlen = strlen(strip_tags($transformed['ttext']));
        if ($ptlen < $data['topicpostmin']) {
            $invalid['ptext'] = xarML('Post must be at least #(1) characters', $data['topicpostmin']);
        } elseif ($ptlen > $data['topicpostmax']) {
            $invalid['ptext'] = xarML('Post can not be more than #(1) characters', $data['topicpostmax']);
        }
        $tsettings = array();
        $tsettings['topicicon'] = $topicicon;
        $tsettings['htmldeny'] = empty($privs['html']) || $htmldeny ? true : false;
        $tsettings['bbcodedeny'] = empty($privs['bbcode']) || $bbcodedeny ? true : false;
        $tsettings['smiliesdeny'] = empty($privs['smilies']) || $smiliesdeny ? true : false;
        $tsettings['approvereplies'] = $data['approvereplies'];
        $psettings = array();
        $psettings = $tsettings;

        if (empty($invalid) && !$preview) {
            if (!xarSecConfirmAuthKey())
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            if (!$tid = xarMod::apiFunc('crispbb', 'user', 'createtopic',
                array(
                    'fid' => $fid,
                    'ttitle' => $ttitle,
                    'pdesc' => $pdesc,
                    'ptext' => $ptext,
                    'towner' => $uid,
                    'tstatus' => $tstatus,
                    'ttype' => $ttype,
                    'topicstype' => $topicstype,
                    'tsettings' => $tsettings,
                    'psettings' => $psettings,
                    'ptime' => $now
                ))) return;

            // End Tracking
            if (!empty($tracker)) {
                $tracker->markRead($data['fid'], $tid);
                $lastreadforum = $tracker->lastRead($data['fid']);
                $unread = false;
                // get any topics since forum was last read
                $topicssince = xarMod::apiFunc('crispbb', 'user', 'gettopics',
                    array('fid' => $fid, 'starttime' => $lastreadforum));
                if (!empty($topicssince)) {
                    $tids = array_keys($topicssince);
                    $readtids = $tracker->seenTids($data['fid']);
                    foreach ($tids as $newtid) { // look for any posts still unread
                        if (in_array($newtid, $readtids)) continue; // read it, skip it
                        $unread = true; // found an unread post
                        break; // only need to find one
                    }
                }
                if (!$unread) { // didn't find any unread posts, mark forum read
                    $tracker->markRead($data['fid']);
                }
            }
            if (!xarMod::apiFunc('crispbb', 'user', 'updateposter',
                array('uid' => $uid))) return;
            // if this topic was created via hooks, we return user to a newreply to the topic
            if (!empty($modname)) {
                // create the hook
                if (!xarMod::apiFunc('crispbb', 'user', 'createhook',
                    array('modname' => $modname, 'itemtype' => $itemtype, 'objectid' => $itemid, 'tid' => $tid
                    ))) return;
                $return_url = xarModURL('crispbb', 'user', 'newreply',
                    array('modname' => $modname, 'itemtype' => $itemtype, 'objectid' => $itemid, 'tid' => $tid));
                /*
                // preserve the return url (links to the hooked module item)
                $real_return_url = xarModURL('crispbb', 'user', 'newreply',
                    array('tid' => $tid, 'return_url' => $return_url));
                xarSessionSetVar('crispbb_hook_active', $now);
                $return_url = $real_return_url;
                */
            } elseif (!empty($data['postbuffer']) || $tstatus == 2) {
                if ($tstatus == 2) {
                    $return_url = xarModURL('crispbb', 'user', 'view',
                        array('fid' => $fid));
                    $data['postbuffer'] = 5;
                    $pageTitle = xarML('Topic Submitted');
                    $message = xarML('Thank you. Your topic has been submitted, and will be displayed once approved.');
                } else {
                    $message = xarML('Topic posted. Thank you');
                    $return_url = xarModURL('crispbb', 'user', 'display',
                    array('tid' => $tid));
                    $pageTitle = xarML('Topic Posted');
                    $data['tid'] = $tid;
                    $data['ttitle'] = $ttitle;
                    $data['pid'] = NULL;
                }
                xarVarSetCached('Meta.refresh','url', $return_url);
                xarVarSetCached('Meta.refresh','time', $data['postbuffer']);
                xarTpl::setPageTitle(xarVarPrepForDisplay($pageTitle));
                $data['pageTitle'] = $pageTitle;

                $data['message'] = $message;
                return xarTPLModule('crispbb', 'user', 'return', $data);
            }

            if (empty($return_url)) {
                $return_url = xarModURL('crispbb', 'user', 'display', array('tid' => $tid));
            }
            xarController::redirect($return_url);
            return true;

        }
    }
    if ($preview || !empty($invalid)) {
        $data['preview'] = $transformed;
    }

    if (!empty($data['iconfolder'])) {
        $iconlist = xarMod::apiFunc('crispbb', 'user', 'gettopicicons',
            array('iconfolder' => $data['iconfolder'], 'shownone' => true));
        if (empty($iconlist[$topicicon])) $topicicon = $data['icondefault'];
        $data['iconlist'] = $iconlist;
    }

    $privs['html'] = ($hashtml && !$htmldeny) || (!$hashtml && $htmldeny) ? true : false;
    $privs['bbcode'] = ($hasbbcode && !$bbcodedeny) || (!$hasbbcode && $bbcodedeny) ? true : false;
    $privs['smilies'] = ($hassmilies && !$smiliesdeny) || (!$hassmilies && $smiliesdeny) ? true : false;

    $pageTitle = xarML('New Topic in #(1)', $data['fname']);
    $data['ttitle'] = $ttitle;
    $data['pdesc'] = $pdesc;
    $data['ptext'] = $ptext;
    $data['tstatus'] = $tstatus;
    $data['ttype'] = $ttype;
    $data['invalid'] = $invalid;
    $data['pageTitle'] = $pageTitle;
    $data['forumLevel'] = $forumLevel;
    $data['htmldeny'] = $htmldeny;
    $data['bbcodedeny'] = $bbcodedeny;
    $data['smiliesdeny'] = $smiliesdeny;
    $data['topicicon'] = $topicicon;
    $data['towner'] = $uid;

    $data['actions'] = $presets['privactionlabels'];
    $data['levels'] = $presets['privleveloptions'];
    $data['privs'] = $privs;
    // call hooks
    $item = array();
    $item['module'] = 'crispbb';
    $item['itemtype'] = $topicstype;
    $hooks = xarModCallHooks('item', 'new', '', $item);

    $data['hookoutput'] = !empty($hooks) ? $hooks : array();
    $data['return_url'] = $return_url;
    xarVarSetCached('Blocks.crispbb', 'fid', $fid);
    xarVarSetCached('Blocks.crispbb', 'catid', $data['catid']);

    $formaction =  xarModCallHooks('item', 'formaction', '', array(), 'crispbb', $data['topicstype']);
    $formdisplay = xarModCallHooks('item', 'formdisplay','', array(), 'crispbb', $data['topicstype']);
    $data['formaction'] = !empty($formaction) && is_array($formaction) ? join('',$formaction) : '';
    $data['formdisplay'] = !empty($formdisplay) && is_array($formdisplay) ? join('',$formdisplay) : '';

    if (xarVarIsCached('Hooks.dynamicdata','withupload') || xarModIsHooked('uploads', 'crispbb', $topicstype)) {
        $data['withupload'] = 1;
    } else {
        $data['withupload'] = 0;
    }
    xarTpl::setPageTitle(xarVarPrepForDisplay($pageTitle));

    return $data;
}
?>