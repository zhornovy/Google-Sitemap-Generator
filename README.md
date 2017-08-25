# Google-Sitemap-Generator

- Auto Crawl
- Ignore broken links (404 error responce)
- Ignore ```mailto:// ``` links
- Ignore ```nofollow``` and ```noindex``` pages
- Manual ignore list
- Manual allowed extentions
- Custom user agent
- Custom save path
- SEO optimized
- Complies with all requirements of the Sitemap standard 
- Easy to use
- Cron friendly

# Examples!

- Simple
```php
include "sitemap.php"
$xml_new = new SiteMapXML('yourwebsite.com');
$xml_new->makeXML();
```

```php
include "sitemap.php"
$xml_new = new SiteMapXML('yourwebsite.com','https');
$xml_new->makeXML();
```
- Advanded
```php
include "sitemap.php"
$xml_new = new SiteMapXML('www.yourwebsite.com', 'https', '/home/user/public_html/sitemap_folder');
$xml_new->show_lastmod = false;
$xml_new->show_changefreq = true;
$xml_new->changefreq = SiteMapXML::WEEKLY;
$xml_new->user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) Custom Agent";
$xml_new->file_name = "sitemap-1.xml";
$xml_new->no_follow = array('/secret_url', '/login.php', '/404/'); //custom pages
$xml_new->allowed_extensions = array('.php', '.html');
$xml_new->makeXML();
```

### Installation

Requires [PHP](https://secure.php.net/) v5.3+
No dependencies are needed.
- Upload `sitemap.php` to your server
- Create new php file or add to existing one
- Include class and use it like in exaples above

### Development

Want to contribute? Great!

**Free Software!**
