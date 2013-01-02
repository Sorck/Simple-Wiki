<?php
/**
 * @file install.php
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
*/

// Make sure our subs file is loaded
add_integration_function('integrate_pre_include', '$sourcedir/SimpleWiki-Subs.php', true);
// Make sure our actions are available
add_integration_function('integrate_actions', 'wiki_integrate_actions', true);
// Make sure we've got our menu button
add_integration_function('integrate_menu_buttons', 'wiki_integrate_menu_buttons', true);
// And some permissions...
add_integration_function('integrate_load_permissions', 'wiki_integrate_load_permissions', true);


// Now setup the database tables
db_extend('packages');

$tables[] = array(
    'table_name' => '{db_prefix}simplewiki_pages',
	'columns' => array(
		array(
			'name' => 'id_page',
			'auto' => true,
			'default' => 0,
			'type' => 'int',
			'size' => 10,
			'null' => false,
		),
		array(
			'name' => 'id_latest_revision',
			'auto' => false,
			'default' => 0,
			'type' => 'int',
			'size' => 11,
			'null' => false,
		),
		array(
			'name' => 'uriname',
			'auto' => false,
			'default' => 0,
			'type' => 'varchar',
			'size' => 128,
			'null' => false,
		),
		array(
			'name' => 'realname',
			'auto' => false,
			'default' => 0,
			'type' => 'varchar',
			'size' => 128,
			'null' => false,
		),
	),
	'indexes' => array(
		array(
			'columns' => array('id_page'),
			'type' => 'primary',
		),
	),
	'if_exists' => 'update',
	'error' => 'fatal',
	'parameters' => array(),
);

$tables[] = array(
	'table_name' => '{db_prefix}simplewiki_revisions',
	'columns' => array(
		array(
			'name' => 'id_revision',
			'auto' => true,
			'default' => 0,
			'type' => 'int',
			'size' => 11,
			'null' => false,
		),
		array(
			'name' => 'id_member',
			'auto' => false,
			'default' => 0,
			'type' => 'int',
			'size' => 8,
			'null' => false,
		),
		array(
			'name' => 'time',
			'auto' => false,
			'default' => 0,
			'type' => 'int',
			'size' => 10,
			'null' => false,
		),
		array(
			'name' => 'name_editor',
			'auto' => false,
			'default' => 0,
			'type' => 'varchar',
			'size' => 80,
			'null' => false,
		),
		array(
			'name' => 'id_page',
			'auto' => false,
			'default' => 0,
			'type' => 'int',
			'size' => 10,
			'null' => false,
		),
		array(
			'name' => 'body',
			'auto' => false,
			//'default' => 0,
			'type' => 'text',
			'null' => false,
		),
	),
	'indexes' => array(
		array(
			'columns' => array('id_revision'),
			'type' => 'primary',
		),
	),
	'if_exists' => 'update',
	'error' => 'fatal',
	'parameters' => array(),
);

foreach ($tables as $row => $table)
	$smcFunc['db_create_table']($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);