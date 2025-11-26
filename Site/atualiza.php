<?php
// hash_senhas_xbdhmwax.php
// Uso: php hash_senhas_xbdhmwax.php

$host = 'localhost';
$db   = 'renant49_bdcaminho';
$user = 'renant49_master';
$pass = 'cQm9dZ8~aNMK';
$charset = 'utf8mb4';
$table = 'tb_usuario';
$codigoEscola = 'XBDHMWAX';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Opcional: preview — conta quantos registros serão afetados
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM {$table} WHERE codigoEscola = ?");
    $countStmt->execute([$codigoEscola]);
    $total = (int)$countStmt->fetchColumn();
    echo "Registros encontrados para codigoEscola={$codigoEscola}: {$total}\n";

    if ($total === 0) {
        echo "Nada a fazer. Saindo.\n";
        exit;
    }

    // Pede confirmação se rodando interativamente (apenas se CLI)
    if (php_sapi_name() === 'cli') {
        echo "Deseja continuar e hashear as senhas desses {$total} usuários? (s/N): ";
        $resp = strtolower(trim(fgets(STDIN)));
        if ($resp !== 's' && $resp !== 'y') {
            echo "Operação cancelada pelo usuário.\n";
            exit;
        }
    }

    // Busca usuários da escola
    $select = $pdo->prepare("SELECT nomeUsuario, senha FROM {$table} WHERE codigoEscola = ?");
    $select->execute([$codigoEscola]);

    // Prepara update
    $update = $pdo->prepare("UPDATE {$table} SET senha = ? WHERE nomeUsuario = ? AND codigoEscola = ?");

    $pdo->beginTransaction();
    $updated = 0;
    $skippedAlreadyHashed = 0;

    while ($row = $select->fetch()) {
        $nomeUsuario = $row['nomeUsuario'];
        $senhaOriginal = $row['senha'];

        // Evitar double-hash: detecta hashes PHP comuns (bcrypt/argon)
        // bcrypt starts with $2y$ or $2a$, argon2 starts with $argon2
        if (is_string($senhaOriginal) && preg_match('/^\$(2y|2a)\$|^\$argon2/i', $senhaOriginal)) {
            $skippedAlreadyHashed++;
            continue; // pula quem já parece estar em hash
        }

        // Gera hash seguro
        $senhaHash = password_hash($senhaOriginal, PASSWORD_DEFAULT);
        if ($senhaHash === false) {
            throw new Exception("Falha ao gerar hash para usuario {$nomeUsuario}");
        }

        $update->execute([$senhaHash, $nomeUsuario, $codigoEscola]);
        $updated++;
    }

    $pdo->commit();

    echo "Finalizado.\n";
    echo "Senhas atualizadas: {$updated}\n";
    echo "Senhas já em hash (ignoradas): {$skippedAlreadyHashed}\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
