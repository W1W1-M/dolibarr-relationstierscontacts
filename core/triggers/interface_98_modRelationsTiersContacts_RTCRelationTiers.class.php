<?php
/* Copyright (C) 2006-2011	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2011      	Regis Houssin        	<regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2014	Marcos García        	<marcosgdf@gmail.com>
 * Copyright (C) 2018-2024	Easya Solutions			<support@easya.solutions>
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
 *  \file       htdocs/core/triggers/interface_98_modRelationsTiersContacts_RTCRelationTiers.class.php
 *  \ingroup    relationstierscontacts
 *  \brief      File of class of triggers for relationstierstiers module
 */
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');


/**
 *  Class of triggers for relationstierscontacts module to manage thirdparties
 */
class InterfaceRTCRelationTiers extends DolibarrTriggers
{
	public $family = 'relationstierscontacts';
	public $description = "Triggers of this module RelationsTiersContacts to manage relation thirdparty.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'technic';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string    $action Event action code
	 * @param Object    $object Object
	 * @param User      $user   Object user
	 * @param Translate $langs  Object langs
	 * @param conf      $conf   Object conf
	 * @return int                        <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->relationstierscontacts->enabled)) {
			return 0; // Module not active, we do nothing
		}

		// Contact create
		if ($action == 'CONTACT_CREATE') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

			dol_include_once('/relationstierscontacts/class/relationtiers.class.php');

			$langs->load('relationstierscontacts@relationstierscontacts');

			// contact thirdparty is filled and relation is mandatory
			if ($object->socid > 0) {
				$relationTiers = new RelationTiers($this->db);

				$relationTiers->fk_soc = $object->socid;
				$relationTiers->fk_socpeople = $object->id;
				$relationTiers->fk_c_relationtiers = GETPOST('relationtiers', 'int');

				$relationTiersDateDebut = 0;
				if (GETPOST('relationtiers_datedebut_')) {
					$relationTiersDateDebut = dol_mktime(0, 0, 0, GETPOST('relationtiers_datedebut_month', 'int'), GETPOST('relationtiers_datedebut_day', 'int'), GETPOST('relationtiers_datedebut_year', 'int'));
				}
				$relationTiers->date_debut = $relationTiersDateDebut;

				$relationTiersDateFin = 0;
				if (GETPOST('relationtiers_datefin_')) {
					$relationTiersDateFin = dol_mktime(0, 0, 0, GETPOST('relationtiers_datefin_month', 'int'), GETPOST('relationtiers_datefin_day', 'int'), GETPOST('relationtiers_datefin_year', 'int'));
				}
				$relationTiers->date_fin = $relationTiersDateFin;

				$relationTiers->commentaire = GETPOST('relationtiers_commentaire');

				$relationTiers->is_main_thirdparty = GETPOST('relationtiers_is_main_thirdparty') ? true : false;

				// create relation thirdparty
				$ret = $relationTiers->create($user);

				if ($ret < 0) {
					$object->errors = array_merge($object->errors, $relationTiers->errors);
				}

				if ($ret < 0) {
					return -1;
				} else {
					return 1;
				}
			}
		} else {
			// Contact modify
			if ($action == 'CONTACT_MODIFY') {
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

				dol_include_once('/relationstierscontacts/class/relationtiers.class.php');

				$langs->load('relationstierscontacts@relationstierscontacts');

				$idRelationTiers = GETPOST('id_relationtiers', 'int');

				if ($idRelationTiers > 0) {
					$relationTiers = new RelationTiers($this->db);
					$relationTiers->fetch($idRelationTiers);

					$relationTiers->fk_socpeople = $object->id;
					$relationTiers->fk_c_relationtiers = GETPOST('relationtiers', 'int');

					$relationTiersDateDebut = 0;
					if (GETPOST('relationtiers_datedebut_')) {
						$relationTiersDateDebut = dol_mktime(0, 0, 0, GETPOST('relationtiers_datedebut_month', 'int'), GETPOST('relationtiers_datedebut_day', 'int'), GETPOST('relationtiers_datedebut_year', 'int'));
					}
					$relationTiers->date_debut = $relationTiersDateDebut;

					$relationTiersDateFin = 0;
					if (GETPOST('relationtiers_datefin_')) {
						$relationTiersDateFin = dol_mktime(0, 0, 0, GETPOST('relationtiers_datefin_month', 'int'), GETPOST('relationtiers_datefin_day', 'int'), GETPOST('relationtiers_datefin_year', 'int'));
					}
					$relationTiers->date_fin = $relationTiersDateFin;

					$relationTiers->commentaire = GETPOST('relationtiers_commentaire');

					$relationTiers->is_main_thirdparty = GETPOST('relationtiers_is_main_thirdparty') ? true : false;

					// update relation thirdparty
					$ret = $relationTiers->update($user);

					if ($ret < 0) {
						$object->errors = array_merge($object->errors, $relationTiers->errors);
					}

					if ($ret < 0) {
						return -1;
					} else {
						return 1;
					}
				}
			} else {
				// Contact delete
				if ($action == 'CONTACT_DELETE') {
					dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

					dol_include_once('/relationstierscontacts/class/relationtiers.class.php');

					$langs->load('relationstierscontacts@relationstierscontacts');

					// delete all relations of this contact
					$relationTiers = new RelationTiers($this->db);
					$ret = $relationTiers->deleteAllByFkSocpeople($object->id, $user);

					if ($ret < 0) {
						return -1;
					} else {
						return 1;
					}
				}
			}
		}

		return 0;
	}
}
