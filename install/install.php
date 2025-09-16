<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$success = false;
$error = null;
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = db();
        
        // Create offers table
        $offers_sql = "
        CREATE TABLE IF NOT EXISTS offers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            url TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($offers_sql);
        $messages[] = "✓ Created offers table";
        
        // Create clicks table
        $clicks_sql = "
        CREATE TABLE IF NOT EXISTS clicks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            offer_id INT NOT NULL,
            transaction_id VARCHAR(100) NOT NULL,
            ip VARCHAR(45),
            converted TINYINT(1) DEFAULT 0,
            status VARCHAR(50) DEFAULT NULL,
            payout DECIMAL(10,2) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_click (offer_id, transaction_id),
            INDEX idx_offer_id (offer_id),
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_converted (converted),
            FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($clicks_sql);
        $messages[] = "✓ Created clicks table";
        
        // Create postbacks table
        $postbacks_sql = "
        CREATE TABLE IF NOT EXISTS postbacks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            offer_id INT,
            transaction_id VARCHAR(100),
            status VARCHAR(50),
            payout DECIMAL(10,2),
            ip VARCHAR(45),
            raw_query TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_offer_id (offer_id),
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($postbacks_sql);
        $messages[] = "✓ Created postbacks table";
        
        // Insert sample offer if none exist
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM offers");
        $count = $count_stmt->fetch()['count'];
        
        if ($count == 0) {
            $sample_sql = "INSERT INTO offers (name, url) VALUES (?, ?)";
            $pdo->prepare($sample_sql)->execute([
                'Sample Offer', 
                'https://example.com/offer?tid={transaction_id}'
            ]);
            $messages[] = "✓ Inserted sample offer";
        }
        
        $success = true;
        $messages[] = "✓ Installation completed successfully!";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S2S Tracking Tool - Installer</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>S2S Tracking Tool - Database Installer</h1>
        
        <?php if ($error): ?>
            <div class="alert error">
                <strong>Installation Failed:</strong><br>
                <?= h($error) ?>
                <br><br>
                <strong>Common Issues:</strong>
                <ul>
                    <li>Make sure you've copied config.example.php to config.php</li>
                    <li>Check your database credentials in config.php</li>
                    <li>Ensure the database exists and is accessible</li>
                    <li>Verify MySQL user has CREATE TABLE privileges</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($messages): ?>
            <div class="alert <?= $success ? 'success' : 'info' ?>">
                <?php foreach ($messages as $msg): ?>
                    <div><?= h($msg) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="installation-complete">
                <h2>Next Steps</h2>
                <ol>
                    <li>Test the click endpoint: <a href="../click.php?offer=1&sub1=test123" target="_blank">../click.php?offer=1&sub1=test123</a></li>
                    <li>Visit the offer page: <a href="../offer.php?id=1&tid=test123" target="_blank">../offer.php?id=1&tid=test123</a></li>
                    <li>Test S2S receiver: <a href="../s2s.php?offer=1&tid=test123&amount=1.23&status=approved" target="_blank">../s2s.php?offer=1&tid=test123&amount=1.23&status=approved</a></li>
                    <li>Use the postback tester: <a href="../postback-test.php" target="_blank">../postback-test.php</a></li>
                </ol>
                
                <p><strong>Security Reminder:</strong> Remove or protect the install/ directory in production!</p>
            </div>
        <?php else: ?>
            <div class="install-form">
                <h2>Database Configuration</h2>
                <p>This installer will create the required database tables for the S2S Tracking Tool.</p>
                
                <div class="config-check">
                    <h3>Configuration Check</h3>
                    <ul>
                        <li>Database Host: <strong><?= h(DB_HOST) ?></strong></li>
                        <li>Database Name: <strong><?= h(DB_NAME) ?></strong></li>
                        <li>Database User: <strong><?= h(DB_USER) ?></strong></li>
                        <li>Config File: <?= file_exists(__DIR__ . '/../config.php') ? '✓ Found' : '✗ Missing (copy config.example.php)' ?></li>
                    </ul>
                </div>
                
                <form method="POST">
                    <button type="submit">Install Database Tables</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>