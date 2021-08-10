
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting`, `descrip`, `hook`, `data`) VALUES
(1, 'Google Count', 'How many google returns per search', 'googlecount', '100'),
(2, 'Status White Checks', 'A pipe-delimited list of positive pattern matches for the stores status scraper', 'status_white_checks', ''),
(3, 'Status Black Checks', 'A pipe-delimited list of negative pattern matches for the stores status scraper', 'status_black_checks', ''),
(6, 'Force Scrape', 'When set to 1, the status scraper will scrape all sites whether previously visited or not. Set to 0, it will only scrape when needed.', 'force_scrape', '1'),
(7, 'Google Address Min Delay', 'Minimum delay for Google address scraper', 'google_address_min_delay', '62'),
(8, 'Google Address Max Delay', 'Maximum delay for Google address scraper.', 'google_address_max_delay', '110'),
(9, 'Bing Phone Max Delay', 'Maximum delay for Bing phone scraper.', 'bing_phone_max_delay', '62'),
(10, 'Bing Phone Min Delay', 'Minimum delay for Bing phone scraper', 'bing_phone_min_delay', '31'),
(11, 'Melissa Data Threshold', 'Set the minimum score Melissa Data is permitted to scrape', 'md_threshold', '6'),
(12, 'Melissa Data IPs', 'A CSV list of IP addresses Melissa Data is permitted to scrape', 'md_ipaddresses', ''),
(13, 'Active Profile', 'The record ID of the currently active profile. See Jobs page.', 'activeProfile', '');


--
-- Dumping data for table `phinxlog`
--

INSERT INTO `phinxlog` (`version`, `migration_name`, `start_time`, `end_time`, `breakpoint`) VALUES
('20210505173410', 'InitMigration', '2021-05-07 15:19:55', '2021-05-07 15:19:55', '0'),
('20210507190847', 'DeleteOldStoreColumns', '2021-05-10 17:17:23', '2021-05-10 17:17:24', '0'),
('20210511174057', 'AddNavTargets', '2021-05-11 17:46:12', '2021-05-11 17:46:12', '0'),
('20210615165200', 'FixAddressNotNullCols','2021-06-23 16:13:52', '2021-06-23 16:13:52', '0');

COMMIT;
