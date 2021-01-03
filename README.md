# tt-rss-plugins
Some plugins for tt-rss.

Install to plugins.local folder of your tt-rss instance.

# Plugin Development Ressources
[Unofficial documentation for hooks in TT-RSS ](https://gist.github.com/Fmstrat/a5adc35633725d9369b50d8524b450ca)

[tt-rss-samples](https://git.tt-rss.org/fox/tt-rss-samples) by fox himself.

Not so comprehensive [Making Plugins](https://tt-rss.org/wiki/MakingPlugins) introduction. Mainly telling that there is none.

A [list of plugins](https://tt-rss.org/wiki/Plugins) provided by various sources.

Use the Feed Debug feature of tt-rss. (right click on feed->debug)

Check the event log.

Trigger updates manually by 
`sudo -u www-data php update.php --force-rehash --debug-feed feed_number_from_feed_debug`

Bindmount init.php from tt-rss installation to your home directory for direct access.
`mount --bind source_file target_file
chown user target_file
`

# Link List
https://tt-rss.org/myfeedsucks/
