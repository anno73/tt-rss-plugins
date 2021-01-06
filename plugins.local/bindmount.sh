#!/bin/bash

files=()
files+=( orf_at/init.php )
files+=( hackaday_com/init.php )
files+=( debug_feed/init.php )
files+=( tumblr_gdpr_ua/init.php )

ttrssBase=/usr/share/tt2rss/plugins.local

echo ${files[@]}

for i in ${files[@]} ; do

	echo "Work for $i"

	s="$ttrssBase/$i"
	t="$i"

	if [ ! -f "$s" ]; then 
		echo "Missing source: $s"
		continue
	fi

	if [ ! -f "$t" ]; then 
		echo "Missing target: $t"
		continue
	fi

	if [ $( stat -c %G "$t" ) == users ]; then 
		mount --bind "$s" "$t"
	fi

done

chown -R anno .

