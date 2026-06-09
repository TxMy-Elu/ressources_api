<?php
$s = opcache_get_status();
echo "OPcache scripts cached: " . $s["opcache_statistics"]["num_cached_scripts"] . PHP_EOL;
echo "OPcache hits: " . $s["opcache_statistics"]["hits"] . PHP_EOL;
echo "OPcache misses: " . $s["opcache_statistics"]["misses"] . PHP_EOL;
echo "OPcache enabled: " . ($s["opcache_enabled"] ? "YES" : "NO") . PHP_EOL;

