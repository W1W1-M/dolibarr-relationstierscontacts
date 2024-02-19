<?php
/* Copyright (C) 2018-2024	Easya Solutions     <support@easya.solutions>
 * Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
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
 *  \file       htdocs/relationstierscontacts/relationcontact.php
 *  \ingroup    relationstierscontacts
 *  \brief      Page of relations of contacts
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');
dol_include_once('/relationstierscontacts/class/relationcontact.class.php');
dol_include_once('/relationstierscontacts/class/html.formrelationstierscontacts.class.php');

$langs->loadLangs(array("relationstierscontacts@relationstierscontacts", "companies"));

$mesg=''; $error=0; $errors=array();

$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');

$idRelationContact = GETPOST('id_relationcontact', 'int');

$object = new Contact($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id);
$objcanvas=null;
$canvas = (!empty($object->canvas) ? $object->canvas : GETPOST("canvas"));
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('contact', 'contactcard', $canvas);
}

// Security check
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe', '', '', 'rowid', $objcanvas); // If we create a contact with no company (shared contacts), no check on write permission

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('relationcontact'));




/*
 * Actions
 */

$relationContact = new RelationContact($db);
if ($idRelationContact > 0) {
	$relationContact->fetch($idRelationContact);
}

$parameters=array('id'=>$id, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: " . $backtopage);
		exit;
	}

	// create relation contact
	if ($action == 'add' && $user->rights->relationstierscontacts->relationcontact->creer) {
		$relationContact->fk_socpeople_a = GETPOST('contact1_id', 'int');
		$relationContact->fk_socpeople_b = GETPOST('contact2_id', 'int');
		$selectRelationContact = GETPOST('relationcontact');

		$selectRelationContactArray = explode('_', $selectRelationContact);
		if (count($selectRelationContactArray) > 1) {
			$relationContact->fk_c_relationcontact = intval($selectRelationContactArray[0]);
			$relationContact->sens = intval($selectRelationContactArray[1]);
		}

		// Fill array 'array_options' with data from add form
		//$ret = $extrafields->setOptionalsFromPost($extralabels, $relationContact);
		//if ($ret < 0) {
		//    $error++;
		//}

		if (!$error) {
			$db->begin();

			$idRelationContactNew = $relationContact->create($user);
			if ($idRelationContactNew < 0) {
				setEventMessages($relationContact->error, $relationContact->errors, 'errors');
				$error++;
			}
		}

		if (!$error) {
			$db->commit();
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
			exit();
		} else {
			$db->rollback();
			$action = 'create';
		}
	} else {
		if ($action == 'confirm_edit' && $user->rights->relationstierscontacts->relationcontact->creer) {
			// modify relation contact
			$relationContact->fk_socpeople_a = GETPOST('contact1_id', 'int');
			$relationContact->fk_socpeople_b = GETPOST('contact2_id', 'int');
			$selectRelationContact = GETPOST('relationcontact');

			$selectRelationContactArray = explode('_', $selectRelationContact);
			if (count($selectRelationContactArray) > 1) {
				$relationContact->fk_c_relationcontact = intval($selectRelationContactArray[0]);
				$relationContact->sens = intval($selectRelationContactArray[1]);
			}

			if (!$error) {
				$db->begin();

				$idRelationContact = $relationContact->update($user);
				if ($idRelationContact < 0) {
					setEventMessages($relationContact->error, $relationContact->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$db->commit();
				header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
				exit();
			} else {
				$db->rollback();
				$action = 'edit';
			}
		} else {
			if ($action == 'confirm_delete' && $user->rights->relationstierscontacts->relationcontact->supprimer) {
				// delete relation contact

				$ret = $relationContact->delete($user);

				if ($ret < 0) {
					setEventMessages($relationContact->error, $relationContact->errors, 'errors');
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
}


/*
 *  View
 */


$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/contactnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->lastname) {
	$title = $object->lastname;
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst") . ' (' . $langs->trans("SeeAbove") . ')';


if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id) {
		$object = new Contact($db);
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error('', $object->error);
		}
	}
	$objcanvas->assign_values($action, $object->id, $object->ref);    // Set value for templates
	$objcanvas->display_canvas($action);                            // Show template
} else {
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------

	/*
	 * Onglets
	 */
	$head = array();
	if ($id > 0) {
		// Si edition contact deja existant
		$object = new Contact($db);
		$res = $object->fetch($id, $user);
		if ($res < 0) {
			dol_print_error($db, $object->error);
			exit;
		}
		$res = $object->fetch_optionals();
		if ($res < 0) {
			dol_print_error($db, $object->error);
			exit;
		}

		// Show tabs
		$head = contact_prepare_head($object);

		$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
	}

	if (!empty($id)) {
		$formRelationsTiersContacts = new FormRelationsTiersContacts($db);
		$form = $formRelationsTiersContacts->form;
		$contactstatic = new Contact($db);

		/*
		* Fiche en mode visualisation
		*/

		//dol_htmloutput_errors($error,$errors);

		print dol_get_fiche_head($head, 'rtc_relation_contact_tab', $title, -1, 'contact');

		$linkback = '<a href="' . DOL_URL_ROOT . '/contact/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref = '<div class="refidno">';
		if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
			$objsoc = new Societe($db);
			$objsoc->fetch($object->socid);
			// Thirdparty
			$morehtmlref .= $langs->trans('ThirdParty') . ' : ';
			if ($objsoc->id > 0) {
				$morehtmlref .= $objsoc->getNomUrl(1);
			} else {
				$morehtmlref .= $langs->trans("ContactNotLinkedToCompany");
			}
		}
		$morehtmlref .= '</div>';

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';

		$object->info($id);
		print dol_print_object_info($object, 1);

		print '</div>';

		print dol_get_fiche_end();

		print '<br />';

		/*
		 * Add relation contact
		 */
		if ($user->rights->relationstierscontacts->relationcontact->creer) {
			print load_fiche_titre($langs->trans('RTCRelationContactAddTitle'), '', '');
			print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="action" value="' . ($action == 'edit' ? 'confirm_edit' : 'add') . '">';
			print '<input type="hidden" name="id" value="' . $id . '">';
			if ($relationContact->id > 0) {
				print '<input type="hidden" name="id_relationcontact" value="' . $relationContact->id . '">';
			}
			print '<input type="hidden" name="contact1_id" value="' . $id . '">';

			print '<div id="addform" class="div-table-responsive-no-min">';
			print '<table class="noborder" width="100%">';

			print '<tr class="liste_titre">';
			print '<td align="left">';
			print $langs->trans('Relation');
			print '</td>';
			print '<td align="left">';
			print $langs->trans('Contact');
			print '</td>';
			print '<td align="left">';
			print '</td>';
			print '</tr>';

			if ($relationContact->id > 0) {
				if ($relationContact->fk_socpeople_b != $object->id) {
					$selectRelationContactId = $relationContact->fk_c_relationcontact . '_' . $relationContact->sens;
					$selectContactId = $relationContact->fk_socpeople_b;
				} else {
					$sensInverse = $relationContact->sens == 0 ? 1 : 0;
					$selectRelationContactId = $relationContact->fk_c_relationcontact . '_' . $sensInverse;
					$selectContactId = $relationContact->fk_socpeople_a;
				}
			} else {
				$selectRelationContactId = '';
				$selectContactId = '';
			}
			print '<tr class="liste">';
			print '<td>';
			$formRelationsTiersContacts->selectAllRelationContact('relationcontact', $selectRelationContactId, 1, 0, 0, '', 0, 0, 0, '', '', 0, '', 0, 0, 1);
			print '</td>';
			print '<td>';
			$form->select_contacts(0, $selectContactId, 'contact2_id', 1, array($id));
			print '</td>';
			print '<td>';
			if ($action == 'edit') {
				print '<input type="submit" class="button" name="actionedit" value="' . $langs->trans("Edit") . '">';
				print '<input type="submit" class="button" name="actioncancel" value="' . $langs->trans("Cancel") . '">';
			} else {
				print '<input type="submit" class="button" name="actionadd" value="' . $langs->trans("Add") . '">';
			}
			print '</td>';
			print '</tr>';

			print '</table>';
			print '</div>';

			print '</form>';

			print '<br />';
		}


		/*
		 * List of relation contact
		 */
		$optioncss = GETPOST('optioncss', 'alpha');
		$sortfield = GETPOST("sortfield", 'alpha');
		$sortorder = GETPOST("sortorder", 'alpha');
		$page = GETPOST('page', 'int');
		$search_status = GETPOST("search_status", 'int');
		if ($search_status == '') {
			$search_status = 1;
		} // always display activ customer first
		$search_name = GETPOST("search_name", 'alpha');
		$search_addressphone = GETPOST("search_addressphone", 'alpha');

		if (!$sortorder) {
			$sortorder = "ASC";
		}
		if (!$sortfield) {
			$sortfield = "t.lastname";
		}

		if (!empty($conf->clicktodial->enabled)) {
			$user->fetch_clicktodial(); // lecture des infos de clicktodial du user
		}

		$extralabels = $extrafields->fetch_name_optionals_label($contactstatic->table_element);

		$contactstatic->fields=array(
			'relation_label' => array('type'=>'varchar(255)', 'label'=>'RTCRelationContactLabel', 'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>1),
			'name'           => array('type'=>'varchar(128)', 'label'=>'Name',                    'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1),
			'poste'          => array('type'=>'varchar(128)', 'label'=>'PostOfFunction',          'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>20),
			'address'        => array('type'=>'varchar(128)', 'label'=>'Address',                 'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>30),
			'statut'         => array('type'=>'integer',      'label'=>'Status',                  'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'default'=>0, 'index'=>1,  'position'=>40, 'arrayofkeyval'=>array(0=>$contactstatic->LibStatut(0, 1), 1=>$contactstatic->LibStatut(1, 1))),
		);

		// Definition of fields for list
		$arrayfields=array(
			't.relation_label' => array('label'=>'RTCRelationContactLabel', 'checked'=>1, 'position'=>1),
			't.rowid' => array('label'=>"TechnicalID", 'checked'=>(getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')?1:0), 'enabled'=>(getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')?1:0), 'position'=>1),
			't.name' => array('label'=>"Name", 'checked'=>1, 'position'=>10),
			't.poste' => array('label'=>"PostOrFunction", 'checked'=>1, 'position'=>20),
			't.address' => array('label'=>(empty($conf->dol_optimize_smallscreen) ? $langs->trans("Address").' / '.$langs->trans("Phone").' / '.$langs->trans("Email") : $langs->trans("Address")), 'checked'=>1, 'position'=>30),
			't.statut' => array('label'=>"Status", 'checked'=>1, 'position'=>40, 'align'=>'center'),
		);

		// Extra fields
		if (!empty($extrafields->attributes[$contactstatic->table_element]['label'])) {
			if (is_array($extrafields->attributes[$contactstatic->table_element]['label']) && count($extrafields->attributes[$contactstatic->table_element]['label'])) {
				foreach ($extrafields->attributes[$contactstatic->table_element]['label'] as $key => $val) {
					// Load language if required
					if (!empty($extrafields->attributes[$contactstatic->table_element]['langfile'][$key])) {
						$langs->load($extrafields->attributes[$contactstatic->table_element]['langfile'][$key]);
					}

					if (!empty($extrafields->attributes[$contactstatic->table_element]['list'][$key])) {
						$arrayfields["ef." . $key] = array(
							'label' => $extrafields->attributes[$contactstatic->table_element]['label'][$key],
							'checked' => (($extrafields->attributes[$contactstatic->table_element]['list'][$key] < 0) ? 0 : 1),
							'position' => $extrafields->attributes[$contactstatic->table_element]['pos'][$key],
							'enabled' => (abs($extrafields->attributes[$contactstatic->table_element]['list'][$key]) != 3 && $extrafields->attributes[$contactstatic->table_element]['perms'][$key])
						);
					}
				}
			}
		}

		// Initialize array of search criterias
		$search = array();
		foreach ($contactstatic->fields as $key => $val) {
			if (GETPOST('search_' . $key, 'alpha')) {
				$search[$key] = GETPOST('search_' . $key, 'alpha');
			}
		}
		$search_array_options = $extrafields->getOptionalsFromPost($extralabels, '', 'search_');

		// Purge search criteria
		// All tests are required to be compatible with all browsers
		if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
			$search_status = '';
			$search_name = '';
			$search_addressphone = '';
			$search_array_options = array();

			foreach ($contactstatic->fields as $key => $val) {
				$search[$key] = '';
			}

			$toselect = '';
		}

		$contactstatic->fields = dol_sort_array($contactstatic->fields, 'position');
		$arrayfields = dol_sort_array($arrayfields, 'position');

		// change selected fields
		include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

		print "\n";

		//$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ContactsForCompany") : $langs->trans("ContactsAddressesForCompany"));
		$title = $langs->trans("RTCRelationContactListTitle");
		print load_fiche_titre($title, '', '');

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
		print '<input type="hidden" name="page" value="' . $page . '">';

		$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
		$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields
		//if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

		print '<div class="div-table-responsive">';        // You can use div-table-responsive-no-min if you dont need reserved height for your table
		print "\n" . '<table class="tagtable liste">' . "\n";

		$param = "id=" . urlencode($object->id);
		if ($search_status != '') {
			$param .= '&search_status=' . urlencode($search_status);
		}
		if ($search_name != '') {
			$param .= '&search_name=' . urlencode($search_name);
		}
		if ($optioncss != '') {
			$param .= '&optioncss=' . urlencode($optioncss);
		}
		// Add $param from extra fields
		$extrafieldsobjectkey = $contactstatic->table_element;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';


		// Fields title search
		// --------------------------------------------------------------------
		print '<tr class="liste_titre">';
		//if (! empty($arrayfields['relation_label']['checked']))    print '<td class="liste_titre"></td>';
		foreach ($contactstatic->fields as $key => $val) {
			$align = '';
			if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
				$align .= ($align ? ' ' : '') . 'center';
			}
			if (in_array($val['type'], array('timestamp'))) {
				$align .= ($align ? ' ' : '') . 'nowrap';
			}
			if ($key == 'status' || $key == 'statut') {
				$align .= ($align ? ' ' : '') . 'center';
			}
			if (!empty($arrayfields['t.' . $key]['checked'])) {
				print '<td class="liste_titre' . ($align ? ' ' . $align : '') . '">';
				if (in_array($key, array('lastname', 'firstname'))) {
					print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key]) . '">';
				} elseif (in_array($key, array('statut'))) {
					print $form->selectarray('search_status', array(
						'-1' => '',
						'0' => $contactstatic->LibStatut(0, 1),
						'1' => $contactstatic->LibStatut(1, 1)
					), $search_status);
				}
				print '</td>';
			}
		}
		// Extra fields
		$extrafieldsobjectkey = $contactstatic->table_element;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields);
		$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $contactstatic);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Action column
		print '<td class="liste_titre" align="right">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
		print '</tr>' . "\n";

		// Fields title label
		// --------------------------------------------------------------------
		print '<tr class="liste_titre">';
		//if (! empty($arrayfields['relation_label']['checked'])) print getTitleFieldOfList($arrayfields['relation_label']['label'], 0, $_SERVER['PHP_SELF'], 'relation_label', '', $param, '', $sortfield, $sortorder, ' ')."\n";
		foreach ($contactstatic->fields as $key => $val) {
			$align = '';
			if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
				$align .= ($align ? ' ' : '') . 'center';
			}
			if (in_array($val['type'], array('timestamp'))) {
				$align .= ($align ? ' ' : '') . 'nowrap';
			}
			if ($key == 'status' || $key == 'statut') {
				$align .= ($align ? ' ' : '') . 'center';
			}
			if (!empty($arrayfields['t.' . $key]['checked'])) {
				print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($align ? 'class="' . $align . '"' : ''), $sortfield, $sortorder, $align . ' ') . "\n";
			}
		}
		// Extra fields
		$extrafieldsobjectkey = $contactstatic->table_element;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
		// Hook fields
		$parameters = array(
			'arrayfields' => $arrayfields,
			'param' => $param,
			'sortfield' => $sortfield,
			'sortorder' => $sortorder
		);
		$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ') . "\n";
		print '</tr>' . "\n";

		$sql1 = 'SELECT';
		$sql1 .= ' t.rowid, t.lastname, t.firstname, t.fk_pays, t.civility, t.poste, t.phone, t.phone_mobile, t.phone_perso, t.fax, t.email, t.socialnetworks, t.statut, t.photo, t.address, t.zip, t.town';
		$sql1 .= ', rc.rowid as rc_id, IF (rc.sens = 0, crc.label_a_b, crc.label_b_a) as relation_label';
		$sql1 .= ' FROM ' . MAIN_DB_PREFIX . 'relationcontact as rc';
		$sql1 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as spa ON spa.rowid = rc.fk_socpeople_a';
		$sql1 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as t ON t.rowid = rc.fk_socpeople_b';
		$sql1 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople_extrafields as ef ON (t.rowid = ef.fk_object)';
		$sql1 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_relationcontact as crc ON crc.rowid = rc.fk_c_relationcontact';
		$sql1 .= ' WHERE rc.fk_socpeople_a = ' . $object->id;
		if ($search_status != '' && $search_status != '-1') {
			$sql1 .= " AND t.statut = " . $db->escape($search_status);
		}
		if ($search_name) {
			$sql1 .= natural_search(array('t.lastname', 't.firstname'), $search_name);
		}
		// Add where from extra fields
		$extrafieldsobjectkey = $contactstatic->table_element;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

		$sql2 = 'SELECT';
		$sql2 .= ' t.rowid, t.lastname, t.firstname, t.fk_pays, t.civility, t.poste, t.phone, t.phone_mobile, t.phone_perso, t.fax, t.email, t.socialnetworks, t.statut, t.photo, t.address, t.zip, t.town';
		$sql2 .= ', rc.rowid as rc_id, IF (rc.sens = 0, crc.label_b_a, crc.label_a_b) as relation_label';
		$sql2 .= ' FROM ' . MAIN_DB_PREFIX . 'relationcontact as rc';
		$sql2 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as t ON t.rowid = rc.fk_socpeople_a';
		$sql2 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as spb ON spb.rowid = rc.fk_socpeople_b';
		$sql2 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople_extrafields as ef ON (t.rowid = ef.fk_object)';
		$sql2 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_relationcontact as crc ON crc.rowid = rc.fk_c_relationcontact';
		$sql2 .= ' WHERE rc.fk_socpeople_b = ' . $object->id;
		if ($search_status != '' && $search_status != '-1') {
			$sql2 .= " AND t.statut = " . $db->escape($search_status);
		}
		if ($search_name) {
			$sql2 .= natural_search(array('t.lastname', 't.firstname'), $search_name);
		}
		// Add where from extra fields
		$extrafieldsobjectkey = $contactstatic->table_element;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

		$sql = 'SELECT';
		$sql .= ' t.rowid, t.lastname, t.firstname, t.fk_pays as country_id, t.civility, t.poste, t.phone as phone_pro, t.phone_mobile, t.phone_perso, t.fax, t.email, t.socialnetworks, t.statut, t.photo, t.civility as civility_id, t.address, t.zip, t.town';
		$sql .= ', t.rc_id, t.relation_label';
		$sql .= ' FROM (' . $sql1 . ' UNION ' . $sql2 . ') as t';
		if ($sortfield == "t.name") {
			$sql .= " ORDER BY t.lastname $sortorder, t.firstname $sortorder";
		} else {
			$sql .= " ORDER BY $sortfield $sortorder";
		}

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}

		$num = $db->num_rows($result);

		if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x'))) {
			$i = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($result);

				$contactstatic->id = $obj->rowid;
				$contactstatic->ref = $obj->ref;
				$contactstatic->statut = $obj->statut;
				$contactstatic->lastname = $obj->lastname;
				$contactstatic->firstname = $obj->firstname;
				$contactstatic->civility_id = $obj->civility_id;
				$contactstatic->civility_code = $obj->civility_id;
				$contactstatic->poste = $obj->poste;
				$contactstatic->address = $obj->address;
				$contactstatic->zip = $obj->zip;
				$contactstatic->town = $obj->town;
				$contactstatic->phone_pro = $obj->phone_pro;
				$contactstatic->phone_mobile = $obj->phone_mobile;
				$contactstatic->phone_perso = $obj->phone_perso;
				$contactstatic->email = $obj->email;
				$contactstatic->socialnetworks = $obj->socialnetworks;
				$contactstatic->photo = $obj->photo;

				$country_code = getCountry($obj->country_id, 2);
				$contactstatic->country_code = $country_code;

				$contactstatic->setGenderFromCivility();
				$contactstatic->fetch_optionals();

				$relationContactStatic = new RelationContact($db);
				$relationContactStatic->id = $obj->rc_id;

				if (is_array($contactstatic->array_options)) {
					foreach ($contactstatic->array_options as $key => $val) {
						$obj->$key = $val;
					}
				}

				print '<tr class="oddeven">';

				// Relation label
				if (!empty($arrayfields['t.relation_label']['checked'])) {
					print '<td>';
					if ($obj->relation_label) {
						print $obj->relation_label;
					}
					print '</td>';
				}

				// ID
				if (!empty($arrayfields['t.rowid']['checked'])) {
					print '<td>';
					print $contactstatic->id;
					print '</td>';
				}

				// Photo - Name
				if (!empty($arrayfields['t.name']['checked'])) {
					print '<td>';
					print $form->showphoto('contact', $contactstatic, 0, 0, 0, 'photorefnoborder valignmiddle marginrightonly', 'small', 1, 0, 1);
					print $contactstatic->getNomUrl(0, '', 0, '&backtopage=' . urlencode($backtopage));
					print '</td>';
				}

				// Job position
				if (!empty($arrayfields['t.poste']['checked'])) {
					print '<td>';
					if ($obj->poste) {
						print $obj->poste;
					}
					print '</td>';
				}

				// Address - Phone - Email
				if (!empty($arrayfields['t.address']['checked'])) {
					print '<td>';
					print $contactstatic->getBannerAddress('contact', $object);
					print '</td>';
				}

				// Status
				if (!empty($arrayfields['t.statut']['checked'])) {
					print '<td align="center">' . $contactstatic->getLibStatut(5) . '</td>';
				}

				// Extra fields
				$extrafieldsobjectkey = $contactstatic->table_element;
				include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';

				// Actions
				print '<td align="right">';

				// Add to agenda
				if (!empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create) {
					print '<a href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&actioncode=&contactid=' . $obj->rowid . '&socid=' . $object->socid . '&backtopage=' . urlencode($backtopage) . '">';
					print img_object($langs->trans("Event"), "action");
					print '</a> &nbsp; ';
				}

				// Edit contact
				if ($user->rights->societe->contact->creer) {
					print '<a href="' . DOL_URL_ROOT . '/contact/card.php?action=edit&id=' . $obj->rowid . '&backtopage=' . urlencode($backtopage) . '">';
					print img_edit('default', 0, 'class="pictoedit fa fa-address-card marginleftonly valignmiddle"');
					print '</a>';
				}

				// Edit relation contact
				if ($user->rights->relationstierscontacts->relationcontact->creer) {
					print '<a href="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $object->id . '&id_relationcontact=' . $relationContactStatic->id . '">';
					print img_edit($langs->trans('RTCRelationContactModify'));
					print '</a>';
				}

				// Delete relation contact
				if ($user->rights->relationstierscontacts->relationcontact->supprimer) {
					print '<a href="' . $_SERVER['PHP_SELF'] . '?action=confirm_delete&id=' . $object->id . '&id_relationcontact=' . $relationContactStatic->id . '">';
					print img_delete($langs->trans('RTCRelationContactDelete'));
					print '</a>';
				}

				print '</td>';

				print "</tr>\n";
				$i++;
			}
		} else {
			$colspan = 1;
			foreach ($arrayfields as $key => $val) {
				if (!empty($val['checked'])) {
					$colspan++;
				}
			}
			print '<tr><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("None") . '</td></tr>';
		}

		print "\n</table>\n";
		print '</div>';

		print '</form>' . "\n";
	}
}


// End of page
llxFooter();
$db->close();
