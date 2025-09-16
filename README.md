# S2S Postback Tracking Tool

A simple server-to-server tracking script and postback tester for affiliate offers.

## Features
- Click recording with unique transaction IDs
- Offer page with conversion simulation
- S2S postback receiver endpoint
- Manual postback testing tool
- Database installer and schema
- Clean UI (CSS) and basic security

## Quick Start
1. Copy `config.example.php` to `config.php` and update DB credentials.
2. Upload all files to your web server.
3. Run `install/install.php` to create the database and tables.
4. Insert an offer into the `offers` table (via SQL or your DB tool). Example:
   ```sql
   INSERT INTO offers (name, url) VALUES ('Sample Offer', 'https://example.com/offer?tid={transaction_id}');
   ```
5. Test a click:
   - `/click.php?offer=1&sub1=test123`
6. Simulate a conversion on the local offer fallback:
   - `/offer.php?id=1&tid=test123` then click "Simulate Conversion"
7. Test S2S receiver directly:
   - `/s2s.php?offer=1&tid=test123&amount=1.23&status=approved`
8. Use the manual tester UI:
   - `/postback-test.php`

## Security Notes
- Inputs are validated/sanitized and SQL uses prepared statements.
- For production: lock down access to `install/`, add authentication to the tester, and consider IP allowlists for the S2S receiver.

## License
MIT
