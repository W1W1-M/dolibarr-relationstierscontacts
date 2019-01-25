<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2014 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *  \file       htdocs/core/triggers/interface_98_modRelationsTiersContacts_RTCActionComm.class.php
 *  \ingroup    relationstierscontacts
 *  \brief      File of class of triggers for relationstierstiers module
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/relationstierscontacts/lib/relationstierscontacts.lib.php');


/**
 *  Class of triggers for relationstierscontacts module to manage thirdparties
 */
class InterfaceRTCActionComm extends DolibarrTriggers
{
	public $family = 'relationstierscontacts';
	public $description = "Triggers of this module RelationsTiersContacts to manage events on relations.";
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

        if ($action == 'ACTION_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            dol_include_once('/relationstierscontacts/class/relationtiers.class.php');

            $langs->load('relationstierscontacts@relationstierscontacts');

            if ($object->id > 0) {
                $sql  = "SELECT";
                $sql .= " t.rowid";
                $sql .= ", t.fk_soc";
                $sql .= ", t.fk_socpeople";
                $sql .= ", t.fk_c_relationtiers";
                $sql .= ", t.fk_actioncomm";
                $sql .= ", t.date_debut";
                $sql .= ", t.date_fin";
                $sql .= ", t.commentaire";
                $sql .= " FROM " . MAIN_DB_PREFIX . "relationtiers as t";
                $sql .= " WHERE t.fk_actioncomm = " . $object->id;

                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->errors[] = $this->db->lasterror();
                    return -1;
                } else {
                    $num = $this->db->num_rows($resql);
                    if ($num > 0) {
                        $obj = $this->db->fetch_object($resql);

                        $relationTiers                     = new RelationTiers($this->db);
                        $relationTiers->id                 = $obj->rowid;
                        $relationTiers->fk_soc             = $obj->fk_soc;
                        $relationTiers->fk_socpeople       = $obj->fk_socpeople;
                        $relationTiers->fk_c_relationtiers = $obj->fk_c_relationtiers;
                        $relationTiers->fk_actioncomm      = $obj->fk_actioncomm;
                        $relationTiers->date_debut         = $object->datep;
                        $relationTiers->date_fin           = $object->datef;
                        $relationTiers->commentaire        = $obj->commentaire;

                        $ret = $relationTiers->update($user);
                        if ($ret < 0) {
                            $this->errors = $relationTiers->errors;
                            return -1;
                        }
                    }
                }
            }

            return 1;
        }
        else if ($action == 'ACTION_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $langs->load('relationstierscontacts@relationstierscontacts');

            if ($object->id > 0) {
                // check if actioncomm exists in thirdparties relations
                $sql  = "SELECT";
                $sql .= " rowid";
                $sql .= " FROM " . MAIN_DB_PREFIX . "relationtiers";
                $sql .= " WHERE fk_actioncomm = " . $object->id;

                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->errors[] = $this->db->lasterror();
                    return -1;
                } else {
                    $num = $this->db->num_rows($resql);
                    if ($num > 0) {
                        $this->errors[] = $langs->trans('RTCErrorRelationTiersActionCommExists');
                        return -1;
                    }
                }
            }

            return 1;
        }

        return 0;
    }
}
