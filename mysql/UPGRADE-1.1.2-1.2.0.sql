-- --------------------------------------------------------------------- --
--                                                                       --
--      QUERIES FOR UPDATING OFS DATABASE FROM V1.1.1 TO V1.2.0          --
--                                                                       --
-- --------------------------------------------------------------------- --
-- 
-- The following queries should bring most aspects of an existing system --
-- from OFS v1.1.1 to OFS v1.2.0. Information that might have changed in --
-- operational systems should be handled properly. As always, please     --
-- be sure to make backups before upgrading a system.
--

-- --------------------------------------------------------------------- --
-- CONFIGURATION TABLE --

-- Add information about transport_identities table
INSERT INTO ofs_configuration (section, name, constant, options, value, description) VALUES
('6. Software', 'new_table_transport_identities', 'NEW_TABLE_TRANSPORT_IDENTITIES', 'input_pattern=\r\n[A-Za-z0-9.-_]+', 'transport_identities', 'Name of the database table containing names for transport identities - i.e. named groups of transport routes that can be used over multiple cycles.'),

-- UPDATE QUERIES:

// Allow Square messages
INSERT INTO ofs_message_types SET key1_target = 'ledger.transaction_id', key2_target = '', description = 'ledger square comment';

-- NEW DATABASE TABLES --

-- Add transport_identities table
CREATE TABLE IF NOT EXISTS ofs_transport_identities (
  transport_id int(11) NOT NULL AUTO_INCREMENT,
  transport_identity_name varchar(255) NOT NULL,
  UNIQUE KEY transport_id (transport_id),
  UNIQUE KEY transport_identity (transport_identity_name)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
-- Provide at least one "default" value
INSERT INTO ofs_transport_identities (transport_id, transport_identity_name) VALUES
(0, 'No transport configured for this cycle');

