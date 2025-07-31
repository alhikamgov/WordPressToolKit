<?php
if ($argc < 2) {
    echo "\nUsage: php webgrab.php sites.txt\n";
    exit;
}

$inputFile = $argv[1];
$outputFile = __DIR__ . '/hasil.txt';

if (!file_exists($inputFile)) {
    echo "File not found: $inputFile\n";
    exit;
}

$urls = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$urls) {
    echo "No URLs found in file.\n";
    exit;
}

foreach ($urls as $site) {
    $site = trim(rtrim($site, '/'));

    echo "\n";
    echo " Site : $site\n";

    $usernames = get_users($site, 3);
    $wpversion = get_version($site);
    $wptheme   = get_theme($site);
    $plugins   = get_plugins($site);

    // Tampilkan ke terminal
    if (!empty($usernames)) {
        foreach ($usernames as $i => $u) {
            echo " Username " . ($i + 1) . " : $u\n";
        }
    } else {
        echo " No Usernames Found\n";
    }

    echo $wpversion ? " WP Version : $wpversion\n" : " Can't Get Version\n";
    echo $wptheme   ? " WP Theme   : $wptheme\n"   : " Can't Get Theme\n";

    if (!empty($plugins)) {
        foreach ($plugins as $plugin) {
            echo " WP Plugin  : $plugin\n";
        }
    } else {
        echo " No Plugins Found\n";
    }

    // Simpan ke hasil.txt
    $line = parse_url($site, PHP_URL_HOST) . '|';
    $line .= !empty($usernames) ? implode(',', $usernames) : '-';
    $line .= '|';
    $line .= $wpversion ?: '-';
    $line .= '|';
    $line .= $wptheme ?: '-';
    $line .= '|';
    $line .= !empty($plugins) ? implode(',', $plugins) : '-';
    $line .= "\n\n";

    file_put_contents($outputFile, $line, FILE_APPEND);
}

// ======== FUNCTION DEFINITIONS ========
function fetch($url) {
    $context = stream_context_create([
        "http" => [
            "user_agent" => "Mozilla/5.0 (compatible)",
            "timeout" => 10
        ]
    ]);
    return @file_get_contents($url, false, $context);
}

function get_users($site, $max = 3) {
    $found = [];
    $i = 1;

    while (count($found) < $max) {
        $url = $site . "/?author=$i";
        $html = fetch($url);

        if (preg_match('/author\/([^\/]+)\//i', $html, $match)) {
            $username = $match[1];
            if (!in_array($username, $found)) {
                $found[] = $username;
            }
        } else {
            break;
        }

        $i++;
        if ($i > 3) break;
    }

    return $found;
}

function get_version($site) {
    $html = fetch($site);
    if (preg_match('/content="WordPress ([^"]+)"/i', $html, $match)) {
        return $match[1];
    }
    return null;
}

function get_theme($site) {
    $html = fetch($site);
    if (preg_match('/\/themes\/([^\/]+)\//', $html, $match)) {
        return $match[1];
    }
    return null;
}

function get_plugins($site) {
    $html = fetch($site);
    preg_match_all('/\/wp-content\/plugins\/(.*?)\//', $html, $matches);
    return array_unique($matches[1]);
}