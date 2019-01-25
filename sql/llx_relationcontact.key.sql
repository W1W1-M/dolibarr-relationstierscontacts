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

ALTER TABLE llx_relationcontact ADD UNIQUE INDEX uk_relationcontact_socpeople (fk_socpeople_a, fk_socpeople_b, fk_c_relationcontact);

ALTER TABLE llx_relationcontact ADD CONSTRAINT fk_relationcontact_fk_socpeople_a FOREIGN KEY (fk_socpeople_a) REFERENCES llx_socpeople (rowid);
ALTER TABLE llx_relationcontact ADD CONSTRAINT fk_relationcontact_fk_socpeople_b FOREIGN KEY (fk_socpeople_b) REFERENCES llx_socpeople (rowid);
