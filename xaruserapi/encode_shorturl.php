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
 * Standard function to encode shorturls for the module
 *
 * @author crisp <crisp@crispcreations.co.uk>
 * @return array
 */
function crispbb_userapi_encode_shorturl($args)
{
    extract($args);

    if (!isset($func)) {
        return;
    }

    $path = [];
    $get = $args;

    // This module name.
    $module = 'crispbb';
    $aliasisset = xarModVars::get($module, 'use_module_alias');
    $aliasname = xarModVars::get($module, 'module_alias_name');

    if (!empty($aliasisset) && !empty($aliasname)) {
        // Check this alias really is a module alias, by mapping
        // it back to its module name.
        $module_for_alias = xarModAlias::resolve($aliasname);
        if ($module_for_alias == $module) {
            // Yes, we have a valid module alias, so use it
            // now instead of the module name.
            $module = $aliasname;
        }
    }

    // if there's a lot of links, transforming titles can be expensive
    // we cache the retrieved items for use in the current request to save on db hits
    static $forums = [];
    static $topics = [];
    static $posts = [];
    static $cats = [];

    $path[] = $module; // ../crispbb (or ../aliasname)

    if (isset($fid) && is_numeric($fid)) {
        if (isset($forums[$fid])) {
            $forum = $forums[$fid];
        }
        if ($func == 'forum_index' && !empty($action) && $action == 'read') {
            unset($get['func']);
            unset($get['action']);
            $path[] = 'read';
        }
        if (empty($forum)) {
            $forum = xarMod::apiFunc('crispbb', 'user', 'getforum', ['fid' => $fid, 'nolinks' => true, 'privcheck' => true]);
            $forums[$fid] = $forum;
        }
        if ($forum != 'NO_PRIVILEGES' && $forum != 'BAD_DATA') {
            $fname = crispbb_encode_shorturl_cleantitle($forum['transformed_fname']);
            unset($get['fid']);
            $path[] = $fname; // ../crispbb/some-forum-name
        }
        $path[] = 'f'.$fid; // ../crispbb/fXXX or ../crispbb/some-forum-name/fXXX
        if ($func == 'view') {
            unset($get['func']);
        } elseif ($func == 'newtopic') {
            unset($get['func']);
            $path[] = 'newtopic';
        } elseif ($func == 'moderate') {
            unset($get['func']);
            $path[] = 'moderate';
        }
    } elseif (isset($tid) && is_numeric($tid)) {
        if (isset($topics[$tid])) {
            $topic = $topics[$tid];
        }
        if (empty($topic)) {
            $topic = xarMod::apiFunc('crispbb', 'user', 'gettopic', ['tid' => $tid, 'nolinks' => true, 'privcheck' => true]);
            $topics[$tid] = $topic;
        }
        if ($topic != 'NO_PRIVILEGES' && $topic != 'BAD_DATA') {
            $ttitle = crispbb_encode_shorturl_cleantitle($topic['transformed_ttitle']);
            unset($get['tid']);
            $path[] = $ttitle; // ../crispbb/some-topic title
        }
        $path[] = 't'.$tid; // ../crispbb/tXXX or ../crispbb/some-topic title/tXXX
        if (isset($pid) && is_numeric($pid)) {
            unset($get['pid']);
            $path[] = 'p'.$pid; // ../crispbb/some-topic title/tXXX/pXXX
        }
        unset($get['func']);
        if ($func == 'display') {
        } elseif ($func == 'moderate') {
            $path[] = 'moderate';
        } elseif ($func == 'newreply') {
            $path[] = 'newreply';
        } elseif ($func == 'splittopic') {
            if (isset($startpid) && is_numeric($startpid)) {
                unset($get['startpid']);
                $path[] = 'p'.$startpid;
            }
            $path[] = 'split';
        } elseif ($func == 'movetopic') {
            $path[] = 'move';
        } elseif ($func == 'modifytopic') {
            $path[] = 'edit';
        } elseif (isset($tstatus) && is_numeric($tstatus)) {
            unset($get['tstatus']);
            if (isset($topic['tstatus'])) {
                if ($topic['tstatus'] == 0 && $tstatus == 1) {
                    $path[] = 'close';
                } elseif ($topic['tstatus'] == 5 && $tstatus == 0) {
                    $path[] = 'undelete';
                } elseif ($topic['tstatus'] == 0 && $tstatus == 4) {
                    $path[] = 'lock';
                } elseif ($topic['tstatus'] == 1 && $tstatus == 0) {
                    $path[] = 'open';
                } elseif ($topic['tstatus'] == 4 && $tstatus == 0) {
                    $path[] = 'unlock';
                } elseif ($tstatus == 5) {
                    $path[] = 'delete';
                }
            }
        }
    } elseif (isset($pid) && is_numeric($pid)) {
        if (isset($posts[$pid])) {
            $post = $posts[$pid];
        }
        if (empty($post)) {
            $post = xarMod::apiFunc('crispbb', 'user', 'getpost', ['pid' => $pid, 'nolinks' => true, 'privcheck' => true]);
            $posts[$pid] = $post;
        }

        if ($post != 'NO_PRIVILEGES') {
            $ttitle = crispbb_encode_shorturl_cleantitle($post['transformed_ttitle']);
            unset($get['pid']);
            $path[] = $ttitle; // ../crispbb/some-topic title
            if ($func != 'displayreply') {
                $path[] = 't'.$post['tid'];
            }
            $path[] = 'p'.$pid;
            unset($get['func']);
            if ($func == 'modifyreply') {
                $path[] = 'edit';
            } elseif ($func == 'updatereply') {
                unset($get['pstatus']);
                if (isset($pstatus) && $pstatus == 5) {
                    $path[] = 'delete';
                }
            }
        }
    } elseif ($func == 'forum_index') { // ../crispbb
        unset($get['func']);
        if (isset($catid) && is_numeric($catid)) {
            if (isset($cats[$catid])) {
                $catinfo = $cats[$catid];
            }
            if (empty($catinfo)) {
                $catinfo = xarMod::apiFunc('categories', 'user', 'getcatinfo', ['cid' => $catid]);
                $cats[$catid] = $catinfo;
            }
            if (!empty($catinfo['name'])) {
                $catname = crispbb_encode_shorturl_cleantitle($catinfo['name']);
                $path[] = $catname;
            }
            unset($get['catid']);
            $path[] = 'c'.$catid; // ../crispbb/cXXX
        }
    } elseif ($func == 'search') {
        unset($get['func']);
        $path[] = 'search'; // ../crispbb/search
    } elseif ($func == 'redirect') {
        unset($get['func']);
        $path[] = 'redirect'; // ../crispbb/redirect
    } elseif ($func == 'updatetopic') {
        unset($get['func']);
        $path[] = 'updatetopic'; // ../crispbb/redirect
    } elseif ($func == 'updatereply') {
        unset($get['func']);
        $path[] = 'updatereply'; // ../crispbb/redirect
    } elseif ($func == 'newtopic') {
        unset($get['func']);
        $path[] = 'newtopic'; // ../crispbb/redirect
    } elseif ($func == 'modifytopic') {
        unset($get['func']);
        $path[] = 'modifytopic'; // ../crispbb/redirect
    } elseif ($func == 'modifyreply') {
        unset($get['func']);
        $path[] = 'modifyreply'; // ../crispbb/redirect
    } elseif ($func == 'newreply') {
        unset($get['func']);
        $path[] = 'newreply'; // ../crispbb/redirect
    } elseif ($func == 'movetopic') {
        unset($get['func']);
        $path[] = 'movetopic'; // ../crispbb/redirect
    } elseif ($func == 'splittopic') {
        unset($get['func']);
        $path[] = 'splittopic'; // ../crispbb/redirect
    } elseif ($func == 'stats') {
        unset($get['func']);
        $path[] = 'stats';
    } elseif ($func == 'moderate') {
        unset($get['func']);
        $path[] = 'moderate';
    }
    return ['path' => $path, 'get' => $get];
}
function crispbb_encode_shorturl_cleantitle($string='')
{
    $string = strtolower(trim(strip_tags($string)));
    if (!empty($string)) {
        $pattern = '/&[^;]+;/'; // replace html entities (&amp;, &quot; etc)
        $string = preg_replace($pattern, '', $string);
        // Non-ascii text TODO: do a proper replace instead where applicable (eg &Aacute; becomes a)
        $pattern = '/[^(\x20-\x7F)]*/'; // removes non-ascii characters including newline
        // $pattern = '/[^(\x20-\x7F)\x0A]*/'; removes non-ascii characters preserving newline
        $string = preg_replace($pattern, '', $string); // strip non-ascii characters
        // strip any other non-word characters, except dashes, undercores, or spaces
        $pattern = '/[^\w\- _]/';
        $string = preg_replace($pattern, '', $string);
        // replace spaces and underscores with dashes
        $string = str_replace(' ', '-', $string);
        $string = str_replace('_', '-', $string);
        // normalize double dashes to a single dash
        $string = preg_replace('/\-\-+/', '-', $string);
    }
    return $string;
}
