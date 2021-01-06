<?php

class orf_at extends Plugin
{

    // define domains here for running on articles on
    private $domains = [ "rss.orf.at" ];

    private $host;

    public function about()
    {
        return array(
            1.0,
            "Try to enhance orf.at content when missing. Requires curl.",
            "anno"
        );
    }

    public function flags()
    {
        return array("needs_curl" => true);
    }

    public function api_version()
    {
        return 2;
    }

    public function init($host)
    {
        // _debug("Initialize orf_at plugin");
        
        // store the provided reference to host
        $this->host = $host;
        
        // hook on some hooks ;)
        // if (function_exists("curl_init"))
        // {
        // $host->add_hook($host::HOOK_FETCH_FEED, $this);
        // $host->add_hook($host::HOOK_FEED_FETCHED, $this);
        // $host->add_hook($host::HOOK_FEED_PARSED, $this);
        $host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
    //   }
    }


    // fetch feed to look for new articles
    public function hook_fetch_feed(
        $feed_data,
        $fetch_url,
        $owner_uid,
        $feed,
        $last_article_timestamp,
        $auth_login,
        $auth_pass
    ) {
        
		// _debug("hook_fetch_feed: fetch_url: " . $fetch_url);

        if (! $this->is_valid_domain($fetch_url)) {
            return $feed_data;
        }

        
        // _debug("hook_fetch_feed: feed: " . $feed);
        // _debug("hook_fetch_feed: fetch_url: " . $fetch_url);

        return $feed_data;
    }	// hook_fetch_feed


    public function hook_feed_fetched(
        $feed_data,
        $fetch_url,
        $owner_uid,
        $feed
    ) {
        _debug("hook_feed_fetched: fetch_url: " . $fetch_url);

        if (! $this->is_valid_domain($fetch_url)) {
            return $feed_data;
        }

        _debug("hook_feed_fetched: feed: " . $feed);

        return $feed_data;
    }	// hook_feed_fetched
    

    public function hook_feed_parsed($feed)
    {
        _debug("hook_feed_parsed: get_title: " . $feed->get_title());
        _debug("hook_feed_parsed: get_link: " . $feed->get_link());
    }	// hook_feed_parsed


    public function hook_article_filter($article)
    {
        
        // _debug("hook_article_filter: article: " . var_export($article, true));

        // _debug("hook_article_filter: article: " . $article["feed"]["fetch_url"]);
        // _debug("hook_article_filter: article: " . $article["link"]);

        if (! $this->is_valid_domain($article["feed"]["fetch_url"])) {
            return $article;
        }
        
        // _debug("hook_article_filter: article: " . var_export($article, true));
        // _debug("hook_article_filter: article: " . $article["title"]);
        
        $content = $article["content"];
        // _debug("hook_article_filter: content[" . mb_strlen($content) . "]: '" . htmlspecialchars($content) . "'");

        /*
            If string is long enough lets assume we have meaningful data
            Even empty content is filled with 131 bytes (incl \n ...):
                <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd"><?xml encoding="UTF-8">
            Alternatively check for <html><body>.+</body></html>
            Predelivered content is only abstract and lacks reference to long article which makes it unclear to the reader that there is more.
            So insert link before </body></html> and return updated article.
        */
        // if ( strlen($content) > 135 )
        if (mb_stripos($content, "<html><body>") !== false) {
            $link = "<p><a href=\"" . $article["link"] . "\">Ganzer Artikel...</a></p>";
            
            // _debug("hook_article_filter: insert link: " . htmlspecialchars($link));
            
            $content = preg_replace("/<\/body>/", $link . "</body>", $content);
            
            // _debug("hook_article_filter: final content: " . htmlspecialchars($content));
            
            $article["content"] = $content;
            
            return $article;
        }


        // Fetch data from URL and insert to content
        //_debug("hook_article_filter: fetch new content from " . $article["link"]);
            
        $tmp = fetch_file_contents(["url" => $article["link"]]);

        if (! $tmp) {
            // seems we got an error. Be gracefull and continue with what we already have.
            return $article;
        }

        // _debug("hook_article_filter: new content: " . htmlspecialchars($tmp));
            
        // select and format content to suit displaying in web and mobile app
        
        // $article["content"] = $tmp; return $article;

        $doc = new DOMDocument("1.0", "UTF-8");

        if (!@$doc->loadHTML($tmp)) {
            return false;
        }

        $xpath = new DOMXPath($doc);

        //*// 1st try: extract the data we really want

        $base_node = $xpath->query('//div[@class="story-content"]')->item(0);

        if ($base_node) {
            $content = $article["content"] = $doc->saveHTML($base_node);
        }
        // */

        /* // 2nd try: delete data we do not want

                foreach ($xpath->query('//nav[@id="skiplinks"]') as $e) { $e->parentNode->removeChild($e); }
                foreach ($xpath->query('//header[@class="header"]') as $e) { $e->parentNode->removeChild($e); }
                foreach ($xpath->query('//div[@class="story-lead"]') as $e) { $e->parentNode->removeChild($e); }
                foreach ($xpath->query('//div[@class="story-meta"]') as $e) { $e->parentNode->removeChild($e); }
        //				foreach ($xpath->query('//div[@class="story-footer"]') as $e) { $e->parentNode->removeChild($e); }
                foreach ($xpath->query('//div[@id="more-to-read-anchor"]') as $e) { $e->parentNode->removeChild($e); }

                foreach ($xpath->query('//footer[@id="page-footer"]') as $e) { $e->parentNode->removeChild($e); }
                foreach ($xpath->query('//div[@class="print-warning"]') as $e) { $e->parentNode->removeChild($e); }

                foreach ($xpath->query('//a[@title="Zurück"]') as $e) { $e->parentNode->removeChild($e); }
                foreach ($xpath->query('//a[@title="Weiter"]') as $e) { $e->parentNode->removeChild($e); }


                $article["content"] = $doc->saveHTML();
        // */

        // _debug("hook_article_filter: final content: " . htmlspecialchars($article["content"]));

        return $article;
    }	// hook_article_filter
    

    // helper function: does string $haystack end with string $needle?
    private function ends_with($haystack, $needle)
    {

    // _debug("ends_with: " . $haystack . " v.s. " . $needle);

        return mb_substr($haystack, -mb_strlen($needle)) === $needle;
    }	// ends_with

    // is the domain in question one of the configured domains?
    private function is_valid_domain($fetch_url)
    {
        // extract domain from whole url
        $url = parse_url($fetch_url, PHP_URL_HOST);
        
        // _debug("is_valid_domain: URL: " . $url);
        // _debug("is_valid_domain: domains: " . implode(",", $this->domains));
        
        $found = array_filter(
            $this->domains,
            function ($t) use ($url) {
                // does the domain in question end with a given url?
                $res = $this->ends_with($url, $t);
                // _debug("is_valid_domain: Loop: " . $t . " " . $url . " res: " . $res);
                return $res;
            }
        );

        // _debug("is_valid_domain: found: " . implode(",", $found));

        return !empty($found);
    }	// is_valid_domain
}
