<?php
/**
 * @file SimpleWiki-Subs.php
 * @author James Robson
 * 
 * Copyright (c) 2013, James Robson
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * 
 *   Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 *   Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA,
 * OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 * 
 * @todo Page locking needs a table to track it's changes.
*/

if(!defined('SMF'))
    die('Hacking Attempt...');

function GetPage($page_name_uri, $page_revision = null)
{
    global $smcFunc;
    // Setup our query
    $qry = "SELECT revisions.body, pages.uriname, revisions.id_editor, revisions.name_editor, pages.realname
            FROM {db_prefix}simplewiki_pages AS pages, {db_prefix}simplewiki_revisions AS revisions
            WHERE pages.uriname = {text:page_uriname}";
    $qry .= is_int($page_revision) ? '
                                    AND revisions.id_revision = {int:page_revision}
                                    AND revisions.id_page = pages.id_page' : '
                                    AND revisions.id_revision = pages.id_latest_revision';
    $qry .= '
    LIMIT 0,1';
    
    // Run our query
    $res = $smcFunc['db_query']('', $qry, array(
        'page_revision' => $page_revision,
        'page_uriname' => $page_name_uri,
        ));
    // Get our content
    $data = $smcFunc['db_fetch_assoc']($res);
    // @todo Deal with redirects?
    if(!$data)
    {
        // Oh... doesn't exist
        return false;
    }
    else
    {
        // Put our data away safely...
        cache_put_data('simplewiki_' . $page_name_uri . (is_int($page_revision) ? '_' . $page_revision : ''), $data);
        return $data;
    }
}

function SavePage($page_real_name, $page_content, $new = false, $opts = array())
{
    global $smcFunc, $user_info;
    //throw new Exception('Feature not implemented - SavePage() in SimpleWiki-Subs.php');
    // Get page data from the pages table.
	$res = $smcFunc['db_query']('', 'SELECT pages.id_page
		FROM {db_prefix}simplewiki_pages AS pages
		WHERE realname = {string:realname}',
		array(
			'realname' => $page_real_name,
		));
	$row = $smcFunc['db_fetch_assoc']($res);
	// Free up a tiny bit of memory...
	$smcFunc['db_free_result']($res);
	// So, do we exist?
	if(!$row)
	{
		// We'll need to create an entry in the pages table.
		$smcFunc['db_insert']('replace',
			'{db_prefix}simplewiki_pages',
			array(
				'uriname' => 'string',
				'realname' => 'string',
			),
			array(
				wiki_to_uriname($real_name),
				$real_name,
			),
			array(
				'id_page',
			));
		// Get the page ID
		$row['id_page'] = $smcFunc['db_insert_id']('{db_prefix}simplewiki_pages', 'id_page');
	}
	// Add a new row to the revisions table.
	$smcFunc['db_insert']('replace',
		'{db_prefix}simplewiki_revisions',
		array(
			'id_editor' => 'int',
			'name_editor' => 'int',
			'body' => 'string',
			'time' => 'int',
			'id_page' => 'int',
		),
		array(
			$user_info['id'],
			$user_info['id'] === 0 ? '127.0.0.1' : $user_info['name'],
			$page_content,
			time(),
			$id_page,
		),
		array(
			'id_revision',
		));
	// Set the last revision now...
	$smcFunc['db_query']('', 'UPDATE {db_prefix}simplewiki_pages
		SET id_latest_revision = {int:revision}
		WHERE id_page = {int:page}', array(
			'page' => $row['id_page'],
			'revision' => $smcFunc['db_insert_id']('{db_prefix}simplewiki_revisions', 'id_revision'),
		));
}

function wiki_to_uriname($realname)
{
	// Replace all whitepsace with underscores.
	return preg_replace('[s]+', '_', $realname);
}

// Permission functions
function wikiIsAllowedTo($perm)
{
    if(allowedTo('simplewiki_admin'))
    	return true;
	isAllowedTo('simplewiki_' . $perm);
}

function wikiAllowedTo($perm)
{
	if(allowedTo('simplewiki_admin'))
		return true;
	if(allowedTo('simplewiki_' . $perm))
		return true;
	return false;
}

/**
 * Don't use this function if there's a query string needed.
 */
function wiki_link($page_name)
{
    global $scripturl, $wiki_scripturl;
    if(defined('WIKI_PRETTY'))
    {
        if(!isset($wiki_scripturl))
        {
            $wiki_scripturl = str_replace('index.php', 'wiki', $scripturl);
        }
        return $wiki_scripturl . '/' . str_replace('%3A', ':', rawurlencode($page_name));
    }
    else
    {
        return $scripturl . '?action=wiki;p=' . str_replace('%3A', ':', rawurlencode($page_name));
    }
}

// === BEGIN INTEGRATION FUNCTIONS ===
function wiki_integrate_actions(&$actionArray)
{
	$actionArray['wiki'] = array('SimpleWiki.php', 'wiki');
}

function wiki_integrate_menu_buttons(&$menu_buttons)
{
	global $txt, $scripturl;
	$menu_buttons['wiki'] = array(
		'title' => $txt['wiki'],
		'href' => $scripturl.'?action=wiki',
		'show' => allowedTo('wiki_view'),
		'sub_buttons' => array(),
	);
}

function wiki_integrate_load_permissions($permissionGroups, &$permissionList, $leftPermissionGroups, $hiddenPermissions, $relabelPermissions)
{
	$permissionList['membergroup'] += array(
		'wiki_edit' => array(false, 'wiki', 'wiki'),
		'wiki_admin' => array(false, 'wiki', 'wiki'),
		'wiki_search' => array(false, 'wiki', 'wiki'),
		'wiki_protect' => array(false, 'wiki', 'wiki'),
		'wiki_view_history' => array(false, 'wiki', 'wiki'),
		'wiki_create' => array(false, 'wiki', 'wiki'),
		'wiki_delete' => array(false, 'wiki', 'wiki'),
	);
}