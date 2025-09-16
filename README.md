# S2S Postback Testing Tool

A comprehensive Server-to-Server (S2S) postback testing application for affiliate marketers and tracker operators.

## English Documentation

### Features

🚀 **Complete Testing Environment**
- Click tracking with macro replacement (`{transaction_id}`, `{sub1}`, etc.)
- Local offer pages with conversion forms
- Real S2S postback firing to your tracker
- Manual postback testing tool

📊 **Analytics Dashboard**
- Today/7-day/30-day metrics
- Top performing offers
- Conversion tracking and CTR monitoring
- Real-time activity logs

🎨 **Modern UI**
- Dark glassmorphism theme
- Responsive design
- Copy-to-clipboard functionality
- Clean admin interface

🔧 **Admin Panel**
- Offers CRUD management
- Global postback configuration
- Comprehensive logging system
- Test connectivity tools

### Quick Start

1. **Installation**
   ```bash
   # Clone the repository
   git clone https://github.com/alfredhafez/S2S-Tracking-Tool.git
   cd S2S-Tracking-Tool
   
   # Copy configuration
   cp config.example.php config.php
   
   # Edit database credentials in config.php
   nano config.php
   ```

2. **Database Setup**
   - Navigate to `/install/install.php` in your browser
   - Enter your database credentials
   - Run the installer to create tables and seed data

3. **Configuration**
   - Go to `/admin/settings.php`
   - Set your postback base URL (e.g., `https://tr.optimawall.com/pbtr?transaction_id={transaction_id}&goal={goal}`)
   - Configure default parameters

4. **Testing**
   - Use prebuilt offers or create your own in `/admin/offers.php`
   - Click offer links to test the flow
   - Submit conversion forms to fire postbacks
   - Monitor activity in `/admin/logs.php`

### Usage Examples

**Click Tracking:**
```
https://yourdomain.com/click.php?offer=1&sub1={transaction_id}
```

**Local Offer Page:**
```
https://yourdomain.com/offer.php?id=1&tid=TEST_TID_123
```

**Manual Testing:**
```
https://yourdomain.com/postback-test.php
```

### Supported Networks

The tool comes with prebuilt offer templates for:
- Adscend Media
- CPALead
- OGAds
- Generic HTTP offers
- Local testing offers

---

## বাংলা ডকুমেন্টেশন

### বৈশিষ্ট্যসমূহ

🚀 **সম্পূর্ণ পরীক্ষার পরিবেশ**
- ম্যাক্রো রিপ্লেসমেন্ট সহ ক্লিক ট্র্যাকিং (`{transaction_id}`, `{sub1}`, ইত্যাদি)
- কনভার্শন ফর্ম সহ স্থানীয় অফার পেজ
- আপনার ট্র্যাকারে সত্যিকারের S2S পোস্টব্যাক পাঠানো
- ম্যানুয়াল পোস্টব্যাক পরীক্ষার টুল

📊 **বিশ্লেষণ ড্যাশবোর্ড**
- আজ/৭ দিন/৩০ দিনের মেট্রিক্স
- সেরা পারফরমিং অফার
- কনভার্শন ট্র্যাকিং এবং CTR মনিটরিং
- রিয়েল-টাইম কার্যকলাপ লগ

🎨 **আধুনিক UI**
- ডার্ক গ্লাসমরফিজম থিম
- রেসপন্সিভ ডিজাইন
- কপি-টু-ক্লিপবোর্ড কার্যকারিতা
- পরিষ্কার অ্যাডমিন ইন্টারফেস

🔧 **অ্যাডমিন প্যানেল**
- অফার CRUD ম্যানেজমেন্ট
- গ্লোবাল পোস্টব্যাক কনফিগারেশন
- ব্যাপক লগিং সিস্টেম
- সংযোগ পরীক্ষার টুল

### দ্রুত শুরু

১. **ইনস্টলেশন**
   ```bash
   # রিপোজিটরি ক্লোন করুন
   git clone https://github.com/alfredhafez/S2S-Tracking-Tool.git
   cd S2S-Tracking-Tool
   
   # কনফিগারেশন কপি করুন
   cp config.example.php config.php
   
   # config.php ফাইলে ডাটাবেস ক্রেডেনশিয়াল এডিট করুন
   nano config.php
   ```

২. **ডাটাবেস সেটআপ**
   - আপনার ব্রাউজারে `/install/install.php` এ যান
   - আপনার ডাটাবেস ক্রেডেনশিয়াল প্রবেশ করান
   - টেবিল তৈরি এবং সিড ডাটার জন্য ইনস্টলার চালান

৩. **কনফিগারেশন**
   - `/admin/settings.php` এ যান
   - আপনার পোস্টব্যাক বেস URL সেট করুন (উদাঃ `https://tr.optimawall.com/pbtr?transaction_id={transaction_id}&goal={goal}`)
   - ডিফল্ট প্যারামিটার কনফিগার করুন

৪. **পরীক্ষা**
   - `/admin/offers.php` এ প্রিবিল্ট অফার ব্যবহার করুন অথবা নিজের তৈরি করুন
   - ফ্লো পরীক্ষার জন্য অফার লিঙ্কে ক্লিক করুন
   - পোস্টব্যাক ফায়ার করার জন্য কনভার্শন ফর্ম সাবমিট করুন
   - `/admin/logs.php` এ কার্যকলাপ মনিটর করুন

### ব্যবহারের উদাহরণ

**ক্লিক ট্র্যাকিং:**
```
https://yourdomain.com/click.php?offer=1&sub1={transaction_id}
```

**স্থানীয় অফার পেজ:**
```
https://yourdomain.com/offer.php?id=1&tid=TEST_TID_123
```

**ম্যানুয়াল পরীক্ষা:**
```
https://yourdomain.com/postback-test.php
```

### সমর্থিত নেটওয়ার্ক

টুলটি নিম্নলিখিত নেটওয়ার্কের জন্য প্রিবিল্ট অফার টেমপ্লেট নিয়ে আসে:
- Adscend Media
- CPALead
- OGAds
- জেনেরিক HTTP অফার
- স্থানীয় পরীক্ষার অফার

---

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- cURL extension enabled
- Modern web browser

## Security Features

- Prepared SQL statements
- CSRF token protection
- Input validation and sanitization
- Optional admin password protection
- Safe macro replacement

## License

This project is open source and available under the MIT License.
