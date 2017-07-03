# SitemapManagerPHP
## Class for managing web site's sitemaps.

This class allows to automatically insert new URLs in to sitemaps.
It manages sitemap_index.xml and fills it with sitemap files.

* Each sitemap file is limited to either 50K urls or 10Mb size.
* Each sitemap file being compressed.
* Sitemap file name format is "sitemap#.xml.gz" eg: sitemap1.xml.gz, sitemap2.xml.gz ...


### Usage:
```bash
include('SitemapManager.php');

$sitemapManager = new SitemapManager();

$sitemapManager->addNewUrlToSitemap("http://mywebsite.com/somepage.php?arg1=aaa&arg2=bbb");
```



### Debugging:
```bash
$sitemapManager->printDebug = true;
```
