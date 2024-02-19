<?php
/* Copyright (C) 2003     	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016	Regis Houssin        	<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/relationstierscontacts/core/substitutions/functions_relationstierscontacts.lib.php
 *	\brief      Substitutions functions for module relationstierscontacts
 *	\ingroup    relationstierscontacts
 */

function relationstierscontacts_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters)
{
	global $db, $conf;

	// Tab relation contact
	if ($object->element == 'contact' && $parameters['needforkey'] == 'SUBSTITUTION_RTCRELATIONSCONTACTSLABEL') {
		$langs->load('relationstierscontacts@relationstierscontacts');

		$nbCollections = 0;

		$sql = 'SELECT';
		$sql .= ' rc.rowid';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'relationcontact as rc';
		$sql .= ' WHERE rc.fk_socpeople_a = ' . $object->id;
		$sql .= ' OR rc.fk_socpeople_b = ' . $object->id;
		$sql .= ' GROUP BY rc.rowid';

		$resql = $db->query($sql);
		if ($resql) {
			$nbCollections = $db->num_rows($resql);
		} else {
			dol_print_error($db);
		}

		$substitutionarray['RTCRELATIONSCONTACTSLABEL'] = $langs->trans('RTCRelationContactTabLabel') . ($nbCollections > 0 ? ' <span class="badge">' . ($nbCollections) . '</span>' : '');
	}

	if ($object->element == 'contact' && $parameters['needforkey'] == 'SUBSTITUTION_RTCRELATIONSTIERSLABEL') {
		$langs->load('relationstierscontacts@relationstierscontacts');

		$nbCollections = 0;

		$sql = 'SELECT';
		$sql .= ' rt.rowid';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'relationtiers as rt';
		$sql .= ' WHERE rt.fk_socpeople = ' . $object->id;
		$sql .= ' GROUP BY rt.rowid';

		$resql = $db->query($sql);
		if ($resql) {
			$nbCollections = $db->num_rows($resql);
		} else {
			dol_print_error($db);
		}

		$substitutionarray['RTCRELATIONSTIERSLABEL'] = $langs->trans('RTCRelationTiersThirdparty') . ($nbCollections > 0 ? ' <span class="badge">' . ($nbCollections) . '</span>' : '');
	}

	// Tab relation thirdparty
	if ($object->element == 'societe' && $parameters['needforkey'] == 'SUBSTITUTION_RTCRELATIONSTIERSLABEL') {
		$langs->load('relationstierscontacts@relationstierscontacts');

		$nbCollections = 0;

		$sql = 'SELECT';
		$sql .= ' rt.rowid';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'relationtiers as rt,' . MAIN_DB_PREFIX . 'socpeople as p';
		$sql .= ' WHERE rt.fk_soc = ' . $object->id;
		$sql .= ' AND rt.fk_socpeople = p.rowid';
		$sql .= ' AND p.statut = 1'; // Limit count to Active Contacts!
		$sql .= ' GROUP BY rt.rowid';

		$resql = $db->query($sql);
		if ($resql) {
			$nbCollections = $db->num_rows($resql);
		} else {
			dol_print_error($db);
		}

		$substitutionarray['RTCRELATIONSTIERSLABEL'] = $langs->trans('ContactsAddresses') . ($nbCollections > 0 ? ' <span class="badge">' . ($nbCollections) . '</span>' : '');
	}
}
