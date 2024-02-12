<?php
/* Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 * \file    htdocs/relationstierscontacts/class/relationcontact.class.php
 * \ingroup relationstierscontacts
 * \brief   Class of relationcontact
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class RelationContact
 */
class RelationContact extends CommonObject
{
	public $element = 'relationcontact';
	public $table_element = 'relationcontact';
    public $fk_element = 'fk_relationcontact';
    public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    /**
     * Cache of relationcontact list
     * @var DictionaryLine[]
     */
    public static $relationcontact_list;

    /**
     * Error message
     * @var string
     */
    public $error;

    /**
     * List of error message
     * @var array
     */
    public $errors;

    /**
     * ID of the relation contact
     * @var int
     */
    public $id;

    /**
     * Id of contact A
     * @var int
     */
    public $fk_socpeople_a;

    /**
     * Id of contact B
     * @var int
     */
    public $fk_socpeople_b;

    /**
     * Id of relation contact (dictionary)
     * @var int
     */
    public $fk_c_relationcontact;

    /**
     * Sens of relation contact
     * @var int
     */
    public $sens;


    /**
	 * Constructor
	 *
	 * @param   DoliDb      $db     Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 *  Create relation contact into database
	 *
	 * @param   User    $user           User that creates
	 * @param   bool    $notrigger      false=launch triggers after, true=disable triggers
	 * @return  int                     <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
    {
        global $conf, $langs, $hookmanager;
        $error = 0;
        $this->errors = array();
        $now = dol_now();
        $langs->load("relationstierscontacts@relationstierscontacts");

        dol_syslog(__METHOD__ . " user_id=" . $user->id, LOG_DEBUG);

        // Clean parameters
        $this->fk_socpeople_a = $this->fk_socpeople_a > 0 ? $this->fk_socpeople_a : 0;
        $this->fk_socpeople_b = $this->fk_socpeople_b > 0 ? $this->fk_socpeople_b : 0;
        $this->fk_c_relationcontact = $this->fk_c_relationcontact > 0 ? $this->fk_c_relationcontact : 0;
        $this->sens = $this->sens > 0 ? $this->sens : 0;

        // Check parameters
        if (empty($this->fk_socpeople_a)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationContactSocpeople"));
            $error++;
        }
        if (empty($this->fk_socpeople_b)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationContactSocpeople"));
            $error++;
        }
        if (empty($this->fk_c_relationcontact)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationContactLabel"));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= " fk_socpeople_a";
        $sql .= ", fk_socpeople_b";
        $sql .= ", fk_c_relationcontact";
        $sql .= ", sens";
        $sql .= ")";
        $sql .= " VALUES (";
        $sql .= " " . $this->fk_socpeople_a;
        $sql .= ", " . $this->fk_socpeople_b;
        $sql .= ", " . $this->fk_c_relationcontact;
        $sql .= ", " . $this->sens;
        $sql .= ")";

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('RELATIONCONTACT_CREATE', $user);
                if ($result < 0) {
                    $error++;
                    dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
                }
                // End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);
            return $this->id;
        }
    }


	/**
	 *  Load relation contact in memory from the database
	 *
     * @param   int     $id         Id object
	 * @return  int                 <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
    {
        global $langs;
        $this->errors = array();
        $langs->load("relationstierscontacts@relationstierscontacts");

        dol_syslog(__METHOD__ . " id=" . $id, LOG_DEBUG);

        $sql  = "SELECT";
        $sql .= " t.rowid";
        $sql .= ", t.fk_socpeople_a";
        $sql .= ", t.fk_socpeople_b";
        $sql .= ", t.fk_c_relationcontact";
        $sql .= ", t.sens";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
        if ($id) $sql .= " WHERE t.rowid = " . $id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                $obj = $this->db->fetch_object($resql);

                /* Relation contact dictionary
                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $relationConactLine = Dictionary::getDictionaryLine($this->db, 'relationstierscontacts', 'relationcontact');
                $res = $relationConactLine->fetch($obj->fk_c_relationcontact);
                if ($res == 0) {
                    $this->errors[] = $langs->trans('RTCErrorRelationContactNotFound');
                    return -1;
                } elseif ($res < 0) {
                    array_merge($this->errors, $relationConactLine->errors);
                    return -1;
                }
                $this->relationcontact_code        = $relationConactLine->fields['code'];
                */

                $this->id                   = $obj->rowid;
                $this->fk_socpeople_a       = $obj->fk_socpeople_a;
                $this->fk_socpeople_b       = $obj->fk_socpeople_b;
                $this->fk_c_relationcontact = $obj->fk_c_relationcontact;
                $this->sens                 = $obj->sens;
            }
            $this->db->free($resql);

            if ($numrows) {
                return 1;
            } else {
                return 0;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

            return -1;
        }
    }


    /**
	 *  Update relation contact into database
	 *
	 * @param   User    $user           User that modifies
	 * @param   bool    $notrigger      false=launch triggers after, true=disable triggers
	 * @return  int                     <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
        global $conf, $langs, $hookmanager;
        $error = 0;
        $this->errors = array();
        $langs->load("relationstierscontacts@relationstierscontacts");

        dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id, LOG_DEBUG);

        // Clean parameters
        $this->fk_socpeople_a = $this->fk_socpeople_a > 0 ? $this->fk_socpeople_a : 0;
        $this->fk_socpeople_b = $this->fk_socpeople_b > 0 ? $this->fk_socpeople_b : 0;
        $this->fk_c_relationcontact = $this->fk_c_relationcontact > 0 ? $this->fk_c_relationcontact : 0;
        $this->sens = $this->sens > 0 ? $this->sens : 0;

        // Check parameters
        if (!($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
            $error++;
        }
        if (empty($this->fk_socpeople_a)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationContactSocpeople"));
            $error++;
        }
        if (empty($this->fk_socpeople_b)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationContactSocpeople"));
            $error++;
        }
        if (empty($this->fk_c_relationcontact)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationContactLabel"));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

		// Update request
		$sql  = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
        $sql .= " fk_socpeople_a = " . $this->fk_socpeople_a;
        $sql .= ", fk_socpeople_b = " . $this->fk_socpeople_b;
        $sql .= ", fk_c_relationcontact = " . $this->fk_c_relationcontact;
        $sql .= ", sens = " . $this->sens;
        $sql .= " WHERE rowid = ".$this->id;

		$this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
        }

        if (!$error && !$notrigger) {
            // Call trigger
            $result = $this->call_trigger('RELATIONCONTACT_MODIFY', $user);
            if ($result < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
            }
            // End call triggers
        }

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);

			return 1;
		}
	}


	/**
	 *  Delete relation contact in database
	 *
     * @param   User    $user           User that deletes
	 * @param   bool    $notrigger      false=launch triggers after, true=disable triggers
	 * @return  int                     <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
    {
        global $conf, $langs;
        $error = 0;
        $this->errors = array();
        $langs->load("relationstierscontacts@relationstierscontacts");

        dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id, LOG_DEBUG);

        // Check parameters
        if (!($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        $this->db->begin();

        // User is mandatory for trigger call
        if (!$error && !$notrigger) {
            // Call trigger
            $result = $this->call_trigger('RELATIONCONTACT_DELETE', $user);
            if ($result < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
            }
            // End call triggers
        }

        // Remove request
        if (!$error) {
            $sql  = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
            $sql .= " WHERE rowid = " . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        if (!$error) {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);

            return 1;
        } else {
            $this->db->rollback();

            return -1;
        }
    }


    /**
     * Delete relations contacts linked to a contact
     *
     * @param   int     $fkSocpeople        Contact id in relation
     * @param   User    $user               User that deletes
     * @param   bool    $notrigger          false=launch triggers after, true=disable triggers
     * @return  int     <0 if KO, >0 if OK
     */
    public function deleteAllByFkSocpeople($fkSocpeople, User $user, $notrigger = false)
    {
        $error = 0;

        $sql  = "SELECT";
        $sql .= " t.rowid";
        $sql .= ", t.fk_socpeople_a";
        $sql .= ", t.fk_socpeople_b";
        $sql .= ", t.fk_c_relationcontact";
        $sql .= ", t.sens";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
        $sql .= " WHERE t.fk_socpeople_a = " . $fkSocpeople;
        $sql .= " OR t.fk_socpeople_b = " . $fkSocpeople;

        $resql = $this->db->query($sql);
        if (!$resql) {
            return -1;
        } else {
            while ($obj = $this->db->fetch_object($resql))
            {
                $this->id                   = $obj->rowid;
                $this->fk_socpeople_a       = $obj->fk_socpeople_a;
                $this->fk_socpeople_b       = $obj->fk_socpeople_b;
                $this->fk_c_relationcontact = $obj->fk_c_relationcontact;
                $this->sens                 = $obj->sens;

                $ret = $this->delete($user, $notrigger);

                if ($ret < 0) {
                    $error++;
                    break;
                }
            }

            if ($error) {
                return -1;
            } else {
                return 1;
            }
        }
    }
}