<?php

/**
 * Tumblr RSS feed
 *
 * Due to GDPR, tumblr made breaking changes to their RSS feeds for european customers.
 * This plugin tries to fix this.
 * In addition it changes all RSS feed URLs to https protocol.
 *
 * The plugin rolls it's own curl download as tt-rss is not able to handle result in non HTTP 200 responses.
 *
 * Based on https://github.com/hkockerbeck/ttrss-tumblr-gdpr-ua
 * Therefore the User Agent switching code is still there, albeit not needed any more.
 *
 * Copy to ${tt-rss install directory}/plugins.local/class name in lower case
 *
 */
class Tumblr_GDPR_UA extends Plugin
{
    private $host;

    public function about()
    {
        return array(
          1.0,
          "Fixes Tumblr feeds for GDPR compliance. Can set alternate user agent. Requires curl.",
          "alex");
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
        // store the provided reference to host
        $this->host = $host;
        // hook on some hooks ;)
        if (function_exists("curl_init")) {
            $host->add_hook($host::HOOK_SUBSCRIBE_FEED, $this);
            $host->add_hook($host::HOOK_FEED_BASIC_INFO, $this);
            $host->add_hook($host::HOOK_FETCH_FEED, $this);
            $host->add_hook($host::HOOK_FEED_FETCHED, $this);
            $host->add_hook($host::HOOK_PREFS_TAB, $this);
        }
    }

    // when subscribing to a new feed
    public function hook_subscribe_feed(
        $feed_data,
        $fetch_url,
        $auth_login,
        $auth_pass
    ) {
        // if the feed is hosted by Tumblr
        if ($this->is_tumblr_domain($fetch_url)) {
            // re-fetch the feed data with changed user agent
            // $feed_data = $this->fetch_contents($fetch_url, $auth_login, $auth_pass);
            $feed_data = $this->fetch_contents_ch($fetch_url, $auth_login, $auth_pass);
        }

        return $feed_data;
    }

    // get basic info about a feed (title and site url, mostly)
    public function hook_feed_basic_info(
        $basic_info,
        $fetch_url,
        $owner_uid,
        $feed,
        $auth_login,
        $auth_pass
    ) {
        // if the feed is hosted by Tumblr
        if ($this->is_tumblr_domain($fetch_url)) {
            // re-fetch the feed data with changed user agent
            // $contents = $this->fetch_contents($fetch_url, $auth_login, $auth_pass);
            $contents = $this->fetch_contents_ch($fetch_url, $auth_login, $auth_pass);
            
            // extract info we need from the feed data
            $parser = new FeedParser($contents);
            $parser->init();
            if (!$parser->error()) {
                $basic_info = array(
                    'title' => mb_substr($parser->get_title(), 0, 199),
                    'site_url' => mb_substr(rewrite_relative_url($fetch_url, $parser->get_link()), 0, 245)
                );
            }
        }

        return $basic_info;
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
        global $fetch_last_error;
        global $fetch_last_error_code;
        global $fetch_last_error_content;
        global $fetch_last_content_type;
        global $fetch_last_modified;
        global $fetch_effective_url;
        global $fetch_effective_ip_addr;
        global $fetch_curl_used;
        global $fetch_domain_hits;
        
        // if the feed is hosted by Tumblr
        if ($this->is_tumblr_domain($fetch_url)) {
            // re-fetch the feed data with changed user agent
            // $feed_data = $this->fetch_contents($fetch_url, $auth_login, $auth_pass);
            $feed_data = $this->fetch_contents_ch($fetch_url, $auth_login, $auth_pass);

            // _debug("HOOK_FETCH_FEED: fetched feed_data:\n" . htmlspecialchars($feed_data));

            if ($this->begins_with($feed_data, '<!DOCTYPE html>')) {
                $feed_data = '<?xml version="1.0" encoding="UTF-8"?><rss xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0"></rss>';
                _debug("HOOK_FETCH_FEED: Feed $fetch_url did not return RSS data. Faking it to suppress subsequent error messages.");
            }
    
            // _debug("HOOK_FETCH_FEED: fetch_last_error: $fetch_last_error");
            // _debug("HOOK_FETCH_FEED: fetch_last_error_code: $fetch_last_error_code");
            // _debug("HOOK_FETCH_FEED: fetch_last_error_content[" . strlen($fetch_last_error_content) . "]:\n" . $this->hex_dump($fetch_last_error_content));
            // _debug("HOOK_FETCH_FEED: fetch_last_error_content[" . strlen($fetch_last_error_content) . "]: intentionally suppressed.");
            // _debug("HOOK_FETCH_FEED: fetch_last_content_type: $fetch_last_content_type");
            // _debug("HOOK_FETCH_FEED: fetch_last_modified: $fetch_last_modified");
            // _debug("HOOK_FETCH_FEED: fetch_effective_url: $fetch_effective_url");
            // _debug("HOOK_FETCH_FEED: fetch_effective_ip_addr: $fetch_effective_ip_addr");
            // _debug("HOOK_FETCH_FEED: fetch_curl_used: $fetch_curl_used");
            // _debug("HOOK_FETCH_FEED: fetch_domain_hits: [ " . implode(",", $fetch_domain_hits) . " ]");
            // _debug("hook_fetch_feed: feed_data: " . htmlspecialchars($feed_data));

            // tumblr redirects because of GDPR but should deliver valid RSS in current response.
            // if ($fetch_last_error_code == 302) {
            // _debug("got 302");
            // }
            
            // $feed_data = $this->ungzipSafe($fetch_last_error_content);

            // _debug("HOOK_FETCH_FEED: final feed_data[" . strlen($feed_data) . "]:\n" . $this->hex_dump($feed_data));
        }

        return $feed_data;
    }   // hook_fetch_feed

    // just some debugging
    public function hook_feed_fetched(
        $feed_data,
        $fetch_url,
        $owner_uid,
        $feed
    ) {
        // if the feed is hosted by Tumblr
        if ($this->is_tumblr_domain($fetch_url)) {
            if ($this->begins_with($feed_data, '<!DOCTYPE html>')) {
                $feed_data = '<?xml version="1.0" encoding="UTF-8"?><rss xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0"></rss>';
                _debug("HOOK_FEED_FETCHED: Feed $fetch_url did not return RSS data. Faking it to suppress subsequent error messages.");
            }

            // _debug("HOOK_FEED_FETCHED: FEED_DATA: " . htmlspecialchars($feed_data));
        }
        
        return $feed_data;
    }   // hook_feed_fetched

    // segment in TT-RSS' prefs to add additional domains
    public function hook_prefs_tab($args)
    {
        if ($args != "prefPrefs") {
            return;
        }

        // replacements in the template
        $replacements = array(
            '{title}' => 'Tumblr GDPR UA',
            '{domainlist}' => implode(PHP_EOL, $this->host->get($this, 'tumblr_domains', array())).PHP_EOL,
            '{user_agent}' => $this->host->get($this, 'user_agent'),
        );

        // set up a _very_ basic template engine
        // so we don't have print out everything
        $template = file_get_contents(__DIR__."/pref_template.html");
        $template = str_replace(array_keys($replacements), array_values($replacements), $template);
        print $template;
    }

    // save data from prefs segment
    public function save()
    {
        $tumblr_domains = explode("\r\n", $_POST['tumblr_domains']);
        $tumblr_domains = array_unique(array_filter($tumblr_domains));
        $this->host->set($this, 'tumblr_domains', $tumblr_domains);

        $user_agent = $_POST['user_agent'];
        $this->host->set($this, 'user_agent', $user_agent);
    }

    // fetch feed data with changed user agent
    private function fetch_contents(
        $fetch_url,
        $auth_login = false,
        $auth_pass = false
    ) {
        $fetch_url = str_replace("http://", "https://", $fetch_url);

        $options = array(
          'url' => $fetch_url,
          'login' => $auth_login,
          'pass' => $auth_pass,
          'useragent' => $this->user_agent(),
          'followlocation' => false,
         );
        
        $contents = fetch_file_contents($options);
        $contents = $this->ungzipSafe($contents);

        // _debug("FETCH_CONTENTS: " . var_export($options, true)); _debug("FETCH_CONTENTS: contents:\n" . htmlspecialchars($contents) . "\n#######");
        return $contents;
    }

    // fetch feed data with changed user agent
    // reference implementation taken from https://git.tt-rss.org/fox/tt-rss/src/master/classes/urlhelper.php
    private function fetch_contents_ch(
        $fetch_url,
        $auth_login = false,
        $auth_pass = false
    ) {
        global $fetch_last_error;

        // Save me a migration to https
        $fetch_url = str_replace("http://", "https://", $fetch_url);

        // Can most likely be omitted as tumbler changed its behaviour.
        // Leave it for now for consistency
        $useragent = $this->user_agent();

        $ch = curl_init($fetch_url);

        // curl_setopt($ch, CURLOPT_VERBOSE, true);         // true to output verbose information. Writes output to STDERR, or the file specified using CURLOPT_STDERR.
        // curl_setopt($ch, CURLINFO_HEADER_OUT, true);     // true to track the handle's request string.
        curl_setopt($ch, CURLOPT_HEADER, true);             // true to include the header in the output.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // true to return the transfer as a string of the return value of curl_exec() instead of outputting it directly.


        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        // curl_setopt($ch, CURLOPT_ENCODING, "");
        // curl_setopt($ch, CURLOPT_COOKIEJAR, "/dev/null");

//        return false;

        if ($auth_login && $auth_pass) {
            curl_setopt($ch, CURLOPT_USERPWD, "$login:$pass");
        }

        $ret = @curl_exec($ch);

        // _debug("FETCH_CONTENTS_CH: curl_error: " . curl_error($ch));     // https://curl.se/libcurl/c/libcurl-errors.html
        // _debug("FETCH_CONTENTS_CH: ret[" . strlen($ret) . "]:\n" . $this->str2hex($ret));
        // _debug("FETCH_CONTENTS_CH: ret[" . strlen($ret) . "]:\n" . $this->hex_dump($ret));
        
        // _debug("FETCH_CONTENTS_CH: CURL_GETINFO: " . var_export(curl_getinfo($ch), true));


        $headers_length = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = explode("\r\n", substr($ret, 0, $headers_length));
        $contents = substr($ret, $headers_length);

        // _debug("FETCH_CONTENTS_CH: headers: " . var_export($headers, true));
        // _debug("FETCH_CONTENTS_CH: contents[" . strlen($contents) . "]:\n" . $this->hex_dump($contents));

        if (! $contents) {
            $fetch_last_error = curl_errno($ch) . " " . curl_error($ch);
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        $contents = $this->ungzipSafe($contents);

        // _debug("FETCH_CONTENTS_CH: final contents[" . strlen($contents) . "]:\n" . htmlspecialchars($contents));

        return $contents;
    }   // fetch_contents_ch

    /**
     * Check if string is gz encoded. If so, decode and return
     *
     * @parm   string
     * @return string
     */
    private function ungzipSafe($s)
    {
        $is_gzipped = RSSUtils::is_gzipped($s);

        if ($is_gzipped) {
            // _debug("ungzipSafe: is gzipped: $s");

            $tmp = @gzdecode($s);

            if ($tmp) {
                $s = $tmp;
            }
        } else {
            // _debug("ungzipSafe: is NOT gzipped: $s");
        }

        return $s;
    }

    private function str2hex(
        $str
    ) {
        $res = implode(
            ' ',
            array_map(
                function ($char) {
                    // return sprintf('%02s', $char);
                    return str_pad($char, 2, '0', STR_PAD_LEFT);
                },
                array_map(
                    'dechex',
                    unpack('C*', $str)
                )
            )
        );

        return $res;
    }

    /**
    * Dumps a string into a traditional hex dump for programmers,
    * in a format similar to the output of the BSD command hexdump -C file.
    * The default result is a string.
    * https://stackoverflow.com/a/34279537
    * Supported options:
    * <pre>
    *   line_sep        - line separator char, default = "\n"
    *   bytes_per_line  - default = 64
    *   pad_char        - character to replace non-readable characters with, default = '.'
    * </pre>
    *
    * @param string $string
    * @param array $options
    * @param string|array
    */
    public function hex_dump($string, array $options = null)
    {
        if (!is_scalar($string)) {
            throw new InvalidArgumentException('$string argument must be a string');
        }
        if (!is_array($options)) {
            $options = array();
        }
        $line_sep       = isset($options['line_sep'])   ? $options['line_sep']          : "\n";
        $bytes_per_line = @$options['bytes_per_line']   ? $options['bytes_per_line']    : 64;
        $pad_char       = isset($options['pad_char'])   ? $options['pad_char']          : '.'; # padding for non-readable characters

        $text_lines = str_split($string, $bytes_per_line);
        $hex_lines  = str_split(bin2hex($string), $bytes_per_line * 2);

        $offset = 0;
        $output = array();
        $bytes_per_line_div_2 = (int)($bytes_per_line / 2);
        foreach ($hex_lines as $i => $hex_line) {
            $text_line = $text_lines[$i];
            $output []=
            sprintf('%08X', $offset) . '  ' .
            str_pad(
                strlen($text_line) > $bytes_per_line_div_2
                ?
                    implode(' ', str_split(substr($hex_line, 0, $bytes_per_line), 2)) . '  ' .
                    implode(' ', str_split(substr($hex_line, $bytes_per_line), 2))
                :
                implode(' ', str_split($hex_line, 2)),
                $bytes_per_line * 3
            ) .
            '  |' . preg_replace('/[^\x20-\x7E]/', $pad_char, $text_line) . '|';
            $offset += $bytes_per_line;
        }
        $output []= sprintf('%08X', strlen($string));
        return @$options['want_array'] ? $output : join($line_sep, $output) . $line_sep;
    }

    // helper function: does string $haystack end with string $needle?
    private function ends_with($haystack, $needle)
    {
        return mb_substr($haystack, -mb_strlen($needle)) === $needle;
    }
    
    private function begins_with($haystack, $needle)
    {
        return mb_substr($haystack, 0, mb_strlen($needle)) === $needle;
    }

    // is the domain in question on tumblr.com or one of the additional domains?
    private function is_tumblr_domain($fetch_url)
    {
        // extract domain from whole url
        $url = parse_url($fetch_url, PHP_URL_HOST);
        // look through list of "known tumblr" urls
        $domains = $this->host->get($this, 'tumblr_domains', array());
        array_push($domains, 'tumblr.com');
        $found = array_filter($domains, function ($t) use ($url) {
            // does the domain in question end with a tumblr url?
            return $this->ends_with($url, $t);
        });

        return !empty($found);
    }

    // if the user provided a custom user agent in the settings, use that
    // otherwise, fall back to Googlebot
    private function user_agent()
    {
        // $fallback_ua = 'Mozilla/5.0 (compatible; Googlebot/4.51; +http://www.google.com/bot.html)';
        // $fallback_ua = 'Mozilla/5.0 (compatible; Googlebot/4.51; +http://www.google.com/bot.html)';
        // $fallback_ua = 'facebookexternalhit/1.0 (+http://www.facebook.com/externalhit_uatext.php)';
        $fallback_ua = 'curl/7.72.0';
      
        $ua = $this->host->get($this, 'user_agent');
        return ($ua == '') ? $fallback_ua : $ua;

        // anno: use this useragent only
        return $fallback_ua;
    }
}
