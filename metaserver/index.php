<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dune Legacy Metaserver Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #1a1a1a;
            color: #f0f0f0;
        }
        h1 {
            color: #d4af37;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 10px;
        }
        .status {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .status.ok { border-left: 5px solid #4CAF50; }
        .status.warning { border-left: 5px solid #ff9800; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #2a2a2a;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background: #333;
            color: #d4af37;
        }
        tr:hover {
            background: #333;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #888;
            font-style: italic;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 0.9em;
        }
        a {
            color: #d4af37;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1><a href="https://dunelegacy.com/">🎮 Dune Legacy</a> Metaserver</h1>
    
    <?php
    require_once 'metaserver.php';
    
    $servers = loadServers();
    $activeServers = [];
    $now = time();
    
    foreach ($servers as $id => $server) {
        if (($now - $server['lastUpdate']) <= SERVER_TIMEOUT) {
            $activeServers[$id] = $server;
        }
    }
    
    $count = count($activeServers);
    ?>
    
    <?php
    $stats = getStats();
    $recentGamesCount = count($stats['recent_games'] ?? []);
    ?>
    
    <div class="status <?php echo $count > 0 ? 'ok' : 'warning'; ?>">
        <strong>Status:</strong> Online<br>
        <strong>Active Servers:</strong> <?php echo $count; ?><br>
        <strong>Total Games (All Time):</strong> <?php echo number_format($stats['total_games'] ?? 0); ?><br>
        <strong>Games (Last 30 Days):</strong> <?php echo number_format($recentGamesCount); ?><br>
        <strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?> UTC
    </div>
    
    <h2>Active Game Servers</h2>
    
    <?php if ($count > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Server Name</th>
                    <th>Map</th>
                    <th>Players</th>
                    <th>Mod</th>
                    <th>Version</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeServers as $server): 
                    // Backward compatibility: default to vanilla for old entries
                    $modName = $server['modName'] ?? 'vanilla';
                    $modVersion = $server['modVersion'] ?? '';
                    $modDisplay = $modName;
                    if (!empty($modVersion)) {
                        $modDisplay .= ' v' . $modVersion;
                    }
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($server['name']); ?></td>
                        <td><?php echo htmlspecialchars($server['map']); ?></td>
                        <td><?php echo $server['numPlayers'] . ' / ' . $server['maxPlayers']; ?></td>
                        <td><?php echo htmlspecialchars($modDisplay); ?></td>
                        <td><?php echo htmlspecialchars($server['version']); ?></td>
                        <td><span style="color: #4CAF50;">● Available</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">
            No active game servers at the moment.<br>
            Start a multiplayer game to host a server!
        </div>
    <?php endif; ?>
    
    <h2>📊 Statistics</h2>
    
    <h3>Recent Games (Last 30 Days)</h3>
    <?php if ($recentGamesCount > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Game Name</th>
                    <th>Map</th>
                    <th>Players</th>
                    <th>Mod</th>
                    <th>Version</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Show last 20 games
                $recentGames = array_slice(array_reverse($stats['recent_games']), 0, 20);
                foreach ($recentGames as $game): 
                    $timeAgo = time() - $game['timestamp'];
                    if ($timeAgo < 3600) {
                        $timeStr = floor($timeAgo / 60) . ' min ago';
                    } elseif ($timeAgo < 86400) {
                        $timeStr = floor($timeAgo / 3600) . ' hours ago';
                    } else {
                        $timeStr = floor($timeAgo / 86400) . ' days ago';
                    }
                    // Backward compatibility: default to vanilla for old entries
                    $modName = $game['modName'] ?? 'vanilla';
                    $modVersion = $game['modVersion'] ?? '';
                    $modDisplay = $modName;
                    if (!empty($modVersion)) {
                        $modDisplay .= ' v' . $modVersion;
                    }
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($game['name']); ?></td>
                        <td><?php echo htmlspecialchars($game['map']); ?></td>
                        <td><?php echo $game['maxPlayers']; ?> max</td>
                        <td><?php echo htmlspecialchars($modDisplay); ?></td>
                        <td><?php echo htmlspecialchars($game['version']); ?></td>
                        <td><?php echo $timeStr; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">
            No games recorded in the last 30 days.
        </div>
    <?php endif; ?>
    
    <h3>Popular Maps</h3>
    <?php if (!empty($stats['popular_maps'])): ?>
        <table>
            <thead>
                <tr>
                    <th>Map Name</th>
                    <th>Times Played</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $topMaps = array_slice($stats['popular_maps'], 0, 10, true);
                foreach ($topMaps as $map => $count): 
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($map); ?></td>
                        <td><?php echo number_format($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">
            No map statistics available yet.
        </div>
    <?php endif; ?>
    
    <h3>Popular Mods</h3>
    <?php if (!empty($stats['popular_mods'])): ?>
        <table>
            <thead>
                <tr>
                    <th>Mod</th>
                    <th>Times Played</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $topMods = array_slice($stats['popular_mods'], 0, 10, true);
                foreach ($topMods as $mod => $count): 
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mod); ?></td>
                        <td><?php echo number_format($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">
            No mod statistics available yet.<br>
            <small>Games without mod info are counted as "vanilla"</small>
        </div>
    <?php endif; ?>
    
    <div class="footer">
        <p>
            <a href="/">← Back to Dune Legacy</a> |
            <a href="https://github.com/dunelegacy/dunelegacy">GitHub</a>
        </p>
        <p>Metaserver API: <code>/metaserver/metaserver.php</code></p>
    </div>
    
    <script>
        // Auto-refresh every 10 seconds
        setTimeout(() => location.reload(), 10000);
    </script>
</body>
</html>

