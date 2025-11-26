<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificação de Status da Sessão</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        h2 { border-bottom: 2px solid #0d4b9e; padding-bottom: 10px; color: #0d4b9e; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #e6f0ff; color: #333; }
        .highlight { font-weight: bold; color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Status e Configurações de Sessão PHP</h1>
        <p>Este script ajuda a confirmar se suas configurações de tempo de vida de sessão foram aplicadas.</p>

        <h2>⚙️ Configurações Ativas (`ini` e Cookie)</h2>
        <table>
            <tr>
                <th>Diretiva</th>
                <th>Valor Atual (Segundos)</th>
                <th>Valor (Minutos)</th>
                <th>Origem</th>
            </tr>
            <tr>
                <td>**session.gc_maxlifetime**</td>
                <td><span class="highlight"><?php echo ini_get('session.gc_maxlifetime'); ?></span></td>
                <td><?php echo round(ini_get('session.gc_maxlifetime') / 60, 2); ?> min</td>
                <td>Tempo de vida dos dados no **Servidor**</td>
            </tr>
            <tr>
                <td>**session.cookie_lifetime**</td>
                <td><span class="highlight"><?php echo ini_get('session.cookie_lifetime'); ?></span></td>
                <td><?php echo round(ini_get('session.cookie_lifetime') / 60, 2); ?> min</td>
                <td>Tempo de vida do ID no **Navegador**</td>
            </tr>
            <tr>
                <td>**session.save_path**</td>
                <td><?php echo ini_get('session.save_path'); ?></td>
                <td>-</td>
                <td>Caminho de armazenamento da sessão</td>
            </tr>
        </table>
        
        <h2>💾 Dados Atuais na Sessão (`$_SESSION`)</h2>
        <?php if (!empty($_SESSION)): ?>
            <table>
                <tr>
                    <th>Chave da Sessão</th>
                    <th>Valor</th>
                </tr>
                <?php foreach ($_SESSION as $key => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td><?php 
                            // Exibe o valor, ou 'ARRAY' se for um array
                            if (is_array($value)) {
                                echo 'ARRAY: ' . print_r($value, true);
                            } else {
                                echo htmlspecialchars($value);
                            }
                        ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Nenhum dado encontrado em <code>$_SESSION</code>. Faça login no seu sistema e recarregue esta página.</p>
        <?php endif; ?>

        <?php
        if (!isset($_SESSION['session_start_time'])) {
            $_SESSION['session_start_time'] = time();
            $_SESSION['first_page_view'] = date('H:i:s');
        }
        $tempo_decorrido = time() - $_SESSION['session_start_time'];
        ?>
        
        <h2>⏱️ Teste de Duração</h2>
        <table>
            <tr>
                <td>**Sessão Iniciada em:**</td>
                <td><?php echo date('Y-m-d H:i:s', $_SESSION['session_start_time']); ?></td>
            </tr>
            <tr>
                <td>**Tempo Decorrido Desde o Início:**</td>
                <td><?php echo $tempo_decorrido; ?> segundos (<?php echo round($tempo_decorrido / 60, 2); ?> minutos)</td>
            </tr>
            <tr>
                <td>**Tempo de Inatividade Máximo Permitido:**</td>
                <td><?php echo ini_get('session.gc_maxlifetime'); ?> segundos (<?php echo round(ini_get('session.gc_maxlifetime') / 3600, 2); ?> horas)</td>
            </tr>
        </table>

        <p class="highlight" style="margin-top: 20px;">
            Para testar a expiração: feche esta página, espere um tempo maior que **<?php echo round(ini_get('session.gc_maxlifetime') / 3600, 2); ?> horas** e tente acessar uma página restrita.
        </p>

    </div>
</body>
</html>