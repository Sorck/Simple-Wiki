<?php
/**
 * @file SimpleWiki.template.php
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
*/

function template_wiki_above()
{
    global $user_info, $txt;
    echo '<div class="title_bar">
            <h3 class="titlebg">
			<div id="quick_search" class="align_right"><form id="wikiSearchForm" action="', wiki_link('search:WikiSpecial'), '" method="post">
			<input class="input_text" type="search" name="q" placeholder="', $txt['search'], '" /><input type="submit" value="', $txt['search'], '" /></form></div>'.$txt['wiki'], '</h3>
	</div><span class="upperframe"><span></span></span>
	<div class="roundframe">', sprintf($txt['wiki_welcome'], ($user_info['name'] ? $user_info['name'] : $txt['guest'])), '
	</div><span class="lowerframe"><span></span></span>';
	echo '<br />';
	// echo '<div id="left_admsection">';
	#echo '<span class="upperframe"><span></span></span>Allo';
	// @todo Wiki Menu
	#echo '</div>';
	//echo '<div class="windowbg2" id="main_admsection"><span class="topslice"><span></span></span>';
	echo '<div class="content">';
}

function template_wiki_below()
{
	//echo '</div><span class="botslice clear"><span></span></span>';
	echo '</div><br class="clear" />';
    template_wiki_copyright();
}

function template_wiki_ns_above()
{
    global $context, $txt;
	$tools = array(
		'edit' => array('name'=>'wiki_edit', 'show'=>wikiAllowedTo('edit'), 'url'=>wiki_link('Edit:page_string'), 'image' => 'wiki_edit.png'),
		'history' => array('name'=>'wiki_history', 'show'=>wikiAllowedTo('history'), 'url'=>wiki_link('History:page_string'), 'image' => 'wiki_history.png'),
		//'protect' => array('name'=>'wiki_protect_page', 'show'=>wikiAllowedTo('protect'), 'url'=>wiki_link('protect:page_string'), 'image' => 'wiki_lock.png'),
		'create' => array('name'=>'wiki_create_new_page', 'show'=>wikiAllowedTo('create'), 'url'=>wiki_link('Create:WikiSpecial'), 'image' => 'wiki_create.png'),
	);

	template_wiki_make_toolbar($tools);
	
	echo '<br class="clear" />';
}

function template_wiki_ns_below()
{
	// nothin' to see here...
}

function template_wiki_namespace_view()
{
    global $context;
	// Display our content.
	echo parse_bbc($context['wiki']['page_data']['body']);
}

// @todo clean
function template_wiki_namespace_edit()
{
    global $txt, $scripturl, $context;
	template_wiki_edit_box(wiki_link('edit:' . $context['wiki']['page_data']['uriname']));
	echo '<form method="post" action="', wiki_link($context['wiki']['page_data']['uriname']), '"><input type="submit" tabindex="3" value="', $txt['wiki_cancel_editting'], '" /></form>';
}

// @todo clean
function template_wiki_edit_box($action, $titlebox = false)
{
	global $txt, $context;
	echo '<form id="wikiEditBox" action="', $action, '" method="post">';
	if($titlebox)
		echo '<h2>', $txt['title'], ':</h2><input type="text" value=\'', (isset($context['wiki']['page_data']['realname']) ? addslashes($context['wiki']['page_data']['realname']) : addslashes($_REQUEST['t'])), '\' name="t" />';
	else
		echo '<h2>', $txt['title'], ':</h2>', (isset($context['wiki']['page_data']['realname']) ? addslashes($context['wiki']['page_data']['realname']) : addslashes($_REQUEST['t']));
	echo '<div id="bbc"></div><div id="smileys"></div>';
	// @todo Session checking
	template_control_richedit('content', 'smileys', 'bbc');
	echo '<input type="submit" tabindex="2" value="', $txt['wiki_save_button'], '" /></form>';
}

function template_wiki_namespace_history()
{
    global $txt, $scripturl, $context;
	
	// Do some pagination
	echo constructPageIndex(wiki_link('History:' . $_REQUEST['p']), $_REQUEST['start'], $context['wiki_total_revisions'], 20);
	
	echo '
	<div class="tborder topic_table">
		<table class="table_grid" cellspacing="0" width="100%">
			<thead>
				<tr class="catbg"><th scope="col" class="first_th">Page</th><th scope="col" class="last_th">Time</th></tr>
			</thead>
			<tbody>';
	foreach($context['wiki_recent'] as $recent)
	{
		echo '
				<tr><td class="windowbg"><a href="', wiki_link('Revision:' . $recent['realname'], 'r=' . $recent['id_revision']), '">', htmlspecialchars($recent['realname']), '</a></td><td class="windowbg2">', timeformat($recent['time']), '</td></tr>';
	}
	
	echo '
			</tbody>
		</table>
	</div>';
}

function template_wiki_special_namespace_search()
{
	global $context;
	echo '
	<div class="tborder topic_table">
		<table class="table_grid" cellspacing="0" width="100%">
			<thead>
				<tr class="catbg"><th scope="col" class="first_th">Page</th><th scope="col" class="last_th">time</th></tr>
			</thead>
			<tbody>';
	foreach($context['wiki_search_results'] as $recent)
	{
		echo '
				<tr><td class="windowbg"><a href="', wiki_link($recent['realname']), '">', htmlspecialchars($recent['realname']), '</a></td><td class="windowbg2">', timeformat($recent['time']), '</td></tr>';
	}
	
	echo '
			</tbody>
		</table>
	</div>';
}

function template_wiki_special_namespace_create()
{
    global $txt, $scripturl, $context;
	template_wiki_edit_box(wiki_link('create:WikiSpecial'), true);
}

// @todo constructPageIndex
function template_wiki_special_namespace_recent()
{
    global $txt, $scripturl, $context;
	
	// Do some pagination
	echo constructPageIndex(wiki_link('Recent:WikiSpecial'), $_REQUEST['start'], $context['wiki_total_revisions'], 20);
	
	echo '
	<div class="tborder topic_table">
		<table class="table_grid" cellspacing="0" width="100%">
			<thead>
				<tr class="catbg"><th scope="col" class="first_th">Page</th><th scope="col" class="last_th">Time</th></tr>
			</thead>
			<tbody>';
	foreach($context['wiki_recent'] as $recent)
	{
		echo '
				<tr><td class="windowbg"><a href="', wiki_link($recent['realname']), '">', htmlspecialchars($recent['realname']), '</a></td><td class="windowbg2">', timeformat($recent['time']), '</td></tr>';
	}
	
	echo '
			</tbody>
		</table>
	</div>';
}

function template_wiki_make_toolbar($tools)
{
    echo '<span class="float_right">';
	foreach($tools as $act => $tool)
	{
		if($tool['show'])	
			echo '<a href="', str_replace('page_string', $_REQUEST['p'], $tool['url']), '">', create_button($tool['image'], $tool['name'], $tool['name']), '</a>';
	}
	echo '</span>';
}

function template_wiki_copyright()
{
	echo '<div class="centertext smalltext"><a href="https://github.com/Sorck/Simple-Wiki">SimpleWiki &copy; 2010-2013, James Robson</a></div>';
}
