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

/**
 *  \file       htdocs/relationstierscontacts/relationtiers.php
 *  \ingroup    relationstierscontacts
 *  \brief      Page of relations of contacts
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');
dol_include_once('/relationstierscontacts/class/relationtiers.class.php');
dol_include_once('/relationstierscontacts/class/html.formrelationstierscontacts.class.php');

$langs->loadLangs(array("relationstierscontacts@relationstierscontacts", "companies"));

$mesg=''; $error=0; $errors=array();

$action		= (GETPOST('action','alpha') ? GETPOST('action','alpha') : 'view');
$confirm    = GETPOST('confirm','alpha');
$backtopage	= GETPOST('backtopage','alpha');
$id			= GETPOST('id','int');
$socid		= GETPOST('socid','int');

$idRelationTiers = GETPOST('id_relationtiers','int');

$object = new Contact($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id);
$objcanvas=null;
$canvas = (! empty($object->canvas)?$object->canvas:GETPOST("canvas"));
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db, $action);
    $objcanvas->getCanvas('contact', 'contactcard', $canvas);
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe', '', '', 'rowid', $objcanvas); // If we create a contact with no company (shared contacts), no check on write permission

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('relationtiers'));




/*
 * Actions
 */

$relationTiers = new RelationTiers($db);
if ($idRelationTiers > 0) {
    $relationTiers->fetch($idRelationTiers);
}

$parameters=array('id'=>$id, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Cancel
    if (GETPOST('cancel','alpha') && ! empty($backtopage))
    {
        header("Location: ".$backtopage);
        exit;
    }

    // create relation thirdparty
    if ($action == 'relation_confirm_create' && $confirm == 'yes' && $user->rights->relationstierscontacts->relationtiers->creer) {
        $relationTiers->fk_soc             = GETPOST('relationtiers_socid', 'int');
        $relationTiers->fk_socpeople       = $id;
        $relationTiers->fk_c_relationtiers = GETPOST('relationtiers', 'int');

        $relationTiersDateDebut = NULL;
        if (GETPOST('relationtiers_datedebut_')) {
            $relationTiersDateDebut = dol_mktime(0, 0, 0, GETPOST('relationtiers_datedebut_month', 'int'), GETPOST('relationtiers_datedebut_day', 'int'), GETPOST('relationtiers_datedebut_year', 'int'));
        }
        $relationTiers->date_debut = $relationTiersDateDebut;

        $relationTiersDateFin = NULL;
        if (GETPOST('relationtiers_datefin_')) {
            $relationTiersDateFin = dol_mktime(0, 0, 0, GETPOST('relationtiers_datefin_month', 'int'), GETPOST('relationtiers_datefin_day', 'int'), GETPOST('relationtiers_datefin_year', 'int'));
        }
        $relationTiers->date_fin = $relationTiersDateFin;

        $relationTiers->commentaire = GETPOST('relationtiers_commentaire');

        $relationTiers->is_main_thirdparty = GETPOST('relationtiers_is_main_thirdparty') ? TRUE : FALSE;

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
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
            exit();
        } else {
            $db->rollback();
            $action = 'relation_create';
        }
    } else if ($action == 'relation_confirm_edit' && $confirm == 'yes' && $user->rights->relationstierscontacts->relationtiers->creer) {
        // modify relation thirdparty
        $relationTiers->fk_soc             = GETPOST('relationtiers_socid', 'int');
        $relationTiers->fk_socpeople       = $id;
        $relationTiers->fk_c_relationtiers = GETPOST('relationtiers', 'int');

        $relationTiersDateDebut = NULL;
        if (GETPOST('relationtiers_datedebut_'))
        {
            $relationTiersDateDebut = dol_mktime(0, 0, 0, GETPOST('relationtiers_datedebut_month', 'int'), GETPOST('relationtiers_datedebut_day', 'int'), GETPOST('relationtiers_datedebut_year', 'int'));
        }
        $relationTiers->date_debut = $relationTiersDateDebut;

        $relationTiersDateFin = NULL;
        if (GETPOST('relationtiers_datefin_'))
        {
            $relationTiersDateFin = dol_mktime(0, 0, 0, GETPOST('relationtiers_datefin_month', 'int'), GETPOST('relationtiers_datefin_day', 'int'), GETPOST('relationtiers_datefin_year', 'int'));
        }
        $relationTiers->date_fin = $relationTiersDateFin;

        $relationTiers->commentaire = GETPOST('relationtiers_commentaire');

        $relationTiers->is_main_thirdparty = GETPOST('relationtiers_is_main_thirdparty') ? TRUE : FALSE;

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
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
            exit();
        } else {
            $db->rollback();
            $action = 'relation_edit';
        }
    } else if ($action == 'relation_confirm_delete' && $confirm == 'yes' && $user->rights->relationstierscontacts->relationtiers->supprimer) {
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


/*
 *  View
 */



$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/contactnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->lastname) $title=$object->lastname;
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';


if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
    // -----------------------------------------
    // When used with CANVAS
    // -----------------------------------------
    if (empty($object->error) && $id)
    {
        $object = new Contact($db);
        $result=$object->fetch($id);
        if ($result <= 0) dol_print_error('',$object->error);
    }
    $objcanvas->assign_values($action, $object->id, $object->ref);	// Set value for templates
    $objcanvas->display_canvas($action);							// Show template
}
else
{
    // -----------------------------------------
    // When used in standard mode
    // -----------------------------------------

    /*
     * Onglets
     */
    $head=array();
    if ($id > 0)
    {
        // Si edition contact deja existant
        $object = new Contact($db);
        $res=$object->fetch($id, $user);
        if ($res < 0) { dol_print_error($db,$object->error); exit; }
        $res=$object->fetch_optionals();
        if ($res < 0) { dol_print_error($db,$object->error); exit; }

        // Show tabs
        $head = contact_prepare_head($object);

        $title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
    }

    if (! empty($id))
    {
        $formRelationsTiersContacts = new FormRelationsTiersContacts($db);
        $form = $formRelationsTiersContacts->form;
        $companystatic = new Societe($db);
        $relationTiersStatic = new RelationTiers($db);

        $formconfirm = '';

        // Confirm relation create or modify
        if (($action == 'relation_create' || $action == 'relation_edit') && $user->rights->relationstierscontacts->relationtiers->creer) {
            $formConfirmQuestion = array();

            // hidden fields
            if ($action == 'relation_edit') {
                $formConfirmTitle = $langs->trans('RTCRelationTiersModify');
                $formConfirmAction = 'relation_confirm_edit';
                $formConfirmQuestion[] = array('type' => 'hidden', 'name' => 'id_relationtiers', 'value' => $relationTiers->id);
            } else {
                $formConfirmTitle = $langs->trans('RTCRelationTiersCreate');
                $formConfirmAction = 'relation_confirm_create';
            }

            // relation label
            $formConfirmSelectRelation = $formRelationsTiersContacts->selectAllRelationTiers('relationtiers', $relationTiers->fk_c_relationtiers, 1, 0, 0, '', 0, 0, 0, '','', 0, '', 0, 0, 0, 1);
            $formConfirmQuestion[] = array('label' => $langs->trans('RTCRelationTiersLabel'), 'type' => 'other', 'name' => 'relationtiers', 'value' => $formConfirmSelectRelation);

            // thirdparty
            $formConfirmSelectThirdparty = $form->select_thirdparty_list($relationTiers->fk_soc,'relationtiers_socid', 1, '', '', 0, array(), '', 0, 0,'minwidth300', '');
            $formConfirmQuestion[] = array('label' => $langs->trans('RTCRelationTiersThirdparty'), 'type' => 'other', 'name' => 'relationtiers_socid', 'value' => $formConfirmSelectThirdparty);

            // date start
            $formConfirmDateDebut = $form->selectDate($relationTiers->date_debut, 'relationtiers_datedebut_', 0, 0, 0, '', 1, 1);
            $formConfirmQuestion[] = array('name' => 'relationtiers_datedebut_day');
            $formConfirmQuestion[] = array('name' => 'relationtiers_datedebut_month');
            $formConfirmQuestion[] = array('name' => 'relationtiers_datedebut_year');
            $formConfirmQuestion[] = array('label' => $langs->trans('RTCRelationTiersDateStartLabel'), 'type' => 'other', 'name' => 'relationtiers_datedebut_', 'value' => $formConfirmDateDebut);

            // date end
            $formConfirmDateFin = $form->selectDate($relationTiers->date_fin, 'relationtiers_datefin_', 0, 0, 0, '', 1, 1);
            $formConfirmQuestion[] = array('name' => 'relationtiers_datefin_day');
            $formConfirmQuestion[] = array('name' => 'relationtiers_datefin_month');
            $formConfirmQuestion[] = array('name' => 'relationtiers_datefin_year');
            $formConfirmQuestion[] = array('label' => $langs->trans('RTCRelationTiersDateEndLabel'), 'type' => 'other', 'name' => 'relationtiers_datefin_', 'value' => $formConfirmDateFin);

            // comment
            $formConfirmTextareaComment = '<textarea class="flat quatrevingtpercent" id="relationtiers_commentaire" name="relationtiers_commentaire" rows="2">' . $relationTiers->commentaire . '</textarea>';
            $formConfirmQuestion[] = array('label' => $langs->trans('RTCRelationTiersCommentLabel'),   'type' => 'other', 'name' => 'relationtiers_commentaire', 'value' => $formConfirmTextareaComment);

            // main thirdparty
            $formConfirmQuestion[] = array('label' => $langs->trans('RTCRelationTiersMainThirdparty'), 'type' => 'checkbox', 'name' => 'relationtiers_is_main_thirdparty', 'value' => $relationTiers->isMainThirdparty());

            $formconfirm = $formRelationsTiersContacts->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $formConfirmTitle, '', $formConfirmAction, $formConfirmQuestion, 0, 1, 400, 800);
        }
        // Confirm relation delete
        else if ($action == 'relation_delete' && $user->rights->relationstierscontacts->relationtiers->supprimer) {
            $formConfirmQuestion = array();
            $formConfirmQuestion[] = array('type' => 'hidden', 'name' => 'id_relationtiers', 'value' => $relationTiers->id);
            $formconfirm = $formRelationsTiersContacts->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RTCRelationTiersDelete'), $langs->trans('RTCRelationTiersConfirmDelete'), 'relation_confirm_delete', $formConfirmQuestion, 0, 1);
        }

        if (! $formconfirm) {
            $parameters = array();
            $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
            elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
        }

        print $formconfirm;



        /*
        * Fiche en mode visualisation
        */

        //dol_htmloutput_errors($error,$errors);

        print dol_get_fiche_head($head, 'rtc_relation_tiers_tab', $title, -1, 'contact');

        $linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        $morehtmlref='<div class="refidno">';
        if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
        {
            $objsoc=new Societe($db);
            $objsoc->fetch($object->socid);
            // Thirdparty
            $morehtmlref.=$langs->trans('ThirdParty') . ' : ';
            if ($objsoc->id > 0) $morehtmlref.=$objsoc->getNomUrl(1);
            else $morehtmlref.=$langs->trans("ContactNotLinkedToCompany");
        }
        $morehtmlref.='</div>';

        dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

        print '<div class="fichecenter">';

        print '<div class="underbanner clearboth"></div>';

        $object->info($id);
        print dol_print_object_info($object, 1);

        print '</div>';

        print dol_get_fiche_end();

        print '<br />';


        /*
         * List of relation tiers
         */
        $optioncss = GETPOST('optioncss', 'alpha');
        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');
        $page = GETPOST('page','int');
        $search_status		= GETPOST("search_status",'int');
        if ($search_status=='') $search_status=1; // always display activ customer first

        if (! $sortorder) $sortorder="ASC";
        if (! $sortfield) $sortfield="t.lastname";

        if (! empty($conf->clicktodial->enabled))
        {
            $user->fetch_clicktodial(); // lecture des infos de clicktodial du user
        }

        $extralabels=$extrafields->fetch_name_optionals_label($companystatic->table_element);

        $companystatic->fields=array(
            'relation_label' => array('type'=>'varchar(255)', 'label'=>'RTCRelationTiersLabel',   'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>2),
            'nom'            => array('type'=>'varchar(128)', 'label'=>'Name',                    'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1),
            'rt_date_debut'  => array('type'=>'date',         'label'=>'RTCRelationTiersDateStartLabel', 'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>11),
            'rt_date_fin'    => array('type'=>'date',         'label'=>'RTCRelationTiersDateEndLabel', 'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>12),
            'rt_commentaire' => array('type'=>'text',         'label'=>'RTCRelationTiersCommentLabel', 'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>13),
            'address'        => array('type'=>'varchar(128)', 'label'=>'Address',                 'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>30),
            'status'         => array('type'=>'integer',      'label'=>'Status',                  'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'default'=>0, 'index'=>1,  'position'=>40, 'arrayofkeyval'=>array(0=>$companystatic->LibStatut(0,1), 1=>$companystatic->LibStatut(1,1))),
        );

        // Definition of fields for list
        $arrayfields=array(
            't.relation_label' => array('label'=>'RTCRelationTiersLabel', 'checked'=>1, 'position'=>1),
            't.rowid'          => array('label'=>"TechnicalID", 'checked'=>($conf->global->MAIN_SHOW_TECHNICAL_ID?1:0), 'enabled'=>($conf->global->MAIN_SHOW_TECHNICAL_ID?1:0), 'position'=>2),
            't.nom'            => array('label'=>"Name", 'checked'=>1, 'position'=>10),
            't.rt_date_debut'  => array('label'=>"RTCRelationTiersDateStartLabel", 'checked'=>1, 'position'=>11),
            't.rt_date_fin'    => array('label'=>"RTCRelationTiersDateEndLabel", 'checked'=>1, 'position'=>12),
            't.rt_commentaire' => array('label'=>"RTCRelationTiersCommentLabel", 'checked'=>1, 'position'=>13),
            't.address'        => array('label'=>(empty($conf->dol_optimize_smallscreen) ? $langs->trans("Address").' / '.$langs->trans("Phone").' / '.$langs->trans("Email") : $langs->trans("Address")), 'checked'=>1, 'position'=>30),
            't.status'         => array('label'=>"Status", 'checked'=>1, 'position'=>40, 'align'=>'center'),
        );
        // Extra fields
        if (is_array($extrafields->attributes[$companystatic->table_element]['label']) && count($extrafields->attributes[$companystatic->table_element]['label']))
        {
            foreach($extrafields->attributes[$companystatic->table_element]['label'] as $key => $val)
            {
                if (! empty($extrafields->attributes[$companystatic->table_element]['list'][$key])) {
                    $arrayfields["ef.".$key]=array(
                        'label'=>$extrafields->attributes[$companystatic->table_element]['label'][$key],
                        'checked'=>(($extrafields->attributes[$companystatic->table_element]['list'][$key]<0)?0:1),
                        'position'=>$extrafields->attributes[$companystatic->table_element]['pos'][$key],
                        'enabled'=>(abs($extrafields->attributes[$companystatic->table_element]['list'][$key])!=3 && $extrafields->attributes[$companystatic->table_element]['perms'][$key]));
                }
            }
        }

        // Initialize array of search criterias
        $search=array();
        foreach($companystatic->fields as $key => $val)
        {
            if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
        }
        $search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

        // Purge search criteria
        if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
        {
            $search_status		 = '';
            $search_array_options=array();

            foreach($companystatic->fields as $key => $val)
            {
                $search[$key]='';
            }
            $toselect='';
        }

        $companystatic->fields = dol_sort_array($companystatic->fields, 'position');
        $arrayfields = dol_sort_array($arrayfields, 'position');

        // change selected fields
        include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

        $newcardbutton = '';
        if ($user->rights->relationstierscontacts->relationtiers->creer)
        {
            $addrelationtiers = $langs->trans('RTCRelationTiersCreate');

            if (version_compare(DOL_VERSION, '10.0.0', '>=')) {
                // Easya compatibility
                $class_fonts_awesome = !empty($conf->global->EASYA_VERSION) ? 'fal' : 'fa';
                $newcardbutton = dolGetButtonTitle($addrelationtiers, '', $class_fonts_awesome.' fa-user-plus', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=relation_create');
            } else {
                $newcardbutton .= '<a class="butActionNew" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=relation_create"><span class="valignmiddle">' . $addrelationtiers . '</span>';
                $newcardbutton .= '<span class="'.$class_fonts_awesome.' fa-user-plus valignmiddle"></span>';
                $newcardbutton .= '</a>';
            }
        }

        print "\n";

        $title = $langs->trans("RTCRelationTiersListTitle");
        print load_fiche_titre($title, $newcardbutton,'');

        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="id" value="'.$object->id.'">';
        print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';

        $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
        $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
        //if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

        print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
        print "\n".'<table class="tagtable liste">'."\n";

        $param="id=".urlencode($object->id);
        if ($search_status != '') $param.='&search_status='.urlencode($search_status);
        if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
        // Add $param from extra fields
        $extrafieldsobjectkey=$companystatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


        // Fields title search
        // --------------------------------------------------------------------
        print '<tr class="liste_titre">';
        foreach($companystatic->fields as $key => $val)
        {
            $align='';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
            if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
            if ($key == 'status' || $key == 'statut') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['t.'.$key]['checked']))
            {
                print '<td class="liste_titre'.($align?' '.$align:'').'">';
                if (in_array($key, array('lastname','name'))) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
                elseif (in_array($key, array('statut'))) print $form->selectarray('search_status', array('-1'=>'','0'=>$companystatic->LibStatut(0,1),'1'=>$companystatic->LibStatut(1,1)),$search_status);
                print '</td>';
            }
        }
        // Extra fields
        $extrafieldsobjectkey=$companystatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

        // Fields from hook
        $parameters=array('arrayfields'=>$arrayfields);
        $reshook=$hookmanager->executeHooks('printFieldListOption', $parameters, $companystatic);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        // Action column
        print '<td class="liste_titre" align="right">';
        $searchpicto=$form->showFilterButtons();
        print $searchpicto;
        print '</td>';
        print '</tr>'."\n";

        // Fields title label
        // --------------------------------------------------------------------
        print '<tr class="liste_titre">';
        foreach($companystatic->fields as $key => $val)
        {
            $align='';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
            if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
            if ($key == 'status' || $key == 'statut') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['t.'.$key]['checked'])) print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($align?'class="'.$align.'"':''), $sortfield, $sortorder, $align.' ')."\n";
        }
        // Extra fields
        $extrafieldsobjectkey=$companystatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
        // Hook fields
        $parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
        $reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"],'','','','align="center"',$sortfield,$sortorder,'maxwidthsearch ')."\n";
        print '</tr>'."\n";


        $sql  = "SELECT t.rowid, t.nom as name, t.name_alias, t.barcode, t.town, t.zip, t.datec, t.code_client, t.code_fournisseur, t.logo";
        $sql .= ", t.fk_stcomm as stcomm_id, t.fk_prospectlevel, t.prefix_comm, t.client, t.fournisseur, t.canvas, t.status as status";
        $sql .= ", t.email, t.phone, t.fax, t.url, t.siren as idprof1, t.siret as idprof2, t.ape as idprof3, t.idprof4 as idprof4, t.idprof5 as idprof5, t.idprof6 as idprof6, t.tva_intra, t.fk_pays";
        $sql .= ", t.tms as date_update, t.datec as date_creation";
        $sql .= ", t.code_compta, t.code_compta_fournisseur";
        $sql .= ", rt.rowid as rt_id, rt.date_debut as rt_date_debut, rt.date_fin as rt_date_fin, rt.commentaire as rt_commentaire";
        $sql .= ", crt.label_b_a as relation_label";
        $sql .= " FROM " . MAIN_DB_PREFIX . "relationtiers as rt";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as t ON t.rowid = rt.fk_soc";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields as ef on (t.rowid = ef.fk_object)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_relationtiers as crt ON crt.rowid = rt.fk_c_relationtiers";
        $sql .= " WHERE rt.fk_socpeople = " . $object->id;
        $sql .= " AND t.entity IN (" . getEntity('societe') . ")";
        if ($search_status!='' && $search_status >= 0) $sql .= " AND t.status = ".$db->escape($search_status);
        // Add where from extra fields
        $extrafieldsobjectkey=$companystatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

        $result = $db->query($sql);
        if (! $result) dol_print_error($db);

        $num = $db->num_rows($result);

        if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x')))
        {
            $i = 0;

            while ($i < $num)
            {
                $obj = $db->fetch_object($result);

                $companystatic->id                      = $obj->rowid;
                $companystatic->name                    = $obj->name;
                $companystatic->name_alias              = $obj->name_alias;
                $companystatic->logo                    = $obj->logo;
                $companystatic->canvas                  = $obj->canvas;
                $companystatic->client                  = $obj->client;
                $companystatic->status                  = $obj->status;
                $companystatic->email                   = $obj->email;
                $companystatic->fournisseur             = $obj->fournisseur;
                $companystatic->code_client             = $obj->code_client;
                $companystatic->code_fournisseur        = $obj->code_fournisseur;
                $companystatic->code_compta_client      = $obj->code_compta;
                $companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                $companystatic->fk_prospectlevel        = $obj->fk_prospectlevel;

                $relationTiersStatic->id          = $obj->rt_id;
                $relationTiersStatic->date_debut  = (!empty($obj->rt_date_debut) ? $db->jdate($obj->rt_date_debut) : NULL);
                $relationTiersStatic->date_fin    = (!empty($obj->rt_date_fin) ? $db->jdate($obj->rt_date_fin) : NULL);
                $relationTiersStatic->commentaire = $obj->rt_commentaire;

                if (is_array($companystatic->array_options))
                {
                    foreach($companystatic->array_options as $key => $val)
                    {
                        $obj->$key = $val;
                    }
                }

                print '<tr class="oddeven">';

                // Relation label
                if (! empty($arrayfields['t.relation_label']['checked']))
                {
                    print '<td>';
                    if ($obj->relation_label) print $obj->relation_label;
                    print '</td>';
                }

                // ID
                if (! empty($arrayfields['t.rowid']['checked']))
                {
                    print '<td>';
                    print $companystatic->id;
                    print '</td>';
                }

                // Name
                if (! empty($arrayfields['t.nom']['checked']))
                {
                    $savalias = $obj->name_alias;
                    if (! empty($arrayfields['t.name_alias']['checked'])) $companystatic->name_alias='';
                    print '<td>';
                    print $companystatic->getNomUrl(1, '', 100, 0, 1);
                    print '</td>';
                    $companystatic->name_alias = $savalias;
                }

                // Name alias
                if (! empty($arrayfields['t.name_alias']['checked']))
                {
                    print '<td>';
                    print $companystatic->name_alias;
                    print '</td>';
                }

                // Relation date start
                if (! empty($arrayfields['t.rt_date_debut']['checked']))
                {
                    print '<td align="center">';
                    if ($relationTiersStatic->date_debut) print dol_print_date($relationTiersStatic->date_debut);
                    print '</td>';
                }

                // Relation date end
                if (! empty($arrayfields['t.rt_date_fin']['checked']))
                {
                    print '<td align="center">';
                    if ($relationTiersStatic->date_fin) print dol_print_date($relationTiersStatic->date_fin);
                    print '</td>';
                }

                // Relation comment
                if (! empty($arrayfields['t.rt_commentaire']['checked']))
                {
                    print '<td>';
                    if ($relationTiersStatic->commentaire) print $relationTiersStatic->commentaire;
                    print '</td>';
                }

                // Customer code
                if (! empty($arrayfields['t.code_client']['checked']))
                {
                    print '<td>' . $obj->code_client . '</td>';
                }

                // Supplier code
                if (! empty($arrayfields['t.code_fournisseur']['checked']))
                {
                    print '<td>' . $obj->code_fournisseur . '</td>';
                }

                // Address (phone, email)
                if (! empty($arrayfields['t.address']['checked']))
                {
                    print '<td>' . $obj->email . '</td>';
                }

                // Status
                if (! empty($arrayfields['t.status']['checked']))
                {
                    print '<td align="center">' . $companystatic->getLibStatut(3) . '</td>';
                }

                // Extra fields
                $extrafieldsobjectkey=$companystatic->table_element;
                include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

                // Actions
                print '<td align="right">';

                // Edit relation thirdparty
                if ($user->rights->relationstierscontacts->relationtiers->creer)
                {
                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=relation_edit&id='. $object->id . '&id_relationtiers=' . $relationTiersStatic->id . '">';
                    print img_edit($langs->trans('RTCRelationTiersModify'));
                    print '</a>';
                }

                // Delete relation thirdparty
                if ($user->rights->relationstierscontacts->relationtiers->supprimer)
                {
                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=relation_delete&id='. $object->id . '&id_relationtiers=' . $relationTiersStatic->id . '">';
                    print img_delete($langs->trans('RTCRelationTiersDelete'));
                    print '</a>';
                }

                print '</td>';

                print "</tr>\n";
                $i++;
            }
        }
        else
        {
            $colspan=1;
            foreach($arrayfields as $key => $val) { if (! empty($val['checked'])) $colspan++; }
            print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
        }

        print "\n</table>\n";
        print '</div>';

        print '</form>'."\n";

    }
}


// End of page
llxFooter();
$db->close();
