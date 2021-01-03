<?php

class hackaday_com extends Plugin
{

	// define domains here for running on articles on
	private $domains = [ "hackaday.com" ];
	
	private $host;

    public function about()
    {
        return array(
			1.0,
			"Try to enhance hackaday.com content with missing entry images. Requires curl.",
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
//		_debug("Initialize hackaday_com plugin");
		
		// store the provided reference to host
		$this->host = $host;
		
        // hook on some hooks ;)
//		if (function_exists("curl_init")) 
//		{
//			$host->add_hook($host::HOOK_FETCH_FEED, $this);
//			$host->add_hook($host::HOOK_FEED_FETCHED, $this);
//			$host->add_hook($host::HOOK_FEED_PARSED, $this);
			$host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
//       }
    }	// init

	function hook_article_filter($article) {
		
//		_debug("hook_article_filter: article: " . var_export($article, true));

//		_debug("hook_article_filter: article: " . $article["feed"]["fetch_url"]);
//		_debug("hook_article_filter: article: " . $article["link"]);

		if (! $this->is_valid_domain($article["feed"]["fetch_url"]))
			return $article;

//		_debug("hook_article_filter: article: " . var_export($article, true));
		$content = $article["content"];
//		_debug("hook_article_filter: content[" . mb_strlen($content) . "]: '" . htmlspecialchars($content) . "'");


		// Get full article page for image link and insert it to article content.
		
		$tmp = fetch_file_contents(["url" => $article["link"]]);
		
		if (! $tmp)
		{
			return $article;
		}
		
		// Image link is encoded like this:
		// <div class="entry-featured-image"><img itemprop="image" content="https://hackaday.com/wp-content/uploads/2020/12/flipdot-clock-thumbnail.jpg?w=600&amp;h=600" 
		//		src="https://hackaday.com/wp-content/uploads/2020/12/flipdot-clock-featured.jpg?w=800" alt=""></div>
		
		if ( preg_match('/<div class="entry-featured-image">.+?src="(?<imagelink>.+?)".+?div>/', $tmp, $matches) !== 1)
		{
			return $article;
		}
		
//		_debug("hook_article_filter: matches: " . var_export($matches, true));
//		_debug("hook_article_filter: matches: " . htmlspecialchars(implode("|||", $matches)));

		$link = $matches["imagelink"];
		
//		_debug("hook_article_filter: imagelink: " . htmlspecialchars($link));

		$link = '<img src="' . $link. '">';

//		_debug("hook_article_filter: imagelink to insert: " . htmlspecialchars($link));
		
		// <html><body>
		
		$content = preg_replace('/<html><body>/', '<html><body><p>' . $link . '</p>', $content);
		
//		_debug("hook_article_filter: content[" . mb_strlen($content) . "]: '" . htmlspecialchars($content) . "'");
		
		$article["content"] = $content; 
		
		return $article;
	}	// hook_article_filter
	
	
	    // helper function: does string $haystack end with string $needle?
    private function ends_with($haystack, $needle)
    {

//		_debug("ends_with: " . $haystack . " v.s. " . $needle);

        return mb_substr($haystack, -mb_strlen($needle)) === $needle;
    }	// ends_with

    // is the domain in question one of the configured domains?
    private function is_valid_domain($fetch_url)
    {
        // extract domain from whole url
        $url = parse_url($fetch_url, PHP_URL_HOST);
		
//		_debug("is_valid_domain: URL: " . $url);
//		_debug("is_valid_domain: domains: " . implode(",", $this->domains));
		
        $found = array_filter($this->domains, 
			function ($t) use ($url) {
				// does the domain in question end with a given url?
				$res = $this->ends_with($url, $t);
//				_debug("is_valid_domain: Loop: " . $t . " " . $url . " res: " . $res);
				return $res;
			}
		);

//		_debug("is_valid_domain: found: " . implode(",", $found));

        return !empty($found);
    }	// is_valid_domain
	
}
?>