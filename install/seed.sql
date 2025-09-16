-- Seed data for S2S Postback Testing Tool

-- Insert default settings
INSERT INTO `settings` (`id`, `postback_base_url`, `default_goal`, `default_amount`, `extra_params`) VALUES
(1, 'https://tr.optimawall.com/pbtr?transaction_id={transaction_id}&goal={goal}', 'conversion', 1.00, '{"source":"s2s_tool","version":"1.0"}');

-- Insert sample offers
INSERT INTO `offers` (`name`, `slug`, `partner_url_template`, `notes`) VALUES
('Adscend Media Sample', 'adscend-sample', 'https://rewardtk.com/click.php?aff=116268&camp=6067997&sub1={transaction_id}', 'Sample Adscend Media offer with sub1 macro for transaction tracking'),
('CPALead Sample', 'cpalead-sample', 'https://cpalead.com/link.php?id=12345&tid={transaction_id}', 'Sample CPALead offer with tid parameter for tracking'),
('OGAds Sample', 'ogads-sample', 'https://ogads.com/click.php?offer=54321&sub={transaction_id}', 'Sample OGAds offer with sub parameter for tracking'),
('Generic HTTP Offer', 'generic-http', 'https://example.com/offer?ref={transaction_id}&campaign=test', 'Generic HTTP offer for testing purposes'),
('Local Test Offer', 'local-test', '', 'Local test offer that always uses the fallback page - no partner URL');