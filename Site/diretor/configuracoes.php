<?php
// Inicie a sessão ANTES de qualquer saída
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifique se o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// 2. Inclua o arquivo de conexão com o banco de dados
require_once __DIR__ . '/../conexao/conecta.php'; 

// 3. Verifique se o tipo de usuário tem permissão para acessar a página
$tipoUsuarioSessao = strtolower(trim($_SESSION['tipoUsuario'] ?? ''));
if ($tipoUsuarioSessao !== 'diretor' && $tipoUsuarioSessao !== 'administrador') {
     session_destroy();
     header("Location: /login.php?acessoNegado");
     exit();
}

$id = $_SESSION['id'];

// Consulta para obter dados do usuário (diretor)
$stmt = $conn->prepare("SELECT nomeCompleto, email, nomeUsuario, telefone, datNasc, metaProvas, plano, codigoEscola, statusPlano, fotoUsuario, tipoImagem, tipoUsuario, cpf FROM tb_usuario WHERE id = ?");
if (!$stmt) {
     die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$stmt->close();

// Consulta para obter dados da escola se o diretor tiver código da escola
$dadosEscola = null;
$alunosEscola = [];
$alunosVinculados = 0;
$alunosPendentes = 0;

if (!empty($usuario['codigoEscola'])) {
    // Dados da escola
    $stmt_escola = $conn->prepare("SELECT nome, codigoEscola, plano, created_at FROM tb_escola WHERE codigoEscola = ?");
    if ($stmt_escola) {
        $stmt_escola->bind_param('s', $usuario['codigoEscola']);
        $stmt_escola->execute();
        $result_escola = $stmt_escola->get_result();
        $dadosEscola = $result_escola->fetch_assoc();
        $stmt_escola->close();
    }
    
    $codigoEscolaDebug = $usuario['codigoEscola'];
    
    // Contar total de alunos da escola (usando 'estudante' em vez de 'Aluno')
    $stmt_alunos = $conn->prepare("SELECT COUNT(*) as total_alunos FROM tb_usuario WHERE codigoEscola = ? AND tipoUsuario = 'estudante'");
    if ($stmt_alunos) {
        $stmt_alunos->bind_param('s', $codigoEscolaDebug);
        $stmt_alunos->execute();
        $result_alunos = $stmt_alunos->get_result();
        $alunosEscola = $result_alunos->fetch_assoc();
        $stmt_alunos->close();
    }
    
    // Contar alunos vinculados (com statusPlano habilitado)
    $stmt_vinculados = $conn->prepare("SELECT COUNT(*) as alunos_vinculados FROM tb_usuario WHERE codigoEscola = ? AND tipoUsuario = 'estudante' AND statusPlano = 'habilitado'");
    if ($stmt_vinculados) {
        $stmt_vinculados->bind_param('s', $codigoEscolaDebug);
        $stmt_vinculados->execute();
        $result_vinculados = $stmt_vinculados->get_result();
        $vinculadosData = $result_vinculados->fetch_assoc();
        $alunosVinculados = $vinculadosData['alunos_vinculados'] ?? 0;
        $stmt_vinculados->close();
    }
    
    // Contar alunos pendentes (com statusPlano pendente)
    $stmt_pendentes = $conn->prepare("SELECT COUNT(*) as alunos_pendentes FROM tb_usuario WHERE codigoEscola = ? AND tipoUsuario = 'estudante' AND statusPlano = 'pendente'");
    if ($stmt_pendentes) {
        $stmt_pendentes->bind_param('s', $codigoEscolaDebug);
        $stmt_pendentes->execute();
        $result_pendentes = $stmt_pendentes->get_result();
        $pendentesData = $result_pendentes->fetch_assoc();
        $alunosPendentes = $pendentesData['alunos_pendentes'] ?? 0;
        $stmt_pendentes->close();
    }
}
// Certifique-se de fechar a conexão no final da página, após todo o código PHP.
$conn->close();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Configurações - Caminho do Saber</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">

    <style>
      :root { --header-height: 120px; }
      body { scroll-padding-top: var(--header-height); }
      main.settings-main { margin-top: var(--header-height); }
      /* Correções pontuais desta página (não relacionadas ao menu) */
      .btn-danger { display:flex; align-items:center; justify-content:center; gap:8px; }
      .modal {
          display:none; position:fixed; top:0; left:0; width:100%; height:100%;
          background-color:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center;
      }
      .modal-content {
          background:#fff; padding:30px; border-radius:12px; max-width:500px; width:90%;
          box-shadow:0 10px 30px rgba(0,0,0,.3); text-align:center;
      }
      .modal-title { font-size:1.5rem; color:#dc3545; margin-bottom:20px; display:flex; align-items:center; justify-content:center; gap:10px; }
      .modal-text { margin-bottom:30px; color:#6c757d; line-height:1.6; }
      .modal-buttons { display:flex; justify-content:center; gap:15px; }
      .alert-warning {
          background-color: rgba(255,193,7,.2); color:#856404; padding:15px; border-radius:8px; margin:20px 0; border-left:4px solid #ffc107;
      }
      /* Estilo para a seção de dados da escola */
      .school-info {
        background: var(--light-gray);
        border-radius: var(--border-radius);
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid var(--primary-color);
      }
      .school-info h3 {
        color: var(--primary-color);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .info-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--medium-gray);
      }
      .info-item:last-child {
        border-bottom: none;
      }
      .info-label {
        font-weight: 500;
        color: var(--primary-dark);
      }
      .info-value {
        color: var(--black);
      }
    </style>
</head>
<body>
    <?php
      include_once __DIR__ . '/menu.php';
    ?>

    <main class="settings-main">
      <div class="settings-container animate-in">
          <h1 class="settings-title">Configurações</h1>

          <div class="settings-tabs">
              <button class="tab-button active" data-tab="profile">Perfil</button>
              <button class="tab-button" data-tab="security">Segurança</button>
              <button class="tab-button" data-tab="plans">Planos</button>
              <?php if (!empty($usuario['codigoEscola'])): ?>
              <button class="tab-button" data-tab="school">Dados da Escola</button>
              <?php endif; ?>
          </div>

          <div id="profile" class="tab-content active">
              <form method="POST" action="updatePerfil.php" class="settings-form" enctype="multipart/form-data">
                  <div class="form-group full-width">
                    <div class="avatar-container">
                        <img src="<?php 
                            if (!empty($usuario['fotoUsuario'])) {
                                if (is_resource($usuario['fotoUsuario'])) {
                                    $usuario['fotoUsuario'] = stream_get_contents($usuario['fotoUsuario']);
                                }
                                echo 'data:' . htmlspecialchars($usuario['tipoImagem']) . ';base64,' . base64_encode($usuario['fotoUsuario']);
                            } else {
                                echo 'https://via.placeholder.com/80';
                            }
                        ?>" alt="Avatar" class="avatar">
                        <div class="avatar-upload">
                            <input type="file" id="fotoUsuario" name="fotoUsuario" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('fotoUsuario').click()">Alterar Foto</button>
                            <small class="form-text">Formatos permitidos: JPG, PNG (Max. 2MB)</small>
                        </div>
                    </div>
                </div>

                  <div class="form-group">
                      <label for="nomeCompleto" class="form-label">Nome completo</label>
                      <input type="text" id="nomeCompleto" name="nomeCompleto" class="form-control" required value="<?php echo htmlspecialchars($usuario['nomeCompleto']); ?>">
                  </div>

                  <div class="form-group">
                      <label for="email" class="form-label">E-mail</label>
                      <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($usuario['email']); ?>">
                  </div>

                  <div class="form-group">
                      <label for="nomeUsuario" class="form-label">Nome de usuário</label>
                      <input type="text" id="nomeUsuario" name="nomeUsuario" class="form-control" required value="<?php echo htmlspecialchars($usuario['nomeUsuario']); ?>" readonly>
                  </div>

                  <div class="form-group">
                      <label for="telefone" class="form-label">Telefone</label>
                      <input type="tel" id="telefone" name="telefone" class="form-control" oninput="mascTelefone(this)" required value="<?php echo htmlspecialchars($usuario['telefone']); ?>">
                  </div>

                  <div class="form-group">
                      <label for="datNasc" class="form-label">Data de Nascimento</label>
                      <input type="text" id="datNasc" name="datNasc" class="form-control" oninput="aplicarMascara(this)" required value="<?php echo htmlspecialchars($usuario['datNasc']); ?>">
                  </div>

                  <div class="form-group">
                        <label for="cpf" class="form-label">CPF</label>
                        <input type="text" id="cpf" name="cpf" class="form-control" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" onblur="removerMascaraCPF(this)">
                    </div>

                  <div class="form-group">
                      <label for="metaProvas" class="form-label">Meta de provas</label>
                      <input type="number" id="metaProvas" name="metaProvas" min="0" class="form-control" required value="<?php echo htmlspecialchars($usuario['metaProvas']); ?>">
                      <small class="form-text">Número de provas que deseja concluir</small>
                  </div>

                  <div class="form-group">
                      <label class="form-label">Plano</label>
                      <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['plano']); ?>" readonly>
                  </div>

                  <div class="form-group">
                      <label class="form-label">Código da escola:</label>
                      <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['codigoEscola']); ?>" readonly>
                  </div>

                  <div class="form-group">
                      <label class="form-label">Status do plano:</label>
                      <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['statusPlano']); ?>" readonly>
                  </div>

                  <div class="form-group">
                      <label class="form-label">Tipo de Usuário</label>
                      <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['tipoUsuario']); ?>" readonly>
                  </div>

                  <div class="form-group full-width">
                      <button type="submit" class="btn btn-primary btn-block">Salvar Alterações</button>
                  </div>
              </form>
          </div>

          <div id="security" class="tab-content">
              <form method="POST" action="updateSenha.php" class="settings-form" onsubmit="return validarSenha()">
                  <div class="form-group full-width">
                    <label for="current_password" class="form-label">Senha Atual</label>
                    <div class="password-container">
                        <input type="password" id="current_password" name="senhaAtual" class="form-control" placeholder="Digite sua senha atual" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-footer">
                        <p>Esqueceu a senha?<a href="/esqueceuSenha/esqueceuSenha.php"> Clique aqui!</a></p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">Nova Senha</label>
                    <div class="password-container">
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Digite uma nova senha" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text">Mínimo 8 caracteres, com 1 letra maiúscula, 1 número e 1 caractere especial</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirme a nova senha" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
               <div class="form-group full-width">
                     <button type="submit" class="btn btn-primary btn-block">Alterar Senha</button>
                 </div>

                  <div class="alert-warning">
                      <i class="fas fa-exclamation-triangle"></i>
                      <strong>Atenção:</strong> A exclusão da conta é permanente e não pode ser desfeita.
                      Todos os seus dados serão removidos do sistema.
                  </div>

                  <div class="form-group full-width">
                      <button type="button" class="btn btn-danger btn-block" onclick="confirmDelete()">
                          <i class="fas fa-trash-alt"></i> Excluir Minha Conta
                      </button>
                  </div>
              </form>
          </div>
          

          <div id="plans" class="tab-content">
              <div class="form-group">
                <label class="form-label">Meu plano</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['plano']); ?>" readonly>
            </div>

            <?php
            // Lógica para mostrar o botão de cancelamento
            $mostraBotao = false;

            $planoUsuario = strtolower($usuario['plano'] ?? '');
            $tipoUsuario = strtolower($usuario['tipoUsuario'] ?? '');

            // O botão deve ser mostrado se o plano não for 'basico' E (o usuário for 'diretor' OU não tiver um 'codigoEscola')
            if ($planoUsuario !== 'basico' && ($tipoUsuario === 'diretor' || empty($usuario['codigoEscola']))) {
                $mostraBotao = true;
            }

            if ($mostraBotao) {
            ?>
            <form method="POST" action="cancelarAssinatura.php" style="display:inline;">
                <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Tem certeza que deseja cancelar sua assinatura? Seu plano será alterado para Básico.')">
                    <i class="fas fa-trash-alt"></i> Cancelar Assinatura
                </button>
            </form>
            <?php
            }
            ?>
              <div class="plans-container">
                  <div class="plan-card">
                      <h4>Individual</h4>
                      <div class="plan-price">R$ 10,99<span>/mês</span></div>
                      <p class="plan-description">Plano Individual</p>
                      <ul class="plan-features">
                          <li>Até 1 alunos</li>
                          <li>Simulados ENEM e Provão Paulista completos</li>
                          <li>1 redação por aluno/Semana</li>
                          <li>Relatórios básicos</li>
                      </ul>
                      <button type="button" class="btn-plan" onclick="window.location.href='planos/assinatura.php?plano=individual'">Assinar</button>
                  </div>

                  <div class="plan-card">
                      <h4>Essencial</h4>
                      <div class="plan-price">R$ 499<span>/mês</span></div>
                      <p class="plan-description">Ideal para pequenas escolas</p>
                      <ul class="plan-features">
                          <li>Até 100 alunos</li>
                          <li>Simulados ENEM e Provão Paulista completos</li>
                          <li>3 redações por aluno/Semana</li>
                          <li>Relatórios detalhados</li>
                      </ul>
                      <button type="button" class="btn-plan" onclick="window.location.href='planos/assinatura.php?plano=essencial'">Assinar</button>
                  </div>

                  <div class="plan-card popular">
                      <div class="popular-badge">Mais Popular</div>
                      <h4>Pro</h4>
                      <div class="plan-price">R$ 1.290<span>/mês</span></div>
                      <p class="plan-description">Para escolas em crescimento</p>
                      <ul class="plan-features">
                          <li>Até 300 alunos</li>
                          <li>Simulados ENEM e Provão Paulista completos</li>
                          <li>5 redações por aluno/Semana</li>
                          <li>Relatórios detalhados</li>
                          <li>Suporte prioritário</li>
                      </ul>
                      <button type="button" class="btn-plan" onclick="window.location.href='planos/assinatura.php?plano=pro'">Assinar</button>
                  </div>

                  <div class="plan-card">
                      <h4>Premium</h4>
                      <div class="plan-price">R$ 2.990<span>/mês</span></div>
                      <p class="plan-description">Solução completa</p>
                      <ul class="plan-features">
                          <li>Até 800 alunos</li>
                          <li>Simulados ENEM e Provão Paulista completos</li>
                          <li>Redações ilimitadas</li>
                          <li>Suporte dedicado</li>
                          <li>Relatórios detalhados</li>
                      </ul>
                      <button type="button" class="btn-plan" onclick="window.location.href='planos/assinatura.php?plano=premium'">Assinar</button>
                  </div>
              </div>
          </div>

         <?php if (!empty($usuario['codigoEscola'])): ?>
<div id="school" class="tab-content">
    <div class="school-info">
        <h3><i class="fas fa-school"></i> Dados da Escola</h3>
        
        <?php if ($dadosEscola): ?>
            <div class="info-item">
                <span class="info-label">Nome da Escola:</span>
                <span class="info-value"><?php echo htmlspecialchars($dadosEscola['nome']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Código da Escola:</span>
                <span class="info-value"><?php echo htmlspecialchars($dadosEscola['codigoEscola']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Plano da Escola:</span>
                <span class="info-value"><?php echo htmlspecialchars($dadosEscola['plano']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Total de Alunos Cadastrados:</span>
                <span class="info-value"><?php echo htmlspecialchars($alunosEscola['total_alunos'] ?? 0); ?> alunos</span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Alunos Vinculados (Habilitados):</span>
                <span class="info-value" style="color: var(--success-color); font-weight: 600;">
                    <?php echo htmlspecialchars($alunosVinculados); ?> alunos
                </span>
            </div>
            
            <?php if ($alunosPendentes > 0): ?>
            <div class="info-item">
                <span class="info-label">Alunos Pendentes:</span>
                <span class="info-value" style="color: var(--error-color); font-weight: 600;">
                    <?php echo htmlspecialchars($alunosPendentes); ?> alunos
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Estatísticas adicionais -->
            <?php 
            $totalAlunos = $alunosEscola['total_alunos'] ?? 0;
            if ($totalAlunos > 0): 
            ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(13, 75, 158, 0.05); border-radius: 8px;">
                <h4 style="color: var(--primary-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chart-bar"></i> Estatísticas da Escola
                </h4>
                <div class="info-item">
                    <span class="info-label">Taxa de Ativação:</span>
                    <span class="info-value">
                        <?php 
                        $taxaAtivacao = round(($alunosVinculados / $totalAlunos) * 100, 1);
                        echo htmlspecialchars($taxaAtivacao) . '%';
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Capacidade Utilizada:</span>
                    <span class="info-value">
                        <?php 
                        // Definir capacidade máxima baseada no plano
                        $capacidades = [
                            'Essencial' => 100,
                            'Pro' => 300,
                            'Premium' => 800
                        ];
                        $capacidadeMaxima = $capacidades[$dadosEscola['plano']] ?? 0;
                        if ($capacidadeMaxima > 0) {
                            $usoCapacidade = round(($totalAlunos / $capacidadeMaxima) * 100, 1);
                            echo htmlspecialchars($usoCapacidade) . '%';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <span class="info-label">Data de Associação:</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($dadosEscola['created_at'])); ?></span>
            </div>
            
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Não foi possível carregar os dados da escola. Verifique se o código está correto.
            </div>
        <?php endif; ?>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Informação:</strong> 
        <ul style="margin: 10px 0 0 20px; text-align: left;">
            <li><strong>Alunos Vinculados:</strong> Estudantes com acesso ativo à plataforma</li>
            <li><strong>Alunos Pendentes:</strong> Estudantes cadastrados mas aguardando ativação</li>
            <li><strong>Taxa de Ativação:</strong> Percentual de estudantes com acesso ativo</li>
        </ul>
    </div>
</div>
<?php endif; ?>
      </div>
    </main>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title"><i class="fas fa-exclamation-circle"></i> Confirmar Exclusão</h2>
            <p class="modal-text">Tem certeza que deseja excluir sua conta permanentemente? Esta ação não pode ser desfeita e todos os seus dados serão perdidos.</p>
            <div class="modal-buttons">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <form method="POST" action="excluirConta.php" style="display:inline;">
                    <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                </form>
            </div>
        </div>
    </div>

    <script src="scriptConfig.js"></script>
    <script>
        // Função para aplicar máscara ao CPF quando a página carregar
        function mascaraCPF(campo) {
            let cpf = campo.value.replace(/\D/g, '');
            cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
            cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
            cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            campo.value = cpf;
        }

        document.addEventListener("DOMContentLoaded", function () {
            // Aplicar máscara ao CPF
            const cpfField = document.getElementById('cpf');
            if (cpfField && cpfField.value) {
                mascaraCPF(cpfField);
            }

            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get("tab");
            if (tab) {
                // desativa todas
                document.querySelectorAll(".tab-button, .tab-content").forEach(el => el.classList.remove("active"));

                // ativa a aba passada na URL
                const btn = document.querySelector(`.tab-button[data-tab="${tab}"]`);
                const content = document.getElementById(tab);

                if (btn && content) {
                    btn.classList.add("active");
                    content.classList.add("active");
                }
            }
        });

        // Função para remover máscaras antes do envio do formulário
        function prepararFormularioPerfil() {
            const form = document.querySelector('form[action="updatePerfil.php"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Remover máscara do CPF
                    const cpfField = document.getElementById('cpf');
                    if (cpfField) {
                        cpfField.value = cpfField.value.replace(/\D/g, '');
                    }
                    
                    // Remover máscara do telefone
                    const telefoneField = document.getElementById('telefone');
                    if (telefoneField) {
                        telefoneField.value = telefoneField.value.replace(/\D/g, '');
                    }
                    
                    // Remover máscara da data de nascimento (opcional)
                    const datNascField = document.getElementById('datNasc');
                    if (datNascField) {
                        datNascField.value = datNascField.value.replace(/\D/g, '');
                    }
                });
            }
        }

        // Executar quando o DOM estiver carregado
        document.addEventListener("DOMContentLoaded", function() {
            prepararFormularioPerfil();
        });
    </script>
  
</body>
</html>