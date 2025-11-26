<?php
// acessarProvaSerial.php

// Inicia a sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica a autenticação do usuário
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = (int)$_SESSION['id'];
$planoUsuario = isset($_SESSION['planoUsuario']) ? $_SESSION['planoUsuario'] : 'Basico';

// Confirma o caminho da conexão (Ajuste se o caminho '/conexao/conecta.php' estiver errado)
require_once __DIR__ . '/conexao/conecta.php';

/* ==============================================
    VERIFICAÇÃO DE LIMITE POR PLANO (PROVAS)
============================================== */
// Configurações de limite de plano (replicando a lógica de provas.php)
$limitesProvas = [
    'Basico' => 3,
    'Individual' => null,
    'Essencial' => null,
    'Pro' => null,
    'Premium' => null,
    'escolaPublica' => null
];
$limiteSemanalProvas = $limitesProvas[$planoUsuario] ?? null;
$erroLimite = false;
$mensagem = ''; // Variável para feedback ao usuário

// Lógica de contagem de limite
if (!is_null($limiteSemanalProvas)) {
    $sqlLimite = "
        SELECT COUNT(*) AS total
        FROM tb_tentativas
        WHERE idUsuario = ?
        AND YEARWEEK(STR_TO_DATE(dataTentativa, '%d/%m/%Y'), 1) = YEARWEEK(CURDATE(), 1)
    ";
    
    if ($stmt = $conn->prepare($sqlLimite)) {
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $qtdProvasSemana = (int)$res['total'];

        if ($qtdProvasSemana >= $limiteSemanalProvas) {
            $erroLimite = true;
        }
    } 
}


/* ==============================================
    PROCESSAMENTO DO FORMULÁRIO (POST)
============================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erroLimite) {
    // 1. Captura e Sanitiza o Serial
    $serial = isset($_POST['serial']) ? trim($_POST['serial']) : '';
    
    if (empty($serial)) {
        $mensagem = "Por favor, insira o código Serial da Prova.";
    } else {
        // 2. Busca a Prova Oculta (simulado='sim')
        // CORRIGIDO: Usando tb_prova
        $sql = "SELECT id FROM tb_prova WHERE serial = ? AND simulado = 'sim' LIMIT 1";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $serial);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($prova = $resultado->fetch_assoc()) {
                
                // 3. Prova Encontrada! Cria o passe de sessão
                $idProva = (int)$prova['id'];
                
                // CRIA O PASSE DE ACESSO
                $_SESSION['acesso_simulado_id'] = $idProva; 
                
                // CRUCIAL: Fecha a conexão e USA EXIT() após o header
                $conn->close();
                
                // REDIRECIONA PARA MOSTRAQUEST.PHP
                header("Location: mostraSimulado.php?id={$idProva}"); 
                exit();
                
            } else {
                // 4. Prova Não Encontrada
                $mensagem = "Código Serial inválido.";
            }
            $stmt->close();
        } else {
             $mensagem = "Erro interno: Falha na preparação da consulta de prova. Tente novamente.";
        }
    }
}

/* ==============================================
    HTML E EXIBIÇÃO
============================================== */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acessar Simulado por Serial</title>
    <style>
        /* Variáveis de Cores (Refinadas) */
        :root {
            --primary-color: #0d4b9e; /* Azul Escuro */
            --primary-light: #4479c4; /* Azul Claro para Hover/Foco */
            --secondary-color: #D4AF37; /* Dourado Principal */
            --secondary-dark: #C4A134; /* Dourado para Hover */
            --background-color: #f0f4f8; /* Fundo Suave */
            --card-background: #ffffff; /* Fundo do Container */
            --text-color: #333333;
            --light-text: #6c757d;
            --error-bg: #f8d7da;
            --error-border: #f5c2c7;
            --error-text: #842029;
            --border-radius-main: 16px;
            --border-radius-small: 8px;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 20px; /* Padding para dispositivos móveis */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 420px; /* Mais compacto */
            width: 100%;
            padding: 35px;
            background: var(--card-background);
            border-radius: var(--border-radius-main);
            /* Sombra Neumórfica/Soft-UI: Duas sombras para profundidade sutil */
            box-shadow: 
                0 6px 15px rgba(0, 0, 0, 0.08),
                0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: translateY(-3px); /* Movimento mais sutil */
            box-shadow: 
                0 10px 20px rgba(0, 0, 0, 0.12),
                0 4px 8px rgba(0, 0, 0, 0.08);
        }
        
        h1 {
            color: var(--primary-color);
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e6ed; /* Linha divisória mais sutil */
        }

        p {
            line-height: 1.5;
            margin-bottom: 25px;
            text-align: center;
            color: var(--light-text);
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 30px; /* Mais espaço */
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 14px 15px;
            box-sizing: border-box;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius-small);
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
            background-color: #fcfcfc;
        }

        input[type="text"]:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(13, 75, 158, 0.15); /* Sombra de foco mais visível */
            outline: none;
            background-color: var(--card-background);
        }

        input[type="text"]::placeholder {
            color: #adb5bd;
        }
        
        button {
            width: 100%;
            padding: 16px;
            background-color: var(--secondary-color);
            color: var(--text-color);
            border: none;
            border-radius: var(--border-radius-small);
            cursor: pointer;
            font-weight: 700;
            font-size: 17px;
            letter-spacing: 1px;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.35);
        }
        
        button:hover {
            background-color: var(--secondary-dark); 
            transform: translateY(-2px); /* Efeito de elevação sutil */
            box-shadow: 0 6px 12px rgba(212, 175, 55, 0.45);
        }
        
        button:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(212, 175, 55, 0.45);
        }
        
        /* Estilo para Mensagens de Erro */
        .message-error {
            color: var(--error-text);
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            padding: 15px;
            border-radius: var(--border-radius-small);
            margin-bottom: 25px;
            font-size: 15px;
            text-align: center;
            font-weight: 500;
        }
        
        /* Estilo para Mensagem de Limite Atingido (Aprimorado) */
        .message-limit {
            background: #fffafa; /* Fundo mais suave que o vermelho */
            border: 1px solid #ffcccc;
            color: var(--error-text);
            padding: 25px;
            border-radius: var(--border-radius-main);
            text-align: center;
            box-shadow: 0 2px 10px rgba(255, 0, 0, 0.1);
        }
        
        .message-limit h2 {
            font-size: 22px;
            margin-bottom: 15px;
            color: var(--error-text);
            font-weight: 700;
        }

        .message-limit p {
            margin-bottom: 12px;
            font-size: 16px;
            color: var(--text-color); /* Usar a cor de texto padrão para melhor leitura */
        }
        
        .message-limit a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: underline;
            transition: color 0.3s;
        }
        
        .message-limit a:hover {
            color: var(--primary-light);
        }
    </style>
</head>
<body>

    <?php 
    // Assumindo que o arquivo menu.php existe no diretório atual ou raiz do projeto
    include __DIR__ . '/menu.php'; // Inclui o menu de navegação 
    ?>
    <div class="container">
        
        <?php if ($erroLimite): ?>
            <div class="message-limit">
                <h2>Limite de Provas Atingido 🚫</h2>
                <p>O seu plano **<?php echo ucfirst($planoUsuario); ?>** permite acessar até
                    **<?php echo $limiteSemanalProvas; ?> provas por semana**.</p>
                <p>Você já atingiu esse limite. Para continuar, aguarde a próxima semana
                    ou <a href='configuracao/configuracoes.php?tab=plans'>faça upgrade de plano</a>.</p>
            </div>
        <?php else: ?>
            <h1>Acesso ao Simulado</h1>
            <p>Insira o **Código Serial** da prova para iniciar:</p>

            <?php if (!empty($mensagem)): ?>
                <div class="message-error"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>

            <form method="POST" action="simulado.php">
                <div class="form-group">
                    <label for="serial">Código Serial da Prova:</label>
                    <input 
                        type="text" 
                        id="serial" 
                        name="serial" 
                        required 
                        placeholder=""
                    >
                </div>
                
                <button type="submit">Acessar Prova</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// Fecha a conexão se ela ainda estiver aberta
if ($conn && $conn->ping()) {
    $conn->close();
}
?>