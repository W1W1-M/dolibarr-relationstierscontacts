<?php
/* Copyright (C) 2007-2015 	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *	\file       htdocs/relationstierscontacts/admin/setup.php
 * 	\ingroup    relationstierscontacts
 *  \brief      Page to setup relationstierscontacts module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include '../../main.inc.php'; // to work if your module directory is into a subdir of root htdocs directory
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include '../../../main.inc.php'; // to work if your module directory is into a subdir of root htdocs directory
}
if (!$res) {
	die("Include of main fails");
}
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');

$langs->load("admin");
$langs->load("relationstierscontacts@relationstierscontacts");
$langs->load("opendsi@relationstierscontacts");

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'alpha');


/*
 *	Actions
 */

if (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code) ? GETPOST($code) : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
		Header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		Header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'set') {
}


/*
 *	View
 */


llxHeader();

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("RelationsTiersContactsSetup"), $linkback, 'title_setup');
print "<br>\n";

$head = relationstierscontacts_admin_prepare_head();

print dol_get_fiche_head($head, 'settings', $langs->trans("Module163019Name"), 0, 'opendsi@relationstierscontacts');

print '<br>';
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set">';

$var = true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

print '</table>';

print dol_get_fiche_end();

print '</form>';

llxFooter();
$db->close();
