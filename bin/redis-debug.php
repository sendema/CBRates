<?php
require dirname(__DIR__).'/vendor/autoload.php';

$redis = new Redis();
try {
    $redis->connect('redis', 6379);
    echo "Successfully connected to Redis\n\n";

    echo "Checking CBR rates data:\n";
    echo "------------------------\n";

    // Поиск ключей с курсами валют
    $keys = $redis->keys('cbr_rates*');
    if (empty($keys)) {
        echo "No CBR rates found in Redis\n";
    } else {
        echo "Found " . count($keys) . " CBR rate entries:\n\n";
        foreach ($keys as $key) {
            $value = $redis->get($key);
            echo "Key: " . $key . "\n";
            echo "Value: " . print_r(json_decode($value, true), true) . "\n";
            echo "TTL: " . $redis->ttl($key) . " seconds\n";
            echo "------------------------\n";
        }
    }

    echo "\nAll Redis keys:\n";
    echo "------------------------\n";
    $allKeys = $redis->keys('*');
    if (empty($allKeys)) {
        echo "Redis database is empty\n";
    } else {
        foreach ($allKeys as $key) {
            echo $key . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}