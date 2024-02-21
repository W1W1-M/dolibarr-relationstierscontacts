<?php
/* Copyright (C) 2007-2015	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Easya Solutions			<support@easya.solutions>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/relationstierscontacts/admin/about.php
 *		\ingroup    relationstierscontacts
 *		\brief      Page about of relationstierscontacts module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');
dol_include_once('/relationstierscontacts/lib/opendsi_common.lib.php');

$langs->load("admin");
$langs->load("relationstierscontacts@relationstierscontacts");
$langs->load("opendsi@relationstierscontacts");

if (!$user->admin) accessforbidden();


/**
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("RelationsTiersContactsSetup"), $linkback, 'title_setup');
print "<br>\n";

$head=relationstierscontacts_admin_prepare_head();

print dol_get_fiche_head($head, 'changelog', $langs->trans("Module163019Name"), 0, 'opendsi@relationstierscontacts');

$changelog = opendsi_common_getChangeLog('relationstierscontacts');

print '<div class="moduledesclong">'."\n";
print (!empty($changelog) ? $changelog : $langs->trans("NotAvailable"));
print '<div>'."\n";

print dol_get_fiche_end();

llxFooter();
$db->close();
