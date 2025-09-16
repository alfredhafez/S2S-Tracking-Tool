<?php
/**
 * S2S Postback Testing Tool Installer
 */

// Load configuration if available
$config_loaded = false;
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
    $config_loaded = true;
}

$install_status = [];
$install_complete = false;

// Handle installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Validate database connection parameters
        $db_host = $_POST['db_host'] ?? ($config_loaded ? DB_HOST : 'localhost');
        $db_name = $_POST['db_name'] ?? ($config_loaded ? DB_NAME : 's2s_tracking');
        $db_user = $_POST['db_user'] ?? ($config_loaded ? DB_USER : 'root');
        $db_pass = $_POST['db_pass'] ?? ($config_loaded ? DB_PASS : '');
        
        // Test database connection
        $dsn = "mysql:host=$db_host;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $install_status[] = ['success', 'Database connection successful'];
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $install_status[] = ['success', "Database '$db_name' created/verified"];
        
        // Connect to the specific database
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Read and execute schema
        $schema_sql = file_get_contents(__DIR__ . '/schema.sql');
        $statements = explode(';', $schema_sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                $pdo->exec($statement);
            }
        }
        
        $install_status[] = ['success', 'Database schema created successfully'];
        
        // Check if data already exists
        $settings_count = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
        $offers_count = $pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
        
        if ($settings_count == 0 || $offers_count == 0) {
            // Execute seed data
            $seed_sql = file_get_contents(__DIR__ . '/seed.sql');
            $statements = explode(';', $seed_sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !str_starts_with($statement, '--')) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore duplicate entry errors for idempotent installs
                        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            $install_status[] = ['success', 'Seed data inserted successfully'];
        } else {
            $install_status[] = ['info', 'Seed data already exists, skipping insertion'];
        }
        
        // Create config.php if it doesn't exist
        if (!$config_loaded) {
            $config_content = file_get_contents(__DIR__ . '/../config.example.php');
            $config_content = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '$db_host');", $config_content);
            $config_content = str_replace("define('DB_NAME', 's2s_tracking');", "define('DB_NAME', '$db_name');", $config_content);
            $config_content = str_replace("define('DB_USER', 'root');", "define('DB_USER', '$db_user');", $config_content);
            $config_content = str_replace("define('DB_PASS', '');", "define('DB_PASS', '$db_pass');", $config_content);
            
            if (isset($_POST['base_url']) && !empty($_POST['base_url'])) {
                $base_url = rtrim($_POST['base_url'], '/');
                $config_content = str_replace("define('BASE_URL', 'http://localhost');", "define('BASE_URL', '$base_url');", $config_content);
            }
            
            file_put_contents(__DIR__ . '/../config.php', $config_content);
            $install_status[] = ['success', 'Configuration file created'];
        }
        
        $install_complete = true;
        $install_status[] = ['success', 'Installation completed successfully!'];
        
    } catch (Exception $e) {
        $install_status[] = ['error', 'Installation failed: ' . $e->getMessage()];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S2S Postback Testing Tool - Installer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d3a 100%);
            color: #e1e1e1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .installer-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #a1a1aa;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #e1e1e1;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #e1e1e1;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #a1a1aa;
        }
        
        .btn {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .status-messages {
            margin-top: 20px;
        }
        
        .status-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .status-success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }
        
        .status-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        
        .status-info {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }
        
        .complete-section {
            text-align: center;
            margin-top: 30px;
        }
        
        .complete-section a {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 8px;
            color: #4ade80;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .complete-section a:hover {
            background: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="logo">
            <h1>S2S Tool</h1>
            <p>Postback Testing Tool Installer</p>
        </div>
        
        <?php if (!$install_complete): ?>
            <form method="post">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="<?= $config_loaded ? DB_HOST : 'localhost' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="<?= $config_loaded ? DB_NAME : 's2s_tracking' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" id="db_user" name="db_user" value="<?= $config_loaded ? DB_USER : 'root' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?= $config_loaded ? DB_PASS : '' ?>">
                </div>
                
                <?php if (!$config_loaded): ?>
                <div class="form-group">
                    <label for="base_url">Base URL (e.g., https://yourdomain.com)</label>
                    <input type="url" id="base_url" name="base_url" placeholder="http://localhost" required>
                </div>
                <?php endif; ?>
                
                <button type="submit" name="install" class="btn">Install S2S Tool</button>
            </form>
        <?php endif; ?>
        
        <?php if (!empty($install_status)): ?>
            <div class="status-messages">
                <?php foreach ($install_status as [$type, $message]): ?>
                    <div class="status-message status-<?= $type ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($install_complete): ?>
            <div class="complete-section">
                <p style="margin-bottom: 20px; color: #a1a1aa;">Installation completed successfully! You can now access the application.</p>
                <a href="../admin/">Go to Admin Panel</a>
                <a href="../">Go to Homepage</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>