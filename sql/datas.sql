-- ============================================================================
-- Copyright (C) 2018	 Open-DSI 	 <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===========================================================================

--
-- List of all managed triggered events (used for trigger agenda automatic events and for notification)
--
-- actions enabled by default (constant created for that) when we enable module agenda
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES
 -- Relation Tiers
 ('RELATIONTIERS_CREATE','Relation tiers created','Executed when a relation tiers is created','relationstierscontacts',163019),
 ('RELATIONTIERS_MODIFY','Relation tiers modified','Executed when a relation tiers is modified','relationstierscontacts',163019),
 ('RELATIONTIERS_DELETE','Relation tiers deleted','Executed when a relation tiers is deleted','relationstierscontacts',163019);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES
 -- Relation Contact
 ('RELATIONCONTACT_CREATE','Relation contact created','Executed when a relation contact is created','relationstierscontacts',163019),
 ('RELATIONCONTACT_MODIFY','Relation contact modified','Executed when a relation contact is modified','relationstierscontacts',163019),
 ('RELATIONCONTACT_DELETE','Relation contact deleted','Executed when a relation contact is deleted','relationstierscontacts',163019);

 INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(163019, 'AC_RTC', 'systemauto', 'Relations Tiers', 'rtc', 1, NULL, NULL, NULL, 30);
