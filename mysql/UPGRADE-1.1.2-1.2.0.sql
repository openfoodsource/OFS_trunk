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

-- Remove some old/extraneous values
DELETE FROM ofs_configuration WHERE name = "font"
DELETE FROM ofs_configuration WHERE name = "fontface"

-- Modify the table schema and data for new version
ALTER TABLE ofs_configuration
  DROP PRIMARY KEY;
ALTER TABLE ofs_configuration
  ADD subsection SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT AFTER section,
  ADD PRIMARY KEY (subsection);
DELETE FROM ofs_configuration WHERE ofs_configuration.name = "fontface";
DELETE FROM ofs_configuration WHERE ofs_configuration.name = "font";
UPDATE ofs_configuration SET options = "section_heading" WHERE constant = "";
UPDATE ofs_configuration SET value = SUBSTR(section, 4) WHERE constant = "";
UPDATE ofs_configuration SET section = SUBSTR(section, 1,1) WHERE 1;
UPDATE ofs_configuration SET constant = CONCAT("SECTION_", section) WHERE constant = "";
ALTER TABLE ofs_configuration
  CHANGE section section TINYINT(3) UNSIGNED NOT NULL;
ALTER TABLE ofs_configuration
  ADD UNIQUE constant (constant);

-- Update existing rows
UPDATE ofs_configuration SET description = 'Advanced database and software settings' WHERE constant = 'SECTION_6';
UPDATE ofs_configuration SET description = 'Configure optional modules such as WordPress and PayPal' WHERE constant = 'SECTION_7';
UPDATE ofs_configuration SET name='current_ofs_version', constant='CURRENT_OFS_VERSION', value = 'OFSv1.2.0' WHERE constant = 'CURRENT_VERSION';
UPDATE ofs_configuration SET options = 'select=\r\nNONE\r\nHTML\r\nTEXT\r\nBOTH', value = IF(value='true', 'BOTH', 'NONE'), description = 'Log errors as TEXT or HTML. TEXT errors will be logged to errors.text and HTML errors to errors.html at the PATH location.' WHERE constant = 'DEBUG_LOGGING';
UPDATE ofs_configuration SET description = 'Set this value to require a minimum password strength. 1=very weak &mdash; 99=extremely strong.\r\nEXAMPLES:\r\nStrength of &ldquo;aaa&rdquo; is 2.\r\nStrength of &ldquo;A7t&rdquo; is 8.\r\nStrength of &ldquo;12345&rdquo; is 16.\r\nStrength of &ldquo;Password&rdquo; is 16.\r\nStrength of &ldquo;buffalo21&rdquo; is 19.\r\nStrength of &ldquo;Ripe Tomato 13&rdquo; is 30.\r\nStrength of &ldquo;!@#$%^&*()&rdquo; is 32.\r\nStrength of &ldquo;&lt;QKHE&rdquo;U82&rdquo;v&gt;2~Nyz=as`FQnG&rdquo; is 57.' WHERE constant = 'MIN_PASSWORD_STRENGTH';
UPDATE ofs_configuration SET options = 'input_pattern=\r\n-?[0-9.]+' WHERE constant = 'MOTD_REPEAT_TIME';
UPDATE ofs_configuration SET options = 'input_pattern=\r\n(UA\\-[0-9]{6,10}\\-[0-9]{1,3})|(GTM\\-[A-Z0-9]{6,12})' WHERE constant = 'GOOGLE_TRACKING_ID';
-- Add additional rows
INSERT INTO ofs_configuration SET section = 7, name = 'wordpress_menu', constant = 'WORDPRESS_MENU', options = 'text_area', value= ''#PARENT SLUG |TITLE                   |URL                                                    |ORDER |PARENT           |ID                  |AUTH TYPE\r\n# SHOPPING MENU ++++++++++++++++++++++++++++++++++++++++++\r\ntop-menu     |Start Shopping!         |OPENFOOD                                               |2     |0                |openfood-menu       |ALL\r\ntop-menu     | Shopping Panel         |OPENFOODpanel_shopping.php                             |10    |openfood-menu    |shopping            |member\r\ntop-menu     | Member Panel           |OPENFOODpanel_member.php                               |20    |openfood-menu    |member              |member\r\ntop-menu     | Producer Panel         |OPENFOODpanel_producer.php                             |30    |openfood-menu    |member              |producer\r\ntop-menu     | Route Admin            |OPENFOODpanel_route_admin.php                          |40    |openfood-menu    |route-admin         |route_admin\r\ntop-menu     | Producer Admin         |OPENFOODpanel_producer_admin.php                       |50    |openfood-menu    |producer-admin      |producer_admin\r\ntop-menu     | Member Admin           |OPENFOODpanel_member_admin.php                         |60    |openfood-menu    |member-admin        |member_admin\r\ntop-menu     | Financial Admin        |OPENFOODpanel_cashier.php                              |70    |openfood-menu    |cashier             |cashier\r\ntop-menu     | OpenFood Admin         |OPENFOODpanel_admin.php                                |80    |openfood-menu    |openfood-admin      |site_admin\r\ntop-menu     | WordPress Admin        |/wordpress/wp-admin/                                   |90    |openfood-menu    |wordpress-admin     |site_admin\r\n', description = 'Use this option to add auth_type conditional menus in WordPress. Note that non OpenFood menus can be managed here also if there is a desire to control auth_type visibility.\r\n<ul><li> each line represents an individual menu item</li>\r\n<li> use pipes to separate elements in order: parent_slug | title | url | order | parent | id | auth_type</li>\r\n<li> for convenience, any white space around elements will be trimmed away</li>\r\n<li> auth_type may include comma-separated (no white space) auth_type values permitted to see this menu item</li>\r\n<li> auth_type = ALL may be used to show the menu item to everyone</li>\r\n<li> parent is the CSS ID of the parent menu item - or zero (0) to make it a top-level menu item</li>\r\n<li> begin comment lines with a # character</li></ul>';
INSERT INTO ofs_configuration SET section = 7, name = 'wordpress_path', constant = 'WORDPRESS_PATH',options = 'input_pattern=\r\n/[a-zA-Z0-9\\.\\/\\-_]+', value= '/wordpress/', description = 'Path, within document root, where the wordpress installation is located. This should have a leading slash. Example: /wordpress/';
INSERT INTO ofs_configuration SET section = 4, name = 'use_availability_matrix', constant = 'USE_AVAILABILITY_MATRIX', options = 'checkbox=\nfalse\r\ntrue', value= 'true', description = 'Checking this box will invoke the availability matrix that restricts certain producers to certain customer sites. Customers without access to a particular producer will not see products from that producer. Producers will be able to change their own availability settings in their producer panel (Select Collection Points) and producer administrators can make changes for all producers.\r\nIf the site does not restrict any producers (or if it does not restrict many) it may be simpler to leave this feature disabled and handle any exceptions by other methods.';
INSERT INTO ofs_configuration SET section = 4, name = 'show_user_menu', constant = 'SHOW_USER_MENU', options = 'checkbox=\r\nfalse\r\ntrue', value= 'true', description = 'Display a small login/logout user menu with basket information on every page.';
INSERT INTO ofs_configuration SET section = 6, name = 'is_developer', constant = 'IS_DEVELOPER', options = 'checkbox=\nfalse\r\ntrue', value= 'false', description = 'This configuration option should almost never be enabled for a live system. It is intended to provide a convenient place for developers to enable features that will help with the development process. In particular, it allows using this configuration interface to modify the configuration settings themselves.';
INSERT INTO ofs_configuration SET section = 6, name = 'new_table_transport_identities', constant = 'NEW_TABLE_TRANSPORT_IDENTITIES', options = 'input_pattern=\r\n[A-Za-z0-9.-_]+', value= 'transport_identities', description = 'Name of the database table containing names for transport identities - i.e. named groups of transport routes that can be used over multiple cycles.';
INSERT INTO ofs_configuration SET section = 4, name = 'show_customer_note_on_label', constant = 'SHOW_CUSTOMER_NOTE_ON_LABEL', options = 'checkbox=\r\nfalse\r\ntrue', value= 'false', description = 'Check this box to show customer notes on labels. Otherwise, the producer will only see an asterisk (*) indicating there is a customer note for the product.';

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

