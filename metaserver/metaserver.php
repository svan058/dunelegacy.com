<?php
/**
 * Dune Legacy Metaserver
 * 
 * Maintains a directory of active multiplayer game servers.
 * Game clients register their servers and query for available games.
 */

// Configuration
if (!defined('SERVER_TIMEOUT')) {
    define('SERVER_TIMEOUT', 60); // Servers expire after 60 seconds
}
if (!defined('DATA_DIR')) {
    define('DATA_DIR', getenv('DATA_DIR') ?: __DIR__);
}
if (!defined('DATA_FILE')) {
    define('DATA_FILE', DATA_DIR . '/servers.dat');
}
if (!defined('MAX_SERVERS')) {
    define('MAX_SERVERS', 100);
}
if (!defined('STATS_FILE')) {
    define('STATS_FILE', DATA_DIR . '/stats.json');
}

// Latest game version - update this when releasing new versions
if (!defined('LATEST_VERSION')) {
    define('LATEST_VERSION', '0.99.2');
}
if (!defined('DOWNLOAD_URL')) {
    define('DOWNLOAD_URL', 'https://dunelegacy.com/#download');
}

// Ensure data directory exists and is writable
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0755, true);
}
if (!is_writable(DATA_DIR)) {
    error_log("Warning: Data directory " . DATA_DIR . " is not writable");
}

/**
 * Get the real client IP address, accounting for load balancers and proxies
 */
function getRealClientIP() {
    // Check for common proxy headers (in order of preference)
    $headers = [
        'HTTP_X_FORWARDED_FOR',   // Most common (DigitalOcean App Platform uses this)
        'HTTP_X_REAL_IP',         // nginx
        'HTTP_CF_CONNECTING_IP',  // Cloudflare
        'HTTP_X_CLUSTER_CLIENT_IP', // Some load balancers
        'REMOTE_ADDR'             // Fallback to direct connection
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            // X-Forwarded-For can contain multiple IPs (client, proxy1, proxy2...)
            // We want the first one (the original client)
            if ($header === 'HTTP_X_FORWARDED_FOR') {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
            } else {
                $ip = $_SERVER[$header];
            }
            
            // Validate IP address
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            // If it's a private IP, keep checking other headers (might be behind multiple proxies)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                // Save as fallback in case no public IP is found
                $fallbackIP = $ip;
            }
        }
    }
    
    // If no public IP found, use the fallback (might be for local development)
    return $fallbackIP ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Only execute main routing if called directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'metaserver.php') {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Get action from query string (support both 'action' and 'command' for backwards compatibility)
    $action = $_GET['action'] ?? $_GET['command'] ?? '';
    
    // Main routing
    switch($action) {
        case 'add':
            handleAdd();
            break;
        case 'update':
            handleUpdate();
            break;
        case 'remove':
            handleRemove();
            break;
        case 'list':
            handleList();
            break;
        case 'version':
            handleVersion();
            break;
        default:
            echo "ERROR: Invalid action\n";
            echo "Valid actions: add, update, remove, list, version\n";
            echo "Use: ?action=list or ?command=list\n";
            http_response_code(400);
    }
}

/**
 * Add a new game server
 */
function handleAdd() {
    // Get real client IP (handle load balancers/proxies)
    $ip = getRealClientIP();
    $port = intval($_GET['port'] ?? 0);
    $secret = sanitize($_GET['secret'] ?? '');
    $name = sanitize($_GET['gamename'] ?? $_GET['name'] ?? 'Unnamed Server');
    $map = sanitize($_GET['mapname'] ?? $_GET['map'] ?? 'Unknown');
    $numPlayers = intval($_GET['numplayers'] ?? 0);
    $maxPlayers = intval($_GET['maxplayers'] ?? 8);
    $version = sanitize($_GET['gameversion'] ?? $_GET['version'] ?? '0.0.0');
    $localIP = sanitize($_GET['localip'] ?? ''); // Optional local/LAN IP from client
    $modName = sanitize($_GET['modname'] ?? 'vanilla');
    $modVersion = sanitize($_GET['modversion'] ?? '');
    
    if ($port < 1 || $port > 65535) {
        echo "ERROR: Invalid port\n";
        return;
    }
    
    if (empty($secret)) {
        echo "ERROR: Secret required\n";
        return;
    }
    
    $servers = loadServers();
    
    // Use SECRET as the unique identifier, not IP+port
    // This correctly identifies the same game even if announced from different IPs
    $serverId = $secret;
    $isNewGame = !isset($servers[$serverId]);
    
    if (count($servers) >= MAX_SERVERS && $isNewGame) {
        echo "ERROR: Server list full\n";
        return;
    }
    
    $servers[$serverId] = [
        'ip' => $ip,
        'port' => $port,
        'secret' => $secret,
        'name' => $name,
        'localIP' => $localIP, // Store local IP if provided
        'map' => $map,
        'numPlayers' => $numPlayers,
        'maxPlayers' => $maxPlayers,
        'version' => $version,
        'modName' => $modName,
        'modVersion' => $modVersion,
        'lastUpdate' => time()
    ];
    
    saveServers($servers);
    
    // Only record in statistics if it's a NEW game
    if ($isNewGame) {
        recordGameStart($name, $map, $maxPlayers, $version, $modName, $modVersion);
    }
    
    echo "OK\n";
}

/**
 * Update existing server status
 */
function handleUpdate() {
    $secret = sanitize($_GET['secret'] ?? '');
    
    if (empty($secret)) {
        echo "ERROR: Secret required\n";
        return;
    }
    
    $servers = loadServers();
    
    if (!isset($servers[$secret])) {
        echo "ERROR: Server not found\n";
        return;
    }
    
    // Update fields if provided
    if (isset($_GET['numplayers'])) {
        $servers[$secret]['numPlayers'] = intval($_GET['numplayers']);
    }
    if (isset($_GET['mapname']) || isset($_GET['map'])) {
        $servers[$secret]['map'] = sanitize($_GET['mapname'] ?? $_GET['map']);
    }
    
    $servers[$secret]['lastUpdate'] = time();
    
    saveServers($servers);
    echo "OK\n";
}

/**
 * Remove a server
 */
function handleRemove() {
    $secret = sanitize($_GET['secret'] ?? '');
    
    if (empty($secret)) {
        echo "ERROR: Secret required\n";
        return;
    }
    
    $servers = loadServers();
    
    if (isset($servers[$secret])) {
        unset($servers[$secret]);
        saveServers($servers);
    }
    
    echo "OK\n";
}

/**
 * List all active servers
 */
function handleList() {
    $servers = loadServers();
    $activeServers = [];
    $now = time();
    $changed = false;
    
    // Filter expired servers
    foreach ($servers as $id => $server) {
        if (($now - $server['lastUpdate']) <= SERVER_TIMEOUT) {
            $activeServers[$id] = $server;
        } else {
            $changed = true;
        }
    }
    
    // Save if we removed expired servers
    if ($changed) {
        saveServers($activeServers);
    }
    
    // Output server list in game-expected format:
    // OK\n
    // <ip>\t<port>\t<name>\t<version>\t<map>\t<numplayers>\t<maxplayers>\t<pwdprotected>\t<lastupdate>\t<localip>\t<modname>\t<modversion>\n
    echo "OK\n";
    foreach ($activeServers as $server) {
        echo sprintf(
            "%s\t%d\t%s\t%s\t%s\t%d\t%d\t%s\t%d\t%s\t%s\t%s\n",
            $server['ip'],
            $server['port'],
            $server['name'],
            $server['version'],
            $server['map'],
            $server['numPlayers'],
            $server['maxPlayers'],
            'false', // password protected (not implemented yet)
            $server['lastUpdate'],
            $server['localIP'] ?? '', // Local/LAN IP if provided
            $server['modName'] ?? 'vanilla',
            $server['modVersion'] ?? ''
        );
    }
}

/**
 * Return latest version information for update checking
 * Response format:
 * OK\n
 * <latest_version>\t<download_url>\n
 */
function handleVersion() {
    $clientVersion = sanitize($_GET['gameversion'] ?? $_GET['version'] ?? '');
    
    echo "OK\n";
    echo LATEST_VERSION . "\t" . DOWNLOAD_URL . "\n";
    
    // Optionally log version checks for analytics
    if (!empty($clientVersion)) {
        $ip = getRealClientIP();
        error_log("Version check from $ip running $clientVersion (latest: " . LATEST_VERSION . ")");
    }
}

/**
 * Load servers from file
 */
function loadServers() {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    
    $data = @file_get_contents(DATA_FILE);
    if ($data === false) {
        return [];
    }
    
    $servers = @unserialize($data);
    return is_array($servers) ? $servers : [];
}

/**
 * Save servers to file
 */
function saveServers($servers) {
    $data = serialize($servers);
    @file_put_contents(DATA_FILE, $data, LOCK_EX);
}

/**
 * Sanitize user input
 */
function sanitize($str) {
    $str = strip_tags($str);
    $str = substr($str, 0, 255);
    return $str;
}

/**
 * Record a game start in statistics
 */
function recordGameStart($name, $map, $maxPlayers, $version, $modName = 'vanilla', $modVersion = '') {
    $stats = loadStats();
    
    // Initialize stats structure if needed
    if (!isset($stats['total_games'])) {
        $stats['total_games'] = 0;
        $stats['recent_games'] = [];
        $stats['popular_maps'] = [];
        $stats['popular_mods'] = [];
    }
    
    // Increment total games
    $stats['total_games']++;
    
    // Add to recent games (last 30 days)
    $stats['recent_games'][] = [
        'name' => $name,
        'map' => $map,
        'maxPlayers' => $maxPlayers,
        'version' => $version,
        'modName' => $modName,
        'modVersion' => $modVersion,
        'timestamp' => time()
    ];
    
    // Clean old games (older than 30 days)
    $monthAgo = time() - (30 * 24 * 60 * 60);
    $stats['recent_games'] = array_filter($stats['recent_games'], function($game) use ($monthAgo) {
        return $game['timestamp'] > $monthAgo;
    });
    
    // Update popular maps count
    if (!isset($stats['popular_maps'][$map])) {
        $stats['popular_maps'][$map] = 0;
    }
    $stats['popular_maps'][$map]++;
    
    // Update popular mods count
    if (!isset($stats['popular_mods'])) {
        $stats['popular_mods'] = [];
    }
    $modKey = $modName . ($modVersion ? ' v' . $modVersion : '');
    if (!isset($stats['popular_mods'][$modKey])) {
        $stats['popular_mods'][$modKey] = 0;
    }
    $stats['popular_mods'][$modKey]++;
    
    saveStats($stats);
    
    // Send Discord notification for new game
    sendDiscordNotification($name, $map, $maxPlayers, $version, $modName, $modVersion);
}

/**
 * Get statistics
 */
function getStats() {
    $stats = loadStats();
    
    // Clean old recent games
    if (isset($stats['recent_games'])) {
        $monthAgo = time() - (30 * 24 * 60 * 60);
        $stats['recent_games'] = array_filter($stats['recent_games'], function($game) use ($monthAgo) {
            return $game['timestamp'] > $monthAgo;
        });
    }
    
    // Sort popular maps by count
    if (isset($stats['popular_maps'])) {
        arsort($stats['popular_maps']);
    }
    
    // Sort popular mods by count
    if (isset($stats['popular_mods'])) {
        arsort($stats['popular_mods']);
    }
    
    return $stats;
}

/**
 * Load statistics from file
 */
function loadStats() {
    $defaultStats = [
        'total_games' => 0,
        'recent_games' => [],
        'popular_maps' => [],
        'popular_mods' => []
    ];
    
    if (!file_exists(STATS_FILE)) {
        return $defaultStats;
    }
    
    $data = @file_get_contents(STATS_FILE);
    if ($data === false) {
        return $defaultStats;
    }
    
    $stats = @json_decode($data, true);
    if (!is_array($stats)) {
        return $defaultStats;
    }
    
    // Ensure popular_mods exists for backward compatibility
    if (!isset($stats['popular_mods'])) {
        $stats['popular_mods'] = [];
    }
    
    return $stats;
}

/**
 * Save statistics to file
 */
function saveStats($stats) {
    $data = json_encode($stats, JSON_PRETTY_PRINT);
    @file_put_contents(STATS_FILE, $data, LOCK_EX);
}

/**
 * Send Discord webhook notification for new game
 */
function sendDiscordNotification($name, $map, $maxPlayers, $version, $modName, $modVersion) {
    $webhookUrl = getenv('DISCORD_WEBHOOK_URL');
    if (empty($webhookUrl)) {
        return;
    }
    
    $modDisplay = $modName;
    if ($modVersion) {
        $modDisplay .= ' v' . $modVersion;
    }
    
    $payload = json_encode([
        'embeds' => [[
            'title' => '🎮 New Game Hosted',
            'color' => 0xE67E22, // Dune orange
            'fields' => [
                ['name' => 'Server', 'value' => $name ?: 'Unnamed', 'inline' => true],
                ['name' => 'Map', 'value' => $map ?: 'Unknown', 'inline' => true],
                ['name' => 'Players', 'value' => "0/$maxPlayers", 'inline' => true],
                ['name' => 'Version', 'value' => $version ?: '?', 'inline' => true],
                ['name' => 'Mod', 'value' => $modDisplay, 'inline' => true],
            ],
            'timestamp' => date('c')
        ]]
    ]);
    
    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    curl_exec($ch);
    curl_close($ch);
}

