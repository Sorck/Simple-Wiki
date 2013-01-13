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
 * @todo Improved BBCodes.
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
	$context['template_layers'][] = (WIRELESS ? WIRELESS_PROTOCOL . '_' : '') . 'wiki';
	
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

// ==== END OF NEW EDITION CODE ====

function wiki_view_all()
{
	global $smcFunc, $txt, $context, $scripturl;
	//this function lists all wiki pages
	if(!wikiAllowedTo('wiki_view'))redirectexit($scripturl.'?action=wiki');
	
	//Are we in wap, wap2, or imode?
	if(WIRELESS)$context['sub_template'] = WIRELESS_PROTOCOL.'_wiki_view_all';
		
	//declaired offset?
	$offset = isset($_REQUEST['start'])?floor(intval($_REQUEST['start'])):0;
	$res = $smcFunc['db_query']('', 'SELECT DISTINCT {db_prefix}wiki_pages.title
		FROM {db_prefix}wiki_pages
		ORDER BY title ASC
		LIMIT {int:offset},25', array('offset'=>$offset));
	$pages = array();
	while($row = $smcFunc['db_fetch_assoc']($res))
	{
		$pages[] = $row['title'];
	}
	//get total no. of pages in existence
	$allq = $smcFunc['db_query']('', 'SELECT DISTINCT {db_prefix}wiki_pages.title
		FROM {db_prefix}wiki_pages', array());
	$context['wiki_nrows'] = $smcFunc['db_num_rows']($allq);
	$context['page_title'] = $txt['wiki_view_all'];
	$context['wiki_template'] = 'view_all';
	$context['wiki_page_list'] = $pages;
}

function wiki_delete()
{
	global $smcFunc, $context, $scripturl;
	//can we delete?
	wikiIsAllowedTo('wiki_delete');
	//if there's no id then fatal error
	if(!isset($_REQUEST['id']))
		fatal_error($txt['wiki_no_page']);
	//fetch the page
	$page = wiki_fetch_pageById($_REQUEST['id']);
	if(!$page)
		fatal_error($txt['wiki_no_page']);
	//are we allowed to delete, based on edit protection
	if(!wiki_can_edit($page['protected']))
		redirectexit($scripturl.'?action=wiki;p='.$page['name']);
	$deleted = $page['deleted']=='1' ? 0:1;
	//now onto the query
	$smcFunc['db_query']('', 'UPDATE {db_prefix}wiki_pages
		SET deleted={int:deleted} WHERE id={int:id}', array('id'=>intval($page['id']), 'deleted'=>$deleted));
	redirectexit($scripturl.'?action=wiki;sa=history;p='.$page['title']);
}

function wiki_upload()
{
	global $context, $txt, $scripturl, $boarddir, $smcFunc, $user_info;
	//Please deactivate on production environments
	$active = true;
	if(!$active)
		fatal_error('Uploads not activated');
	wikiIsAllowedTo('wiki_upload');
	if(isset($_REQUEST['save'])&&isset($_FILES['wiki_file']))
	{
		//validate the file
		$filename = basename($_FILES['wiki_file']['name']);
		$extension = substr($filename, strrpos($filename, '.') + 1);
		$maxsize = 1000000; //1mb limit
		//allow jpegs, gifs, pdfs, pngs - will add admin cpanel choice later
		$exts = array('jpg', 'jpeg', 'gif', 'png', 'pdf');
		foreach($exts as $type)
		{
			$extensions[$type] = 1;
		}
		if(!isset($extensions[$extension]))
			fatal_error('Invalid Extension');
		//add the file to the database
		$smcFunc['db_insert']('insert', '{db_prefix}wiki_files', array('name'=>'string','hash'=>'string','user'=>'int'),
			array($filename, $hash, $user_info['id']));
		//filename is a HASHED_SIZE . _ . ID . _ . FILENAME
		$hash = md5($_FILES['wiki_file']['size']);
		$goto = $boarddir.'/wiki_uploads/'.$hash.'';
		if($_FILES['wiki_file']['size']>=$maxsize)
			fatal_error('File Too Large');
		$v = move_uploaded_file($_FILES['wiki_file']['tmp_name'], $goto);
		if(!$v)
			fatal_error('Failed to create file on server');

		redirectexit($scripturl.'?action=wiki;sa=upload');
	}
	$context['page_title'] = 'Wiki Uploads';
	$context['wiki_template'] = 'upload';
}

function wiki_search()
{
	//the mastery of searching :P
	global $txt, $smcFunc, $context, $scripurl;
	
	//Are we in wap, wap2, or imode?
	if(WIRELESS)$context['sub_template'] = WIRELESS_PROTOCOL.'_wiki_search';
	
	//are we allowed to search?
	if(!wikiAllowedTo('wiki_search'))
		redirectexit($scripturl.'?action=wiki');
	$context['page_title'] = $txt['search'];
	//is there a search term?
	if(isset($_REQUEST['q'])&&!empty($_REQUEST['q']))
	{
		$sql = 'SELECT distinct {db_prefix}wiki_pages.title, {db_prefix}members.real_name
		FROM {db_prefix}wiki_pages
			LEFT JOIN {db_prefix}members
			ON {db_prefix}members.id_member = {db_prefix}wiki_pages.user
		WHERE {db_prefix}wiki_pages.title LIKE {string:query} OR {db_prefix}wiki_pages.content LIKE "{raw:query}"
		LIMIT 0,15';
		$res = $smcFunc['db_query']('', $sql, array('query'=>'%'.wiki_search_escape($_REQUEST['q']).'%'));
		$i = 0;
		$results = array();
		while($row = $smcFunc['db_fetch_assoc']($res))
		{
			if($row!='1')
			{
				$results[] = $row;
			}
			$i++;
		}
		$context['wiki_search_results'] = $results;
		$context['page_title'] = sprintf($txt['wiki_search_query'], $_REQUEST['q']);
	}
	$context['wiki_template'] = 'search';
}

function wiki_protect()
{
	global $txt, $context, $scripturl, $smcFunc;
	$context['robot_no_index'] = true;
	wikiIsAllowedTo('wiki_protect');
	$context['page_title'] = $txt['wiki_protect_page'].': '.$_REQUEST['p'];
	$context['linktree'][] = array('url'=>'index.php?action=wiki;p='.$_REQUEST['p'], 'name' => $_REQUEST['p']);
	$context['linktree'][] = array('name'=>$txt['wiki_protect_page'],'url'=>'index.php?action=wiki;sa=protect;p='.$_REQUEST['p']);
	$page = wiki_fetch_page($_REQUEST['p'], true);
	if(!$page)fatal_error($txt['wiki_no_page']);
	$context['wiki_template'] = 'protect';
	$context['wiki_page'] = $page;
	$context['wiki_protect_options'] = array(
		'no_protection' => array('show'=>true, 'value'=>'no_protection', 'text'=>$txt['wiki_unprotect'], 'key'=>0),
		'forum_admin' => array('show'=>$context['user']['is_admin'], 'value'=>'forum_admin', 'text'=>$txt['wiki_admins_only'], 'key'=>1),
		'wiki_admin' => array('show'=>wikiAllowedTo('wiki_admin'), 'value'=>'wiki_admin', 'text'=>$txt['wiki_wadmins_only'], 'key'=>2),
		'no_guests' => array('show'=>$context['user']['is_logged'], 'value'=>'no_guests', 'text'=>$txt['wiki_no_guests'], 'key'=>3),
	);
	
	if(isset($_REQUEST['save'])&&isset($_POST['protect_form']))
	{
		//is the option in our list?
		$protect_id = $context['wiki_protect_options'][$_POST['protect_form']]['key'];
		if(isset($context['wiki_protect_options']))
		{
			$smcFunc['db_query']('', 'UPDATE {db_prefix}wiki_pages 
				SET protected = {int:protect} 
				WHERE title = {string:page} 
				AND time = {int:time}
				LIMIT 1', 
				array(
					'protect' => $protect_id,
					'time' => $page['time'],
					'page' => $_REQUEST['p'],
				)
			);
		}
		redirectexit($scripturl.'?action=wiki;sa=protect;p='.$_REQUEST['p'].';updated');
	}
}

function wiki_manage_bans()
{
	global $scripturl, $smcFunc, $context;
	$context['wiki_template'] = 'manage_bans';
	$offset = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
	$res = $smcFunc['db_query']('', 'SELECT * FROM {db_prefix}wiki_bans LIMIT {int:offset},15', array('offset' => $offset));
	while($row = $smcFunc['db_fetch_assoc']($res))
	{
		$context['list_bans'][] = $row;
	}
	//a little processing...
	if(isset($_REQUEST['save']) && isset($_POST['user']) && !empty($_POST['user']))
	{
		//proccess
		$u = intval($_POST['user']);
		$smcFunc['db_insert']('insert', '{db_prefix}wiki_bans', array('user' => 'int'), array($u));
		redirectexit($scripturl.'?action=wiki;sa=admin;area=manage_bans');
	}
}

$wiki_headers = array();
$last = array();
function wiki_parse($text, $toc = false, $cats = false)
{
	global $wiki_headers, $last;
	$text = censorText($text);
	$text = htmlspecialchars($text);
	$last['h2'] = 0;
	$toreturn =  parse_bbc($text, true);
	//if we want the TOC then give it to them
	if($toc)
		$toreturn = wiki_toc().$toreturn;
	//show categories?
	if($cats)
		$toreturn .= wiki_list_cats();
	return $toreturn;
}

function wiki_make_h2($match)
{
	global $wiki_headers, $last;
	$content = $match;
	$wiki_headers[$content] = array();
	$last['h2'] = $content;
	$toreturn = '<h2 class="largetext"><a name="'.$content.'">'.$content.'</a></h2><hr />';
	return $toreturn;
}

$last['h2'] = 0;

function wiki_make_h3($match)
{
	global $wiki_headers, $last;
	$content = $match;
	if(!isset($last['h2']))
	{
		// !!! what should we do in this situation?
		//setting them to h2's seems logical but later proper h2's may be found
	}
	else
	{
		$wiki_headers[$last['h2']][$content] = array();
		$last['h3'] = $content;
		$toreturn = '<h3><a name="'.$content.'">'.$content.'</a></h3>';
		return $toreturn;
	}
}

function wiki_toc()
{
	global $wiki_headers;
	//settings
	$type = 'ol'; // either ul or ol
	//if wireless then a couple of changes are needed
	if(WIRELESS)$type='ul';
	$toc = '<'.$type.' class="decimal_list">';
	$i_1 = isset($wiki_headers[0]) ? 0 : 1;
	foreach($wiki_headers as $act => $header)
	{
		$toc .= $i_1 == 0 ? '' : '<li>'.(WIRELESS ? '':'<span><strong><a href="#'.htmlspecialchars($act).'">'.$i_1.'</strong></span>').'<span class="span_margin">'.$act.'</a></span>';
		if(is_array($header))
		{
			$toc .= '<'.$type.' class="decimal_list">';
			$i_2 = 1;
			foreach($header as $key => $var)
			{
				$toc .= '<li>'.(WIRELESS ? '':'<span><strong><a href="#'.htmlspecialchars($key).'">'.$i_1.'.'.$i_2.'</strong>').'</span><span class="span_margin">'.$key.'</a></span></li>';
				$i_2++;
			}
			$toc .= '</'.$type.'>';
		}
		$toc .= '</li>';
		$i_1++;
	}
	$toc .= '</'.$type.'>';
	return $toc;
}

function wiki_preparse($string)
{
	//this updates categories contained in the database
	global $smcFunc;
	$smcFunc['db_query']('', 'DELETE FROM {db_prefix}wiki_categories WHERE page_name={string:page}', array('page'=>$_REQUEST['p']));
	$string = preg_replace_callback('~\[categories\](.+)\[/categories\]~iUs', 'wiki_parse_categories', $string);
	return $string;
}

function wiki_parse_categories($matches)
{
	global $smcFunc;
	$cats = explode('#', $matches[1]);
	foreach($cats as $cat)
	{
		$smcFunc['db_insert']('insert', '{db_prefix}wiki_categories', array('cat_name'=>'text', 'page_name'=>'text'), array(htmlspecialchars($cat), $_REQUEST['p']));
	}
	return '[categories]'.$matches[1].'[/categories]';
}
function wiki_list_cats()
{	
	global $smcFunc, $scripturl;
	//fetch cats
	$res = $smcFunc['db_query']('', 'SELECT *
		FROM {db_prefix}wiki_categories
		WHERE page_name = {string:page}', array('page'=>$_REQUEST['p']));
	$cats = '<div class="wiki_cats">';
	while($row = $smcFunc['db_fetch_assoc']($res))
	{
		$cats .= '[<a href="'.$scripturl.'?action=wiki;p=Category:'.$row['cat_name'].'">'.$row['cat_name'].'</a>] ';
	}
	$cats .= '</div>';
	return $cats;
}
$GLOBALS['wiki']['number_templates'] = 0;

//this marvelous function parses the template code
function parse_template($data)
{
	global $scripturl;
	//if there's no data then return a parse error
	$GLOBALS['wiki']['number_templates'] ++;
	//if we're on the 15th or higher template then don't parse, this stops infinite parse loops & overloading the database
	if($GLOBALS['wiki']['number_templates']>=15)return;
	if(empty($data))return '<font size=50pt color=red>Template Parse Error!</font>';
	//the delimiter for variables, default is "##"
	$del = '##';
	$info = explode($del, $data);
	$template = isset($GLOBALS['wiki']['template'][$info[0]]) ? $GLOBALS['wiki']['template'][$info[0]] : wiki_fetch_page($info[0]);
	$GLOBALS['wiki']['template'][$info[0]] = $template;
	//if the template doesn't exist then we better link to the creation page or show a parse error
	if(!is_array($template))return wikiAllowedTo('wiki_create')?'<a href="'.$scripturl.'?action=wiki;sa=create;p='.htmlspecialchars($info[0]).'">Create Template</a>':'<font size=50pt color=red>Template does not exist</font>';
	$temp = $template['content'];
	//any variables being passed? If not then there's no point running the regex
	if(isset($info[1]) && !empty($info[1]))
	{
		//we use the format of {var} for variable at the moment
		unset($info[0]);
		$GLOBALS['wiki']['temp'] = $info;
		//$temp = preg_replace_callback('~{([a-zA-Z0-9_-]+)}~', 'wiki_varf', $temp);
		//now for our varf experimental:
		$temp = preg_replace_callback('~{var:([0-9+)]}~', 'wiki_varfe', $temp);
	}
	//now lets parse & return it
	return wiki_parse($temp);
}

//this is the superior function which allows {var:1}, {var:2}, etc so you can input [template]test##Sorck] and
//the page of "{var:1} is the author of SimpleWiki[br]Colonel {var:1} is what he is most commonly known as"
//replaces every instance of {var:1} with the 1st passed variable (in the example it is 'Sorck')
function wiki_varfe($matches)
{
	//the match better not be empty!
	$match = $matches[0];
	if(empty($match))return;
	$id = intval($matches[0]);
	if(!$id)
		return;
	if(!isset($GLOBALS['wiki']['temp'][$id]))
		return;
	return un_htmlspecialchars($GLOBALS['wiki']['temp'][$id]);
}
?>