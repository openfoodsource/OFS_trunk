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
    ofs_configuration.section = '1. Server Setup'
    AND ofs_configuration.name = 'domainname';

-- Set new software version
UPDATE ofs_configuration
  SET options = 'read_only',
    value = 'OFSv1.1.1'
  WHERE ofs_configuration.section = '1. Server Setup'
    AND ofs_configuration.name = 'current_version';
