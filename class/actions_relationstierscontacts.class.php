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
        global $langs;

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
                $out .= $form->select_date($relationTiers->date_debut, 'relationtiers_datedebut_', 0, 0, 1, '', 1, 1, 1);
                $out .= '</td></tr>';

                // Date end
                $out .= '<tr><td>' . $langs->trans('RTCRelationTiersDateEndLabel') . '</td><td>';
                $out .= $form->select_date($relationTiers->date_fin, 'relationtiers_datefin_', 0, 0, 1, '', 1, 1, 1);
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

                $socid = GETPOST('socid','int');
                $idRelationTiers = GETPOST('id_relationtiers','int');

                // id relation thirdparty
                $relationTiers = new RelationTiers($this->db);
                if ($idRelationTiers > 0) {
                    $relationTiers->fetch($idRelationTiers);
                    $out .= '<input type="hidden" name="id_relationtiers" value="'. $relationTiers->id . '" />';
                }

                // id of thirdparty
                $out .= '<input type="hidden" name="socid" value="'. $socid . '" />';

                if ($relationTiers->id > 0) {
                    // Relation Contact/Thirdparty
                    $out .= '<tr><td class="fieldrequired">' . $langs->trans('RTCRelationTiersLabel') . '</td><td>';
                    $out .= $formRelationsTiersContacts->selectAllRelationTiers('relationtiers', $relationTiers->fk_c_relationtiers, 1, 0, 0, '', 0, 0, 0, '', '', 0, '', 0, 0, 0, 1);
                    $out .= '</td></tr>';

                    // Date start
                    $out .= '<tr><td>' . $langs->trans('RTCRelationTiersDateStartLabel') . '</td><td>';
                    $out .= $form->select_date($relationTiers->date_debut, 'relationtiers_datedebut_', 0, 0, 1, '', 1, 1, 1);
                    $out .= '</td></tr>';

                    // Date end
                    $out .= '<tr><td>' . $langs->trans('RTCRelationTiersDateEndLabel') . '</td><td>';
                    $out .= $form->select_date($relationTiers->date_fin, 'relationtiers_datefin_', 0, 0, 1, '', 1, 1, 1);
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
                // delete line tr thirdparty selection
                $out .= '   jQuery("select#socid").parent().parent().remove();';
                $out .= '});';
                $out .= '</script>';

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
}
