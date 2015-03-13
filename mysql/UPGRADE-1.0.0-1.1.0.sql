-- --------------------------------------------------------------------- --
--                                                                       --
--      QUERIES FOR UPDATING OFS DATABASE FROM V1.0.0 TO V1.1.0          --
--                                                                       --
-- --------------------------------------------------------------------- --
-- 
-- The following queries should bring most aspects of an existing system --
-- from OFS v1.0.0 to OFS v1.1.0. Information that might have changed in --
-- operational systems should be handled properly. As always, please     --
-- be sure to make backups before upgrading a system.
--

-- --------------------------------------------------------------------- --
-- CONFIGURATION TABLE --

-- Update validation pattern for PATH configuration
UPDATE ofs_configuration
  SET options = 'input_pattern= (/[a-zA-Z0-9\\.\\-_]+)*/'
  WHERE ofs_configuration.section = '3. File Setup'
  AND ofs_configuration.name = 'food_coop_store_path';

-- Set new software version
UPDATE ofs_configuration
  SET options = 'read_only=\r\nOFSv0.9.0\r\nOFSv0.9.1\r\nOFSv0.9.2\r\nOFSv0.9.3\r\nOFSv1.0.0\r\nOFSv1.0.1\r\nOFSv1.1.0',
    value = 'OFSv1.1.0'
  WHERE ofs_configuration.section = '1. Server Setup'
    AND ofs_configuration.name = 'current_version';
-- Insert defaults for new configuration options

INSERT INTO ofs_configuration (section, name, constant, options, value, description) VALUES
('1. Server Setup', 'min_password_strength', 'MIN_PASSWORD_STRENGTH', 'input_pattern=\r\n[0-9]+', '0', 'Set this value to require a minimum password strength. 1=weak &mdash; 10=very strong.'),
('4. Display Options', 'motd_content', 'MOTD_CONTENT', 'text_area', '<h3 style="float:left;">Open Food Source Software</h3>\r\n<p class="alert_box">\r\n  <span style="font-style:italic;font-size:150%">Remember...</span><br>You are obligated to buy whatever is in your basket when ordering closes. There is not a &quot;checkout&quot; button.</strong>\r\n</p>\r\n<p style="clear:left;">\r\n  For more information about this software, visit <a href="http://openfoodsource.org" target="blank">OpenFoodSource.org</a> (transparent solutions for local food hub networks). You are also encouraged to paricipate in the Open Food Federation, a network for ongoing development and support of organizations using the Open Food Source software.\r\n</p>\r\n', 'This is a message that will be displayed to members on their first login, when they view the message of the day, and also every once in a while (see motd_repeat_time). This will probably include HTML codes.'),
('4. Display Options', 'motd_repeat_time', 'MOTD_REPEAT_TIME', 'input_pattern=\r\n\\-?[0-9.]+', '180', 'Minimum length of time (days) between forced viewing of the message of the day (MOTD). After this period, when a member is active on the site, they will be shown the message again as a reminder. Set this to any negative value to disable the MOTD entirely.'),
('4. Display Options', 'producer_display_address', 'PRODUCER_DISPLAY_ADDRESS', 'select=\r\nSTREET CITY STATE ZIP COUNTY\r\nCITY STATE COUNTY\r\nCOUNTY\r\nCITY COUNTY\r\nNONE', 'CITY COUNTY', 'When producers publish their home address, what parts are displayed on the producer information page?'),
('4. Display Options', 'producer_pub_address', 'PRODUCER_PUB_ADDRESS', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s home address is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_pub_email2', 'PRODUCER_PUB_EMAIL2', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s secondary e-mail address is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_pub_email', 'PRODUCER_PUB_EMAIL', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s primary e-mail address is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_pub_fax', 'PRODUCER_PUB_FAX', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s FAX number is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_pub_phonec', 'PRODUCER_PUB_PHONEC', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s cell phone number is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_pub_phoneh', 'PRODUCER_PUB_PHONEH', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s home phone number is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_pub_phonet', 'PRODUCER_PUB_PHONET', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s toll-free phone number is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_pub_phonew', 'PRODUCER_PUB_PHONEW', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s work phone number is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_pub_web', 'PRODUCER_PUB_WEB', 'select=\r\nOPTIONAL\r\nREQUIRED\r\nDENIED', 'OPTIONAL', 'Configure whether the producer''s website is required to be displayed on producer listings. This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_require_email', 'PRODUCER_REQ_EMAIL', 'checkbox=\r\nfalse\r\ntrue', 'true', 'Are producers required to publish at least one e-mail address &ndash; either primary or secondary email? This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),
('4. Display Options', 'producer_require_phone', 'PRODUCER_REQ_PHONE', 'checkbox=\r\nfalse\r\ntrue', 'true', 'Are producers required to publish at least one phone number &ndash; either home, work, mobile, or toll-free? This will only affect new producers. It does not affect those who are already listed unless they make changes to their information.'),


-- ------------------------------------------------------------------------
-- MEMBERSHIP TYPES --

-- Change column name from `revert_to` to `may_convert_to` for clarity
ALTER TABLE ofs_membership_types
  CHANGE revert_to may_convert_to VARCHAR( 255 ) NOT NULL;


-- ------------------------------------------------------------------------
-- ORDER CYCLES TABLE --

-- Add new column `customer_type` for restricting order cycles by member/institution
ALTER TABLE ofs_order_cycles
  ADD customer_type SET('member', 'institution') NOT NULL AFTER delivery_date;

-- Populate any existing data with new values compatible with old behavior
UPDATE ofs_order_cycles
  SET customer_type = 'member,institution'
  WHERE 1;


-- ------------------------------------------------------------------------
-- TRANSPORT METRICS TABLE --

-- Correct a column name collision with the reserved word `key`
ALTER TABLE ofs_transport_metrics
  CHANGE `key` metric_id INT(11) NOT NULL,
  DROP INDEX `key`,
  ADD INDEX metric_id (metric_id);


-- ------------------------------------------------------------------------
-- REMOVE SPURIOUS TABLES --

-- Drop table: `openfood_config` (using `configuration`)
DROP TABLE ofs_openfood_config;

-- Drop table: `ledger_backup` that was erroneously released
DROP TABLE ofs_ledger_backup;

