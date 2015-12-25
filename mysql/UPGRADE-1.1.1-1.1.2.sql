-- --------------------------------------------------------------------- --
--                                                                       --
--      QUERIES FOR UPDATING OFS DATABASE FROM V1.1.1 TO V1.1.2          --
--                                                                       --
-- --------------------------------------------------------------------- --
-- 
-- The following queries should bring most aspects of an existing system --
-- from OFS v1.1.0 to OFS v1.1.1. Information that might have changed in --
-- operational systems should be handled properly. As always, please     --
-- be sure to make backups before upgrading a system.
--

-- --------------------------------------------------------------------- --
-- CONFIGURATION TABLE --

-- Update domainname to accept multiple (optional) domain designations
UPDATE ofs_configuration
  SET
    options = 'text_area',
    description = 'Domain names for this site. List one per line. The first line will be used for e-mail. Example openfoodsource.org gives e-mail addresses like help@openfoodsource.org. The first line should not include any subdomain portion, like www unless it is used for e-mail.'
  WHERE
    section = '1. Server Setup'
    AND name = 'domainname';

-- Add accounting_zero_datetime to configuration file for globally pinning accounts
INSERT INTO
  new_configuration
SET
  section = '5. Finances',
  name = 'accounting_zero_datetime',
  constant = 'ACCOUNTING_ZERO_DATETIME',
  options = 'input_pattern= .*',
  value = '1970-01-01 00:00:00',
  description= 'Set a date/time for the accounting to be restarted. In normal operation, this should be left empty. For organizations which have historical data from the legacy accounting system, this provides a convenient way to ignore any discrepancies in that older data. Enter as YYY-MM-DD HH:MM:SS.';

-- Add anonymous_markup_percent to configuration file so this can be removed from order_cycles table
INSERT INTO
  new_configuration
SET
  section = '5. Finances',
  name = 'anonymous_markup_percent',
  constant = 'ANONYMOUS_MARKUP_PERCENT',
  options = 'input_pattern= \r\n[0-9.]+',
  value = '25',
  description= 'This is the default customer markup to show for products when members are not logged in or when visitors are just looking at the site.';

-- Transition Google tracking to allow for Google Tag Manager
UPDATE ofs_configuration
SET
  name = 'google_tracking_id',
  constant = 'GOOGLE_TRACKING_ID',
  options = 'input_pattern=\r\n(UA\\-[0-9]{6,10}\\-[0-9]{1,3})|(GTM\\-[A-Z0-9]{6,12})',
  value = '',
  description = 'To monitor the traffic on your site with Google Analytics or Google Tag Manager, enter the tracking ID here. An Analytics ID will look something like UA-000000-01 and a Tag Manager ID will look something like GTM-XXXXXX. Leave the field blank if you do not want to use Google tracking.'
WHERE
  section = '7. Optional Modules'
  AND name = 'google_analytics_tracking_id';

-- Set new software version
UPDATE ofs_configuration
  SET options = 'read_only',
    value = 'OFSv1.1.2'
  WHERE
    section = '1. Server Setup'
    AND name = 'current_version';
