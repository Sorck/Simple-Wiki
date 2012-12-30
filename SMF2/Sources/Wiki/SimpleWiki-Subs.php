<?php
/**
 * @file SimpleWiki-Subs.php
 * @author James Robson
 * 
 * Copyright (c) 2012, James Robson
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
*/

/*
Page locking needs a table to track it's changes.
*/

if(!defined('SMF'))
    die('Hacking Attempt...');

function GetPage($page_name_uri, $page_revision = null)
{
    global $smcFunc;
    // Setup our query
    $qry = "SELECT revisions.body, pages.uriname, revisions.editor_id, revisions.editor_name
            FROM {db_prefix}sw_pages AS pages, {db_prefix}sw_revisions AS revisions
            WHERE pages.uriname = {text:page_uriname}";
    $qry .= is_int($page_revision) ? 'AND revision.id_revision = {int:page_revision}
                                            AND revision.id_revision = pages.id_revision' : 'AND revision.id_revision = page.id_last_revision';
    $qry .= 'LIMIT 0,1';
    
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

function SavePage($page_real_name, $page_content, $opts = array())
{
    throw new Exception('Feature not implemented - SavePage() in SimpleWiki-Subs.php');
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