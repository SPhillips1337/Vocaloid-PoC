<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "User: " . get_current_user() . "\n";
echo "UID: " . getmyuid() . "\n";
echo "GID: " . getmygid() . "\n";

$envPath = __DIR__ . '/../.env';
echo "Env Path: $envPath\n";
if (file_exists($envPath)) {
    echo "Env exists.\n";
    if (is_readable($envPath)) {
        echo "Env is readable.\n";
        $env = parse_ini_file($envPath);
        echo "Env parsed: " . (is_array($env) ? 'OK' : 'FAIL') . "\n";
    } else {
        echo "Env NOT readable.\n";
    }
} else {
    echo "Env NOT found.\n";
}

$jobDir = __DIR__ . '/render_jobs';
echo "Job Root: $jobDir\n";
if (is_writable($jobDir)) {
    echo "Job Root is writable.\n";
    $testDir = $jobDir . '/test_' . uniqid();
    if (mkdir($testDir)) {
        echo "mkdir OK.\n";
        rmdir($testDir);
    } else {
        echo "mkdir FAIL.\n";
    }
} else {
    echo "Job Root NOT writable.\n";
}
?>
