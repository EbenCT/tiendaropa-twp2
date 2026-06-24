<?php
try {
    $pdo = new PDO(
        'pgsql:host=db.tecnoweb.org.bo;port=5432;dbname=db_grupo21sa',
        'grupo21sa',
        'grup021grup021*',
        [PDO::ATTR_TIMEOUT => 10]
    );
    $stmt = $pdo->query('SELECT COUNT(*) FROM usuario');
    $count = $stmt->fetchColumn();
    echo "✅ Conexión exitosa. Usuarios en BD: " . $count . PHP_EOL;
    
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name");
    echo "Tablas: ";
    foreach ($tables as $row) {
        echo $row[0] . " ";
    }
    echo PHP_EOL;
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
