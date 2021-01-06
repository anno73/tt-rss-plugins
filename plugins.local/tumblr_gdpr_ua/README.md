# ttrss-tumblr-gdpr-ua

Plugin for the RSS Reader [Tiny Tiny RSS](https://tt-rss.org/) to handle RSS feeds from Tumblr in Europe.

See [README-hkockerbeck.md](README-original.md) from [hkockerbeck's effort](https://github.com/hkockerbeck/ttrss-tumblr-gdpr-ua) for initial reason for this plugin.

# What has changed?

As it looks like, Tumbler now delivers RSS feeds again, regardless of blog status and user agent string. 
Unfortunately this is appended to the HTTP 302 response in addition to a HTTP location header, pointing to a consent page.
TT-RSS tries to resolve the redirects and forwards and therefore fails in getting the content.

This version of the plugin does not use fetch_file_content() but runs its own curl fetch and post processing.

The basic structure incl user agent configuration of the initial plugin is left in place, just in case.

# Installation

Copy to tt-rss/plugin.local directory
Enable in Preferences
If desired adapt the user agent string - but this is not necessary any more - at least not for me.

# Thanks

[hkockerbeck](https://github.com/hkockerbeck) for the initial effort.
