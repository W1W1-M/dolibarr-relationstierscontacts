<?php
/* Copyright (C) 2018-2024	 Easya Solutions        <support@easya.solutions>
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
 * \file    htdocs/relationstierscontacts/class/relationtiers.class.php
 * \ingroup relationstierscontacts
 * \brief   Class of relationtiers
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class RelationTiers
 */
class RelationTiers extends CommonObject
{
	public $element = 'relationtiers';
	public $table_element = 'relationtiers';
	public $fk_element = 'fk_relationtiers';
	public $ismultientitymanaged = 1;    // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe


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
	 * ID of the relation thirdparty
	 * @var int
	 */
	public $id;

	/**
	 * Id of thirdparty
	 * @var int
	 */
	public $fk_soc;

	/**
	 * Id of contact
	 * @var int
	 */
	public $fk_socpeople;

	/**
	 * Id of relation tiers (dictionary)
	 * @var int
	 */
	public $fk_c_relationtiers;

	/**
	 * Id of actioncomm
	 * @var int
	 */
	public $fk_actioncomm;

	/**
	 * Start date of relation
	 * @var date
	 */
	public $date_debut;

	/**
	 * End date of relation
	 * @var date
	 */
	public $date_fin;

	/**
	 * Comment of relation
	 * @var string
	 */
	public $commentaire;

	/**
	 * Is main thirdparty
	 * @var bool
	 */
	public $is_main_thirdparty;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create relation third-party into database
	 *
	 * @param	User	$user      		User that creates
	 * @param	bool	$notrigger		false=launch triggers after, true=disable triggers
	 * @return  int		Return <0 if KO, Id of created object if OK
	 *
	 * @throws  Exception
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
		$this->fk_soc = $this->fk_soc > 0 ? $this->fk_soc : 0;
		$this->fk_socpeople = $this->fk_socpeople > 0 ? $this->fk_socpeople : 0;
		$this->fk_c_relationtiers = $this->fk_c_relationtiers > 0 ? $this->fk_c_relationtiers : 0;
		$this->fk_actioncomm = $this->fk_actioncomm > 0 ? $this->fk_actioncomm : 0;
		$this->date_debut = $this->date_debut > 0 ? $this->date_debut : null;
		$this->date_fin = $this->date_fin > 0 ? $this->date_fin : null;
		$this->commentaire = trim($this->commentaire);

		// Check parameters
		if (empty($this->fk_soc)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationTiersThirdparty"));
			$error++;
		}
		if (empty($this->fk_socpeople)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationTiersSocpeople"));
			$error++;
		}
		if (empty($this->fk_c_relationtiers)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationTiersLabel"));
			$error++;
		}
		if ($this->date_debut && $this->date_fin && $this->date_debut > $this->date_fin) {
			$this->errors[] = $langs->trans("RTCErrorRelationTiersDateStartSupToDateEnd");
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
			return -3;
		}

		$this->db->begin();

		if (!$error) {
			$idActionComm = $this->_createActionComm($user);

			if ($idActionComm < 0) {
				$error++;
			} else {
				$this->fk_actioncomm = $idActionComm;
			}
		}

		if (!$error) {
			// Insert request
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
			$sql .= " fk_soc";
			$sql .= ", fk_socpeople";
			$sql .= ", fk_c_relationtiers";
			$sql .= ", fk_actioncomm";
			$sql .= ", date_debut";
			$sql .= ", date_fin";
			$sql .= ", commentaire";
			$sql .= ")";
			$sql .= " VALUES (";
			$sql .= " " . $this->fk_soc;
			$sql .= ", " . $this->fk_socpeople;
			$sql .= ", " . $this->fk_c_relationtiers;
			$sql .= ", " . $this->fk_actioncomm;
			$sql .= ", " . ($this->date_debut > 0 ? "'" . $this->db->idate($this->date_debut) . "'" : 'NULL');
			$sql .= ", " . ($this->date_fin > 0 ? "'" . $this->db->idate($this->date_fin) . "'" : 'NULL');
			$sql .= ", '" . $this->db->escape($this->commentaire) . "'";
			$sql .= ")";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			}
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

			if (!$error) {
				if ($this->contact === null) {
					$this->fetch_contact($this->fk_socpeople);
				}

				if ($this->is_main_thirdparty === true) {
					// update contact with main thirdparty
					$this->contact->socid = $this->fk_soc;
					$result = $this->contact->update($this->contact->id, $user, 1);
					if ($result < 0) {
						$error++;
						$this->errors[] = $this->contact->errorsToString();
						dol_syslog(__METHOD__ . " Errors on update contact : " . $this->errorsToString(), LOG_ERR);
					}
				} else {
					// remove main thirdparty of this contact only if contact thirdparty in relation is main thirdparty
					if ($this->contact->socid > 0 && $this->contact->socid == $this->fk_soc) {
						$this->contact->socid = -1;
						$result = $this->contact->update($this->contact->id, $user, 1);
						if ($result < 0) {
							$error++;
							$this->errors[] = $this->contact->errorsToString();
							dol_syslog(__METHOD__ . " Errors on update contact : " . $this->errorsToString(), LOG_ERR);
						}
					}
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('RELATIONTIERS_CREATE', $user);
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
	 * Load relation third-party in memory from the database
	 *
	 * @param 	int 	$id 	Id object
	 * @return  int		Return <0 if KO, 0 if not found, >0 if OK
	 *
	 * @throws  Exception
	 */
	public function fetch($id)
	{
		global $langs;
		$this->errors = array();
		$langs->load("relationstierscontacts@relationstierscontacts");

		dol_syslog(__METHOD__ . " id=" . $id, LOG_DEBUG);

		$sql = "SELECT";
		$sql .= " t.rowid";
		$sql .= ", t.fk_soc";
		$sql .= ", t.fk_socpeople";
		$sql .= ", t.fk_c_relationtiers";
		$sql .= ", t.fk_actioncomm";
		$sql .= ", t.date_debut";
		$sql .= ", t.date_fin";
		$sql .= ", t.commentaire";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		if ($id) {
			$sql .= " WHERE t.rowid = " . $id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->fk_soc = $obj->fk_soc;
				$this->fk_socpeople = $obj->fk_socpeople;
				$this->fk_c_relationtiers = $obj->fk_c_relationtiers;
				$this->fk_actioncomm = $obj->fk_actioncomm;
				$this->date_debut = (!empty($obj->date_debut) ? $this->db->jdate($obj->date_debut) : null);
				$this->date_fin = (!empty($obj->date_fin) ? $this->db->jdate($obj->date_fin) : null);
				$this->commentaire = $obj->commentaire;
				$this->is_main_thirdparty = $this->isMainThirdparty();
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
	 * Update relation third-party into database
	 *
	 * @param	User 	$user      	User that modifies
	 * @param	bool 	$notrigger		false=launch triggers after, true=disable triggers
	 * @return  int		Return <0 if KO, >0 if OK
	 *
	 * @throws  Exception
	 */
	public function update(User $user, $notrigger = false)
	{
		global $conf, $langs, $hookmanager;
		$error = 0;
		$this->errors = array();
		$langs->load("relationstierscontacts@relationstierscontacts");

		dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id, LOG_DEBUG);

		// Clean parameters
		$this->fk_soc = $this->fk_soc > 0 ? $this->fk_soc : 0;
		$this->fk_socpeople = $this->fk_socpeople > 0 ? $this->fk_socpeople : 0;
		$this->fk_c_relationtiers = $this->fk_c_relationtiers > 0 ? $this->fk_c_relationtiers : 0;
		$this->fk_actioncomm = $this->fk_actioncomm > 0 ? $this->fk_actioncomm : 0;
		$this->date_debut = $this->date_debut > 0 ? $this->date_debut : null;
		$this->date_fin = $this->date_fin > 0 ? $this->date_fin : null;
		$this->commentaire = trim($this->commentaire);

		// Check parameters
		if (!($this->id > 0)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if (empty($this->fk_soc)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationTiersThirdparty"));
			$error++;
		}
		if (empty($this->fk_socpeople)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationTiersSocpeople"));
			$error++;
		}
		if (empty($this->fk_c_relationtiers)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationTiersLabel"));
			$error++;
		}
		if ($this->date_debut && $this->date_fin && $this->date_debut > $this->date_fin) {
			$this->errors[] = $langs->trans("RTCErrorRelationTiersDateStartSupToDateEnd");
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
			return -3;
		}

		$this->db->begin();

		if (!$error) {
			require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

			if ($this->fk_actioncomm <= 0) {
				$idActionComm = $this->_createActionComm($user);

				if ($idActionComm < 0) {
					$error++;
				} else {
					$this->fk_actioncomm = $idActionComm;
				}
			} else {
				// modify or delete actioncomm
				$actionComm = new ActionComm($this->db);
				$actionComm->fetch($this->fk_actioncomm);

				if (empty($this->date_debut) || empty($this->date_fin)) {
					// delete actioncomm
					$ret = $actionComm->delete(1);

					if ($ret < 0) {
						$error++;
						$this->errors = $actionComm->errors;
					} else {
						$this->fk_actioncomm = 0;
					}
				} else {
					if ($actionComm->datep != $this->date_debut || $actionComm->datef != $this->date_fin) {
						if ($this->date_fin >= $this->date_debut) {
							$actionComm->datep = $this->date_debut;
							$actionComm->datef = $this->date_fin;

							// modify dates in actioncomm
							$sql = "UPDATE " . MAIN_DB_PREFIX . "actioncomm";
							$sql .= " SET datep = " . (strval($actionComm->datep) != '' ? "'" . $actionComm->db->idate($actionComm->datep) . "'" : 'NULL');
							$sql .= ", datep2 = " . (strval($actionComm->datef) != '' ? "'" . $actionComm->db->idate($actionComm->datef) . "'" : 'NULL');
							$sql .= " WHERE id = " . $actionComm->id;

							$resql = $this->db->query($sql);
							if (!$resql) {
								$error++;
								$this->errors[] = 'Error ' . $this->db->lasterror();
								dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
							}
						}
					}
				}
			}
		}

		if (!$error) {
			if ($this->contact === null) {
				$this->fetch_contact($this->fk_socpeople);
			}

			if ($this->is_main_thirdparty === true) {
				// update contact with main thirdparty
				$this->contact->socid = $this->fk_soc;
				$result = $this->contact->update($this->contact->id, $user, 1);
				if ($result < 0) {
					$error++;
					$this->errors[] = $this->contact->errorsToString();
					dol_syslog(__METHOD__ . " Errors on update contact : " . $this->errorsToString(), LOG_ERR);
				}
			} else {
				// get old relation contact
				$oldRelationTiers = new self($this->db);
				$oldRelationTiers->fetch($this->id);
				if ($oldRelationTiers->contact === null) {
					$oldRelationTiers->fetch_contact($oldRelationTiers->fk_socpeople);
				}

				// remove main thirdparty in old contact if changed
				if ($oldRelationTiers->fk_socpeople > 0 && $this->fk_socpeople != $oldRelationTiers->fk_socpeople) {
					$oldRelationTiers->contact->socid = -1;
					$oldRelationTiers->contact->update($oldRelationTiers->contact->id, $user, 1);
				}

				// remove main thirdparty in this contact only if we had a main thirdparty
				if ($this->contact->socid > 0) {
					$this->contact->socid = -1;

					$result = $this->contact->update($this->contact->id, $user, 1);
					if ($result < 0) {
						$error++;
						$this->errors[] = $this->contact->errorsToString();
						dol_syslog(__METHOD__ . " Errors on update contact : " . $this->errorsToString(), LOG_ERR);
					}
				}
			}
		}

		if (!$error) {
			// Update request
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
			$sql .= " fk_soc = " . $this->fk_soc;
			$sql .= ", fk_socpeople = " . $this->fk_socpeople;
			$sql .= ", fk_c_relationtiers = " . $this->fk_c_relationtiers;
			$sql .= ", fk_actioncomm = " . $this->fk_actioncomm;
			$sql .= ", date_debut = " . ($this->date_debut > 0 ? "'" . $this->db->idate($this->date_debut) . "'" : 'NULL');
			$sql .= ", date_fin = " . ($this->date_fin > 0 ? "'" . $this->db->idate($this->date_fin) . "'" : 'NULL');
			$sql .= ", commentaire = '" . $this->db->escape($this->commentaire) . "'";
			$sql .= " WHERE rowid = " . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('RELATIONTIERS_MODIFY', $user);
			if ($result < 0) {
				$error++;
				dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();
			dol_syslog(__METHOD__ . " success", LOG_DEBUG);

			return 1;
		}
	}

	/**
	 * Delete relation third-party in database
	 *
	 * @param	User 	$user      	User that deletes
	 * @param 	bool 	$notrigger	false=launch triggers after, true=disable triggers
	 * @return  int		Return <0 if KO, >0 if OK
	 *
	 * @throws  Exception
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
		if (empty($this->fk_soc)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationTiersThirdparty"));
			$error++;
		}
		if (empty($this->fk_socpeople)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RTCRelationTiersSocpeople"));
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
			$result = $this->call_trigger('RELATIONTIERS_DELETE', $user);
			if ($result < 0) {
				$error++;
				dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
			}
			// End call triggers
		}

		// delete related actioncomm
		if (!$error) {
			require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
			if ($this->fk_actioncomm > 0) {
				$actioncomm = new ActionComm($this->db);
				$actioncomm->fetch($this->fk_actioncomm);

				if ($actioncomm->id > 0) {
					$ret = $actioncomm->delete(1);

					if ($ret < 0) {
						$error++;
						$this->errors = $actioncomm->errors;
						dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
					}
				}
			}
		}

		// remove contact thirdparty if is main
		if (!$error) {
			if ($this->isMainThirdparty()) {
				if ($this->contact === null) {
					$this->fetch_contact($this->fk_socpeople);
				}

				$this->contact->socid = -1;
				$result = $this->contact->update($this->contact->id, $user, 1);

				if ($result < 0) {
					$error++;
					$this->errors[] = $this->contact->errorsToString();
					dol_syslog(__METHOD__ . " Errors on update contact : " . $this->errorsToString(), LOG_ERR);
				}
			}
		}

		// Remove relation
		if (!$error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
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
	 * Delete relations third-party linked to a contact
	 *
	 * @param 	int  	$fkSocpeople 	Contact id in relation
	 * @param	User 	$user        	User that deletes
	 * @param	bool 	$notrigger   	false=launch triggers after, true=disable triggers
	 * @return  int     Return <0 if KO, >0 if OK
	 *
	 * @throws  Exception
	 */
	public function deleteAllByFkSocpeople($fkSocpeople, User $user, $notrigger = false)
	{
		$error = 0;

		$sql = "SELECT";
		$sql .= " t.rowid";
		$sql .= ", t.fk_soc";
		$sql .= ", t.fk_socpeople";
		$sql .= ", t.fk_c_relationtiers";
		$sql .= ", t.fk_actioncomm";
		$sql .= ", t.date_debut";
		$sql .= ", t.date_fin";
		$sql .= ", t.commentaire";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE t.fk_socpeople = " . $fkSocpeople;

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		} else {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->id = $obj->rowid;
				$this->fk_soc = $obj->fk_soc;
				$this->fk_socpeople = $obj->fk_socpeople;
				$this->fk_c_relationtiers = $obj->fk_c_relationtiers;
				$this->fk_actioncomm = $obj->fk_actioncomm;
				$this->date_debut = $this->db->jdate($obj->date_debut);
				$this->date_fin = $this->db->jdate($obj->date_fin);
				$this->commentaire = $obj->commentaire;
				$this->is_main_thirdparty = $this->isMainThirdparty();

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

	/**
	 * Create actionComm
	 *
	 * @param	User 	$user      		User that modifies
	 * @param	bool	$notrigger 		false=launch triggers after, true=disable triggers
	 * @return  int		Return <0 if KO, 0 or $idActionComm if OK
	 */
	private function _createActionComm(User $user, $notrigger = false)
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

		$langs->load('relationstierscontacts@relationstierscontacts');

		$fk_element = $this->fk_soc;
		$this->fetch_thirdparty();
		$elementtype = $this->thirdparty->element;
		$this->fetch_contact($this->fk_socpeople);

		$now = dol_now();
		$actionCommLabel = $langs->transnoentities('RTCRelationTiersActionCommLabel', $this->thirdparty->getFullName($langs), $this->contact->getFullName($langs));
		$actionCommNote = $actionCommLabel;
		$actionCommDateP = $this->date_debut;
		$actionCommDateF = $this->date_fin;
		if (empty($actionCommDateP) && $actionCommDateF > 0 && $actionCommDateF > $now) {
			$actionCommDateP = $now;
		}
		$this->sendtoid = 0;
		$this->socid = $this->fk_soc;

		if (!empty($actionCommDateP) && (empty($actionCommDateF) || $actionCommDateF >= $actionCommDateP)) {
			$contactforaction = new Contact($this->db);
			$societeforaction = new Societe($this->db);
			// Set contactforaction if there is only 1 contact.
			if (is_array($this->sendtoid)) {
				if (count($this->sendtoid) == 1) {
					$contactforaction->fetch(reset($this->sendtoid));
				}
			} else {
				if ($this->sendtoid > 0) {
					$contactforaction->fetch($this->sendtoid);
				}
			}
			// Set societeforaction.
			if ($this->socid > 0) {
				$societeforaction->fetch($this->socid);
			}

			// create actioncomm
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code = 'AC_RTC';        // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
			$actioncomm->code = 'AC_RTC';
			$actioncomm->label = $actionCommLabel;
			$actioncomm->note = $actionCommNote;
			$actioncomm->datep = $actionCommDateP;
			$actioncomm->datef = $actionCommDateF;
			$actioncomm->durationp = 0;
			$actioncomm->punctual = 1;
			$actioncomm->percentage = -1;   // Not applicable
			$actioncomm->societe = $societeforaction;
			$actioncomm->contact = $contactforaction;
			$actioncomm->socid = $societeforaction->id;
			$actioncomm->contactid = $contactforaction->id;
			$actioncomm->authorid = $user->id;   // User saving action
			$actioncomm->userownerid = $user->id;    // Owner of action
			// Fields when action is en email (content should be added into note)
			$actioncomm->email_msgid = $this->email_msgid;
			$actioncomm->email_from = $this->email_from;
			$actioncomm->email_sender = $this->email_sender;
			$actioncomm->email_to = $this->email_to;
			$actioncomm->email_tocc = $this->email_tocc;
			$actioncomm->email_tobcc = $this->email_tobcc;
			$actioncomm->email_subject = $this->email_subject;
			$actioncomm->errors_to = $this->errors_to;

			$actioncomm->fk_element = $fk_element;
			$actioncomm->elementtype = $elementtype;

			$idActionComm = $actioncomm->create($user, $notrigger);       // User creating action

			if ($idActionComm > 0) {
				return $idActionComm;
			} else {
				$this->errors = $actioncomm->errors;
				return -1;
			}
		}

		return 0;
	}

	/**
	 * Get all child id of a company
	 *
	 * @param 	int   		$socId          	Company id
	 * @param 	array 		$childIdList    	List of last child id
	 * @param 	array 		$allChildIdList 	List of all child id
	 * @return  int|array	Return <0 if KO, array of child id if OK
	 *
	 * @throws Exception
	 */
	public function getAllChildIdList($socId, $childIdList = array(), $allChildIdList = array())
	{
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		dol_syslog(__METHOD__ . " socId=" . $socId, LOG_DEBUG);

		$sql = "SELECT";
		$sql .= " s.rowid, s.parent";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
		$sql .= " WHERE s.entity IN (" . getEntity('societe') . ")";
		if (count($childIdList) <= 0) {
			$sql .= " AND s.parent = " . $socId;
		} else {
			$sql .= " AND s.parent IN (" . implode(', ', $childIdList) . ')';
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		} else {
			$childIdList = array();

			$nbChild = $this->db->num_rows($resql);
			if ($nbChild <= 0) {
				return $allChildIdList;
			} else {
				while ($obj = $this->db->fetch_object($resql)) {
					$childIdList[$obj->rowid] = $obj->rowid;
					$allChildIdList[$obj->rowid] = $obj->rowid;
				}

				$this->getAllChildIdList($socId, $childIdList, $allChildIdList);
			}

			return $allChildIdList;
		}
	}

	/**
	 * Determine if the third-party in relation is main third-party of the contact
	 *
	 * @param	bool	$noCache	[=FALSE] to use cache, else TRUE
	 * @return	bool    FALSE if not main thirdparty, else TRUE
	 *
	 * @throws  Exception
	 */
	public function isMainThirdparty($noCache = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if ($this->is_main_thirdparty === null || $noCache === true) {
			$isMainThirdparty = false;

			if (($this->contact === null && $this->fk_socpeople > 0) || $noCache === true) {
				$this->fetch_contact($this->fk_socpeople);
			}

			if ($this->fk_soc > 0 && $this->fk_soc == $this->contact->socid) {
				$isMainThirdparty = true;
			}
		} else {
			$isMainThirdparty = $this->is_main_thirdparty;
		}

		return $isMainThirdparty;
	}
}
