<?php

class SiteMapXML
{

    const ALWAYS = 'always';
    const HOURLY = 'hourly';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';
    const NEVER = 'never';

    public $show_lastmod = 1;
    public $show_changefreq = 1;
    public $show_priority = 1;
    public $changefreq = SiteMapXML::WEEKLY;
    public $user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36";
    public $no_follow = array('/secret_url', '/login.php', '/404/');
    public $allowed_extensions = array('php', 'aspx', 'htm', 'html', 'asp', 'cgi');
    public $file_path = '';
    public $file_name = "sitemap.xml";
    private $urls = array();
    private $host = 'example.com';
    private $protocol = 'http';

    public function __construct($host, $protocol = 'http', $file_path = '')
    {
        $this->host = trim($host, '/');
        $this->protocol = $protocol . '://';
        $this->file_path = $file_path != '' ? $file_path : $_SERVER['DOCUMENT_ROOT'] . '/';
        $this->urls[$this->protocol . $this->host] = '0';
    }

    public function makeXML()
    {
        $this->get_urls();
        $this->saveXML();
    }

    private function get_urls($page = '')
    {
        if ($page == '') {
            $page = $this->protocol . $this->host;
        }
        //is already visited
        if ($this->urls[$page] != 0) {
            return false;
        }

        //get content, if 404 page so do not add it
        $content = $this->get_page_content($page);
        if (!$content) {
            unset($this->urls[$page]);
            return false;
        }
        //checking as visited;
        $this->urls[$page] = count($this->urls);
        //get all link at this page
        preg_match_all("/<[Aa][\s]{1}[^>]*[Hh][Rr][Ee][Ff][^=]*=[ '\"\s]*([^ \"'>\s#]+)[^>]*>/", $content, $fetched_links);
        $links = array();

        foreach ($fetched_links[0] as $key => $value) {
            if (!preg_match('/.*[Rr][Ee][Ll].?=.?("|\'|)(nofollow|noindex).*?("|\'|).*/', $value)) {
                $links[$key] = $fetched_links[1][$key];
            } else {
                $links[$key] = '/';
            }
        }
        for ($i = 0; $i < count($links); $i++) {
            //one sitemap must have less then 50 000 url;
            if (count($this->urls) > 49999) {
                return false;
            }
            //if relative link
            if (!strstr($links[$i], $this->protocol . $this->host)) {
                $links[$i] = $this->protocol . $this->host . $links[$i];
            }
            //deleting anchors
            $links[$i] = preg_replace("/#.*/X", "", $links[$i]);
            $urlinfo = @parse_url($links[$i]);
            if (!isset($urlinfo['path'])) {
                $urlinfo['path'] = NULL;
            }
            //if mailto or another website or main page so skip it
            if ((isset($urlinfo['host']) AND $urlinfo['host'] != $this->host) OR $urlinfo['path'] == '/' OR isset($this->urls[$links[$i]]) OR strstr($links[$i], '@')) {
                continue;
            }
            //if link in nofollow
            $blocked_url_manually = 0;
            if ($this->no_follow != NULL) {
                foreach ($this->no_follow as $url) {
                    if (strstr($links[$i], $url)) {
                        $blocked_url_manually = 1;
                        break;
                    }
                }
            }
            if ($blocked_url_manually == 1) {
                continue;
            }
            //check extension
            $ext = count(explode('.', $urlinfo['path'])) == 1 ? '' : explode('.', $urlinfo['path'])[1];
            $noext = 0;
            if ($ext != '' AND strstr($urlinfo['path'], '.') AND count($this->allowed_extensions) != 0) {
                $noext = 1;
                foreach ($this->allowed_extensions as $of) {
                    if ($ext == $of) {
                        $noext = 0;
                        continue;
                    }
                }
            }
            if ($noext == 1) {
                continue;
            }
            $this->urls[$links[$i]] = 0;
            //check for new url at this page
            $this->get_urls($links[$i]);
        }
        return true;
    }

    private function get_page_content($url)
    {
        set_time_limit(0);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        $data = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 404) {
            $data = false;
        }
        curl_close($ch);
        return $data;
    }

    private function saveXML()
    {
        date_default_timezone_set('UTC');
        $date = date('Y-m-d H:i:s');
        $date = str_replace(' ', 'T', $date);
        $sitemapXML = '<?xml version="1.0" encoding="UTF-8"?>'. PHP_EOL .
            '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '.
            'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        $this->urls = array_flip($this->urls);
        foreach ($this->urls as $url) {
            $sitemapXML .= "\r\n<url><loc>{$url}</loc>";
            if ($this->show_lastmod) {
                $sitemapXML .= "<lastmod>$date+00:00</lastmod>";
            }
            if ($this->show_changefreq) {
                $sitemapXML .= "<changefreq>$this->changefreq</changefreq>";
            }
            if ($this->show_priority) {
                $sitemapXML .= "<priority>0.8</priority>";
            }
            $sitemapXML .= "</url>";
        }
        $sitemapXML .= "\r\n</urlset>";
        $sitemapXML = trim(strtr($sitemapXML, array('%2F' => '/', '%3A' => ':', '%3F' => '?', '%3D' => '=', '%26' => '&', '%27' => "'", '%22' => '"', '%3E' => '>', '%3C' => '<', '%23' => '#', '&' => '&')));
        $fp = fopen($this->file_path . $this->file_name, 'w+');
        if (!fwrite($fp, $sitemapXML)) {
            echo 'Write Error!';
        }
        echo "<pre>";
        print_r($this->urls);
        fclose($fp);
    }
}