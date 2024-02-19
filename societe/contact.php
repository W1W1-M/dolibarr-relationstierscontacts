<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2013  Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2024	Easya Solutions     	<support@easya.solutions>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
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
 *  \file       htdocs/relationstierscontacts/societe/contact.php
 *  \ingroup    relationstierscontacts
 *  \brief      Page of contacts of thirdparties
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');
dol_include_once('/relationstierscontacts/class/relationtiers.class.php');
dol_include_once('/relationstierscontacts/class/html.formrelationstierscontacts.class.php');

$langs->loadLangs(array("companies", "commercial", "bills", "banks", "users", "relationstierscontacts@relationstierscontacts"));
if (! empty($conf->categorie->enabled)) $langs->load("categories");
if (! empty($conf->incoterm->enabled)) $langs->load("incoterm");
if (! empty($conf->notification->enabled)) $langs->load("mails");

$mesg=''; $error=0; $errors=array();

$action = GETPOST('action', 'aZ09') ?: 'view';
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm');
$socid = GETPOSTINT('socid') ?: GETPOSTINT('id');

$idRelationTiers = GETPOST('id_relationtiers', 'int');

if ($user->socid) $socid=$user->socid;
if (empty($socid) && $action == 'view') $action='create';

$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartycontact','globalcard'));

if ($action == 'view' && $object->fetch($socid) <= 0) {
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($socid);
$canvas = $object->canvas ? $object->canvas : GETPOST("canvas");
$objcanvas=null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', $objcanvas);


/*
 * Actions
 */

$relationTiers = new RelationTiers($db);
if ($idRelationTiers > 0) {
	$relationTiers->fetch($idRelationTiers);
}

$parameters = array('id' => $socid, 'objcanvas' => $objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		$action = '';
		if (!empty($backtopage)) {
			header("Location: " . $backtopage);
			exit;
		}
	}

	// create relation thirdparty
	if ($action == 'relation_confirm_create' && $confirm == 'yes' && $user->rights->relationstierscontacts->relationtiers->creer) {
		$relationTiers->fk_soc = $socid;
		$relationTiers->fk_socpeople = GETPOST('relationtiers_socpeople', 'int');
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

		if (!$error) {
			$db->begin();

			$idRelationTiersNew = $relationTiers->create($user);
			if ($idRelationTiersNew < 0) {
				setEventMessages($relationTiers->error, $relationTiers->errors, 'errors');
				$error++;
			}
		}

		if (!$error) {
			$db->commit();
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $socid);
			exit();
		} else {
			$db->rollback();
			$action = 'relation_create';
		}
	} else {
		if ($action == 'relation_confirm_edit' && $confirm == 'yes' && $user->rights->relationstierscontacts->relationtiers->creer) {
			// modify relation thirdparty
			$relationTiers->fk_soc = $socid;
			$relationTiers->fk_socpeople = GETPOST('relationtiers_socpeople', 'int');
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

			if (!$error) {
				$db->begin();

				$idRelationTiers = $relationTiers->update($user);
				if ($idRelationTiers < 0) {
					setEventMessages($relationTiers->error, $relationTiers->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$db->commit();
				header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $socid);
				exit();
			} else {
				$db->rollback();
				$action = 'relation_edit';
			}
		} else {
			if ($action == 'relation_confirm_delete' && $confirm == 'yes' && $user->rights->relationstierscontacts->relationtiers->supprimer) {
				// delete relation thirdparty
				$ret = $relationTiers->delete($user);

				if ($ret < 0) {
					setEventMessages($relationTiers->error, $relationTiers->errors, 'errors');
					$error++;
				}

				if (!$error) {
					$db->commit();
				} else {
					$db->rollback();
				}
				$action = '';
			}
		}
	}

	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';
}


/*
 *  View
 */

$formRelationsTiersContacts = new FormRelationsTiersContacts($db);
$form = $formRelationsTiersContacts->form;

if ($socid > 0 && empty($object->id)) {
	$result = $object->fetch($socid);
	if ($result <= 0) {
		dol_print_error('', $object->error);
	}
}

$title = $langs->trans("ThirdParty");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = $object->name . " - " . $langs->trans('Card');
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst") . ' (' . $langs->trans("SeeAbove") . ')';


if (!empty($object->id)) $res=$object->fetch_optionals();
//if ($res < 0) { dol_print_error($db); exit; }


$head = societe_prepare_head($object);

print dol_get_fiche_head($head, 'rtc_relation_tiers_tab', $langs->trans("ThirdParty"), 0, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 'arearefnobottom');

print dol_get_fiche_end();

print '<br>';

if ($action != 'presend') {
	$formconfirm = '';

	// Confirm relation create or modify
	if (($action == 'relation_create' || $action == 'relation_edit') && $user->rights->relationstierscontacts->relationtiers->creer) {
		$formConfirmQuestion = array();

		// hidden fields
		if ($action == 'relation_edit') {
			$formConfirmTitle = $langs->trans('RTCRelationTiersModify');
			$formConfirmAction = 'relation_confirm_edit';
			$formConfirmQuestion[] = array(
				'type' => 'hidden',
				'name' => 'id_relationtiers',
				'value' => $relationTiers->id
			);
		} else {
			$formConfirmTitle = $langs->trans('RTCRelationTiersCreate');
			$formConfirmAction = 'relation_confirm_create';
		}

		// relation label
		$formConfirmSelectRelation = $formRelationsTiersContacts->selectAllRelationTiers('relationtiers', $relationTiers->fk_c_relationtiers, 1, 0, 0, '', 0, 0, 0, '', '', 0, '', 0, 0, 0);
		$formConfirmQuestion[] = array(
			'label' => $langs->trans('RTCRelationTiersLabel'),
			'type' => 'other',
			'name' => 'relationtiers',
			'value' => $formConfirmSelectRelation
		);

		// contact
		$formConfirmSelectContacts = $form->selectcontacts(0, $relationTiers->fk_socpeople, 'relationtiers_socpeople', 1, '', '', 0, 'minwidth300');
		$formConfirmQuestion[] = array(
			'label' => $langs->trans('RTCRelationTiersSocpeople'),
			'type' => 'other',
			'name' => 'relationtiers_socpeople',
			'value' => $formConfirmSelectContacts
		);

		// date start
		$formConfirmDateDebut = $form->selectDate($relationTiers->date_debut, 'relationtiers_datedebut_', 0, 0, 0, '', 1, 1);
		$formConfirmQuestion[] = array('name' => 'relationtiers_datedebut_day');
		$formConfirmQuestion[] = array('name' => 'relationtiers_datedebut_month');
		$formConfirmQuestion[] = array('name' => 'relationtiers_datedebut_year');
		$formConfirmQuestion[] = array(
			'label' => $langs->trans('RTCRelationTiersDateStartLabel'),
			'type' => 'other',
			'name' => 'relationtiers_datedebut_',
			'value' => $formConfirmDateDebut
		);

		// date end
		$formConfirmDateFin = $form->selectDate($relationTiers->date_fin, 'relationtiers_datefin_', 0, 0, 0, '', 1, 1);
		$formConfirmQuestion[] = array('name' => 'relationtiers_datefin_day');
		$formConfirmQuestion[] = array('name' => 'relationtiers_datefin_month');
		$formConfirmQuestion[] = array('name' => 'relationtiers_datefin_year');
		$formConfirmQuestion[] = array(
			'label' => $langs->trans('RTCRelationTiersDateEndLabel'),
			'type' => 'other',
			'name' => 'relationtiers_datefin_',
			'value' => $formConfirmDateFin
		);

		// comment
		$formConfirmTextareaComment = '<textarea class="flat quatrevingtpercent" id="relationtiers_commentaire" name="relationtiers_commentaire" rows="2">' . $relationTiers->commentaire . '</textarea>';
		$formConfirmQuestion[] = array(
			'label' => $langs->trans('RTCRelationTiersCommentLabel'),
			'type' => 'other',
			'name' => 'relationtiers_commentaire',
			'value' => $formConfirmTextareaComment
		);

		// main thirdparty
		$formConfirmQuestion[] = array(
			'label' => $langs->trans('RTCRelationTiersMainThirdparty'),
			'type' => 'checkbox',
			'name' => 'relationtiers_is_main_thirdparty',
			'value' => $relationTiers->isMainThirdparty()
		);

		$formconfirm = $formRelationsTiersContacts->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $formConfirmTitle, '', $formConfirmAction, $formConfirmQuestion, 0, 1, 400, 800);
	} else { // Confirm relation delete
		if ($action == 'relation_delete' && $user->rights->relationstierscontacts->relationtiers->supprimer) {
			$formConfirmQuestion = array();
			$formConfirmQuestion[] = array(
				'type' => 'hidden',
				'name' => 'id_relationtiers',
				'value' => $relationTiers->id
			);
			$formconfirm = $formRelationsTiersContacts->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RTCRelationTiersDelete'), $langs->trans('RTCRelationTiersConfirmDelete'), 'relation_confirm_delete', $formConfirmQuestion, 0, 1);
		}
	}

	if (!$formconfirm) {
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}
	}

	// Print form confirm
	print $formconfirm;

	// Contacts list
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
		FormRelationsTiersContacts::show_contacts($conf, $langs, $db, $object, $_SERVER["PHP_SELF"] . '?socid=' . $object->id);
	}

	// Addresses list
	if (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT)) {
		// TODO : add this method to html formrelationstierscontacts.class.php
		//$result = show_addresses($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	}

	// Contacts list of all child company
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
		FormRelationsTiersContacts::show_all_child_contacts($conf, $langs, $db, $object);
	}
}


// End of page
llxFooter();
$db->close();
