SimpleWiki Copytight (C), 2010-2012, James Robson

SimpleWiki is available in a working form for SMF 2 (please see the thread on the SimpleMachines community forums)
This repo is for the development of SimpleWiki (smWiki) for smCore.

SimpleWiki uses the following design principle:
* Everything is a page

Yes, everything is accessed as if it were a wiki page. For example, the following accesses the Main Page:
'''http://example.org/wiki/Main_Page'''
The following will move that page:
'''http://example.org/wiki/move:Main_Page'''
To see it's history:
'''http://example.org/wiki/history:Main_Page'''

We are still looking for opinions as to whether to use colons to separate namespaces or a slash (example '''http://example.org/wiki/move/Main_Page''')

Credits:
* PHP QR Code is distributed under LGPL 3 Copyright (C) 2010 Dominik Dzienia

TODO:
* Remove all views of the form wiki_*.html
* Moves from .html to .twig templates
* Make the C, U and D of CRUD possible
* Create a way to search the wiki
* Remove all old controllers
* Gut out the whole system so that the control logic is done in GenericLoader rather than PageFactory