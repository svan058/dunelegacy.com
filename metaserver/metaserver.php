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
    
    // Get action from query string
    $action = $_GET['action'] ?? '';
    
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
            http_response_code(400);
    }
}

/**
 * Add a new game server
 */
function handleAdd() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $port = intval($_GET['port'] ?? 0);
    $name = sanitize($_GET['name'] ?? 'Unnamed Server');
    $map = sanitize($_GET['map'] ?? 'Unknown');
    $numPlayers = intval($_GET['numplayers'] ?? 0);
    $maxPlayers = intval($_GET['maxplayers'] ?? 8);
    $version = sanitize($_GET['version'] ?? '0.0.0');
    
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
    if (isset($_GET['map'])) {
        $servers[$serverId]['map'] = sanitize($_GET['map']);
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
    
    // Output server list
    echo count($activeServers) . "\n";
    foreach ($activeServers as $server) {
        echo sprintf(
            "%s|%d|%s|%s|%d|%d|%s\n",
            $server['ip'],
            $server['port'],
            $server['name'],
            $server['map'],
            $server['numPlayers'],
            $server['maxPlayers'],
            $server['version']
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

