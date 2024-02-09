<?php
/* Copyright (C) 2018		Open-DSI			<support@open-dsi.fr>
 * Copyright (C) 2024		William Mead		<w1w1_m@icloud.com>
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
 *	\file       htdocs/relationstierscontacts/class/html.formrelationstierscontacts.class.php
 *  \ingroup    relationstierscontacts
 *	\brief      File of class with all html predefined components for relations tiers contacts
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

/**
 *	Class to manage generation of HTML components
 *	Only common components for relations tiers contacts must be here.
 *
 */
class FormRelationsTiersContacts
{
    public $db;
    public $error;
    public $num;

    /**
     * @var Form  Instance of the form
     */
    public $form;

    /**
     * @var FormDictionary  Instance of the form form dictionaries
     */
    public $formdictionary;



    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->form = new Form($this->db);
        $this->formdictionary = new FormDictionary($this->db);
    }


    /**
     *     Show a confirmation HTML form or AJAX popup.
     *     Easiest way to use this is with useajax=1.
     *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
     *     just after calling this method. For example:
     *       print '<script type="text/javascript">'."\n";
     *       print 'jQuery(document).ready(function() {'."\n";
     *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
     *       print '});'."\n";
     *       print '</script>'."\n";
     *
     *     @param  	string		$page        	   	Url of page to call if confirmation is OK. Can contains paramaters (param 'action' and 'confirm' will be reformated)
     *     @param	string		$title       	   	Title
     *     @param	string		$question    	   	Question
     *     @param 	string		$action      	   	Action
     *	   @param  	array		$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
     *												type can be 'hidden', 'text', 'password', 'checkbox', 'radio', 'date', 'morecss', ...
     * 	   @param  	string		$selectedchoice  	'' or 'no', or 'yes' or '1' or '0'
     * 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
     *     @param  	int			$height          	Force height of box
     *     @param	int			$width				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
     *     @param	int			$disableformtag		1=Disable form tag. Can be used if we are already inside a <form> section.
     *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirm($page, $title, $question, $action, $formquestion='', $selectedchoice='', $useajax=0, $height=200, $width=500, $disableformtag=0)
    {
        global $langs,$conf;
        global $useglobalvars;

        $more='';
        $formconfirm='';
        $inputok=array();
        $inputko=array();

        // Clean parameters
        $newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;
        if ($conf->browser->layout == 'phone') $width='95%';

        if (is_array($formquestion) && ! empty($formquestion))
        {
            // First add hidden fields and value
            foreach ($formquestion as $key => $input)
            {
                if (is_array($input) && ! empty($input))
                {
                    if ($input['type'] == 'hidden')
                    {
                        $more.='<input type="hidden" id="'.$input['name'].'" name="'.$input['name'].'" value="'.dol_escape_htmltag($input['value']).'">'."\n";
                    }
                }
            }

            // Now add questions
            $more.='<table class="paddingtopbottomonly" width="100%">'."\n";
            $more.='<tr><td colspan="3">'.(! empty($formquestion['text'])?$formquestion['text']:'').'</td></tr>'."\n";
            foreach ($formquestion as $key => $input)
            {
                if (is_array($input) && ! empty($input))
                {
                    $size=(! empty($input['size'])?' size="'.$input['size'].'"':'');
                    $moreattr=(! empty($input['moreattr'])?' '.$input['moreattr']:'');
                    $morecss=(! empty($input['morecss'])?' '.$input['morecss']:'');

                    if ($input['type'] == 'text')
                    {
                        $more.='<tr><td>'.$input['label'].'</td><td colspan="2" align="left"><input type="text" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></td></tr>'."\n";
                    }
                    else if ($input['type'] == 'password')
                    {
                        $more.='<tr><td>'.$input['label'].'</td><td colspan="2" align="left"><input type="password" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></td></tr>'."\n";
                    }
                    else if ($input['type'] == 'select')
                    {
                        $more.='<tr><td>';
                        if (! empty($input['label'])) $more.=$input['label'].'</td><td valign="top" colspan="2" align="left">';
                        $more.=$this->form->selectarray($input['name'],$input['values'],$input['default'],1,0,0,$moreattr,0,0,0,'',$morecss);
                        $more.='</td></tr>'."\n";
                    }
                    else if ($input['type'] == 'checkbox')
                    {
                        $more.='<tr>';
                        $more.='<td>'.$input['label'].' </td><td align="left">';
                        $more.='<input type="checkbox" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$moreattr;
                        if (! is_bool($input['value']) && $input['value'] != 'false' && $input['value'] != '0') $more.=' checked';
                        if (is_bool($input['value']) && $input['value']) $more.=' checked';
                        if (isset($input['disabled'])) $more.=' disabled';
                        $more.=' /></td>';
                        $more.='<td align="left">&nbsp;</td>';
                        $more.='</tr>'."\n";
                    }
                    else if ($input['type'] == 'radio')
                    {
                        $i=0;
                        foreach($input['values'] as $selkey => $selval)
                        {
                            $more.='<tr>';
                            if ($i==0) $more.='<td class="tdtop">'.$input['label'].'</td>';
                            else $more.='<td>&nbsp;</td>';
                            $more.='<td width="20"><input type="radio" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'" value="'.$selkey.'"'.$moreattr;
                            if ($input['disabled']) $more.=' disabled';
                            $more.=' /></td>';
                            $more.='<td align="left">';
                            $more.=$selval;
                            $more.='</td></tr>'."\n";
                            $i++;
                        }
                    }
                    else if ($input['type'] == 'date')
                    {
                        $more.='<tr><td>'.$input['label'].'</td>';
                        $more.='<td colspan="2" align="left">';
                        $more.=$this->form->selectDate($input['value'], $input['name'], 0, 0, 0, '', 1, 0);
                        $more.='</td></tr>'."\n";
                        $formquestion[] = array('name'=>$input['name'].'day');
                        $formquestion[] = array('name'=>$input['name'].'month');
                        $formquestion[] = array('name'=>$input['name'].'year');
                        $formquestion[] = array('name'=>$input['name'].'hour');
                        $formquestion[] = array('name'=>$input['name'].'min');
                    }
                    else if ($input['type'] == 'other')
                    {
                        $more.='<tr><td>';
                        if (! empty($input['label'])) $more.=$input['label'].'</td><td colspan="2" align="left">';
                        $more.=$input['value'];
                        $more.='</td></tr>'."\n";
                    }

                    else if ($input['type'] == 'onecolumn')
                    {
                        $more.='<tr><td colspan="3" align="left">';
                        $more.=$input['value'];
                        $more.='</td></tr>'."\n";
                    }
                }
            }
            $more.='</table>'."\n";
        }

        // JQUI method dialog is broken with jmobile, we use standard HTML.
        // Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
        // See page product/card.php for example
        if (! empty($conf->dol_use_jmobile)) $useajax=0;
        if (empty($conf->use_javascript_ajax)) $useajax=0;

        if ($useajax)
        {
            $autoOpen=true;
            $dialogconfirm='dialog-confirm';
            $button='';
            if (! is_numeric($useajax))
            {
                $button=$useajax;
                $useajax=1;
                $autoOpen=false;
                $dialogconfirm.='-'.$button;
            }
            $pageyes=$page.(preg_match('/\?/',$page)?'&':'?').'action='.$action.'&confirm=yes';
            $pageno=($useajax == 2 ? $page.(preg_match('/\?/',$page)?'&':'?').'confirm=no':'');
            // Add input fields into list of fields to read during submit (inputok and inputko)
            if (is_array($formquestion))
            {
                foreach ($formquestion as $key => $input)
                {
                    //print "xx ".$key." rr ".is_array($input)."<br>\n";
                    if (is_array($input) && isset($input['name'])) array_push($inputok,$input['name']);
                    if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko,$input['name']);
                }
            }
            // Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
            $formconfirm.= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
            if (! empty($more)) {
                $formconfirm.= '<div class="confirmquestions">'.$more.'</div>';
            }
            $formconfirm.= ($question ? '<div class="confirmmessage">'.img_help('','').' '.$question . '</div>': '');
            $formconfirm.= '</div>'."\n";

            $formconfirm.= "\n<!-- begin ajax form_confirm page=".$page." -->\n";
            $formconfirm.= '<script type="text/javascript">'."\n";
            $formconfirm.= 'jQuery(document).ready(function() {
            $(function() {
            	$( "#'.$dialogconfirm.'" ).dialog(
            	{
                    autoOpen: '.($autoOpen ? "true" : "false").',';
            if ($newselectedchoice == 'no')
            {
                $formconfirm.='
						open: function() {
            				$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
            }
            $formconfirm.='
                    resizable: false,
                    height: "'.$height.'",
                    width: "'.$width.'",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "'.dol_escape_js($langs->transnoentities("OK")).'": function() {
                        	var options="";
                        	var inputok = '.json_encode($inputok).';
                         	var pageyes = "'.dol_escape_js(! empty($pageyes)?$pageyes:'').'";
                         	if (inputok.length>0) {
                         		$.each(inputok, function(i, inputname) {
                         			var more = "";
                         			if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         		    if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
                         			var inputvalue = $("#" + inputname + more).val();
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         		});
                         	}
                         	var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
                         	//alert(urljump);
            				if (pageyes.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        },
                        "'.dol_escape_js($langs->transnoentities("Annuler")).'": function() {
                        	var options = "";
                         	var inputko = '.json_encode($inputko).';
                         	var pageno="'.dol_escape_js(! empty($pageno)?$pageno:'').'";
                         	if (inputko.length>0) {
                         		$.each(inputko, function(i, inputname) {
                         			var more = "";
                         			if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         			var inputvalue = $("#" + inputname + more).val();
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         		});
                         	}
                         	var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
                         	//alert(urljump);
            				if (pageno.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        }
                    }
                }
                );

            	var button = "'.$button.'";
            	if (button.length > 0) {
                	$( "#" + button ).click(function() {
                		$("#'.$dialogconfirm.'").dialog("open");
        			});
                }
            });
            });
            </script>';
            $formconfirm.= "<!-- end ajax form_confirm -->\n";
        }
        else
        {
            $formconfirm.= "\n<!-- begin form_confirm page=".$page." -->\n";

            if (empty($disableformtag)) $formconfirm.= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";

            $formconfirm.= '<input type="hidden" name="action" value="'.$action.'">'."\n";
            if (empty($disableformtag)) $formconfirm.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";

            $formconfirm.= '<table width="100%" class="valid">'."\n";

            // Line title
            $formconfirm.= '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>'."\n";

            // Line form fields
            if ($more)
            {
                $formconfirm.='<tr class="valid"><td class="valid" colspan="3">'."\n";
                $formconfirm.=$more;
                $formconfirm.='</td></tr>'."\n";
            }

            // Line with question
            $formconfirm.= '<tr class="valid">';
            $formconfirm.= '<td class="valid">'.$question.'</td>';
            $formconfirm.= '<td class="valid">';
            $formconfirm.= $this->selectyesno("confirm",$newselectedchoice);
            $formconfirm.= '</td>';
            $formconfirm.= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="'.$langs->trans("Validate").'"></td>';
            $formconfirm.= '</tr>'."\n";

            $formconfirm.= '</table>'."\n";

            if (empty($disableformtag)) $formconfirm.= "</form>\n";
            $formconfirm.= '<br>';

            $formconfirm.= "<!-- end form_confirm -->\n";
        }

        return $formconfirm;
    }


    /**
     * Get all relation contact for select
     *
     * @return  array   List of all relation contact
     */
    public function getAllRelationContactList()
    {
        global $langs;

        $relationContactList = array();

        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionary = Dictionary::getDictionary($this->db, 'relationstierscontacts', 'relationcontact');

        // Get lines
        $lines = $dictionary->fetch_lines(1, array(), array('position' => 'ASC'), 0, 0, false, true);

        if ($lines < 0) {
            $this->error = $dictionary->errorsToString();
            dol_syslog(__METHOD__ . " Error : No relation contact in dictionary", LOG_ERR);
        } else {
            if (count($lines) <= 0) {
                $this->error = $langs->trans("RTCErrorRelationContactDictionaryNoLines");
                dol_syslog(__METHOD__ . " Error : No lines in dictionary relation contact", LOG_ERR);
            } else {
                foreach($lines as $line) {
                    $relationContactList[$line->id . '_0'] = $line->fields['label_a_b'];
                    $relationContactList[$line->id . '_1'] = $line->fields['label_b_a'];
                }
            }
        }

        return $relationContactList;
    }


    /**
     * Get all relation tiers for select
     *
     * @param   int     $sens       [=0] Label from Thirdparty to Contact, =1 Label from Contact to Thirdparty
     * @return  array   List of all relation tiers
     */
    public function getAllRelationTiersList($sens=0)
    {
        global $langs;

        $relationTiersList = array();

        dol_include_once('/advancedictionaries/class/dictionary.class.php');

        $dictionary = Dictionary::getDictionary($this->db, 'relationstierscontacts', 'relationtiers');

        // Get lines
        $lines = $dictionary->fetch_lines(1, array(), array('position' => 'ASC'), 0, 0, false, true);

        if ($lines < 0) {
            $this->error = $dictionary->errorsToString();
            dol_syslog(__METHOD__ . " Error : No relation thirdparty in dictionary", LOG_ERR);
        } else {
            if (count($lines) <= 0) {
                $this->error = $langs->trans("RTCErrorRelationTiersDictionaryNoLines");
                dol_syslog(__METHOD__ . " Error : No lines in dictionary relation thirdparty", LOG_ERR);
            } else {
                $fieldRalationLabel = 'label_a_b';
                if ($sens == 1) {
                    $fieldRalationLabel = 'label_b_a';
                }

                foreach($lines as $line) {
                    $relationTiersList[$line->id] = $line->fields[$fieldRalationLabel];
                }
            }
        }

        return $relationTiersList;
    }


    /**
     * Show html select for all relation contact
     *
     * @param	string			$htmlname			Name of html select area. Must start with "multi" if this is a multiselect
     * @param	array			$array				Array (key => value)
     * @param	string|string[]	$id					Preselected key or preselected keys for multiselect
     * @param	int|string		$show_empty			0 no empty value allowed, 1 or string to add an empty value into list (key is -1 and value is '' or '&nbsp;' if 1, key is -1 and value is text if string), <0 to add an empty value with key that is this value.
     * @param	int				$key_in_label		1 to show key into label with format "[key] value"
     * @param	int				$value_as_key		1 to use value as key
     * @param   string			$moreparam			Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   int				$translate			1=Translate and encode value
     * @param	int				$maxlen				Length maximum for labels
     * @param	int				$disabled			Html select box is disabled
     * @param	string			$sort				'ASC' or 'DESC' = Sort on label, '' or 'NONE' or 'POS' = Do not sort, we keep original order
     * @param	string			$morecss			Add more class to css styles
     * @param	int				$addjscombo			Add js combo
     * @param   string          $moreparamonempty	Add more param on the empty option line. Not used if show_empty not set
     * @param   int             $disablebademail	Check if an email is found into value and if not disable and colorize entry
     * @param   int             $nohtmlescape		No html escaping.
     * @param   int             $show               [=1] to print, 0 not to print
     * @return	string|void		HTML select string or Print
     */
    public function selectAllRelationContact($htmlname, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $moreparam='', $translate=0, $maxlen=0, $disabled=0, $sort='', $morecss='', $addjscombo=0, $moreparamonempty='', $disablebademail=0, $nohtmlescape=0, $show=1)
    {
        $relationContactList = $this->getAllRelationContactList();

        $out = $this->selectRelationContact($htmlname, $relationContactList, $id, $show_empty, $key_in_label, $value_as_key, $moreparam, $translate, $maxlen, $disabled, $sort, $morecss, $addjscombo, $moreparamonempty, $disablebademail, $nohtmlescape);

        if ($show) {
            print $out;
        } else {
            return $out;
        }
    }


    /**
     * Show html select for all relation thirdparty
     *
     * @param	string			$htmlname			Name of html select area. Must start with "multi" if this is a multiselect
     * @param	array			$array				Array (key => value)
     * @param	string|string[]	$id					Preselected key or preselected keys for multiselect
     * @param	int|string		$show_empty			0 no empty value allowed, 1 or string to add an empty value into list (key is -1 and value is '' or '&nbsp;' if 1, key is -1 and value is text if string), <0 to add an empty value with key that is this value.
     * @param	int				$key_in_label		1 to show key into label with format "[key] value"
     * @param	int				$value_as_key		1 to use value as key
     * @param   string			$moreparam			Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   int				$translate			1=Translate and encode value
     * @param	int				$maxlen				Length maximum for labels
     * @param	int				$disabled			Html select box is disabled
     * @param	string			$sort				'ASC' or 'DESC' = Sort on label, '' or 'NONE' or 'POS' = Do not sort, we keep original order
     * @param	string			$morecss			Add more class to css styles
     * @param	int				$addjscombo			Add js combo
     * @param   string          $moreparamonempty	Add more param on the empty option line. Not used if show_empty not set
     * @param   int             $disablebademail	Check if an email is found into value and if not disable and colorize entry
     * @param   int             $nohtmlescape		No html escaping.
     * @param   int             $show               [=1] to print, 0 not to print
     * @param   int             $sens               [=0] Label from Thirdparty to Contact, =1 Label from Contact to Thirdparty
     * @return	string|void		HTML select string or Print
     */
    public function selectAllRelationTiers($htmlname, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $moreparam='', $translate=0, $maxlen=0, $disabled=0, $sort='', $morecss='', $addjscombo=0, $moreparamonempty='', $disablebademail=0, $nohtmlescape=0, $show=1, $sens=0)
    {
        $relationTiersList = $this->getAllRelationTiersList($sens);

        $out = $this->selectRelationTiers($htmlname, $relationTiersList, $id, $show_empty, $key_in_label, $value_as_key, $moreparam, $translate, $maxlen, $disabled, $sort, $morecss, $addjscombo, $moreparamonempty, $disablebademail, $nohtmlescape);

        if ($show) {
            print $out;
        } else {
            return $out;
        }
    }


    /**
     *	Return a HTML select string, built from an array of key+value.
     *  Note: Do not apply langs->trans function on returned content, content may be entity encoded twice.
     *
     *	@param	string			$htmlname			Name of html select area. Must start with "multi" if this is a multiselect
     *	@param	array			$array				Array (key => value)
     *	@param	string|string[]	$id					Preselected key or preselected keys for multiselect
     *	@param	int|string		$show_empty			0 no empty value allowed, 1 or string to add an empty value into list (key is -1 and value is '' or '&nbsp;' if 1, key is -1 and value is text if string), <0 to add an empty value with key that is this value.
     *	@param	int				$key_in_label		1 to show key into label with format "[key] value"
     *	@param	int				$value_as_key		1 to use value as key
     *	@param  string			$moreparam			Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     *	@param  int				$translate			1=Translate and encode value
     * 	@param	int				$maxlen				Length maximum for labels
     * 	@param	int				$disabled			Html select box is disabled
     *  @param	string			$sort				'ASC' or 'DESC' = Sort on label, '' or 'NONE' or 'POS' = Do not sort, we keep original order
     *  @param	string			$morecss			Add more class to css styles
     *  @param	int				$addjscombo			Add js combo
     *  @param  string          $moreparamonempty	Add more param on the empty option line. Not used if show_empty not set
     *  @param  int             $disablebademail	Check if an email is found into value and if not disable and colorize entry
     *  @param  int             $nohtmlescape		No html escaping.
     * 	@return	string								HTML select string.
     *  @see multiselectarray
     */
    public function selectRelationContact($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $moreparam='', $translate=0, $maxlen=0, $disabled=0, $sort='', $morecss='', $addjscombo=0, $moreparamonempty='', $disablebademail=0, $nohtmlescape=0)
    {
        // select array
        $out = Form::selectarray($htmlname, $array, $id, $show_empty, $key_in_label, $value_as_key, $moreparam, $translate, $maxlen, $disabled, $sort, $morecss, $addjscombo, $moreparamonempty, $disablebademail, $nohtmlescape);

        return $out;
    }



    /**
     *	Return a HTML select string, built from an array of key+value.
     *  Note: Do not apply langs->trans function on returned content, content may be entity encoded twice.
     *
     *	@param	string			$htmlname			Name of html select area. Must start with "multi" if this is a multiselect
     *	@param	array			$array				Array (key => value)
     *	@param	string|string[]	$id					Preselected key or preselected keys for multiselect
     *	@param	int|string		$show_empty			0 no empty value allowed, 1 or string to add an empty value into list (key is -1 and value is '' or '&nbsp;' if 1, key is -1 and value is text if string), <0 to add an empty value with key that is this value.
     *	@param	int				$key_in_label		1 to show key into label with format "[key] value"
     *	@param	int				$value_as_key		1 to use value as key
     *	@param  string			$moreparam			Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     *	@param  int				$translate			1=Translate and encode value
     * 	@param	int				$maxlen				Length maximum for labels
     * 	@param	int				$disabled			Html select box is disabled
     *  @param	string			$sort				'ASC' or 'DESC' = Sort on label, '' or 'NONE' or 'POS' = Do not sort, we keep original order
     *  @param	string			$morecss			Add more class to css styles
     *  @param	int				$addjscombo			Add js combo
     *  @param  string          $moreparamonempty	Add more param on the empty option line. Not used if show_empty not set
     *  @param  int             $disablebademail	Check if an email is found into value and if not disable and colorize entry
     *  @param  int             $nohtmlescape		No html escaping.
     * 	@return	string								HTML select string.
     *  @see multiselectarray
     */
    public function selectRelationTiers($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $moreparam='', $translate=0, $maxlen=0, $disabled=0, $sort='', $morecss='', $addjscombo=0, $moreparamonempty='', $disablebademail=0, $nohtmlescape=0)
    {
        // select array
        $out = Form::selectarray($htmlname, $array, $id, $show_empty, $key_in_label, $value_as_key, $moreparam, $translate, $maxlen, $disabled, $sort, $morecss, $addjscombo, $moreparamonempty, $disablebademail, $nohtmlescape);

        return $out;
    }


    /**
     * Show html area for list of contacts
     *
     * @param   Conf		$conf		Object conf
     * @param   Translate	$langs		Object langs
     * @param   DoliDB		$db			Database handler
     * @param   Societe		$object		Third party object
     * @param   string		$backtopage	Url to go once contact is created
     * @return	void
     */
    public static function show_contacts($conf, $langs, $db, $object, $backtopage='')
    {
        global $extrafields, $hookmanager, $user;
        global $contextpage;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        dol_include_once('/relationstierscontacts/class/relationtiers.class.php');

        $form = new Form($db);

        $optioncss = GETPOST('optioncss', 'alpha');
        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');
        $page = GETPOST('page','int');
        $search_status		= GETPOST("search_status",'int');
        if ($search_status=='') $search_status=1; // always display activ customer first
        $search_name = GETPOST("search_name",'alpha');
        $search_addressphone = GETPOST("search_addressphone",'alpha');

        if (! $sortorder) $sortorder="ASC";
        if (! $sortfield) $sortfield="t.lastname";

        if (! empty($conf->clicktodial->enabled))
        {
            $user->fetch_clicktodial(); // lecture des infos de clicktodial du user
        }

        $contactstatic = new Contact($db);
        $relationTiersStatic = new RelationTiers($db);

        $extralabels=$extrafields->fetch_name_optionals_label($contactstatic->table_element);

        $contactstatic->fields=array(
            'label_a_b'          => array('type'=>'varchar(255)', 'label'=>'RTCRelationTiersLabel', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>1, 'sort' => 'relation_label'),
            'name'               => array('type'=>'varchar(128)', 'label'=>'Name',                  'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1),
            'is_main_thirdparty' => array('type'=>'integer',      'label'=>'RTCRelationTiersMainThirdparty', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>11, 'sort' => '', 'align' => 'center'),
            'poste'              => array('type'=>'varchar(128)', 'label'=>'PostOfFunction',        'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>20),
            'address'            => array('type'=>'varchar(128)', 'label'=>'Address',               'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>30),
            'date_debut'            => array('type'=>'date', 'label'=>'Begin',               'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>40),
            'date_fin'            => array('type'=>'date', 'label'=>'End',               'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>50),
            'statut'             => array('type'=>'integer',      'label'=>'Status',                'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'default'=>0, 'index'=>1,  'position'=>60, 'arrayofkeyval'=>array(0=>$contactstatic->LibStatut(0,1), 1=>$contactstatic->LibStatut(1,1))),
        );

        // Definition of fields for list
        $arrayfields=array(
            't.label_a_b'          => array('label'=>'RTCRelationTiersLabel', 'checked'=>1, 'position'=>1),
            't.rowid'              => array('label'=>"TechnicalID", 'checked'=>($conf->global->MAIN_SHOW_TECHNICAL_ID?1:0), 'enabled'=>($conf->global->MAIN_SHOW_TECHNICAL_ID?1:0), 'position'=>1),
            't.name'               => array('label'=>"Name", 'checked'=>1, 'position'=>10),
            't.is_main_thirdparty' => array('label'=>'RTCRelationTiersMainThirdparty', 'checked'=>1, 'position'=>11),
            't.poste'              => array('label'=>"PostOrFunction", 'checked'=>1, 'position'=>20),
            't.address'            => array('label'=>(empty($conf->dol_optimize_smallscreen) ? $langs->trans("Address").' / '.$langs->trans("Phone").' / '.$langs->trans("Email") : $langs->trans("Address")), 'checked'=>1, 'position'=>30),
            'rt.date_debut'            => array('label'=> 'RTCRelationTiersBegin', 'checked'=>1, 'position'=>40),
            'rt.date_fin'            => array('label'=> 'RTCRelationTiersEnd', 'checked'=>1, 'position'=>50),
            't.statut'             => array('label'=>"Status", 'checked'=>1, 'position'=>40, 'align'=>'center'),
        );
        // Extra fields
        if (is_array($extrafields->attributes[$contactstatic->table_element]['label']) && count($extrafields->attributes[$contactstatic->table_element]['label']))
        {
            foreach($extrafields->attributes[$contactstatic->table_element]['label'] as $key => $val)
            {
                if (! empty($extrafields->attributes[$contactstatic->table_element]['list'][$key])) {
                    // Load language if required
                    if (! empty($extrafields->attributes[$contactstatic->table_element]['langfile'][$key])) $langs->load($extrafields->attributes[$contactstatic->table_element]['langfile'][$key]);

                    $arrayfields["ef.".$key]=array(
                        'label'=>$extrafields->attributes[$contactstatic->table_element]['label'][$key],
                        'checked'=>(($extrafields->attributes[$contactstatic->table_element]['list'][$key]<0)?0:1),
                        'position'=>$extrafields->attributes[$contactstatic->table_element]['pos'][$key],
                        'enabled'=>(abs($extrafields->attributes[$contactstatic->table_element]['list'][$key])!=3 && $extrafields->attributes[$contactstatic->table_element]['perms'][$key]));
                }
            }
        }

        // Initialize array of search criterias
        $search=array();
        foreach($contactstatic->fields as $key => $val)
        {
            if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
        }
        $search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

        // Purge search criteria
        if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
        {
            $search_status		 = '';
            $search_name         = '';
            $search_addressphone = '';
            $search_array_options=array();

            foreach($contactstatic->fields as $key => $val)
            {
                $search[$key]='';
            }
            $toselect='';
        }

        $contactstatic->fields = dol_sort_array($contactstatic->fields, 'position');
        $arrayfields = dol_sort_array($arrayfields, 'position');

        $newcardbutton='';
        if ($user->rights->societe->contact->creer)
        {
            $addcontact = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));

            if (version_compare(DOL_VERSION, '10.0.0', '>=')) {
                // Easya compatibility
                $class_fonts_awesome = !empty($conf->global->EASYA_VERSION) ? 'fal' : 'fa';
                $newcardbutton = dolGetButtonTitle($addcontact, '', $class_fonts_awesome.' fa-plus-circle', DOL_URL_ROOT.'/contact/card.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage));
            } else {
                $newcardbutton = '<a class="butActionNew" href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage).'"><span class="valignmiddle">'.$addcontact.'</span>';
                $newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
                $newcardbutton.= '</a>';
            }
        }
        if ($user->rights->relationstierscontacts->relationtiers->creer)
        {
            $addrelationtiers = $langs->trans('RTCRelationTiersCreate');

            if (version_compare(DOL_VERSION, '10.0.0', '>=')) {
                // Easya compatibility
                $class_fonts_awesome = !empty($conf->global->EASYA_VERSION) ? 'fal' : 'fa';
                $newcardbutton .= dolGetButtonTitle($addrelationtiers, '', $class_fonts_awesome.' fa-plus-circle',  $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=relation_create');
            } else {
                if ($newcardbutton) $newcardbutton .= ' | ';
                $newcardbutton .= '<a class="butActionNew" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=relation_create"><span class="valignmiddle">' . $addrelationtiers . '</span>';
                $newcardbutton .= '<span class="fa fa-plus-circle valignmiddle"></span>';
                $newcardbutton .= '</a>';
            }
        }

        print "\n";

        //$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ContactsForCompany") : $langs->trans("ContactsAddressesForCompany"));
        $title = $langs->trans("RTCRelationTiersListTitle");
        print load_fiche_titre($title, $newcardbutton,'');

        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="socid" value="'.$object->id.'">';
        print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';

        $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
        $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
        //if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

        print '<div class="div-table-responsive-no-min">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
        print "\n".'<table class="tagtable liste">'."\n";

        $param="socid=".urlencode($object->id);
        if ($search_status != '') $param.='&search_status='.urlencode($search_status);
        if ($search_name != '')   $param.='&search_name='.urlencode($search_name);
        if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
        // Add $param from extra fields
        $extrafieldsobjectkey=$contactstatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

        $sql  = "SELECT t.rowid, t.lastname, t.firstname, t.fk_pays as country_id, t.civility, t.poste, t.phone as phone_pro, t.phone_mobile, t.phone_perso, t.fax, t.email, t.socialnetworks, t.statut, t.photo, t.civility as civility_id, t.address, t.zip, t.town";
        $sql .= ", rt.rowid as rt_id, rt.fk_soc as rt_socid, rt.date_debut, rt.date_fin, crt.label_a_b as relation_label";
        $sql .= " FROM " . MAIN_DB_PREFIX . "relationtiers as rt";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as t ON t.rowid = rt.fk_socpeople";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople_extrafields as ef on (t.rowid = ef.fk_object)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_relationtiers as crt ON crt.rowid = rt.fk_c_relationtiers";
        $sql .= " WHERE rt.fk_soc = " . $object->id;
        if ($search_status!='' && $search_status != '-1') $sql .= " AND t.statut = ".$db->escape($search_status);
        if ($search_name) $sql .= natural_search(array('t.lastname', 't.firstname'), $search_name);
        // Add where from extra fields
        $extrafieldsobjectkey=$contactstatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
        if ($sortfield == "t.name") $sql.=" ORDER BY t.lastname $sortorder, t.firstname $sortorder";
        else $sql.= " ORDER BY $sortfield $sortorder";

        $result = $db->query($sql);
        if (! $result) dol_print_error($db);

        $num = $db->num_rows($result);

        // Fields title search
        // --------------------------------------------------------------------
        print '<tr class="liste_titre">';
        foreach($contactstatic->fields as $key => $val)
        {
            $align='';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
            if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
            if ($key == 'status' || $key == 'statut') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['t.'.$key]['checked']))
            {
                print '<td class="liste_titre'.($align?' '.$align:'').'">';
                if (in_array($key, array('lastname','name'))) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
                elseif (in_array($key, array('statut'))) print $form->selectarray('search_status', array('-1'=>'','0'=>$contactstatic->LibStatut(0,1),'1'=>$contactstatic->LibStatut(1,1)),$search_status);
                print '</td>';
            }
        }

        // Add Begin / End columns.
        foreach((array) $relationTiersStatic as $key => $val)
        {
        	if ( $key !== 'date_debut' && $key !== 'date_fin' ) {

        		continue;
	        }

            $align='';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
	        if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
	        if ($key == 'status' || $key == 'statut') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['rt.'.$key]['checked']))
            {
                print '<td class="liste_titre'.($align?' '.$align:'').'">';
                print '</td>';
            }
        }

        // Extra fields
        $extrafieldsobjectkey=$contactstatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

        // Fields from hook
        $parameters=array('arrayfields'=>$arrayfields);
        $reshook=$hookmanager->executeHooks('printFieldListOption', $parameters, $contactstatic);    // Note that $action and $object may have been modified by hook
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
        foreach($contactstatic->fields as $key => $val)
        {
            $align = isset($val['align']) ? $val['align'] : '';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
            if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
            if ($key == 'status' || $key == 'statut') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['t.'.$key]['checked'])) print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], isset($val['sort']) ? $val['sort'] : 't.'.$key, '', $param, ($align?'class="'.$align.'"':''), $sortfield, $sortorder, $align.' ')."\n";
        }

	    // Add Begin / End columns.
	    foreach((array) $relationTiersStatic as $key => $val)
	    {
		    if ( $key !== 'date_debut' && $key !== 'date_fin' ) {

			    continue;
		    }

            $align = isset($val['align']) ? $val['align'] : '';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
            if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
            if ($key == 'status' || $key == 'statut') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['rt.'.$key]['checked'])) print getTitleFieldOfList($arrayfields['rt.'.$key]['label'], 0, $_SERVER['PHP_SELF'], isset($val['sort']) ? $val['sort'] : 'rt.'.$key, '', $param, ($align?'class="'.$align.'"':''), $sortfield, $sortorder, $align.' ')."\n";
        }

        // Extra fields
        $extrafieldsobjectkey=$contactstatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
        // Hook fields
        $parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
        $reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"],'','','','align="center"',$sortfield,$sortorder,'maxwidthsearch ')."\n";
        print '</tr>'."\n";

        $i = -1;

        if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x')))
        {
            $i = 0;

            while ($i < $num)
            {
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
                $contactstatic->web = $obj->web;
                $contactstatic->socialnetworks = $obj->socialnetworks;
                $contactstatic->photo = $obj->photo;

                $country_code = getCountry($obj->country_id, 2);
                $contactstatic->country_code = $country_code;

                $contactstatic->setGenderFromCivility();
                $contactstatic->fetch_optionals();

                $relationTiersStatic->id = $obj->rt_id;
                $relationTiersStatic->fetch($relationTiersStatic->id);

                if (is_array($contactstatic->array_options))
                {
                    foreach($contactstatic->array_options as $key => $val)
                    {
                        $obj->$key = $val;
                    }
                }

                print '<tr class="oddeven">';

                // Relation label
                if (! empty($arrayfields['t.label_a_b']['checked']))
                {
                    print '<td>';
                    if ($obj->relation_label) print $obj->relation_label;
                    print '</td>';
                }

                // ID
                if (! empty($arrayfields['t.rowid']['checked']))
                {
                    print '<td>';
                    print $contactstatic->id;
                    print '</td>';
                }

                // Photo - Name
                if (! empty($arrayfields['t.name']['checked']))
                {
                    print '<td>';
                    print $form->showphoto('contact',$contactstatic,0,0,0,'photorefnoborder valignmiddle marginrightonly','small',1,0,1);
                    print $contactstatic->getNomUrl(0,'',0,'&backtopage='.urlencode($backtopage));
                    print '</td>';
                }

                if (! empty($arrayfields['t.is_main_thirdparty']['checked'])) {
                    print '<td align="center">';
                    $rtcStaticIsMainThirdpartyChecked = '';
                    if ($relationTiersStatic->isMainThirdparty(TRUE)) {
                        $rtcStaticIsMainThirdpartyChecked = ' checked="checked"';
                    }
                    print '<input type="checkbox" name="rtc_static_is_main_thirdparty"' . $rtcStaticIsMainThirdpartyChecked . ' disabled />';
                    print '</td>';
                }

                // Job position
                if (! empty($arrayfields['t.poste']['checked']))
                {
                    print '<td>';
                    if ($obj->poste) print $obj->poste;
                    print '</td>';
                }

                // Address - Phone - Email
                if (! empty($arrayfields['t.address']['checked']))
                {
                    print '<td>';
                    print $contactstatic->getBannerAddress('contact', $object);
                    print '</td>';
                }

	            // Status
	            if (! empty($arrayfields['t.statut']['checked']))
	            {
		            print '<td align="center">'.$contactstatic->getLibStatut(5).'</td>';
	            }

                // Begin
                if (! empty($arrayfields['rt.date_debut']['checked']))
                {
                    print '<td class="ici">';
                    print dol_print_date( $relationTiersStatic->date_debut );
                    print '</td>';
                }

                // End
                if (! empty($arrayfields['rt.date_fin']['checked']))
                {
                    print '<td>';
	                print dol_print_date( $relationTiersStatic->date_fin );
                    print '</td>';
                }

                // Extra fields
                $extrafieldsobjectkey=$contactstatic->table_element;
                include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

                // Actions
                print '<td align="right">';

                // Add to agenda
                if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
                {
                    print '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&actioncode=&contactid='.$obj->rowid.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">';
                    print img_object($langs->trans("Event"),"action");
                    print '</a> &nbsp; ';
                }

                // Edit contact
                if ($user->rights->societe->contact->creer)
                {
                    print '<a href="' . DOL_URL_ROOT . '/contact/card.php?action=edit&id=' . $obj->rowid . '&socid=' . $object->id . '&id_relationtiers=' . $relationTiersStatic->id . '&backtopage=' . urlencode($backtopage) . '">';
                    print img_edit();
                    print '</a>';
                }

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


    /**
     * Show html area for list of contacts of all child company
     *
     * @param	Conf		$conf		Object conf
     * @param	Translate	$langs		Object langs
     * @param	DoliDB		$db			Database handler
     * @param	Societe		$object		Third party object
     * @return  void
     *
     * @throws  Exception
     */
    public static function show_all_child_contacts($conf, $langs, $db, $object)
    {
        global $extrafields, $hookmanager, $user;
        global $contextpage;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        dol_include_once('/relationstierscontacts/class/relationtiers.class.php');

        $form = new Form($db);

        $optioncss = GETPOST('optioncss', 'alpha');
        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');
        $page = GETPOST('page','int');
        $search_status		= GETPOST("search_status",'int');
        if ($search_status=='') $search_status=1; // always display activ customer first
        $search_name = GETPOST("search_name",'alpha');
        $search_addressphone = GETPOST("search_addressphone",'alpha');

        if (! $sortorder) $sortorder="ASC";
        if (! $sortfield) $sortfield="t.lastname";

        if (! empty($conf->clicktodial->enabled))
        {
            $user->fetch_clicktodial(); // lecture des infos de clicktodial du user
        }

        $contactstatic = new Contact($db);
        $relationTiersStatic = new RelationTiers($db);
        $societeStatic = new Societe($db);

        $extralabels=$extrafields->fetch_name_optionals_label($contactstatic->table_element);

        $contactstatic->fields=array(
            'fk_soc'    => array('type'=>'varchar(255)', 'label'=>'RTCRelationTiersThirdparty', 'enabled'=>0, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>1, 'sort' => 'rt_socid'),
            'label_a_b' => array('type'=>'varchar(255)', 'label'=>'RTCRelationTiersLabel',      'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>2, 'sort' => 'relation_label'),
            'name'      => array('type'=>'varchar(128)', 'label'=>'Name',                       'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1),
            'poste'     => array('type'=>'varchar(128)', 'label'=>'PostOfFunction',             'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>20),
            'address'   => array('type'=>'varchar(128)', 'label'=>'Address',                    'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>30),
            'statut'    => array('type'=>'integer',      'label'=>'Status',                     'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'default'=>0, 'index'=>1,  'position'=>40, 'arrayofkeyval'=>array(0=>$contactstatic->LibStatut(0,1), 1=>$contactstatic->LibStatut(1,1))),
        );

        // Definition of fields for list
        $arrayfields=array(
            't.fk_soc' => array('label'=>'RTCRelationTiersThirdparty', 'checked'=>1, 'position'=>1),
            't.label_a_b' => array('label'=>'RTCRelationTiersLabel', 'checked'=>1, 'position'=>1),
            't.rowid'=>array('label'=>"TechnicalID", 'checked'=>($conf->global->MAIN_SHOW_TECHNICAL_ID?1:0), 'enabled'=>($conf->global->MAIN_SHOW_TECHNICAL_ID?1:0), 'position'=>1),
            't.name'=>array('label'=>"Name", 'checked'=>1, 'position'=>10),
            't.poste'=>array('label'=>"PostOrFunction", 'checked'=>1, 'position'=>20),
            't.address'=>array('label'=>(empty($conf->dol_optimize_smallscreen) ? $langs->trans("Address").' / '.$langs->trans("Phone").' / '.$langs->trans("Email") : $langs->trans("Address")), 'checked'=>1, 'position'=>30),
            't.statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>40, 'align'=>'center'),
        );
        // Extra fields
        if (is_array($extrafields->attributes[$contactstatic->table_element]['label']) && count($extrafields->attributes[$contactstatic->table_element]['label']))
        {
            foreach($extrafields->attributes[$contactstatic->table_element]['label'] as $key => $val)
            {
                if (! empty($extrafields->attributes[$contactstatic->table_element]['list'][$key])) {
                    $arrayfields["ef.".$key]=array(
                        'label'=>$extrafields->attributes[$contactstatic->table_element]['label'][$key],
                        'checked'=>(($extrafields->attributes[$contactstatic->table_element]['list'][$key]<0)?0:1),
                        'position'=>$extrafields->attributes[$contactstatic->table_element]['pos'][$key],
                        'enabled'=>(abs($extrafields->attributes[$contactstatic->table_element]['list'][$key])!=3 && $extrafields->attributes[$contactstatic->table_element]['perms'][$key]));
                }
            }
        }

        // Initialize array of search criterias
        $search=array();
        foreach($contactstatic->fields as $key => $val)
        {
            if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
        }
        $search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

        // Purge search criteria
        if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
        {
            $search_status		 = '';
            $search_name         = '';
            $search_addressphone = '';
            $search_array_options=array();

            foreach($contactstatic->fields as $key => $val)
            {
                $search[$key]='';
            }
            $toselect='';
        }

        $contactstatic->fields = dol_sort_array($contactstatic->fields, 'position');
        $arrayfields = dol_sort_array($arrayfields, 'position');


        //$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ContactsForCompany") : $langs->trans("ContactsAddressesForCompany"));
        $title = $langs->trans("RTCRelationTiersListChildTitle");
        print load_fiche_titre($title, '','');

        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="socid" value="'.$object->id.'">';
        print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';

        $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
        $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
        //if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

        print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
        print "\n".'<table class="tagtable liste">'."\n";

        $param="socid=".urlencode($object->id);
        if ($search_status != '') $param.='&search_status='.urlencode($search_status);
        if ($search_name != '')   $param.='&search_name='.urlencode($search_name);
        if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
        // Add $param from extra fields
        $extrafieldsobjectkey=$contactstatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

        // find all child id of this thirdpaty
        $allChildIdList = $relationTiersStatic->getAllChildIdList($object->id);
        $allChildIdList = is_array($allChildIdList) ? $allChildIdList : array();
        $allChildIdList[$object->id] = $object->id;

        $sql  = "SELECT t.rowid, t.lastname, t.firstname, t.fk_pays as country_id, t.civility, t.poste, t.phone as phone_pro, t.phone_mobile, t.phone_perso, t.fax, t.email, t.socialnetworks, t.statut, t.photo, t.civility as civility_id, t.address, t.zip, t.town";
        $sql .= ", rt.rowid as rt_id, rt.fk_soc as rt_socid, crt.label_a_b as relation_label";
        $sql .= " FROM " . MAIN_DB_PREFIX . "relationtiers as rt";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as t ON t.rowid = rt.fk_socpeople";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople_extrafields as ef on (t.rowid = ef.fk_object)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_relationtiers as crt ON crt.rowid = rt.fk_c_relationtiers";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = rt.fk_soc";
        $sql .= " WHERE s.parent IN (" . implode(' ,', $allChildIdList) . ")";
        if ($search_status!='' && $search_status != '-1') $sql .= " AND t.statut = ".$db->escape($search_status);
        if ($search_name) $sql .= natural_search(array('t.lastname', 't.firstname'), $search_name);
        // Add where from extra fields
        $extrafieldsobjectkey=$contactstatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
        if ($sortfield == "t.name") $sql.=" ORDER BY t.lastname $sortorder, t.firstname $sortorder";
        else $sql.= " ORDER BY $sortfield $sortorder";

        $result = $db->query($sql);
        if (! $result) dol_print_error($db);

        $num = $db->num_rows($result);

        // Fields title search
        // --------------------------------------------------------------------
        print '<tr class="liste_titre">';
        foreach($contactstatic->fields as $key => $val)
        {
            $align='';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
            if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
            if ($key == 'status' || $key == 'statut') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['t.'.$key]['checked']))
            {
                print '<td class="liste_titre'.($align?' '.$align:'').'">';
                if (in_array($key, array('lastname','name'))) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
                elseif (in_array($key, array('statut'))) print $form->selectarray('search_status', array('-1'=>'','0'=>$contactstatic->LibStatut(0,1),'1'=>$contactstatic->LibStatut(1,1)),$search_status);
                print '</td>';
            }
        }
        // Extra fields
        $extrafieldsobjectkey=$contactstatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

        // Fields from hook
        $parameters=array('arrayfields'=>$arrayfields);
        $reshook=$hookmanager->executeHooks('printFieldListOption', $parameters, $contactstatic);    // Note that $action and $object may have been modified by hook
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
        foreach($contactstatic->fields as $key => $val)
        {
            $align='';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
            if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
            if ($key == 'status' || $key == 'statut') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['t.'.$key]['checked'])) print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], isset($val['sort']) ? $val['sort'] : 't.'.$key, '', $param, ($align?'class="'.$align.'"':''), $sortfield, $sortorder, $align.' ')."\n";
        }
        // Extra fields
        $extrafieldsobjectkey=$contactstatic->table_element;
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
        // Hook fields
        $parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
        $reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"],'','','','align="center"',$sortfield,$sortorder,'maxwidthsearch ')."\n";
        print '</tr>'."\n";

        $i = -1;

        if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x')))
        {
            $i = 0;

            while ($i < $num)
            {
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
                $contactstatic->web = $obj->web;
                $contactstatic->socialnetworks = $obj->socialnetworks;
                $contactstatic->photo = $obj->photo;

                $country_code = getCountry($obj->country_id, 2);
                $contactstatic->country_code = $country_code;

                $contactstatic->setGenderFromCivility();
                $contactstatic->fetch_optionals();

                $relationTiersStatic->id = $obj->rt_id;

                if (is_array($contactstatic->array_options))
                {
                    foreach($contactstatic->array_options as $key => $val)
                    {
                        $obj->$key = $val;
                    }
                }

                print '<tr class="oddeven">';

                // Relation thirdparty
                if (! empty($arrayfields['t.fk_soc']['checked']))
                {
                    print '<td>';
                    if ($obj->rt_socid) {
                        $societeStatic->fetch($obj->rt_socid);
                        print $societeStatic->getNomUrl(1);
                    }
                    print '</td>';
                }

                // Relation label
                if (! empty($arrayfields['t.label_a_b']['checked']))
                {
                    print '<td>';
                    if ($obj->relation_label) print $obj->relation_label;
                    print '</td>';
                }

                // ID
                if (! empty($arrayfields['t.rowid']['checked']))
                {
                    print '<td>';
                    print $contactstatic->id;
                    print '</td>';
                }

                // Photo - Name
                if (! empty($arrayfields['t.name']['checked']))
                {
                    print '<td>';
                    print $form->showphoto('contact',$contactstatic,0,0,0,'photorefnoborder valignmiddle marginrightonly','small',1,0,1);
                    print $contactstatic->getNomUrl(0,'',0);
                    print '</td>';
                }

                // Job position
                if (! empty($arrayfields['t.poste']['checked']))
                {
                    print '<td>';
                    if ($obj->poste) print $obj->poste;
                    print '</td>';
                }

                // Address - Phone - Email
                if (! empty($arrayfields['t.address']['checked']))
                {
                    print '<td>';
                    print $contactstatic->getBannerAddress('contact', $object);
                    print '</td>';
                }

                // Status
                if (! empty($arrayfields['t.statut']['checked']))
                {
                    print '<td align="center">'.$contactstatic->getLibStatut(5).'</td>';
                }

                // Extra fields
                $extrafieldsobjectkey=$contactstatic->table_element;
                include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

                // Actions
                print '<td align="right">';
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

