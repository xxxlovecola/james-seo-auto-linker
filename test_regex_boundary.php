<?php
$keyword = 'Pinterest';
$text = 'How to do business on Pinterest? My best tips…';
$regex_template = '/(?<![\p{L}\p{N}])(' . preg_quote($keyword, '/') . ')(?![\p{L}\p{N}])/msui';

echo preg_replace($regex_template, '[$1]', $text) . "\n";
