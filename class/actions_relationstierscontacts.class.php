<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');

/**
 *  \file       htdocs/relationstierscontacts/class/actions_relationstierscontacts.class.php
 *  \ingroup    relationstierscontacts
 *  \brief      File for hooks
 */

class ActionsRelationsTiersContacts
{

    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;


    /**
     * @var array Redirection by default if none redirection founded
     */
    public $redirections = array(
        '/societe/contact.php'   => '/relationstierscontacts/societe/contact.php',
    );


    /**
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;

        if (in_array('contactcard', explode(':', $parameters['context']))) {

            if ($action == 'create') {
                dol_include_once('/relationstierscontacts/class/relationtiers.class.php');
                dol_include_once('/relationstierscontacts/class/html.formrelationstierscontacts.class.php');

                $langs->load('relationstierscontacts@relationstierscontacts');

                $formRelationsTiersContacts = new FormRelationsTiersContacts($this->db);
                $form = $formRelationsTiersContacts->form;

                $out  = '';

                $socid = GETPOST('socid','int');

                $formRelationFieldRequired = '';
                if ($socid > 0) {
                    // id of thirdparty
                    $out .= '<input type="hidden" name="socid" value="' . $socid . '" />';

                    // relation is mandatory if thirdparty id is set
                    $formRelationFieldRequired = ' class="fieldrequired"';
                }

                $relationTiers = new RelationTiers($this->db);

                // Relation Contact/Thirdparty
                $out .= '<tr><td' . $formRelationFieldRequired . '>' . $langs->trans('RTCRelationTiersLabel') . '</td><td>';
                $out .= $formRelationsTiersContacts->selectAllRelationTiers('relationtiers', $relationTiers->fk_c_relationtiers, 1, 0, 0, '', 0, 0, 0, '', '', 0, '', 0, 0, 0, 1);
                $out .= '</td></tr>';

                // Date start
                $out .= '<tr><td>' . $langs->trans('RTCRelationTiersDateStartLabel') . '</td><td>';
                $out .= $form->selectDate($relationTiers->date_debut, 'relationtiers_datedebut_', 0, 0, 1, '', 1, 1);
                $out .= '</td></tr>';

                // Date end
                $out .= '<tr><td>' . $langs->trans('RTCRelationTiersDateEndLabel') . '</td><td>';
                $out .= $form->selectDate($relationTiers->date_fin, 'relationtiers_datefin_', 0, 0, 1, '', 1, 1);
                $out .= '</td></tr>';

                // Comment
                $out .= '<tr><td>' . $langs->trans('RTCRelationTiersCommentLabel') . '</td>';
                $out .= '<td colspan="2">';
                $out .= '<textarea class="flat quatrevingtpercent" id="relationtiers_commentaire" name="relationtiers_commentaire" rows="2">' . $relationTiers->commentaire . '</textarea>';
                $out .= '</td>';
                $out .= '</tr>';

                // Main thirdparty
                $formIsMainThirdpartyChecked = '';
                if ($relationTiers->isMainThirdparty()) {
                    $formIsMainThirdpartyChecked = ' checked="checked"';
                }
                $out .= '<tr><td>' . $langs->trans('RTCRelationTiersMainThirdparty') . '</td><td>';
                $out .= '<input type="checkbox" name="relationtiers_is_main_thirdparty" value="on"' . $formIsMainThirdpartyChecked . ' />';
                $out .= '</td></tr>';

                $hookmanager->resPrint = $out;

            } else if ($action == 'edit') {
                dol_include_once('/relationstierscontacts/class/relationtiers.class.php');
                dol_include_once('/relationstierscontacts/class/html.formrelationstierscontacts.class.php');

                $langs->load('relationstierscontacts@relationstierscontacts');

                $formRelationsTiersContacts = new FormRelationsTiersContacts($this->db);
                $form = $formRelationsTiersContacts->form;

                $out  = '';

                //$socid = GETPOST('socid','int');
                $socid = GETPOSTISSET('socid') ? GETPOST('socid','int') : $object->socid;
                $idRelationTiers = GETPOST('id_relationtiers','int');

                // socid can be empty (set 0 not to update socid in socpeople)
                if ($socid > 0) {
                    $out .= '<input type="hidden" name="socid" value="' . $socid . '" />';
                } else {
                    $out .= '<input type="hidden" name="socid" value="0" />';
                }

                // id relation thirdparty
                $relationTiers = new RelationTiers($this->db);
                if ($idRelationTiers > 0) {
                    $relationTiers->fetch($idRelationTiers);
                    $out .= '<input type="hidden" name="id_relationtiers" value="'. $relationTiers->id . '" />';
                }

                if ($relationTiers->id > 0) {
                    // Relation Contact/Thirdparty
                    $out .= '<tr><td class="fieldrequired">' . $langs->trans('RTCRelationTiersLabel') . '</td><td>';
                    $out .= $formRelationsTiersContacts->selectAllRelationTiers('relationtiers', $relationTiers->fk_c_relationtiers, 1, 0, 0, '', 0, 0, 0, '', '', 0, '', 0, 0, 0, 1);
                    $out .= '</td></tr>';

                    // Date start
                    $out .= '<tr><td>' . $langs->trans('RTCRelationTiersDateStartLabel') . '</td><td>';
                    $out .= $form->selectDate($relationTiers->date_debut, 'relationtiers_datedebut_', 0, 0, 1, '', 1, 1);
                    $out .= '</td></tr>';

                    // Date end
                    $out .= '<tr><td>' . $langs->trans('RTCRelationTiersDateEndLabel') . '</td><td>';
                    $out .= $form->selectDate($relationTiers->date_fin, 'relationtiers_datefin_', 0, 0, 1, '', 1, 1);
                    $out .= '</td></tr>';

                    // Comment
                    $out .= '<tr><td>' . $langs->trans('RTCRelationTiersCommentLabel') . '</td>';
                    $out .= '<td colspan="2">';
                    $out .= '<textarea class="flat quatrevingtpercent" id="relationtiers_commentaire" name="relationtiers_commentaire" rows="2">' . $relationTiers->commentaire . '</textarea>';
                    $out .= '</td>';
                    $out .= '</tr>';

                    // Main thirdparty
                    $formIsMainThirdpartyChecked = '';
                    if ($relationTiers->isMainThirdparty()) {
                        $formIsMainThirdpartyChecked = ' checked="checked"';
                    }
                    $out .= '<tr><td>' . $langs->trans('RTCRelationTiersMainThirdparty') . '</td><td>';
                    $out .= '<input type="checkbox" name="relationtiers_is_main_thirdparty" value="on"' . $formIsMainThirdpartyChecked . ' />';
                    $out .= '</td></tr>';
                }

                $out .= '<script type="text/javascript">';
                $out .= 'jQuery(document).ready(function(){';
                // delete line tr thirdparty with select (add hidden socid input before)
                if (empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
                    $out .= '   jQuery("select#socid").parent().parent().remove();';
                } else {
                    $out .= '   jQuery("#search_socid").parent().parent().remove();';
                }
                $out .= '});';
                $out .= '</script>';

                $hookmanager->resPrint = $out;
            }
        } elseif (in_array('thirdpartycard', explode(':', $parameters['context']))) {
            if ($action == 'create' && !empty($conf->global->THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION)) {
                dol_include_once('/relationstierscontacts/class/relationtiers.class.php');
                dol_include_once('/relationstierscontacts/class/html.formrelationstierscontacts.class.php');

                $langs->load('relationstierscontacts@relationstierscontacts');

                $formRelationsTiersContacts = new FormRelationsTiersContacts($this->db);
                $form = $formRelationsTiersContacts->form;

                $out = '';

                $formRelationFieldRequired = ' class="fieldrequired"';

                $relationTiers = new RelationTiers($this->db);

                // Relation Thirdparty/Contact with class "individualline" to work on change radio button "private"
                $out .= '<tr class="individualline"><td' . $formRelationFieldRequired . '>' . $langs->trans('RTCRelationTiersLabel') . '</td><td>';
                $out .= $formRelationsTiersContacts->selectAllRelationTiers('relationtiers', $relationTiers->fk_c_relationtiers, 1, 0, 0, '', 0, 0, 0, '', '', 0, '', 0, 0, 0);
                $out .= '</td></tr>';

                // Date start with class "individualline" to work on change radio button "private"
                $out .= '<tr class="individualline"><td>' . $langs->trans('RTCRelationTiersDateStartLabel') . '</td><td>';
                $out .= $form->selectDate($relationTiers->date_debut, 'relationtiers_datedebut_', 0, 0, 1, '', 1, 1);
                $out .= '</td></tr>';

                // Date end with class "individualline" to work on change radio button "private"
                $out .= '<tr class="individualline"><td>' . $langs->trans('RTCRelationTiersDateEndLabel') . '</td><td>';
                $out .= $form->selectDate($relationTiers->date_fin, 'relationtiers_datefin_', 0, 0, 1, '', 1, 1);
                $out .= '</td></tr>';

                // Comment with class "individualline" to work on change radio button "private"
                $out .= '<tr class="individualline"><td>' . $langs->trans('RTCRelationTiersCommentLabel') . '</td>';
                $out .= '<td colspan="2">';
                $out .= '<textarea class="flat quatrevingtpercent" id="relationtiers_commentaire" name="relationtiers_commentaire" rows="2">' . $relationTiers->commentaire . '</textarea>';
                $out .= '</td>';
                $out .= '</tr>';

                // Main thirdparty by default
                $formIsMainThirdpartyChecked = ' checked="checked"';
                //$out .= '<tr><td>' . $langs->trans('RTCRelationTiersMainThirdparty') . '</td><td>';
                $out .= '<input type="hidden" name="relationtiers_is_main_thirdparty" value="on"' . $formIsMainThirdpartyChecked . ' />';
                //$out .= '</td></tr>';

                $hookmanager->resPrint = $out;
            }
        }

        return 0;
    }


    /**
     * Overloading the updateSession function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function updateSession($parameters, &$object, &$action, $hookmanager)
    {
        $this->_redirection();

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the afterLogin function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function afterLogin($parameters, &$object, &$action, $hookmanager)
    {
        $this->_redirection();

        return 0; // or return 1 to replace standard code
    }

    private function _redirection()
    {
        $path_src = preg_replace('/^' . preg_quote(DOL_URL_ROOT, '/') . '/i', '', $_SERVER["PHP_SELF"]);

        $target = $first_path = $last_path = '';
        /*if (preg_match('/^\/([^\/]*)\/(.*)/i', $path_src, $matches)) {
            $first_path = strtolower($matches[1]);
            $last_path = strtolower($matches[2]);
        }

        if ($first_path == 'societe') {
			// custom treatment
            switch ($path_src) {
                case '/societe/card.php':
					$target = 'new_custom_path;
                    break;
			}
		}*/

        if (empty($target)) {
            if (isset($this->redirections[$path_src]))
                $target = $this->redirections[$path_src];
        }

        if (!empty($target)) {
            $url = dol_buildpath($target, 1);
            $_SESSION['substitition_post_variables'] = $_POST;
            $params = http_build_query($_GET);
            header("Location: " . $url . (!empty($params) ? '?' . $params : ''));
            exit;
        }

        if (isset($_SESSION['substitition_post_variables'])) {
            $_POST = array_merge($_POST, $_SESSION['substitition_post_variables']);
            unset($_SESSION['substitition_post_variables']);
        }
    }


	/**
	 * Overloading the afterSelectOptions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function afterSelectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user, $db;

		$contexts = explode(':', $parameters['context']);

		if (in_array('actioncard', $contexts) && in_array('selectcontacts', $contexts)) {
			if ($action == 'create' || $action == 'edit'
				&& $parameters['socid'] > 0) {

				// We are on the action/card.php screen, adding an event to the Third party.
				// The afterSelectOptions hook is in the Form::selectcontacts function.
				// We are after the select <option> HTML generation.
				// We are adding the RelationsTiersContacts contacts to the select options, in the $out parameter.
				$out = $parameters['out'];

				$socid = $parameters['socid'];

				$showfunction = $parameters['showfunction'];

				$showsoc = $parameters['showsoc'];

				// Get RelationsTiersContacts contacts for current socid.
				$contact_ids = relationtierscontacts_get_contact_ids_by_tiers( $socid );

				$contactstatic = new Contact( $db );

				foreach( $contact_ids as $contact_id )
				{
					$contactstatic->fetch( $contact_id );

					$out.= '<option value="'.$contact_id.'"';
					$out.= '>';
					$out.= $contactstatic->getFullName($langs);
					if ($showfunction && $contactstatic->poste) $out.= ' ('.$contactstatic->poste.')';
					if (($showsoc > 0) && $contactstatic->company) $out.= ' - ('.$contactstatic->company.')';
					$out.= '</option>';
				}

				$parameters['out'] = $out;
			}
		}

		return 0;
	}

	/**
	 * Overloading the formObjectOptions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function restrictedArea($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user;
		$context = explode(':', $parameters['context']);

		if (in_array('main', $context)) {
			$features = $parameters['features'];
			$objectid = $parameters['objectid'];
			$tableandshare = 'socpeople&societe';
			$feature2 = '';
			$dbt_keyfield = '';
			$dbt_select = $parameters['idtype'];
			$isdraft = 0;

			if ($features != 'contact') {
				return 0;
			}

			if ($dbt_select != 'rowid' && $dbt_select != 'id') $objectid = "'".$objectid."'";

			// Features/modules to check
			$featuresarray = array($features);
			if (preg_match('/&/', $features)) $featuresarray = explode("&", $features);
			elseif (preg_match('/\|/', $features)) $featuresarray = explode("|", $features);

			// More subfeatures to check
			if (!empty($feature2)) $feature2 = explode("|", $feature2);

			// More parameters
			$params = explode('&', $tableandshare);
			$dbtablename = (!empty($params[0]) ? $params[0] : '');
			$sharedelement = (!empty($params[1]) ? $params[1] : $dbtablename);

			$listofmodules = explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL);

			// Check read permission from module
			$readok = 1; $nbko = 0;
			foreach ($featuresarray as $feature) {	// first we check nb of test ko
				$featureforlistofmodule = $feature;
				if ($featureforlistofmodule == 'produit') $featureforlistofmodule = 'product';
				if (!empty($user->socid) && !empty($conf->global->MAIN_MODULES_FOR_EXTERNAL) && !in_array($featureforlistofmodule, $listofmodules)) {	// If limits on modules for external users, module must be into list of modules for external users
					$readok = 0; $nbko++;
					continue;
				}

				if ($feature == 'societe') {
					if (!$user->rights->societe->lire && !$user->rights->fournisseur->lire) { $readok = 0; $nbko++; }
				} elseif ($feature == 'contact') {
					if (!$user->rights->societe->contact->lire) { $readok = 0; $nbko++; }
				} elseif ($feature == 'produit|service') {
					if (!$user->rights->produit->lire && !$user->rights->service->lire) { $readok = 0; $nbko++; }
				} elseif ($feature == 'prelevement') {
					if (!$user->rights->prelevement->bons->lire) { $readok = 0; $nbko++; }
				} elseif ($feature == 'cheque') {
					if (!$user->rights->banque->cheque) { $readok = 0; $nbko++; }
				} elseif ($feature == 'projet') {
					if (!$user->rights->projet->lire && !$user->rights->projet->all->lire) { $readok = 0; $nbko++; }
				} elseif (!empty($feature2)) { 													// This is for permissions on 2 levels
					$tmpreadok = 1;
					foreach ($feature2 as $subfeature) {
						if ($subfeature == 'user' && $user->id == $objectid) continue; // A user can always read its own card
						if (!empty($subfeature) && empty($user->rights->$feature->$subfeature->lire) && empty($user->rights->$feature->$subfeature->read)) { $tmpreadok = 0; }
						elseif (empty($subfeature) && empty($user->rights->$feature->lire) && empty($user->rights->$feature->read)) { $tmpreadok = 0; }
						else { $tmpreadok = 1; break; } // Break is to bypass second test if the first is ok
					}
					if (!$tmpreadok) {	// We found a test on feature that is ko
						$readok = 0; // All tests are ko (we manage here the and, the or will be managed later using $nbko).
						$nbko++;
					}
				} elseif (!empty($feature) && ($feature != 'user' && $feature != 'usergroup')) {		// This is permissions on 1 level
					if (empty($user->rights->$feature->lire)
						&& empty($user->rights->$feature->read)
						&& empty($user->rights->$feature->run)) { $readok = 0; $nbko++; }
				}
			}

			// If a or and at least one ok
			if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) $readok = 1;

			if (!$readok) accessforbidden();
			//print "Read access is ok";

			// Check write permission from module (we need to know write permission to create but also to delete drafts record or to upload files)
			$createok = 1; $nbko = 0;
			$wemustcheckpermissionforcreate = (GETPOST('sendit', 'alpha') || GETPOST('linkit', 'alpha') || GETPOST('action', 'aZ09') == 'create' || GETPOST('action', 'aZ09') == 'update');
			$wemustcheckpermissionfordeletedraft = ((GETPOST("action", "aZ09") == 'confirm_delete' && GETPOST("confirm", "aZ09") == 'yes') || GETPOST("action", "aZ09") == 'delete');

			if ($wemustcheckpermissionforcreate || $wemustcheckpermissionfordeletedraft)
			{
				foreach ($featuresarray as $feature)
				{
					if ($feature == 'contact') {
						if (!$user->rights->societe->contact->creer) { $createok = 0; $nbko++; }
					} elseif ($feature == 'produit|service') {
						if (!$user->rights->produit->creer && !$user->rights->service->creer) { $createok = 0; $nbko++; }
					} elseif ($feature == 'prelevement') {
						if (!$user->rights->prelevement->bons->creer) { $createok = 0; $nbko++; }
					} elseif ($feature == 'commande_fournisseur') {
						if (!$user->rights->fournisseur->commande->creer) { $createok = 0; $nbko++; }
					} elseif ($feature == 'banque') {
						if (!$user->rights->banque->modifier) { $createok = 0; $nbko++; }
					} elseif ($feature == 'cheque') {
						if (!$user->rights->banque->cheque) { $createok = 0; $nbko++; }
					} elseif ($feature == 'import') {
						if (!$user->rights->import->run) { $createok = 0; $nbko++; }
					} elseif ($feature == 'ecm') {
						if (!$user->rights->ecm->upload) { $createok = 0; $nbko++; }
					}
					elseif (!empty($feature2)) {														// This is for permissions on one level
						foreach ($feature2 as $subfeature) {
							if ($subfeature == 'user' && $user->id == $objectid && $user->rights->user->self->creer) continue; // User can edit its own card
							if ($subfeature == 'user' && $user->id == $objectid && $user->rights->user->self->password) continue; // User can edit its own password

							if (empty($user->rights->$feature->$subfeature->creer)
								&& empty($user->rights->$feature->$subfeature->write)
								&& empty($user->rights->$feature->$subfeature->create)) {
								$createok = 0;
								$nbko++;
							} else {
								$createok = 1;
								// Break to bypass second test if the first is ok
								break;
							}
						}
					} elseif (!empty($feature))	{												// This is for permissions on 2 levels ('creer' or 'write')
						//print '<br>feature='.$feature.' creer='.$user->rights->$feature->creer.' write='.$user->rights->$feature->write; exit;
						if (empty($user->rights->$feature->creer)
							&& empty($user->rights->$feature->write)
							&& empty($user->rights->$feature->create)) {
							$createok = 0;
							$nbko++;
						}
					}
				}

				// If a or and at least one ok
				if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) $createok = 1;

				if ($wemustcheckpermissionforcreate && !$createok) accessforbidden();
				//print "Write access is ok";
			}

			// Check create user permission
			$createuserok = 1;
			if (GETPOST('action', 'aZ09') == 'confirm_create_user' && GETPOST("confirm", 'aZ09') == 'yes')
			{
				if (!$user->rights->user->user->creer) $createuserok = 0;

				if (!$createuserok) accessforbidden();
				//print "Create user access is ok";
			}

			// Check delete permission from module
			$deleteok = 1; $nbko = 0;
			if ((GETPOST("action", "aZ09") == 'confirm_delete' && GETPOST("confirm", "aZ09") == 'yes') || GETPOST("action", "aZ09") == 'delete')
			{
				foreach ($featuresarray as $feature)
				{
					if ($feature == 'contact')
					{
						if (!$user->rights->societe->contact->supprimer) $deleteok = 0;
					}
					elseif ($feature == 'produit|service')
					{
						if (!$user->rights->produit->supprimer && !$user->rights->service->supprimer) $deleteok = 0;
					}
					elseif ($feature == 'commande_fournisseur')
					{
						if (!$user->rights->fournisseur->commande->supprimer) $deleteok = 0;
					}
					elseif ($feature == 'banque')
					{
						if (!$user->rights->banque->modifier) $deleteok = 0;
					}
					elseif ($feature == 'cheque')
					{
						if (!$user->rights->banque->cheque) $deleteok = 0;
					}
					elseif ($feature == 'ecm')
					{
						if (!$user->rights->ecm->upload) $deleteok = 0;
					}
					elseif ($feature == 'ftp')
					{
						if (!$user->rights->ftp->write) $deleteok = 0;
					}elseif ($feature == 'salaries')
					{
						if (!$user->rights->salaries->delete) $deleteok = 0;
					}
					elseif ($feature == 'salaries')
					{
						if (!$user->rights->salaries->delete) $deleteok = 0;
					}
					elseif (!empty($feature2))							// This is for permissions on 2 levels
					{
						foreach ($feature2 as $subfeature)
						{
							if (empty($user->rights->$feature->$subfeature->supprimer) && empty($user->rights->$feature->$subfeature->delete)) $deleteok = 0;
							else { $deleteok = 1; break; } // For bypass the second test if the first is ok
						}
					}
					elseif (!empty($feature))							// This is used for permissions on 1 level
					{
						//print '<br>feature='.$feature.' creer='.$user->rights->$feature->supprimer.' write='.$user->rights->$feature->delete;
						if (empty($user->rights->$feature->supprimer)
							&& empty($user->rights->$feature->delete)
							&& empty($user->rights->$feature->run)) $deleteok = 0;
					}
				}

				// If a or and at least one ok
				if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) $deleteok = 1;

				if (!$deleteok && !($isdraft && $createok)) accessforbidden();
				//print "Delete access is ok";
			}

			$result = 1;

			// If we have a particular object to check permissions on, we check this object
			// is linked to a company allowed to $user.
			if (!empty($objectid) && $objectid > 0)
			{
				$ok = $this->checkUserAccessToObject($user, $featuresarray, $objectid, $tableandshare, $feature2, $dbt_keyfield, $dbt_select);
				$params = array('objectid' => $objectid, 'features' => join(',', $featuresarray), 'features2' => $feature2);
				$result = $ok ? 1 : accessforbidden('', 1, 1, 0, $params);
			}

			$this->results['result'] = $result > 0 ? 1 : 0;

			return 1;
		}

		return 0;
	}

	/**
	 * Check access by user to object.
	 * This function is also called by restrictedArea
	 *
	 * @param User			$user			User to check
	 * @param array			$featuresarray	Features/modules to check. Example: ('user','service','member','project','task',...)
	 * @param int|string	$objectid		Object ID if we want to check a particular record (optional) is linked to a owned thirdparty (optional).
	 * @param string		$tableandshare	'TableName&SharedElement' with Tablename is table where object is stored. SharedElement is an optional key to define where to check entity for multicompany modume. Param not used if objectid is null (optional).
	 * @param string		$feature2		Feature to check, second level of permission (optional). Can be or check with 'level1|level2'.
	 * @param string		$dbt_keyfield	Field name for socid foreign key if not fk_soc. Not used if objectid is null (optional)
	 * @param string		$dbt_select		Field name for select if not rowid. Not used if objectid is null (optional)
	 * @return	bool						True if user has access, False otherwise
	 * @see restrictedArea()
	 */
	function checkUserAccessToObject($user, $featuresarray, $objectid = 0, $tableandshare = '', $feature2 = '', $dbt_keyfield = '', $dbt_select = 'rowid')
	{
		global $db, $conf;

		// More parameters
		$params = explode('&', $tableandshare);
		$dbtablename = (!empty($params[0]) ? $params[0] : '');
		$sharedelement = (!empty($params[1]) ? $params[1] : $dbtablename);

		foreach ($featuresarray as $feature)
		{
			$sql = '';

			// For backward compatibility
			if ($feature == 'member')  $feature = 'adherent';
			if ($feature == 'project') $feature = 'projet';
			if ($feature == 'task')    $feature = 'projet_task';

			$check = array('adherent', 'banque', 'bom', 'don', 'mrp', 'user', 'usergroup', 'product', 'produit', 'service', 'produit|service', 'categorie', 'resource', 'expensereport', 'holiday'); // Test on entity only (Objects with no link to company)
			$checksoc = array('societe'); // Test for societe object
			$checkother = array('contact', 'agenda'); // Test on entity and link to third party. Allowed if link is empty (Ex: contacts...).
			$checkproject = array('projet', 'project'); // Test for project object
			$checktask = array('projet_task');
			$nocheck = array('barcode', 'stock'); // No test
			//$checkdefault = 'all other not already defined'; // Test on entity and link to third party. Not allowed if link is empty (Ex: invoice, orders...).

			// If dbtablename not defined, we use same name for table than module name
			if (empty($dbtablename))
			{
				$dbtablename = $feature;
				$sharedelement = (!empty($params[1]) ? $params[1] : $dbtablename); // We change dbtablename, so we set sharedelement too.
			}

			// Check permission for object with entity
			if (in_array($feature, $check))
			{
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				if (($feature == 'user' || $feature == 'usergroup') && !empty($conf->multicompany->enabled))
				{
					if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
					{
						if ($conf->entity == 1 && $user->admin && !$user->entity)
						{
							$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
							$sql .= " AND dbt.entity IS NOT NULL";
						}
						else
						{
							$sql .= ",".MAIN_DB_PREFIX."usergroup_user as ug";
							$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
							$sql .= " AND ((ug.fk_user = dbt.rowid";
							$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
							$sql .= " OR dbt.entity = 0)"; // Show always superadmin
						}
					}
					else {
						$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
						$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
					}
				}
				else
				{
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}
			}
			elseif (in_array($feature, $checksoc))	// We check feature = checksoc
			{
				// If external user: Check permission for external users
				if ($user->socid > 0)
				{
					if ($user->socid <> $objectid) return false;
				}
				// If internal user: Check permission for internal users that are restricted on their objects
				elseif (!empty($conf->societe->enabled) && ($user->rights->societe->lire && !$user->rights->societe->client->voir))
				{
					$sql = "SELECT COUNT(sc.fk_soc) as nb";
					$sql .= " FROM (".MAIN_DB_PREFIX."societe_commerciaux as sc";
					$sql .= ", ".MAIN_DB_PREFIX."societe as s)";
					$sql .= " WHERE sc.fk_soc IN (".$objectid.")";
					$sql .= " AND sc.fk_user = ".$user->id;
					$sql .= " AND sc.fk_soc = s.rowid";
					$sql .= " AND s.entity IN (".getEntity($sharedelement, 1).")";
				}
				// If multicompany and internal users with all permissions, check user is in correct entity
				elseif (!empty($conf->multicompany->enabled))
				{
					$sql = "SELECT COUNT(s.rowid) as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
					$sql .= " WHERE s.rowid IN (".$objectid.")";
					$sql .= " AND s.entity IN (".getEntity($sharedelement, 1).")";
				}
			}
			elseif (in_array($feature, $checkother))	// Test on entity and link to societe. Allowed if link is empty (Ex: contacts...).
			{
				// If external user: Check permission for external users
				if ($user->socid > 0)
				{
					if ($dbtablename == 'socpeople') {
						$master_option = $conf->global->RELATIONSTIERSCONTACTS_CONTACT_MASTER;
						$master_option = 0 < $master_option && $master_option <= 2 ? $master_option : 0;
						$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
						$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
						$link = array();
						if (empty($master_option) || $master_option == 1) {
							$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "relationcontact as rc1 ON dbt.rowid = " . $this->db->ifsql('rc1.sens = 0', 'rc1.fk_socpeople_b', 'rc1.fk_socpeople_a');
							$link[] = 'sp.rowid = ' . $this->db->ifsql('rc1.sens = 0', 'rc1.fk_socpeople_a', 'rc1.fk_socpeople_b');
						}
						if (empty($master_option) || $master_option == 2) {
							$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "relationcontact as rc2 ON dbt.rowid = " . $this->db->ifsql('rc2.sens = 0', 'rc2.fk_socpeople_a', 'rc2.fk_socpeople_b');
							$link[] = 'sp.rowid = ' . $this->db->ifsql('rc2.sens = 0', 'rc2.fk_socpeople_b', 'rc2.fk_socpeople_a');
						}
						$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp ON (" . implode(' OR ', $link) . ')';
						$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = sp.fk_soc";
						$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = '" . $user->id . "' AND (u.fk_soc = s.rowid OR u.fk_socpeople = sp.rowid)";
						$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
						$sql .= " AND (dbt.fk_soc = ".$user->socid . " OR u.rowid IS NOT NULL)"; // Contact not linked to a company or to a company of user
					} else {
						$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
						$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
						$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
						$sql .= " AND dbt.fk_soc = ".$user->socid;
					}
				}
				// If internal user: Check permission for internal users that are restricted on their objects
				elseif (!empty($conf->societe->enabled) && ($user->rights->societe->lire && !$user->rights->societe->client->voir))
				{
					if ($dbtablename == 'socpeople') {
						$master_option = $conf->global->RELATIONSTIERSCONTACTS_CONTACT_MASTER;
						$master_option = 0 < $master_option && $master_option <= 2 ? $master_option : 0;
						$sql = "SELECT COUNT(dbt." . $dbt_select . ") as nb";
						$sql .= " FROM " . MAIN_DB_PREFIX . $dbtablename . " as dbt"; //contact
						$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON dbt.fk_soc = sc.fk_soc AND sc.fk_user = '" . $user->id . "'";
						$link = array();
						if (empty($master_option) || $master_option == 1) {
							$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "relationcontact as rc1 ON dbt.rowid = " . $this->db->ifsql('rc1.sens = 0', 'rc1.fk_socpeople_b', 'rc1.fk_socpeople_a');
							$link[] = 'sp.rowid = ' . $this->db->ifsql('rc1.sens = 0', 'rc1.fk_socpeople_a', 'rc1.fk_socpeople_b');
						}
						if (empty($master_option) || $master_option == 2) {
							$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "relationcontact as rc2 ON dbt.rowid = " . $this->db->ifsql('rc2.sens = 0', 'rc2.fk_socpeople_a', 'rc2.fk_socpeople_b');
							$link[] = 'sp.rowid = ' . $this->db->ifsql('rc2.sens = 0', 'rc2.fk_socpeople_b', 'rc2.fk_socpeople_a');
						}
						$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp ON (" . implode(' OR ', $link) . ')';
						$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = sp.fk_soc";
						$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = '" . $user->id . "' AND (u.fk_soc = s.rowid OR u.fk_socpeople = sp.rowid)";
						$sql .= " WHERE dbt." . $dbt_select . " IN (" . $objectid . ")";
						$sql .= " AND (dbt.fk_soc IS NULL OR sc.fk_soc IS NOT NULL OR u.rowid IS NOT NULL)"; // Contact not linked to a company or to a company of user
						$sql .= " AND dbt.entity IN (" . getEntity($sharedelement, 1) . ")";
					} else {
						$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
						$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
						$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON dbt.fk_soc = sc.fk_soc AND sc.fk_user = '".$user->id."'";
						$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
						$sql .= " AND (dbt.fk_soc IS NULL OR sc.fk_soc IS NOT NULL)"; // Contact not linked to a company or to a company of user
						$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";

					}
				}
				// If multicompany and internal users with all permissions, check user is in correct entity
				elseif (!empty($conf->multicompany->enabled))
				{
					$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}

				if ($feature == 'agenda')// Also check myactions rights
				{
					if ($objectid > 0 && empty($user->rights->agenda->allactions->read)) {
						require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
						$action = new ActionComm($db);
						$action->fetch($objectid);
						if ($action->authorid != $user->id && $action->userownerid != $user->id && !(array_key_exists($user->id, $action->userassigned))) {
							return false;
						}
					}
				}
			}
			elseif (in_array($feature, $checkproject))
			{
				if (!empty($conf->projet->enabled) && empty($user->rights->projet->all->lire))
				{
					include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
					$projectstatic = new Project($db);
					$tmps = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, 0);
					$tmparray = explode(',', $tmps);
					if (!in_array($objectid, $tmparray)) return false;
				}
				else
				{
					$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}
			}
			elseif (in_array($feature, $checktask))
			{
				if (!empty($conf->projet->enabled) && empty($user->rights->projet->all->lire))
				{
					$task = new Task($db);
					$task->fetch($objectid);

					include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
					$projectstatic = new Project($db);
					$tmps = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, 0);
					$tmparray = explode(',', $tmps);
					if (!in_array($task->fk_project, $tmparray)) return false;
				}
				else
				{
					$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}
			}
			elseif (!in_array($feature, $nocheck))		// By default (case of $checkdefault), we check on object entity + link to third party on field $dbt_keyfield
			{
				// If external user: Check permission for external users
				if ($user->socid > 0)
				{
					if (empty($dbt_keyfield)) dol_print_error('', 'Param dbt_keyfield is required but not defined');
					$sql = "SELECT COUNT(dbt.".$dbt_keyfield.") as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= " WHERE dbt.rowid IN (".$objectid.")";
					$sql .= " AND dbt.".$dbt_keyfield." = ".$user->socid;
				}
				// If internal user: Check permission for internal users that are restricted on their objects
				elseif (!empty($conf->societe->enabled) && ($user->rights->societe->lire && !$user->rights->societe->client->voir))
				{
					if (empty($dbt_keyfield)) dol_print_error('', 'Param dbt_keyfield is required but not defined');
					$sql = "SELECT COUNT(sc.fk_soc) as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= ", ".MAIN_DB_PREFIX."societe as s";
					$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND sc.fk_soc = dbt.".$dbt_keyfield;
					$sql .= " AND dbt.".$dbt_keyfield." = s.rowid";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
					$sql .= " AND sc.fk_user = ".$user->id;
				}
				// If multicompany and internal users with all permissions, check user is in correct entity
				elseif (!empty($conf->multicompany->enabled))
				{
					$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}
			}

			if ($sql)
			{
				$resql = $db->query($sql);
				if ($resql)
				{
					$obj = $db->fetch_object($resql);
					if (!$obj || $obj->nb < count(explode(',', $objectid))) return false;
				}
				else
				{
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public function replaceThirdparty($parameters, $object, $action)
	{
		$table = 'relationtiers';

		$sub_sql  = "SELECT fk_socpeople";
		$sub_sql .= " FROM `".MAIN_DB_PREFIX.$table."`";
		$sub_sql .= " WHERE fk_soc = " . (int) $parameters['soc_dest'];

		$sql  = 'UPDATE '.MAIN_DB_PREFIX.$table.' SET fk_soc = '.((int) $parameters['soc_dest']);
		$sql .= ' WHERE fk_soc = '.((int) $parameters['soc_origin']);
		$sql .= ' AND fk_socpeople NOT IN ('.$sub_sql.')';
		if (!$this->db->query($sql)) {
			return false;
		}

		// remove useless duplicates from origin fk_soc
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX.$table;
		$sql .= " WHERE fk_soc = ".(int) $parameters['soc_origin'];
		if (!$this->db->query($sql)) {
			return false;
		}

		return true;
	}

}
