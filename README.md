SimpleWiki Copytight (C), 2010-2012, James Robson

SimpleWiki has two different versions:
* SimpleWiki 2 for SMF2
* smWiki for smCore

The latter is experimental due to a non-finalised smCore API. SimpleWiki is of Alpha quality but it does work on my test system.

SimpleWiki uses the following design principle:
* Everything is a page

Yes, everything is accessed as if it were a wiki page. For example, the following accesses the Main Page:
'''http://example.org/wiki/Main_Page'''
The following will move that page:
'''http://example.org/wiki/move:Main_Page'''
To see it's history:
'''http://example.org/wiki/history:Main_Page'''
and so on.
We also have specialist functions that are namespaces of the 'WikiSpecial' page such as looking at recent edits:
'''http://example.org/wiki/recent:WikiSpecial'''
Or searching the Wiki:
'''http://example.org/wiki/search:WikiSpecial'''

We are still looking for opinions as to whether to use colons to separate namespaces or a slash (example '''http://example.org/wiki/move/Main_Page''')

SimpleWiki for SMF2 is designed to make as few changes to the SMF2 code as possible. To that end we currently change a single file - we add our language strings to the end of Modifications.[lang].php - but this shouldn't cause any problems because it just adds straight to the end of the file. We hope to move to no edits at all in the near future.

Credits:
* PHP QR Code is distributed under LGPL 3 Copyright (C) 2010 Dominik Dzienia

TODO (SMF2):
* Unit tests
* Single function for finding history (recent:WikiSpecial and history:PageName)
* Cache everything

TODO (smCore):
* Remove all views of the form wiki_*.html
* Moves from .html to .twig templates
* Make the C, U and D of CRUD possible
* Create a way to search the wiki
* Remove all old controllers
* Gut out the whole system so that the control logic is done in GenericLoader rather than PageFactory
