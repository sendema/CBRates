<?php
// bin/check-redis.php
require dirname(__DIR__).'/vendor/autoload.php';

$redis = new Redis();
try {
    $redis->connect('redis', 6379);

    // Поиск ключей с курсами валют
    $keys = $redis->keys('cbr_rates*');
    if (empty($keys)) {
        echo "No CBR rates found in Redis\n";
    } else {
        echo "Found " . count($keys) . " CBR rate entries:\n\n";
        foreach ($keys as $key) {
            $value = $redis->get($key);
            $data = json_decode($value, true);
            echo "Key: " . $key . "\n";
            echo "Value: " . print_r($data, true) . "\n";
            echo "TTL: " . $redis->ttl($key) . " seconds\n";
            echo "------------------------\n";
        }
    }

    // Показать все ключи в Redis
    echo "\nAll Redis keys:\n";
    $allKeys = $redis->keys('*');
    print_r($allKeys);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}