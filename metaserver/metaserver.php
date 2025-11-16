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

// Ensure data directory exists and is writable
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0755, true);
}
if (!is_writable(DATA_DIR)) {
    error_log("Warning: Data directory " . DATA_DIR . " is not writable");
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
        default:
            echo "ERROR: Invalid action\n";
            echo "Valid actions: add, update, remove, list\n";
            echo "Use: ?action=list or ?command=list\n";
            http_response_code(400);
    }
}

/**
 * Add a new game server
 */
function handleAdd() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $port = intval($_GET['port'] ?? 0);
    $name = sanitize($_GET['gamename'] ?? $_GET['name'] ?? 'Unnamed Server');
    $map = sanitize($_GET['mapname'] ?? $_GET['map'] ?? 'Unknown');
    $numPlayers = intval($_GET['numplayers'] ?? 0);
    $maxPlayers = intval($_GET['maxplayers'] ?? 8);
    $version = sanitize($_GET['gameversion'] ?? $_GET['version'] ?? '0.0.0');
    
    if ($port < 1 || $port > 65535) {
        echo "ERROR: Invalid port\n";
        return;
    }
    
    $servers = loadServers();
    $serverId = $ip . ':' . $port;
    
    if (count($servers) >= MAX_SERVERS && !isset($servers[$serverId])) {
        echo "ERROR: Server list full\n";
        return;
    }
    
    $servers[$serverId] = [
        'ip' => $ip,
        'port' => $port,
        'name' => $name,
        'map' => $map,
        'numPlayers' => $numPlayers,
        'maxPlayers' => $maxPlayers,
        'version' => $version,
        'lastUpdate' => time()
    ];
    
    saveServers($servers);
    
    // Record game start in statistics
    recordGameStart($name, $map, $maxPlayers, $version);
    
    echo "OK\n";
}

/**
 * Update existing server status
 */
function handleUpdate() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $port = intval($_GET['port'] ?? 0);
    $serverId = $ip . ':' . $port;
    
    $servers = loadServers();
    
    if (!isset($servers[$serverId])) {
        echo "ERROR: Server not found\n";
        return;
    }
    
    // Update fields if provided
    if (isset($_GET['numplayers'])) {
        $servers[$serverId]['numPlayers'] = intval($_GET['numplayers']);
    }
    if (isset($_GET['mapname']) || isset($_GET['map'])) {
        $servers[$serverId]['map'] = sanitize($_GET['mapname'] ?? $_GET['map']);
    }
    
    $servers[$serverId]['lastUpdate'] = time();
    
    saveServers($servers);
    echo "OK\n";
}

/**
 * Remove a server
 */
function handleRemove() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $port = intval($_GET['port'] ?? 0);
    $serverId = $ip . ':' . $port;
    
    $servers = loadServers();
    
    if (isset($servers[$serverId])) {
        unset($servers[$serverId]);
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
    // <ip>\t<port>\t<name>\t<version>\t<map>\t<numplayers>\t<maxplayers>\t<pwdprotected>\t<lastupdate>\n
    echo "OK\n";
    foreach ($activeServers as $server) {
        echo sprintf(
            "%s\t%d\t%s\t%s\t%s\t%d\t%d\t%s\t%d\n",
            $server['ip'],
            $server['port'],
            $server['name'],
            $server['version'],
            $server['map'],
            $server['numPlayers'],
            $server['maxPlayers'],
            'false', // password protected (not implemented yet)
            $server['lastUpdate']
        );
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
function recordGameStart($name, $map, $maxPlayers, $version) {
    $stats = loadStats();
    
    // Initialize stats structure if needed
    if (!isset($stats['total_games'])) {
        $stats['total_games'] = 0;
        $stats['recent_games'] = [];
        $stats['popular_maps'] = [];
    }
    
    // Increment total games
    $stats['total_games']++;
    
    // Add to recent games (last 30 days)
    $stats['recent_games'][] = [
        'name' => $name,
        'map' => $map,
        'maxPlayers' => $maxPlayers,
        'version' => $version,
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
    
    saveStats($stats);
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
    
    return $stats;
}

/**
 * Load statistics from file
 */
function loadStats() {
    if (!file_exists(STATS_FILE)) {
        return [
            'total_games' => 0,
            'recent_games' => [],
            'popular_maps' => []
        ];
    }
    
    $data = @file_get_contents(STATS_FILE);
    if ($data === false) {
        return [
            'total_games' => 0,
            'recent_games' => [],
            'popular_maps' => []
        ];
    }
    
    $stats = @json_decode($data, true);
    return is_array($stats) ? $stats : [
        'total_games' => 0,
        'recent_games' => [],
        'popular_maps' => []
    ];
}

/**
 * Save statistics to file
 */
function saveStats($stats) {
    $data = json_encode($stats, JSON_PRETTY_PRINT);
    @file_put_contents(STATS_FILE, $data, LOCK_EX);
}

