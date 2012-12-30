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
*/

function template_main()
{
    global $context, $user_info, $txt;
    echo '<div class="title_bar">
        	<h3 class="titlebg">
			<div id="quick_search" class="align_right"><form action="index.php?action=wiki;sa=search" method="post">
			<input class="input_text" type="text" name="q" value="'.$txt['search'].'" /></form></div>'.$txt['wiki'].'</h3>
	</div><span class="upperframe"><span></span></span>
	<div class="roundframe">'.sprintf($txt['wiki_welcome'], ($user_info['name']?$user_info['name']:$txt['guest'])).'
	</div><span class="lowerframe"><span></span></span>';
	echo '<br /><div id="left_admsection">';
	// @todo Wiki Menu
	echo '</div>';
	echo '<div class="windowbg2" id="main_admsection"><span class="topslice"><span></span></span><div class="content">';
    call_user_func('template_wiki_' . $context['wiki_theme']);
    echo '</div><span class="botslice clear"><span></span></span></div><br class="clear" />';
    template_wiki_copyright();
}

function template_wiki_namespace_view()
{
    global $context, $txt;    
	$tools = array(
		'edit' => array('name'=>'wiki_edit', 'show'=>wikiAllowedTo('edit'), 'url'=>'index.php?action=wiki;sa=edit;p={page:string}', 'image' => 'wiki_edit.png'),
		'history' => array('name'=>'wiki_history', 'show'=>wikiAllowedTo('view_history'), 'url'=>'index.php?action=wiki;sa=history;p={page:string}', 'image' => 'wiki_history.png'),
		'protect' => array('name'=>'wiki_protect_page', 'show'=>wikiAllowedTo('protect'), 'url'=>'index.php?action=wiki;sa=protect;p={page:string}', 'image' => 'wiki_lock.png'),
		'create' => array('name'=>'wiki_create_new_page', 'show'=>wikiAllowedTo('create'), 'url'=>'index.php?action=wiki;sa=create', 'image' => 'wiki_create.png'),
	);

	/*$toolbar = */template_wiki_make_toolbar($tools);
	#echo $toolbar;
	if(function_exists('template_wiki_namespace_'.$context['name_space']))
		call_user_func('template_wiki_namespace_'.$context['name_space']);
	echo $context['wiki']['page_data']['body'];
}

function template_wiki_make_toolbar($tools)
{
    echo '<span class="float_right">';
	foreach($tools as $act => $tool)
	{
		if($tool['show'])	
			echo '<a href="', str_replace('{page:string}', $_REQUEST['p'], $tool['url']), '">', create_button($tool['image'], $tool['name'], $tool['name']), '</a>';
	}
	echo '</span>';
}

function template_wiki_copyright()
{
	echo '<div class="centertext smalltext"><a href="http://simplewiki.co.uk">SimpleWiki &copy; 2010-2012, James Robson</a></div>';
}