<?php
/* Copyright (C) 2006-2011 	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2011      	Regis Houssin        	<regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2014 	Marcos García        	<marcosgdf@gmail.com>
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
 *  \file       htdocs/core/triggers/interface_99_modRelationsTiersContacts_RTCRelationContact.class.php
 *  \ingroup    relationstierscontacts
 *  \brief      File of class of triggers for relationstierscontacts module
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');


/**
 *  Class of triggers for relationstierscontacts module to manage contacts
 */
class InterfaceRTCRelationContact extends DolibarrTriggers
{
	public $family = 'relationstierscontacts';
	public $description = "Triggers of this module RelationsTiersContacts to manage relation contact.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'technic';

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * @param string		$action		Event action code
     * @param Object		$object     Object
     * @param User		    $user       Object user
     * @param Translate 	$langs      Object langs
     * @param conf		    $conf       Object conf
     * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->relationstierscontacts->enabled)) return 0;     // Module not active, we do nothing

        // Contact delete
        if ($action == 'CONTACT_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            dol_include_once('/relationstierscontacts/class/relationcontact.class.php');

            $langs->load('relationstierscontacts@relationstierscontacts');

            // delete all relations of this contact
            $relationContact = new RelationContact($this->db);
            $ret = $relationContact->deleteAllByFkSocpeople($object->id, $user);

            if ($ret < 0) {
                return -1;
            } else {
                return 1;
            }
        }

        return 0;
    }
}
