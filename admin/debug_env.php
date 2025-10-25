<?php
header('Content-Type: text/plain; charset=utf-8');
echo "MONGODB_URI=" . (
    getenv('MONGODB_URI') ?: ($_ENV['MONGODB_URI'] ?? $_SERVER['MONGODB_URI'] ?? '')
) . PHP_EOL;
echo "MONGO_URI=" . (
    getenv('MONGO_URI') ?: ($_ENV['MONGO_URI'] ?? $_SERVER['MONGO_URI'] ?? '')
) . PHP_EOL;