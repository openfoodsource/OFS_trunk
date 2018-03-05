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

-- Extend weight in basket_items to allow three decimal places
ALTER TABLE ofs_basket_items
  CHANGE total_weight total_weight DECIMAL( 8, 3 ) NOT NULL DEFAULT '0.00';

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
INSERT INTO ofs_configuration SET section = 7, name = 'square_enabled', constant = 'SQUARE_ENABLED', options = 'checkbox=\r\nfalse\r\ntrue', value= 'false', description = 'Select this option if the site accepts Square payments.';

-- UPDATE QUERIES:
ALTER TABLE ofs_baskets
  CHANGE delivery_type delivery_type CHAR(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE ofs_categories
  ADD FULLTEXT category_name_fulltext (category_name);

ALTER TABLE ofs_ledger
  CHANGE site_id site_id SMALLINT(5) UNSIGNED NULL DEFAULT NULL COMMENT 'References key from sites table';

ALTER TABLE ofs_members
  CHANGE username username VARCHAR(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

ALTER TABLE ofs_order_cycles
  ADD transport_id INT( 11 ) NOT NULL DEFAULT '0' AFTER customer_type;

ALTER TABLE ofs_products
  ADD INDEX pricing_unit (pricing_unit),
  ADD INDEX ordering_unit (ordering_unit),
  ADD FULLTEXT product_name_fulltext (product_name),
  ADD FULLTEXT product_description_fulltext (product_description);

ALTER TABLE ofs_product_images
  CHANGE file_name file_name VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  CHANGE mime_type mime_type VARCHAR(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  ADD INDEX producer_id (producer_id);

ALTER TABLE ofs_subcategories ADD FULLTEXT subcategory_name_fulltext (subcategory_name);

// Allow Square messages
INSERT INTO ofs_message_types SET key1_target = 'ledger.transaction_id', key2_target = '', description = 'ledger square comment';


-- Add transport_identities table
CREATE TABLE IF NOT EXISTS ofs_transport_identities (
  transport_id int(11) NOT NULL AUTO_INCREMENT,
  transport_identity_name varchar(255) NOT NULL,
  UNIQUE KEY transport_id (transport_id),
  UNIQUE KEY transport_identity (transport_identity_name)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
-- Provide at least one "default" value
INSERT INTO ofs_transport_identities (transport_id, transport_identity_name) VALUES
(1, 'No transport configured for this cycle');

-- Add new option to configuration table
INSERT INTO iowatest_openfood.ofs_configuration
  SET
    section = '2',
    subsection = NULL,
    name = 'trust_admin',
    constant = 'TRUST_ADMIN',
    options = 'multi_options=\r\nunsuspend own producer\r\nedit own customer invoice\r\napprove own product',
    value = 'unsuspend own producer,edit own customer invoice,approve own product',
    description = 'These selections are used to permit administrators to edit their own information, in addition to that of others. Without these permissions, administrators are not allowed to modify certain aspects of their own information.';


-- CONVERT PRODUCTS TABLE --------------------------------------------------------------------------

-- We are moving away from a single "confirmed" field to using two fields: "approved" and "active"
-- The following series of queries is NOT an authoritative solution. Please read through the steps
-- before executing them and try to understand what is going on. There is only one DELETE step
-- where products are at risk for being deleted. This can be skipped to be on the safe side. The
-- cost benefit tradeoff is that the less of these steps are done, the more products will need to
-- reconfirmed because they missed confirmation in the older versions.

-- Convert products table to include explicit fields for "confirmed" and "approved"
ALTER TABLE ofs_products
  ADD approved TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER confirmed,
  ADD active TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER approved

-- We will use an abbreviated copy of the ofs_products table to help with some of
-- the following updates

CREATE TABLE ofs_products2 (
  pvid int(11) unsigned NOT NULL auto_increment ,
  product_id int(11) NOT NULL ,
  product_version mediumint(9) unsigned NOT NULL default '1',
  product_name varchar(75) NOT NULL default '',
  product_description longtext NOT NULL ,
  unit_price decimal(9, 3) NOT NULL default '0.000',
  pricing_unit varchar(20) NOT NULL default '',
  ordering_unit varchar(20) NOT NULL default '',
  extra_charge decimal(9, 2) default '0.00',
  confirmed tinyint(1) NOT NULL default '0',
  approved tinyint(1) unsigned NOT NULL default '0',
  active tinyint(1) unsigned NOT NULL default '0',
  created datetime NOT NULL ,
  modified datetime NOT NULL ,
  tangible tinyint(1) NOT NULL default '1',
  PRIMARY KEY (pvid),
  KEY product_version (product_id, product_version),
  KEY confirmed (confirmed))

INSERT INTO ofs_products2
SELECT
  pvid,
  product_id,
  product_version,
  product_name,
  product_description,
  unit_price,
  pricing_unit,
  ordering_unit,
  extra_charge,
  confirmed,
  approved,
  active,
  created,
  modified,
  tangible
FROM ofs_products;

-- Set all "preferred" products as "active" products
UPDATE ofs_products
  SET active = 1
  WHERE confirmed = -1

-- NOW BEGIN ASSIGNING "approved" PRODUCTS

-- NOTE: At any time in the following, use this query to discover how many
-- products remain to be approved:
    SELECT COUNT(pvid)
    FROM ofs_products
    WHERE approved = 0

-- Set all "confirmed" products as "approved" products
UPDATE ofs_products SET approved = 1 WHERE confirmed = 1

-- If you want to assume that any product previously purchased should be approved
-- then set all previously-purchased versions of a product to "approved"
UPDATE ofs_products
  LEFT JOIN ofs_basket_items USING (product_id, product_version)
  SET ofs_products.approved = 1
  WHERE bpid IS NOT NULL

-- The earlier OFS version did not correctly keep "approved" product settings
-- so many products that are not "active" were probably once approved. We need
-- to make some simplifying assumptions to prevent the need to suddenly have
-- hundreds or thousands of products to re-approve from the web front-end.

-- For the following queries, we will use approved > 1 as a conditional approval value
-- as we require approved products to pass all the applied filters below

-- Sometimes products are mistakenly assigned tangible=0 when they really are tangible.
-- If you want to conditionally approve all products with tangible=1:
UPDATE ofs_products
SET approved = 2
WHERE
  approved = 0
  AND tangible = 1

-- Sometimes producer just forget to enter a price
-- If you want to conditionally approve all products with either a unit_price or extra_charge:
UPDATE ofs_products
SET approved = 3                                    /* One more than value SET in the prior step */
WHERE
  approved = 2                                      /* Match value SET in prior step */
  AND (unit_price != 0 OR extra_charge != 0)

-- The following step will approve all products having a product_name that matches an existing
-- "approved" product_name (If it was okay once then it should be okay again, right?)
-- NOTE: On one system this query many minutes to complete; timeouts may apply
UPDATE ofs_products
LEFT JOIN ofs_products2 USING(product_name)
SET ofs_products.approved = 4                       /* One more than value SET in the prior step */
WHERE
  ofs_products.approved = 3                         /* One more than value SET in the prior step */
  AND ofs_products2.product_name IS NOT NULL        /* product_name matches a product */
  AND ofs_products2.approved = 1                    /* where it was previously approved */

-- The following step will approve all products having a product_description that matches an existing
-- "approved" product_description (If it was okay once then it should be okay again, right?)
-- NOTE: Since product descriptions are long and diverse, this requirement may be overly limiting
-- ALSO NOTE: On one system this query required more than an hour to complete; timeouts may apply
SELECT
  ofs_products.pvid,
  ofs_products.product_id,
  ofs_products.product_version,
  ofs_products.product_name
UPDATE ofs_products
LEFT JOIN ofs_products2 USING(product_description)
SET ofs_products.approved = 5                       /* One more than value SET in the prior step */
WHERE
  ofs_products.approved = 4                         /* One more than value SET in the prior step */
  AND ofs_products2.product_description IS NOT NULL /* product_description matches a product */
  AND ofs_products2.approved = 1                    /* where it was previously approved */

-- The following step will approve all products having an ordering_unit that matches an existing
-- "approved" ordering_unit (If it was okay once then it should be okay again, right?)
-- NOTE: On one system this query over seven minutes to complete; timeouts may apply
UPDATE ofs_products
LEFT JOIN ofs_products2 USING(ordering_unit)
SET ofs_products.approved = 6                       /* One more than value SET in the prior step */
WHERE
  ofs_products.approved = 5                         /* One more than value SET in the prior step */
  AND ofs_products2.product_description IS NOT NULL /* ordering_unit matches a product */
  AND ofs_products2.approved = 1                    /* where it was previously approved */

-- The following step will approve all products having an pricing_unit that matches an existing
-- "approved" pricing_unit (If it was okay once then it should be okay again, right?)
UPDATE ofs_products
LEFT JOIN ofs_products2 USING(pricing_unit)
SET ofs_products.approved = 7                       /* One more than value SET in the prior step */
WHERE
  ofs_products.approved = 6                         /* One more than value SET in the prior step */
  AND ofs_products2.product_description IS NOT NULL /* pricing_unit matches a product */
  AND ofs_products2.approved = 1                    /* where it was previously approved */

-- You can check how many products passed/failed the various criteria above with this query
SELECT
  COUNT(pvid),
  approved
FROM ofs_products
WHERE approved > 1
GROUP BY approved
ORDER BY approved

-- Now convert all the products that passed all filters (approved = 7) to approved = 1
UPDATE ofs_products
SET approved = 1
WHERE
  approved = 7

-- Finally, convert all products that did not pass all filters back to approved = 0
UPDATE ofs_products
SET approved = 0
WHERE
  approved > 1

-- If desired, you can simply delete those products that have never been ordered
-- and have been unconfirmed for a certain period of time (e.g. a year = 365 days)

-- Here is a list of those products (invert the > sign to see the products that would remain)
SELECT
  pvid,
  product_id,
  product_version,
  product_name,
  modified
FROM ofs_products
LEFT JOIN ofs_basket_items USING (product_id, product_version)
WHERE
  approved = 0
  AND bpid IS NULL
  AND DATEDIFF(NOW(), modified) > 365 /* Number of days old */

-- And apply the condition to delete those products
DELETE ofs_products
FROM ofs_products
LEFT JOIN ofs_basket_items USING (product_id, product_version)
WHERE
  approved = 0
  AND bpid IS NULL
  AND DATEDIFF(NOW(), modified) > 365 /* Number of days old */

-- If there is still no "active" version for any particular product_id, then
-- set it equal to the highest "confirmed" (aka "approved") version
UPDATE ofs_products
SET ofs_products.active = 1
WHERE
  ofs_products.product_version = (
    SELECT MAX( product_version )
    FROM (SELECT product_id, product_version, confirmed FROM ofs_products) foo
    WHERE foo.product_id = ofs_products.product_id
      AND foo.confirmed =1
    )
  AND (
    SELECT SUM(active)
    FROM (SELECT product_id, product_version, confirmed, active FROM ofs_products) bar
    WHERE bar.product_id = ofs_products.product_id
    GROUP BY product_id
    ) = 0

-- Check all products that will remain to be confirmed
SELECT
  product_id,
  product_version,
  product_name,
  confirmed,
  active,
  approved
FROM ofs_products
WHERE approved = 0

-- Check for products without an active version (There may be a few recently added products)
SELECT
  product_id,
  product_version,
  SUM(active) AS sum_approved
FROM ofs_products
WHERE 1
GROUP BY product_id
HAVING sum_approved = 0
