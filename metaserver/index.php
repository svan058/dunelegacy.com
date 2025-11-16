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
    <h1>🎮 Dune Legacy Metaserver</h1>
    
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
    
    <div class="status <?php echo $count > 0 ? 'ok' : 'warning'; ?>">
        <strong>Status:</strong> Online<br>
        <strong>Active Servers:</strong> <?php echo $count; ?><br>
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
                    <th>Version</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeServers as $server): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($server['name']); ?></td>
                        <td><?php echo htmlspecialchars($server['map']); ?></td>
                        <td><?php echo $server['numPlayers'] . ' / ' . $server['maxPlayers']; ?></td>
                        <td><?php echo htmlspecialchars($server['version']); ?></td>
                        <td><code><?php echo $server['ip'] . ':' . $server['port']; ?></code></td>
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

