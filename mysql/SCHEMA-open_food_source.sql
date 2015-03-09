CREATE TABLE IF NOT EXISTS ofs_accounts (
  account_id mediumint(9) NOT NULL AUTO_INCREMENT COMMENT 'used for account_number in ledger entries',
  internal_key varchar(25) NOT NULL COMMENT 'textual name passed by software',
  internal_subkey varchar(25) NOT NULL DEFAULT '',
  account_number varchar(50) NOT NULL,
  sub_account_number varchar(50) NOT NULL DEFAULT '',
  description varchar(255) NOT NULL,
  UNIQUE KEY internal_key (internal_key),
  UNIQUE KEY account_id (account_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_availability (
  producer_id mediumint(9) NOT NULL,
  site_id smallint(5) unsigned NOT NULL,
  UNIQUE KEY producer_delcode_id (producer_id,site_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_baskets (
  basket_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  member_id int(11) NOT NULL DEFAULT '0',
  delivery_id int(11) NOT NULL DEFAULT '0',
  site_id smallint(5) unsigned NOT NULL,
  delivery_postal_code varchar(15) NOT NULL COMMENT 'postal code where product delivery will occur',
  delivery_type char(1) NOT NULL,
  delivery_cost decimal(6,2) NOT NULL DEFAULT '0.00',
  order_cost decimal(6,2) NOT NULL DEFAULT '0.00',
  order_cost_type enum('fixed','percent') NOT NULL,
  customer_fee_percent decimal(6,3) NOT NULL DEFAULT '0.000' COMMENT 'percentage assessed for this member',
  order_date datetime NOT NULL,
  checked_out tinyint(1) NOT NULL DEFAULT '0',
  locked tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (basket_id),
  UNIQUE KEY delivery_member_id (delivery_id,member_id),
  KEY delivery_id (delivery_id),
  KEY member_id (member_id),
  KEY delcode_id (site_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE IF NOT EXISTS ofs_basket_items (
  bpid int(10) unsigned NOT NULL AUTO_INCREMENT,
  basket_id int(11) NOT NULL DEFAULT '0',
  product_id int(11) NOT NULL DEFAULT '0',
  product_version mediumint(9) NOT NULL,
  quantity int(11) NOT NULL DEFAULT '0',
  total_weight decimal(8,2) NOT NULL DEFAULT '0.00',
  product_fee_percent decimal(6,3) NOT NULL COMMENT 'percentage assessed for this product',
  subcategory_fee_percent decimal(6,3) NOT NULL COMMENT 'percentage assessed for this subcategory',
  producer_fee_percent decimal(6,3) NOT NULL COMMENT 'percentage assessed for this producer',
  out_of_stock mediumint(9) NOT NULL DEFAULT '0',
  future_delivery tinyint(1) NOT NULL DEFAULT '0',
  future_delivery_type varchar(20) NOT NULL DEFAULT '',
  checked_out tinyint(4) NOT NULL DEFAULT '0',
  date_added datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (bpid),
  KEY product_version_id (product_id,product_version),
  KEY basket_id (basket_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE IF NOT EXISTS ofs_categories (
  category_id int(11) NOT NULL AUTO_INCREMENT,
  category_name varchar(37) NOT NULL DEFAULT '',
  category_desc varchar(225) DEFAULT NULL,
  taxable tinyint(4) NOT NULL DEFAULT '0',
  parent_id int(11) NOT NULL DEFAULT '0',
  sort_order int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (category_id),
  UNIQUE KEY category_name (category_name)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_configuration (
  section varchar(40) NOT NULL,
  name varchar(40) NOT NULL,
  constant varchar(40) NOT NULL,
  options text NOT NULL,
  value text NOT NULL,
  description text NOT NULL,
  PRIMARY KEY (name,section)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_delivery_codes (
  delcode_id varchar(5) NOT NULL DEFAULT '',
  delcode varchar(40) NOT NULL DEFAULT '',
  deltype char(1) DEFAULT NULL,
  deldesc longtext,
  delcharge double DEFAULT '0',
  route_id double NOT NULL DEFAULT '0',
  hub char(3) DEFAULT NULL,
  truck_code char(3) NOT NULL DEFAULT '',
  delivery_postal_code varchar(15) NOT NULL COMMENT 'used to store zipcode for delivery sites (deltype=P)',
  inactive tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (delcode_id),
  KEY delcode (delcode),
  KEY route_id (route_id),
  KEY truck_code (truck_code)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_delivery_types (
  deltype_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  deltype_group char(1) NOT NULL DEFAULT '',
  deltype char(1) NOT NULL DEFAULT '',
  deltype_title varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (deltype_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_how_heard (
  how_heard_id smallint(6) NOT NULL AUTO_INCREMENT,
  how_heard_name varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (how_heard_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_hubs (
  hub_id smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  hub_short varchar(50) NOT NULL DEFAULT '',
  hub_long varchar(255) NOT NULL DEFAULT '',
  hub_color varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (hub_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_inventory (
  inventory_id int(11) NOT NULL AUTO_INCREMENT,
  producer_id mediumint(9) NOT NULL,
  description varchar(50) NOT NULL,
  quantity int(11) NOT NULL,
  PRIMARY KEY (inventory_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_ledger (
  transaction_id int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'transaction_id',
  transaction_group_id varchar(25) NOT NULL DEFAULT '',
  source_type enum('producer','member','tax','internal') CHARACTER SET utf8 NOT NULL COMMENT 'type of source account',
  source_key int(11) NOT NULL,
  target_type enum('producer','member','tax','internal') CHARACTER SET utf8 NOT NULL COMMENT 'type of destination account',
  target_key int(11) NOT NULL,
  amount decimal(9,2) NOT NULL DEFAULT '0.00',
  text_key varchar(20) NOT NULL COMMENT 'use to classify the transaction type',
  effective_datetime datetime NOT NULL,
  posted_by mediumint(9) DEFAULT NULL COMMENT 'member_id of person adding this transaction',
  replaced_by int(10) unsigned DEFAULT NULL COMMENT 'transaction_id that replaces/updates this transaction',
  replaced_datetime int(11) DEFAULT NULL COMMENT 'When was transaction replaced',
  timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'time transaction was last changed',
  basket_id int(10) unsigned DEFAULT NULL COMMENT 'References key from baskets table',
  bpid int(10) unsigned DEFAULT NULL COMMENT 'References key from basket_items table',
  site_id smallint(5) unsigned DEFAULT NULL COMMENT 'References key from sites table',
  delivery_id int(10) unsigned DEFAULT NULL COMMENT 'References key from order_cycles table',
  pvid int(10) unsigned DEFAULT NULL COMMENT 'References key from products table',
  PRIMARY KEY (transaction_id),
  KEY text_key (text_key),
  KEY source_key (source_key),
  KEY target_key (target_key),
  KEY basket_id (basket_id),
  KEY bpid (bpid),
  KEY delcode_id (site_id),
  KEY pvid (pvid),
  KEY delivery_id (delivery_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_members (
  member_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  pending tinyint(4) NOT NULL DEFAULT '0',
  username varchar(20) DEFAULT NULL,
  password varchar(100) DEFAULT NULL,
  auth_type set('member','producer','institution','orderex','member_admin','producer_admin','route_admin','cashier','site_admin','board') NOT NULL DEFAULT 'member',
  business_name varchar(50) DEFAULT NULL,
  preferred_name varchar(50) NOT NULL,
  last_name varchar(25) DEFAULT NULL,
  first_name varchar(25) DEFAULT NULL,
  last_name_2 varchar(25) DEFAULT NULL,
  first_name_2 varchar(25) DEFAULT NULL,
  no_postal_mail tinyint(1) NOT NULL DEFAULT '0',
  address_line1 varchar(50) DEFAULT NULL,
  address_line2 varchar(50) DEFAULT NULL,
  city varchar(15) DEFAULT NULL,
  state char(2) DEFAULT NULL,
  zip varchar(10) DEFAULT NULL,
  county varchar(15) DEFAULT NULL,
  work_address_line1 varchar(50) NOT NULL,
  work_address_line2 varchar(50) NOT NULL,
  work_city varchar(15) NOT NULL DEFAULT '',
  work_state char(2) NOT NULL DEFAULT '',
  work_zip varchar(10) NOT NULL,
  email_address varchar(100) DEFAULT NULL,
  email_address_2 varchar(100) DEFAULT NULL,
  home_phone varchar(20) DEFAULT NULL,
  work_phone varchar(20) DEFAULT NULL,
  mobile_phone varchar(20) DEFAULT NULL,
  fax varchar(20) DEFAULT NULL,
  toll_free varchar(20) DEFAULT NULL,
  home_page varchar(200) DEFAULT NULL,
  membership_type_id tinyint(4) NOT NULL DEFAULT '0',
  customer_fee_percent decimal(6,3) NOT NULL DEFAULT '0.000',
  membership_date date DEFAULT NULL,
  last_renewal_date date NOT NULL,
  membership_discontinued tinyint(1) unsigned NOT NULL DEFAULT '0',
  mem_taxexempt tinyint(1) NOT NULL DEFAULT '0',
  mem_delch_discount tinyint(1) NOT NULL DEFAULT '0',
  how_heard_id smallint(3) NOT NULL DEFAULT '0',
  notes text NOT NULL,
  PRIMARY KEY (member_id),
  UNIQUE KEY username_m (username),
  KEY pending (pending),
  KEY auth_type (auth_type),
  KEY membership_type_id (membership_type_id),
  KEY last_renewal_date (last_renewal_date),
  KEY membership_discontinued (membership_discontinued)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE IF NOT EXISTS ofs_membership_types (
  membership_type_id int(11) NOT NULL AUTO_INCREMENT,
  set_auth_type set('+member','+producer','+institution','+unfi','-member','-producer','-institution','-unfi') NOT NULL COMMENT '+ include auth_type, - exclude auth_type',
  initial_cost float NOT NULL,
  order_cost float NOT NULL,
  order_cost_type enum('fixed','percent') NOT NULL DEFAULT 'fixed',
  customer_fee_percent decimal(6,3) NOT NULL DEFAULT '0.000',
  producer_fee_percent decimal(6,3) NOT NULL DEFAULT '0.000',
  membership_class varchar(30) NOT NULL,
  membership_description varchar(255) NOT NULL,
  pending tinyint(4) NOT NULL DEFAULT '1',
  enabled_type tinyint(4) NOT NULL,
  may_convert_to varchar(255) NOT NULL,
  renew_cost float NOT NULL,
  expire_after int(11) NOT NULL,
  expire_type enum('day','week','month','year','calendar year','cycle','order') NOT NULL,
  expire_message varchar(255) NOT NULL,
  PRIMARY KEY (membership_type_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_messages (
  message_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  message_type_id smallint(5) NOT NULL COMMENT 'reference to message_types table',
  referenced_key1 int(10) NOT NULL DEFAULT '0' COMMENT 'key for primary indexing',
  referenced_key2 int(10) NOT NULL DEFAULT '0' COMMENT 'key for secondary indexing',
  referenced_key3 varchar(25) CHARACTER SET utf8 NOT NULL COMMENT 'tertiary key, if needed',
  message text CHARACTER SET utf8 NOT NULL COMMENT 'message or data to be stored',
  PRIMARY KEY (message_id),
  KEY referenced_key1 (referenced_key1),
  KEY referenced_key2 (referenced_key2)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_message_types (
  message_type_id smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  key1_target varchar(35) NOT NULL,
  key2_target varchar(35) NOT NULL,
  description varchar(50) NOT NULL,
  PRIMARY KEY (message_type_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_order_cycles (
  delivery_id int(11) NOT NULL AUTO_INCREMENT COMMENT 'Do not change this field to type BIGINT',
  date_open datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  date_closed datetime DEFAULT '0000-00-00 00:00:00',
  order_fill_deadline datetime NOT NULL,
  delivery_date date NOT NULL DEFAULT '0000-00-00',
  customer_type set('member','institution') NOT NULL,
  msg_all text NOT NULL,
  msg_bottom text NOT NULL,
  coopfee double NOT NULL DEFAULT '0',
  invoice_price tinyint(4) NOT NULL COMMENT '0=show coop price; 1=show retail price',
  producer_markdown double NOT NULL,
  retail_markup double NOT NULL,
  wholesale_markup double NOT NULL,
  PRIMARY KEY (delivery_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_payment_method (
  method_id smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  method_short varchar(15) NOT NULL COMMENT 'Name of payment type',
  transaction_fee decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Amount added to each transaction as a fee',
  transaction_percentage decimal(5,3) NOT NULL DEFAULT '0.000' COMMENT 'Percentage of transaction charged as a fee',
  paid_by set('payer','payee','none') NOT NULL COMMENT 'Who are the transaction fees applied to?',
  active tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (method_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_producers (
  producer_id mediumint(9) NOT NULL AUTO_INCREMENT,
  list_order smallint(6) NOT NULL,
  producer_link varchar(50) NOT NULL,
  pending tinyint(4) NOT NULL DEFAULT '0',
  member_id int(11) NOT NULL DEFAULT '0',
  business_name varchar(50) NOT NULL,
  payee varchar(50) NOT NULL,
  producer_fee_percent decimal(6,3) NOT NULL DEFAULT '0.000' COMMENT 'Percentage assessed to this producer',
  unlisted_producer tinyint(1) NOT NULL DEFAULT '0',
  producttypes varchar(150) NOT NULL DEFAULT '',
  about text NOT NULL,
  ingredients text NOT NULL,
  general_practices text NOT NULL,
  highlights text NOT NULL,
  additional text NOT NULL,
  liability_statement text NOT NULL,
  pub_address tinyint(1) NOT NULL DEFAULT '0',
  pub_email tinyint(1) NOT NULL DEFAULT '0',
  pub_email2 tinyint(1) NOT NULL DEFAULT '0',
  pub_phoneh tinyint(1) NOT NULL DEFAULT '0',
  pub_phonew tinyint(1) NOT NULL DEFAULT '0',
  pub_phonec tinyint(1) NOT NULL DEFAULT '0',
  pub_phonet tinyint(1) NOT NULL DEFAULT '0',
  pub_fax tinyint(1) NOT NULL DEFAULT '0',
  pub_web tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (producer_id),
  KEY member_id (member_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_producers_logos (
  logo_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  producer_id mediumint(9) NOT NULL,
  logo_desc varchar(50) NOT NULL DEFAULT '',
  bin_data longblob NOT NULL,
  filename varchar(50) NOT NULL DEFAULT '',
  filesize varchar(50) NOT NULL DEFAULT '',
  filetype varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (logo_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE IF NOT EXISTS ofs_producers_registration (
  pid int(5) NOT NULL AUTO_INCREMENT,
  producer_id mediumint(9) NOT NULL,
  member_id int(5) NOT NULL DEFAULT '0',
  business_name varchar(50) NOT NULL DEFAULT '',
  website varchar(100) NOT NULL DEFAULT '',
  products text NOT NULL,
  practices text NOT NULL,
  pest_management text NOT NULL,
  productivity_management text NOT NULL,
  feeding_practices text NOT NULL,
  soil_management text NOT NULL,
  water_management text NOT NULL,
  land_practices text NOT NULL,
  additional_information text NOT NULL,
  licenses_insurance text NOT NULL,
  organic_products text NOT NULL,
  certifying_agency text NOT NULL,
  agency_phone text NOT NULL,
  agency_fax text NOT NULL,
  organic_cert tinyint(1) NOT NULL DEFAULT '0',
  date_added date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (pid),
  UNIQUE KEY producer_id (producer_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE IF NOT EXISTS ofs_production_types (
  production_type_id int(11) NOT NULL AUTO_INCREMENT,
  prodtype varchar(35) DEFAULT NULL,
  proddesc varchar(225) DEFAULT NULL,
  PRIMARY KEY (production_type_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_products (
  pvid int(11) unsigned NOT NULL AUTO_INCREMENT,
  product_id int(11) NOT NULL,
  product_version mediumint(9) unsigned NOT NULL DEFAULT '1',
  producer_id mediumint(9) NOT NULL,
  product_name varchar(75) NOT NULL DEFAULT '',
  account_number varchar(15) NOT NULL,
  inventory_pull smallint(6) NOT NULL DEFAULT '1',
  inventory_id int(11) NOT NULL DEFAULT '0',
  product_description longtext NOT NULL,
  subcategory_id int(11) NOT NULL DEFAULT '0',
  future_delivery smallint(6) NOT NULL DEFAULT '0',
  future_delivery_type varchar(10) NOT NULL DEFAULT '',
  production_type_id int(11) NOT NULL DEFAULT '0',
  unit_price decimal(9,3) NOT NULL DEFAULT '0.000',
  pricing_unit varchar(20) NOT NULL DEFAULT '',
  ordering_unit varchar(20) NOT NULL DEFAULT '',
  random_weight smallint(1) NOT NULL DEFAULT '0',
  meat_weight_type varchar(20) DEFAULT NULL,
  minimum_weight decimal(6,2) DEFAULT '0.00',
  maximum_weight decimal(6,2) DEFAULT '0.00',
  extra_charge decimal(9,2) DEFAULT '0.00',
  product_fee_percent decimal(6,2) NOT NULL DEFAULT '0.00',
  image_id int(10) NOT NULL DEFAULT '0',
  listing_auth_type enum('institution','member','unlisted','archived','unfi') NOT NULL DEFAULT 'member' COMMENT 'auth_type for customers who can see this product listing',
  taxable tinyint(4) NOT NULL DEFAULT '0',
  confirmed tinyint(1) NOT NULL DEFAULT '0',
  retail_staple tinyint(1) NOT NULL DEFAULT '0',
  staple_type char(1) NOT NULL DEFAULT '',
  created datetime NOT NULL,
  modified datetime NOT NULL,
  tangible tinyint(1) NOT NULL DEFAULT '1',
  sticky tinyint(1) NOT NULL DEFAULT '0' COMMENT 'may be removed from basket only by admin',
  hide_from_invoice tinyint(1) NOT NULL DEFAULT '0',
  storage_id tinyint(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (pvid),
  KEY product_version (product_id,product_version),
  KEY inventory_id (inventory_id),
  KEY confirmed (confirmed),
  KEY producer_id (producer_id),
  KEY subcategory_id (subcategory_id),
  KEY production_type_id (production_type_id),
  KEY storage_id (storage_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_product_images (
  image_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  producer_id mediumint(8) unsigned NOT NULL,
  title varchar(255) NOT NULL,
  caption varchar(255) NOT NULL,
  image_content longblob NOT NULL,
  file_name varchar(50) NOT NULL DEFAULT '',
  content_size int(11) NOT NULL,
  mime_type varchar(50) NOT NULL DEFAULT '',
  width smallint(5) unsigned NOT NULL,
  height smallint(5) unsigned NOT NULL,
  PRIMARY KEY (image_id),
  KEY producer_id (producer_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE IF NOT EXISTS ofs_product_storage_types (
  storage_id mediumint(9) NOT NULL AUTO_INCREMENT,
  storage_type varchar(40) NOT NULL DEFAULT '',
  storage_code varchar(4) DEFAULT NULL,
  PRIMARY KEY (storage_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_repeat_orders (
  repeat_id int(11) NOT NULL AUTO_INCREMENT,
  product_id int(11) NOT NULL,
  repeat_cycles tinyint(4) NOT NULL,
  warn_cycles tinyint(4) NOT NULL,
  order_last_added smallint(6) NOT NULL,
  PRIMARY KEY (repeat_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Scheduling data for repeating orders';

CREATE TABLE IF NOT EXISTS ofs_routes (
  route_id smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  route_name varchar(40) NOT NULL DEFAULT '',
  rtemgr_member_id int(11) NOT NULL DEFAULT '0',
  rtemgr_namecd char(1) DEFAULT NULL,
  route_desc longtext,
  admin int(11) NOT NULL DEFAULT '0',
  hub_id smallint(5) unsigned NOT NULL,
  inactive tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (route_id),
  KEY rtemgr_member_id (rtemgr_member_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_sites (
  site_id smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  site_type set('producer','customer','institution') NOT NULL COMMENT 'Who can use this site',
  site_short varchar(10) NOT NULL COMMENT 'Abbreviation for routing',
  site_long varchar(40) NOT NULL,
  delivery_type set('P','D') NOT NULL DEFAULT 'P',
  site_description text NOT NULL,
  delivery_charge double NOT NULL DEFAULT '0',
  route_id smallint(5) unsigned NOT NULL,
  hub_id smallint(5) unsigned NOT NULL,
  truck_code char(3) NOT NULL DEFAULT '',
  delivery_postal_code varchar(15) NOT NULL COMMENT 'used to store zipcode for delivery sites (deltype=P)',
  inactive tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (site_id),
  KEY delcode (site_long),
  KEY route_id (route_id),
  KEY truck_code (truck_code)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_status (
  status_scope varchar(50) NOT NULL,
  status_key varchar(50) NOT NULL,
  status_value text NOT NULL,
  timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ttl_minutes int(11) NOT NULL COMMENT 'time to live',
  UNIQUE KEY scope_key (status_scope,status_key)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_subcategories (
  subcategory_id int(11) NOT NULL AUTO_INCREMENT,
  subcategory_name varchar(35) NOT NULL DEFAULT '',
  category_id int(11) NOT NULL DEFAULT '0',
  taxable tinyint(4) NOT NULL DEFAULT '0',
  subcategory_fee_percent decimal(6,3) NOT NULL DEFAULT '0.000' COMMENT 'Adjust fee for this subcategory',
  PRIMARY KEY (subcategory_id),
  KEY category_id (category_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_tax_rates (
  tax_id int(11) NOT NULL AUTO_INCREMENT,
  region_code varchar(20) NOT NULL,
  region_type varchar(15) NOT NULL,
  region_name varchar(40) NOT NULL DEFAULT '0',
  postal_code varchar(15) NOT NULL,
  order_id_start mediumint(9) NOT NULL,
  order_id_stop mediumint(9) NOT NULL COMMENT 'use 0 for current/ongoing values',
  tax_percent decimal(6,3) NOT NULL DEFAULT '0.000' COMMENT 'tax percent',
  PRIMARY KEY (tax_id),
  UNIQUE KEY region_postal_code (region_code,postal_code,order_id_start),
  KEY region_code (region_code)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Need only include lines that have actual tax values';

CREATE TABLE IF NOT EXISTS ofs_transactions (
  transaction_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  transaction_type mediumint(8) unsigned NOT NULL DEFAULT '0',
  transaction_name varchar(75) NOT NULL DEFAULT '',
  transaction_amount double(6,2) NOT NULL DEFAULT '0.00',
  transaction_user varchar(20) NOT NULL DEFAULT '',
  transaction_producer_id mediumint(9) NOT NULL,
  transaction_member_id int(11) unsigned DEFAULT NULL,
  transaction_basket_id int(11) unsigned DEFAULT NULL,
  transaction_delivery_id int(10) unsigned DEFAULT NULL,
  transaction_taxed tinyint(1) NOT NULL DEFAULT '0',
  transaction_timestamp datetime DEFAULT NULL,
  transaction_batchno mediumint(10) unsigned DEFAULT NULL,
  transaction_memo varchar(20) DEFAULT NULL,
  transaction_comments varchar(200) DEFAULT NULL,
  transaction_method char(1) DEFAULT NULL,
  xfer_to_ledger int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (transaction_id),
  KEY transaction_member_id (transaction_member_id),
  KEY basket_id (transaction_basket_id),
  KEY ttype (transaction_type),
  KEY transaction_producer_id (transaction_producer_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE IF NOT EXISTS ofs_transactions_types (
  ttype_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  ttype_parent int(10) unsigned NOT NULL DEFAULT '0',
  ttype_name varchar(75) NOT NULL DEFAULT '',
  ttype_creditdebit varchar(6) NOT NULL DEFAULT '',
  ttype_taxed tinyint(1) NOT NULL DEFAULT '0',
  ttype_desc varchar(100) NOT NULL DEFAULT '',
  ttype_status tinyint(1) NOT NULL DEFAULT '0',
  ttype_whereshow enum('','customer','producer') NOT NULL DEFAULT 'customer',
  ttype_value decimal(7,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (ttype_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_transaction_group_enum (
  adjustment_group_enum int(11) NOT NULL AUTO_INCREMENT COMMENT 'Enumeration of ledger.adjustment_group values',
  PRIMARY KEY (adjustment_group_enum)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_translation (
  context varchar(25) NOT NULL,
  input varchar(255) NOT NULL,
  output varchar(255) NOT NULL,
  last_seen datetime NOT NULL,
  PRIMARY KEY (context,input)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_transport_legs (
  route_id int(11) NOT NULL,
  leg_id int(11) NOT NULL,
  leg_sequence int(11) NOT NULL,
  UNIQUE KEY route_segment_sequence (route_id,leg_id,leg_sequence)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_transport_metrics (
  metric_id int(11) NOT NULL,
  site_start int(11) NOT NULL,
  site_finish int(11) NOT NULL,
  distance_km int(11) NOT NULL,
  time_minutes int(11) NOT NULL,
  UNIQUE KEY site_start_finish (site_start,site_finish),
  KEY metric_id (metric_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS ofs_transport_stops (
  leg_id int(11) NOT NULL,
  site_sequence int(11) NOT NULL,
  site_id varchar(1) NOT NULL,
  truck_id int(11) NOT NULL,
  layover_minutes int(11) NOT NULL,
  UNIQUE KEY segment_truck_sequence (leg_id,truck_id,site_sequence)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE VIEW ofs_transport AS
  (SELECT 
    ofs_transport_legs.leg_id AS leg_id,
    ofs_transport_legs.route_id AS route_id,
    ofs_transport_legs.leg_sequence AS leg_sequence,
    ofs_transport_stops.site_sequence AS site_sequence,
    ofs_transport_stops.site_id AS site_id,
    ofs_transport_stops.truck_id AS truck_id,
    ofs_transport_stops.layover_minutes AS layover_minutes
  FROM (ofs_transport_legs
    left join ofs_transport_stops
    on((ofs_transport_legs.leg_id = ofs_transport_stops.leg_id))));
