SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `ofs_members` (`member_id`, `pending`, `username`, `password`, `auth_type`, `business_name`, `preferred_name`, `last_name`, `first_name`, `last_name_2`, `first_name_2`, `no_postal_mail`, `address_line1`, `address_line2`, `city`, `state`, `zip`, `county`, `work_address_line1`, `work_address_line2`, `work_city`, `work_state`, `work_zip`, `email_address`, `email_address_2`, `home_phone`, `work_phone`, `mobile_phone`, `fax`, `toll_free`, `home_page`, `membership_type_id`, `customer_fee_percent`, `membership_date`, `last_renewal_date`, `membership_discontinued`, `mem_taxexempt`, `mem_delch_discount`, `how_heard_id`, `notes`) VALUES
(1, 0, 'peter', '5f4dcc3b5aa765d61d8327deb882cf99', 'member,producer,member_admin,producer_admin,route_admin,cashier,site_admin,board', '', 'Peter and Bessie Salamander', 'Salamander', 'Peter', 'Salamander', 'Bessie', 0, '567 Deer', '', 'Mojave', 'XA', '12365', 'Diamondback', '', '', '', '', '', 'bogus1@openfoodsource.org', '', '123-456-7890', '123-456-0987', '123-456-7809', '123-456-7980', '', '', 1, 25.000, '2010-06-01', '2014-06-01', 0, 0, 0, 0, ''),
(2, 0, 'paul', '5f4dcc3b5aa765d61d8327deb882cf99', 'member', '', 'Paul Rattlesnake', 'Rattlesnake', 'Paul', '', '', 0, '98765 West Maple  #304', '', 'Flatwood', 'XA', '12356', 'Bovine', '', '', '', '', '', 'bogus2@openfoodsource.org', '', '231-465-7890', '', '231-564-9998', '', '', '', 3, 25.000, '2013-01-01', '2014-01-01', 0, 0, 0, 0, ''),
(3, 0, 'mary', '5f4dcc3b5aa765d61d8327deb882cf99', 'member,institution,board', '', 'Mary Ferret and Jack Skunkworth', 'Ferret', 'Mary', 'Skunkworth', 'Jack', 0, '1122 N Pinewood St.', '', 'Beachwood', 'XA', '12345', 'Fowl', '', '', '', '', '', 'bogus3a@openfoodsource.org', 'bogus3b@openfoodsource.org', '333-444-7890', '', '', '', '', '', 2, 25.000, '2014-10-01', '2014-10-01', 0, 0, 0, 0, 'This person is really a weasel.');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
