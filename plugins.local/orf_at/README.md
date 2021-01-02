# orf_at

This plugin tries to extract the meaningful information of articles from www.orf.at RSS feed.

Many if not most articles have no article content given. For these the content of article link will be fetched, formatted and added instead.

Some articles do have content. But they do not have a link to the full article page. In this case the article link is injected to the existing content at the bottom.

# Installation

Place whole directory orf_at to plugins.local subdirectory on your tt-rss instance and enable plugin in tt-rss/Preferences/Plugins page.
