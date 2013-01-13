<?php
/**
 * @file SimpleWiki.php
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
 * @todo Move namespaces to their own files.
 * @todo Allow drag and drop extending of SimpleWiki
 * @todo Canonical links.
 * @todo Improved BBCodes (wiki links and titles)
*/

if(!defined('SMF'))
    die('Hacking Attempt...');

function wiki($call = false)
{
    global $sourcedir, $scripturl, $context, $txt;
    // Load some important SimpleWiki functions.
    require_once $sourcedir . '/SimpleWiki-Subs.php';
    // They better be allowed to view the Wiki...
    wikiIsAllowedTo('simplewiki_view');
    // Setup the link tree
    $context['linktree'][] = array('name'=>$txt['wiki'], 'url' => $scripturl . '?action=wiki');
    
	// Make sure we have the template loaded up
	loadTemplate('SimpleWiki');
    // Now setup our template layers
	$context['template_layers'][] = 'wiki';
	
    // Come on people... request a page!
    if(!isset($_REQUEST['p']) || empty($_REQUEST['p']))
    {
        redirectexit($scripturl . '?action=wiki;p=Main_Page');
    }
    
    /**
     * Page request format
     * PAGE_NAMESPACE:PAGE_NAME
     */
    
    // Split up the page
    $colpos = strpos($_REQUEST['p'], ':');
    if(!$colpos)
    {
        $page_parts = array('view', $_REQUEST['p']);
    }
    else
    {
        $page_parts = array(substr($_REQUEST['p'], 0, $colpos), substr($_REQUEST['p'], $colpos+1));
    }
    
    // Is the requested 'namespace' valid?
    if(preg_match('/[^a-z_\-0-9]/i', $page_parts[0]))
    {
        // Tut tut tut. Invalid namespace.
        fatal_error('Ivalid SimpleWiki namesapce detected.', false);
    }
    
    // Is it the special page?
    $special = strtolower($page_parts[1]) === 'wikispecial';
	
    // Do we have a valid handler for this namespace?
    if((!$special && function_exists('wiki_namespace_' . $page_parts[0])) || ($special && function_exists('wiki_special_namespace_' . $page_parts[0])))
    {
        if($special)
        {
            // Load the namespace
            call_user_func('wiki_special_namespace_' . $page_parts[0]);
			// Load the right sub template.
			$context['sub_template'] = 'wiki_special_namespace_' . $page_parts[0];
        }
        else
        {
            // Get the page
            $page = GetPage($page_parts[1]);
            $context['template_layers'][] = 'wiki_ns';
			$context['wiki']['page_data'] = $page;
			if($page)
            {
                $context['linktree'][] = array('name' => $page['realname'], 'url' => wiki_link($page_parts[1]));
            }
            // And now load the namespace
            $ns_title = call_user_func('wiki_namespace_' . $page_parts[0], $page_parts[1], $page);
            if(isset($ns_title))
            {
                $context['linktree'][] = array('name' => $ns_title, 'url' => wiki_link($page_parts[0] . ':' . $page_parts[1]));
            }
            // Make sure we're loading the correct sub template.
			$context['sub_template'] = 'wiki_namespace_' . $page_parts[0];

        }
    }
    else
    {
        // Well this one patently doesn't exist...
        fatal_error('Unknown SimpleWiki namesapce requested', false);
    }
}

/**
 * Wiki page viewing namespace
 * @author James Robson
 * @param $page_uriname The URIname of the page
 * @param $page_data The contents of the page in array format or false if the page does not exist.
 */
function wiki_namespace_view($page_uriname, $page_data)
{
    global $scripturl, $context;
    // If the page doesn't exist then redirect to page creation
    if(!$page_data)
    {
        redirectexit($scripturl . '?action=wiki;p=Create:WikiSpecial;t=' . rawurlencode($page_uriname));
    }
}

function wiki_namespace_edit($page_uriname, $page_data)
{
    global $scripturl, $context, $modSettings, $sourcedir;
    isAllowedTo('edit');
    // If page data doesn't already exist then this is creation...
    if(!$page_data)
    {
        // @todo Should we do the creation anyhow?
        redirectexit($scripturl . '?action=wiki;p=Create:WikiSpecial;t=' . rawurlencode($page_uriname));
    }
	// @todo Check page's protection level
	// So we're saving then?
	if(isset($_POST['content']))
	{
		// Setting an empty page? That's not how you delete...
		if(empty($_POST['content']))
		{
			redirectexit(wiki_link('Delete:' . $page_uriname));
		}
		// Not changing anything? We're not going to waste insert queries on you!
		if($_POST['content'] === $page_data['body'])
		{
			redirectexit(wiki_link($page_uriname));
		}
		// OK then, lets save
		SavePage($page_data['realname'], $_POST['content']);
		// Make sure they see their finished page
		redirectexit(wiki_link($page_uriname));
	}
    $modSettings['disable_wysiwyg'] = true;//!empty($modSettings['disable_wysiwyg']) || empty($modSettings['enableBBC']);
    require_once($sourcedir . '/Subs-Editor.php');
	$editorOptions = array(
		'id' => 'content',
		'value' => $page_data['body'],
		'labels' => array(
			'post_button' => 'Post',
		),
		// add height and width for the editor
		'height' => '175px',
		'width' => '100%',
		// We do XML preview here.
		'preview_type' => 2,
	);
	create_control_richedit($editorOptions);
    return 'Edit';
}

/**
 * Wikipedia redirect namespace
 * @author James Robson
 * @param $page_uriname The URIname of the page
 * @param $page_data The contents of the page in array format or false if the page does not exist.
 * @todo Make this optional to turn on.
 * @todo Give a 'You are leaving this website. We are not responsible for external website content' message
 */
function wiki_namespace_wikipedia($page_uriname, $page_data)
{
    redirectexit('http://en.wikipedia.org/wiki/' . rawurlencode($page_uriname));
}

/**
 * SimpleWiki recent revisions namespace.
 * @author James Robson
 * @todo As we'll want recent revisions in the history:page_name namespace, move db stuff to subs.
 * @todo Cache everything.
 */
function wiki_special_namespace_recent()
{
    global $context, $smcFunc, $scripturl;
    $_REQUEST['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    // @todo offset
    // Get the latest posts.
    $res = $smcFunc['db_query']('', 'SELECT pages.realname, pages.uriname, revisions.id_revision, revisions.id_member, revisions.name_editor, revisions.time
                                    FROM {db_prefix}simplewiki_pages AS pages, {db_prefix}simplewiki_revisions AS revisions
                                    WHERE pages.id_page = revisions.id_page
                                    ORDER BY revisions.id_revision DESC
                                    LIMIT {int:start},20', array(
                                        'start' => $_REQUEST['start'],
                                    ));
    while($row = $smcFunc['db_fetch_assoc']($res))
    {
        $context['wiki_recent'][] = $row;
    }
    // If this was on start=0 I would be worried... it means you have an empty wiki!
    if(!isset($context['wiki_recent']))
    {
        fatal_error('No wiki edits have been found!', false);
    }
    else
    {
        $context['canonical_url'] = wiki_link('Recent:WikiSpecial', 'start=' . $_REQUEST['start']);
    }
	$smcFunc['db_free_result']($res);
	$res = $smcFunc['db_query']('', 'SELECT count(*) as i
									FROM {db_prefix}simplewiki_revisions', array());
	$context['wiki_total_revisions'] = $smcFunc['db_fetch_assoc']($res)['i'];
}

function wiki_namespace_history($page_uriname, $page_data)
{
    global $context, $smcFunc, $scripturl;
	$current_revision = $page_data ? $page_data['id_revision'] : 0;
    $_REQUEST['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    // @todo offset
    // Get the latest posts.
    $res = $smcFunc['db_query']('', 'SELECT pages.realname, pages.uriname, revisions.id_revision, revisions.id_member, revisions.name_editor, revisions.time
                                    FROM {db_prefix}simplewiki_pages AS pages, {db_prefix}simplewiki_revisions AS revisions
                                    WHERE pages.uriname = {string:name}
										AND pages.id_page = revisions.id_page
                                    ORDER BY revisions.id_revision DESC
                                    LIMIT {int:start},20', array(
                                        'start' => $_REQUEST['start'],
										'name' => $page_uriname,
                                    ));
    while($row = $smcFunc['db_fetch_assoc']($res))
    {
        $context['wiki_recent'][] = $row;
    }
    // If this was on start=0 I would be worried... it means you have an empty wiki!
    if(!isset($context['wiki_recent']))
    {
        fatal_error('No edits for this page have been found!', false);
    }
    else
    {
        $context['canonical_url'] = wiki_link('History:' . $page_uriname, 'start=' . $_REQUEST['start']);
    }
	$smcFunc['db_free_result']($res);
	$res = $smcFunc['db_query']('', 'SELECT count(*) as i
									FROM {db_prefix}simplewiki_revisions
									WHERE id_page = {int:id_page}', array(
										'id_page' => $page_data['id_page'],
									));
	$context['wiki_total_revisions'] = $smcFunc['db_fetch_assoc']($res)['i'];
}

// @todo custom search index
function wiki_special_namespace_search()
{
	global $smcFunc, $context;
	$_REQUEST['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
	$res = $smcFunc['db_query']('', 'SELECT revisions.body, pages.realname, revisions.time
		FROM {db_prefix}simplewiki_revisions AS revisions,
			{db_prefix}simplewiki_pages AS pages
		WHERE pages.id_latest_revision = revisions.id_revision
			AND MATCH (revisions.body, pages.realname)
		AGAINST ({text:qry} IN BOOLEAN MODE)
		LIMIT {int:offset},10', array(
			'qry' => $_POST['q'],
			'offset' => $_REQUEST['start']
		));
	while($row = $smcFunc['db_fetch_assoc']($res))
	{
		$context['wiki_search_results'][] = $row;
	}
	if(!isset($context['wiki_search_results']))
	{
		fatal_error('No search results found.', false);
	}
}

function wiki_special_namespace_create()
{
	global $scripturl, $context, $modSettings, $sourcedir;
	isAllowedTo('edit');
	// @todo Check if page already exists.
	// @todo Check page's protection level
	// So we're saving then?
	if(isset($_POST['content']) && isset($_POST['t']))
	{
		// Setting an empty page? That's pointless!
		if(empty($_POST['content']))
		{
			fatal_error('Cannot create an empty page.', false);
		}
		if(empty($_POST['t']))
		{
			fatal_error('Pages must have a title.', false);
		}
		// OK then, lets save
		SavePage($_POST['t'], $_POST['content']);
		// Redirect...
		redirectexit(wiki_link(wiki_to_uriname($_POST['t'])));
	}
    $modSettings['disable_wysiwyg'] = true;//!empty($modSettings['disable_wysiwyg']) || empty($modSettings['enableBBC']);
    require_once($sourcedir . '/Subs-Editor.php');
	$editorOptions = array(
		'id' => 'content',
		'value' => '',
		'labels' => array(
			'post_button' => 'Post',
		),
		// add height and width for the editor
		'height' => '175px',
		'width' => '100%',
		// We do XML preview here.
		'preview_type' => 2,
	);
	create_control_richedit($editorOptions);
}