<?php
/* Copyright (C) 2018-2024	 Easya Solutions        <support@easya.solutions>
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 *	\file       htdocs/relationstierscontacts/lib/relationstierscontacts.lib.php
 * 	\ingroup	relationstierscontacts
 *	\brief      Functions for the module relationstierscontacts
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function relationstierscontacts_admin_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/relationstierscontacts/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/relationstierscontacts/admin/dictionaries.php", 1);
	$head[$h][1] = $langs->trans("Dictionary");
	$head[$h][2] = 'dictionaries';
	$h++;

	$head[$h][0] = dol_buildpath("/relationstierscontacts/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	$head[$h][0] = dol_buildpath("/relationstierscontacts/admin/changelog.php", 1);
	$head[$h][1] = $langs->trans("OpenDsiChangeLog");
	$head[$h][2] = 'changelog';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'relationstierscontacts_admin');

	return $head;
}

/**
 * Get contact ids by third-party
 *
 * @param	int		$socid		Id of company
 * @return	array
 */
function relationtierscontacts_get_contact_ids_by_tiers($socid)
{
	global $langs, $db;

	require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

	dol_include_once('/relationstierscontacts/class/relationtiers.class.php');

	$sql = "SELECT";
	$sql .= " t.rowid";
	$sql .= ", t.fk_socpeople";
	$sql .= " FROM " . MAIN_DB_PREFIX . "relationtiers as t";
	$sql .= " WHERE t.fk_soc = " . $socid;

	$contact_ids = array();

	$resql = $db->query($sql);

	if ($resql) {
		$numrows = $db->num_rows($resql);

		while ($numrows-- > 0) {
			$relation_tiers_raw = $db->fetch_object($resql);

			if (!empty($contact_ids[$relation_tiers_raw->fk_socpeople])) {
				continue;
			}

			$contact_ids[$relation_tiers_raw->fk_socpeople] = $relation_tiers_raw->fk_socpeople;
		}

		$db->free($resql);
	}

	return $contact_ids;
}
