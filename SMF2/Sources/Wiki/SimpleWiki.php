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
 * 
 * @todo Move namespaces to their own files.
 * @todo Allow drag and drop extending of SimpleWiki
 * @todo Canonical links.
*/

if(!defined('SMF'))
    die('Hacking Attempt...');

function wiki($call = false)
{
	global $sourcedir, $scripturl;
    // Load some important SimpleWiki functions.
    require_once $sourcedir . '/Wiki/SimpleWiki-Subs.php';
    // They better be allowed to view the Wiki...
	wikiIsAllowedTo('simplewiki_view');
    
    // Come on people... request a page!
    if(!isset($_REQUEST['p']))
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
            call_user_func('wiki_special_namespace_' . $page_parts[0], array($page_parts[1]));
            $context['wiki_theme'] = 'special_namespace_' . $page_parts[0];
        }
        else
        {
            // Get the page
            $page = GetPage($page_parts[1]);
            // And now load the namespace
            call_user_func('wiki_namespace_' . $page_parts[0], array($page_parts[1], $page));
            $context['wiki_theme'] = 'namespace_' . $page_parts[0];
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
    redirectexit('http://en.wikipedia.org/wiki/' . $page_uriname);
}

// ==== END OF NEW EDITION CODE ====

function wiki_init()
{
	global $context, $txt, $scripturl;
	$context['wiki_reports'] = array();
	$context['html_headers'] .= '<style type="text/css">.decimal_list{list-style:none;}.span_margin{margin-left:0.8em;}.float_left{float:left;}.float_right{float:right;}</style>';
	loadLanguage('wiki');
	loadTemplate('wiki');
	if(isset($_REQUEST['p']))$_REQUEST['p']=htmlspecialchars($_REQUEST['p']);
	//linktree...
	$context['linktree'][] = array('name'=>$txt['wiki'],'url'=>$scripturl.'?action=wiki',);
	$context['name_space'] = false;
	$context['protection_array'] = array(
		0 => true, //obviously if there's no protection then you can edit it :P
		1 => $context['user']['is_admin'],
		2 => wikiAllowedTo('wiki_admin'),
		3 => $context['user']['is_logged'],
	);
}

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

function wiki_can_edit($p_level)
{
	global $context;
	//this function handles protection, based upon given protection level
	$p_level = intval($p_level);
	//admin's are always allowed, not really admins otherwise :P
	if($context['user']['is_admin'])
		return true;
	//if we can't edit then it's a definite no no, wiki admins being an exception
	if(!wikiAllowedTo('wiki_edit'))
		return false;
	//if the protection level doesn't exist then someone's been doing some changes, we allow as long as they're not a guest
	if(!isset($context['protection_array'][$p_level]))
		return $context['user']['is_logged']?true:false;
	//so lets get the protection level
	return $context['protection_array'][$p_level];
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

function wiki_main()
{
	global $sourcedir;
	// !!! maybe we could add dynamic file detection here?
	$sactions = array(
		'edit' => 'wiki_edit',
		'save' => 'wiki_save',
		'admin' => 'wiki_admin',
		'create' => 'wiki_create',
		'history' => 'wiki_history',
		'protect' => 'wiki_protect',
		'search' => 'wiki_search',
		'delete' => 'wiki_delete',
		'v_all' => 'wiki_view_all',
	);
	if(isset($_REQUEST['sa'])&&isset($sactions[$_REQUEST['sa']]))
		return $sactions[$_REQUEST['sa']];
	elseif(file_exists($sourcedir.'/wiki/'.$_REQUEST['sa'].'.function.php'))
	{
		require_once($sourcedir.'/wiki/'.$_REQUEST['sa'].'.function.php');
		// fairly complex...
		return 'wiki_'.(function_exists('wiki_'.$_REQUEST['sa']) ? $_REQUEST['sa']:'page');
	}
	else
		return 'wiki_page';
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

//handles all file downloading, passes 
function wiki_download_file()
{
	global $smcFunc, $txt;
	if(isset($_REQUEST['img']))
		wiki_display_img();
	if(!isset($_REQUEST['file']))
		fatal_error($txt['wiki_no_file']);
}

//this function shows an uploaded image
function wiki_display_img()
{
	global $smcFunc, $context, $boarddir;
	//is there a requested image
	$req = isset($_REQUEST['id']) ? intval($_REQUEST['id']):false;
	//if there's no image then we can't do anything
	if(!$req)
		die();
	$res = $smcFunc['db_query']('', 'SELECT * FROM {db_prefix}wiki_files WHERE id = {int:id}', array($req));
	$img = $smcFunc['db_fetch_assoc']($res);
	//no image? stop processing then
	if(!is_array($img))
		die();
	$image = $boarddir.'/wiki_imgs/'.$img['image'];
	if($img['type'] == 'jpeg')
	{
		$im = imagecreatefromjpeg($image);
		header('content-type: image/jpeg');
		imagejpeg($im);
	}
	if($img['type'] == 'gif')
	{
		$im = imagecreatefromgif($image);
		header('content-type: image/gif');
		imagegif($im);
	}
	if($img['type'] == 'png')
	{
		$im = imagecreatefrompng($image);
		header('content-type: image/png');
		imagepng($im);
	}
	imagedestroy($im);
	die();
	// !!! should record hits
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

function wiki_history()
{
	global $txt, $smcFunc, $user_info, $scripturl, $context;
	//are we allowed to see the history?
	wikiIsAllowedTo('wiki_view_history');
	//does the page exist?
	if(!wiki_fetch_page($_REQUEST['p'], true))fatal_error(sprintf($txt['wiki_error_no_such_page'], $_REQUEST['p']));
	//do the query to get ALL history
	$sql = 'SELECT {db_prefix}wiki_pages.*, {db_prefix}members.member_name, {db_prefix}members.real_name 
		FROM {db_prefix}wiki_pages LEFT JOIN {db_prefix}members 
		ON {db_prefix}wiki_pages.user={db_prefix}members.id_member 
		WHERE title={string:name} 
		ORDER BY time DESC 
		LIMIT {int:offset},10';
	$res = $smcFunc['db_query']('',$sql,array('name'=>$_REQUEST['p'],'offset'=>floor(intval($_REQUEST['start']))));
	while($row = $smcFunc['db_fetch_assoc']($res))
	{
		$rows[] = $row;
	}
	//quickly find how many approapriate rows there are (without limit)
	$res = $smcFunc['db_query']('','SELECT * FROM {db_prefix}wiki_pages WHERE title={string:name}', array('name'=>$_REQUEST['p']));
	$_REQUEST['start'] = isset($_REQUEST['start'])?intval($_REQUEST['start']):0;
	$context['history_pageIndex'] = constructPageIndex($scripturl.'index.php?action=wiki;sa=history;p='.$_REQUEST['p'], $_REQUEST['start'], $smcFunc['db_num_rows']($res), 10);
	$context['history_pages'] = $rows;
	$context['wiki_template'] = 'history';
	$context['linktree'][] = array('url'=>'index.php?action=wiki;sa=history;p='.$_REQUEST['p'], 'name' => $txt['wiki_history'],);
	$context['page_title'] = $txt['wiki_history'];
}

function wiki_save()
{
	global $txt, $smcFunc, $user_info, $scripturl;
	//are we allowed to save?
	$context['robot_no_index'] = true;
	wikiIsAllowedTo('wiki_edit');
	if(!isset($_REQUEST['p']))fatal_error($txt['wiki_no_page']);
	elseif(empty($_REQUEST['p']))fatal_error($txt['wiki_no_page']);
	// !!! should add a test for page protection
	//get current page & protection, if the page exists obviously
	$page = wiki_fetch_page($_REQUEST['p'], true, false);
	$protection = $page?$page['protected']:'0';
	$content = wiki_preparse($_POST['wiki_content']);
	//test for permission on wiki pages
	if(!isset($_POST['wiki_content']))fatal_error($txt['wiki_blank_content']);
	//now, attempt to save
	$smcFunc['db_insert']('insert', '{db_prefix}wiki_pages',
		array(
			'title' => 'text', 'time' => 'int', 'ip' => 'text', 'user' => 'int', 'content' => 'text', 'protected' => 'text'
		),
		array(
			$_REQUEST['p'], time(), $_SERVER['REMOTE_ADDR'], $user_info['id'], $content, $protection
		)
	);
	//now update the cache...
	$new = $page;
	$new['content'] = $content;
	$new['time'] = time();
	cache_put_data('wiki_'.$_REQUEST['p'], $new);
	redirectexit($scripturl.'?action=wiki;p='.$_REQUEST['p']);
}

function wiki_create()
{
	global $context, $txt, $modSettings, $scripturl, $sourcedir;
	$context['robot_no_index'] = true;
	//are we allowed to edit/create?
	wikiIsAllowedTo('wiki_create');	
	$page = isset($_REQUEST['p']) ? $_REQUEST['p'] : '';
	if(isset($_REQUEST['p']))
	{
		//fetch the page
		$pagecontent = wiki_fetch_page($page);
		if($pagecontent)redirectexit($scripturl.'?action=wiki;sa=edit;p='.$page);
	}
	if(WIRELESS)$context['sub_template'] = WIRELESS_PROTOCOL.'_wiki_edit';
	$context['wiki_template'] = 'create';
	$context['linktree'][] = array('url'=>'index.php?action=wiki;sa=create;p='.$page, 'name' => $txt['wiki_create'],);
	$context['page_title'] = sprintf($txt['wiki_creating'], $_REQUEST['p']);
	$modSettings['disable_wysiwyg'] = !empty($modSettings['disable_wysiwyg']) || empty($modSettings['enableBBC']);
	require_once($sourcedir . '/Subs-Editor.php');
	$editorOptions = array(
		'id' => 'wiki_content',
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

function wiki_edit()
{
	global $context, $txt, $modSettings, $scripturl, $sourcedir;
	$context['robot_no_index'] = true;
	$page = isset($_REQUEST['p']) ? $_REQUEST['p'] : 'Main Page';
	$_REQUEST['p'] = $page;
	//are we allowed to edit? if not then send them to the main wiki page
	if(!wikiAllowedTo('wiki_edit'))redirectexit('index.php?action=wiki;p='.$page);
	//fetch the page
	$pagecontent = wiki_fetch_page($page);
	if(!wiki_can_edit($pagecontent['protected']))fatal_error(sprintf($txt['wiki_is_protected'], $pagecontent['title']));
	if(!$pagecontent)redirectexit($scripturl.'?action=wiki;sa=create;p='.$page);
	$context['wiki_page_content'] = $pagecontent['content'];
	if(WIRELESS)$context['sub_template'] = WIRELESS_PROTOCOL.'_wiki_edit';
	$context['wiki_template'] = 'edit';
	$context['linktree'][] = array('url'=>'index.php?action=wiki;p='.$page, 'name' => $page,);
	$context['linktree'][] = array('url'=>'index.php?action=wiki;sa=edit;p='.$page, 'name' => $txt['wiki_edit'],);
	$context['page_title'] = sprintf($txt['wiki_editting'], htmlspecialchars($page));
	$modSettings['disable_wysiwyg'] = !empty($modSettings['disable_wysiwyg']) || empty($modSettings['enableBBC']);
	require_once($sourcedir . '/Subs-Editor.php');
	$editorOptions = array(
		'id' => 'wiki_content',
		'value' => $context['wiki_page_content'],
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

function wiki_page()
{
	global $context, $txt, $scripturl;
	if(WIRELESS)$context['sub_template'] = WIRELESS_PROTOCOL.'_wiki_page';
	$context['wiki_template'] = 'page'; //this loads up the template for viewing standard pages
	//namespaces
	$nameSpaces = array(
		//'User' => array('function'=>'user'),
		//'Category' => array('function'=>'cat'),
	);

	$parray = explode(':', $_REQUEST['p']);
	if(!isset($parray[1]))
		$nm = false;
	else
		$nm = $parray[0];
	
	$_REQUEST['p_namespace'] = isset($nm)&&isset($nameSpaces[$nm])?true:false;
	//$xy = isset($nm)&&isset($nameSpaces[$nm])?true:false;
	$_REQUEST['p_no_namespace'] = str_replace($nm.':', '', $_REQUEST['p']);
	$nm = strtolower($nm);
	if($nm !== false && isset($nameSpaces[$nm]))
	{
		call_user_func('wiki_namespace_'.$nameSpaces[$nm]['function']);
	}
	//the above is our old ns system, the following will be better for future use :)
	elseif($nm !== false && file_exists($sourcedir.'/wiki/'.$nm.'.ns.php'))
	{
		require_once($sourcedir.'/wiki/'.$nm.'.ns.php');
		call_user_func('wiki_namespace_'.$nm);
	}
		
	if(isset($_REQUEST['id'])&&!empty($_REQUEST['id']))
	{
		$content = wiki_fetch_pageById($_REQUEST['id']);
		if($content==false)fatal_error($txt['wiki_error_bad_id']);
		$context['page_title'] = $txt['wiki'].' - '.$content['title'];
		$_REQUEST['p'] = $content['title'];
	}
	else
	{
		$page = isset($_REQUEST['p']) ? $_REQUEST['p'] : 'Main Page';
		$context['wiki_current_page'] = $page;
		$_REQUEST['p'] = $page;
		$context['page_title'] = $txt['wiki'].' - '.$page;
		$content = wiki_fetch_page($page);
		if($content==false&&!$_REQUEST['p_namespace'])redirectexit($scripturl.'?action=wiki;sa=create;p='.$_REQUEST['p']);
	}
	//if(!is_array($content))die(print_r($content));
	$context['wiki_page_content'] = wiki_parse($content['content'], true, true);
	$context['linktree'][] = array('url'=>'index.php?action=wiki;p='.$_REQUEST['p'], 'name' => $_REQUEST['p']);
}

function wiki_admin()
{
	global $context, $txt;
	// !!! must work on, sensitive stuff should be in the main admin cp
	//we have our own admin cp dedicated just to the wiki!
	if(!wikiAllowedTo('wiki_admin'))redirectexit('index.php?action=wiki');
	$context['wiki_template'] = 'admin';
	$context['page_title'] = $txt['wiki_admin'];
	
	//areas
	$areas = array(
		'manage_bans' => 'wiki_manage_bans',
		'config' => 'wiki_admin_config',
	);
	if(isset($_REQUEST['area']) && isset($areas[$_REQUEST['area']]))
		$areas[$_REQUEST['area']]();
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

function wiki_admin_config()
{
	global $smcFunc;
	// !!! should this be stricter than wiki admins? such as a specific wiki_config permission?
	$context['wiki_template'] = 'config';
}

function wiki_fetch_page($name, $allow_deleted = false, $cache = true)
{
	global $smcFunc;
	//first see if it's in the cache
	if($cache)$cached = cache_get_data('wiki_'.$name);
	if(!$cache || !$cached)
	{
		//select query
		$sql = "SELECT * FROM {db_prefix}wiki_pages WHERE title={string:name}".($allow_deleted?'':"AND deleted='0'")."ORDER BY time DESC LIMIT 0,1";
		$resource = $smcFunc['db_query']('', $sql, array('name'=>$name,));
		$row = $smcFunc['db_fetch_assoc']($resource);
		//store in the cache to reduce the number of queries in future page loads
		cache_put_data('wiki_'.$name, $row);
	}
	else
		$row = $cached;
	// !!! handle redirects
	if(!is_array($row)||empty($row['content']))
		return false;
	
	//update the database to say we've viewed it
	$smcFunc['db_query']('', 'UPDATE {db_prefix}wiki_pages SET views = views + 1 WHERE title = {string:page}', array('page' => $row['title']));
	
	return $row;
}

function wikiLog()
{
	global $smcFunc, $user_info;
	//we don't have the db tables so don't do anything
	return;
	$smcFunc['db_insert']('insert', '{db_prefix}wiki_log_view',
		array('user' => 'int', 'ip' => 'text', 'page' => 'text'),
		array($user_info['id'], $_SERVER['REMOTE_ADDR'], $_REQUEST['p']));
}

function wiki_fetch_pageById($id)
{
	global $smcFunc;
	$cached = cache_get_data('wiki-id_'.$id);
	if(!$cached)
	{
		//select query
		$sql = "SELECT * FROM {db_prefix}wiki_pages WHERE id={int:id} ORDER BY time DESC LIMIT 0,1";
		$resource = $smcFunc['db_query']('', $sql, array('id'=>$id,));
		$row = $smcFunc['db_fetch_assoc']($resource);
		cache_put_data('wiki-id_'.$id);
	}
	else
		$row = $cached;
	// !!! handle redirects
	if(!is_array($row)||empty($row['content']))
		return false;
	return $row;
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

function wiki_search_escape($text)
{
	$text = str_replace(array('\'', '"', '\\'), '', $text);
	//$text = addslashes($text);
	return $text;
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

//this is a function to aid in passing variables to templates
function wiki_varf($matches)
{
	//the match better have something in it!
	$match = $matches[0];
	if(empty($match))return;
	//is there an active variable to replace it with?
	foreach($GLOBALS['wiki']['temp'] as $act => $row)
	{
		$id = $act;
		//we only want the 1st available variable
		break;
	}
	if(!isset($id))return;
	$var = $GLOBALS['wiki']['temp'][$id];
	//better make sure it doesn't continue to exist
	unset($GLOBALS['wiki']['temp'][$id]);
	//now return the data
	return un_htmlspecialchars($var);
}

function wiki_is_banned($fatal = false)
{
	global $smcFunc, $user_info, $context;
	//if we've allready tested then no need to test again
	if(isset($context['wiki_banned']) && !$fatal) return;
	//"innocent until proven guilty" is our motto here
	$context['wiki_banned'] = false;
	$noban_admin = true;
	//admins can't be banned, else it would be chaos :P
	if(allowedTo('wiki_admin') && $noban_admin)
		return false;
	//we cache our bans to reduce server load, this means a ban can take several minutes to come into effect
	$cache = cache_get_data('wiki_bans_'.$user_info['id']);
	//$cache = false;
	if(!$cache)
	{
		//now fetch from the banned table
		$res = $smcFunc['db_query']('', 'SELECT * 
			FROM {db_prefix}wiki_bans 
			WHERE ip = {string:ip} 
			OR user = {int:user}',
			array('ip'=>$_SERVER['REMOTE_ADDR'], 'user'=>$user_info['id'])
		);
		$row = $smcFunc['db_fetch_assoc']($res);
		if(!is_array($row))
		{
			$context['wiki_banned'] = false;
			cache_put_data('wiki_bans_'.$user_info['id'], array('banned' => false), 300);
			return false;
		}
		else
		{
			cache_put_data('wiki_bans_'.$user_info['id'], array('banned' => true), 300);
			if($fatal)
				fatal_error('You have been banned from the wiki, either because of your IP or user account, you may be able to continue browsing but you cannot edit any pages');
			$context['wiki_reports'][] = 'You\'ve been banned from this wiki hence cannot edit pages.';
			return true;
		}
	}
	//the cache says that we're banned! if fatal throw an error, else just return 
	elseif($cache['banned'])
	{
		$context['wiki_banned'] = true;
		$context['wiki_reports'][] = 'You\'ve been banned from this wiki hence cannot edit pages.';
		if($fatal)
		{
			fatal_error('You have been banned from the wiki, either because of your IP or user account, you may be able to continue browsing but you cannot edit any pages');
		}
		else
			return true;
	}
	//I'd say we're not banned then
	else
	{
		return false;
	}
}

//this function formats the URL depending upon if SEF/SEO/Pretty URLS are enabled :)
function wiki_pageUrl($page_name, $type = 'page')
{
	global $scripturl, $modsettings;
	//Is the wiki SEF online?
	if(empty($modsettings['wiki_sef_online']))
		return $scripturl.'?action=wiki;p='.$page_name;
	$url = str_replace('index.php', '', $scripturl);
	$url .= 'wiki/';
	$conversions = array(
		'edit' => 'edit/',
		'page' => '',
		'protect' => 'protect/',
		'create' => 'create/',
		'history' => 'history/',
	);
	if($type == 'edit')
	{
		$url .= 'edit/';
	}
	$url .= $page_name;
	return $url;
}
?>