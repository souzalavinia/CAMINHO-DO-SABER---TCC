<?php
session_start();

require_once '../conexao/conecta.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$id = $_SESSION['id'];

// Consulta para obter dados do usuário
$stmt = $conn->prepare("SELECT nomeCompleto, email, nomeUsuario, telefone, datNasc, metaProvas, tipoUsuario, fotoUsuario, tipoImagem, plano, codigoEscola, cpf FROM tb_usuario WHERE id = ?");
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

// Consulta para obter dados da escola se o usuário tiver código da escola
// Consulta para obter dados da escola se o usuário tiver código da escola
$dadosEscola = null;
if (!empty($usuario['codigoEscola'])) {
    $stmt_escola = $conn->prepare("SELECT 
        e.nome, 
        e.codigoEscola, 
        e.plano, 
        e.created_at,
        u.nomeCompleto as diretor_nome
    FROM tb_escola e 
    LEFT JOIN tb_usuario u ON e.codigoEscola = u.codigoEscola AND u.tipoUsuario = 'Diretor'
    WHERE e.codigoEscola = ? 
    LIMIT 1");
    if ($stmt_escola) {
        $stmt_escola->bind_param('s', $usuario['codigoEscola']);
        $stmt_escola->execute();
        $result_escola = $stmt_escola->get_result();
        $dadosEscola = $result_escola->fetch_assoc();
        $stmt_escola->close();
    }
}

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
      /* Novo estilo para o campo do código da escola com o botão de edição */
      .input-group {
        display: flex;
        align-items: center;
        width: 100%;
      }
      .input-group .form-control {
        flex-grow: 1;
        border-right: none;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
      }
      .input-group .btn-edit {
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #fff;
        background-color: #007bff; /* Ou a cor primária do seu tema */
        border: 1px solid #007bff;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        cursor: pointer;
        transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
      }
      .input-group .btn-edit:hover {
        background-color: #0056b3;
        border-color: #0056b3;
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
      include_once __DIR__ . '/../menu.php';
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
                                echo 'https://placehold.co/80';
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
                      <input type="text" id="nomeUsuario" name="nomeUsuario" class="form-control" required value="<?php echo htmlspecialchars($usuario['nomeUsuario']); ?>"readonly >
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
                        <input type="text" id="cpf" name="cpf" class="form-control" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" readonly>
                    </div>

                  <div class="form-group">
                      <label for="metaProvas" class="form-label">Meta de provas</label>
                      <input type="number" id="metaProvas" name="metaProvas" min="0" class="form-control" required value="<?php echo htmlspecialchars($usuario['metaProvas']); ?>">
                      <small class="form-text">Número de provas que deseja concluir</small>
                  </div>

                  <div class="form-group">
                      <label for="codigoEscola" class="form-label">Código da Escola</label>
                      <div class="input-group">
                          <input type="text" id="codigoEscola" name="codigoEscola" class="form-control" 
                                 value="<?php echo htmlspecialchars($usuario['codigoEscola'] ?? ''); ?>" 
                                 placeholder="Digite o código da escola"
                                 <?php echo empty($usuario['codigoEscola']) ? '' : 'readonly'; ?>
                                 >
                          <button type="button" class="btn-edit" id="btnEditCodigoEscola" onclick="toggleEditCodigoEscola()">
                            <?php echo empty($usuario['codigoEscola']) ? '<i class="fas fa-plus"></i>' : '<i class="fas fa-pencil-alt"></i>'; ?>
                          </button>
                      </div>
                      <?php if (empty($usuario['codigoEscola'])): ?>
                          <small class="form-text">Adicione o código fornecido pela sua escola para associar sua conta.</small>
                      <?php else: ?>
                          <small class="form-text">Clique no ícone de lápis para alterar o código.</small>
                      <?php endif; ?>
                  </div>
                  <div class="form-group">
                      <label class="form-label">Plano</label>
                      <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['plano']); ?>" readonly>
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
                          <span class="toggle-password" onclick="togglePassword('current_password')">
                              <i class="far fa-eye"></i>
                          </span>
                          <div class="form-footer">
                              <p>Esqueceu a senha?<a href="../esqueceuSenha/esqueceuSenha.php"> Clique aqui!</a></p>
                          </div>
                      </div>
                  </div>

                  <div class="form-group">
                      <label for="new_password" class="form-label">Nova Senha</label>
                      <div class="password-container">
                          <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Digite uma nova senha" required>
                          <span class="toggle-password" onclick="togglePassword('new_password')">
                              <i class="far fa-eye"></i>
                          </span>
                      </div>
                      <small class="form-text">Mínimo 8 caracteres, com 1 letra maiúscula, 1 número e 1 caractere especial</small>
                  </div>

                  <div class="form-group">
                      <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                      <div class="password-container">
                          <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirme a nova senha" required>
                          <span class="toggle-password" onclick="togglePassword('confirm_password')">
                              <i class="far fa-eye"></i>
                          </span>
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

            // O botão deve ser mostrado se o plano não for 'Basico' e uma das seguintes condições for verdadeira:
            // 1. O usuário é um Diretor
            // 2. O usuário não tem um código de escola associado (usuário comum)
            if ($usuario['plano'] !== 'Basico' && ($usuario['tipoUsuario'] === 'Diretor' || empty($usuario['codigoEscola']))) {
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

            <div id="cancelModal" class="modal">
                <div class="modal-content">
                    <h2 class="modal-title"><i class="fas fa-exclamation-circle"></i> Confirmar Cancelamento</h2>
                    <p class="modal-text">Tem certeza que deseja cancelar sua assinatura? Seu plano será alterado para Básico.</p>
                    <div class="modal-buttons">
                        <button class="btn btn-secondary" onclick="closeCancelModal()">Cancelar</button>
                        <form method="POST" action="cancelarAssinatura.php" style="display:inline;">
                            <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="plans-container">
                <div class="plan-card">
                    <h4>Individual</h4>
                    <div class="plan-price">R$ 10,99<span>/mês</span></div>
                    <p class="plan-description">Plano Individual</p>
                    <ul class="plan-features">
                        <li>Até 1 aluno</li>
                        <li>Simulados ilimitados</li>
                        <li>3 redações por aluno/semana</li>
                    </ul>
                    <button type="button" class="btn-plan" onclick="window.location.href='../planos/assinatura.php?plano=individual'">Assinar</button>
                </div>

                <div class="plan-card">
                    <h4>Essencial</h4>
                    <div class="plan-price">R$ 499<span>/mês</span></div>
                    <p class="plan-description">Ideal para pequenas escolas</p>
                    <ul class="plan-features">
                        <li>Até 100 alunos</li>
                        <li>Simulados ilimitados</li>
                        <li>3 redações por aluno/semana</li>
                        <li>Painel do diretor</li>
                    </ul>
                    <button type="button" class="btn-plan" onclick="window.location.href='../planos/assinatura.php?plano=essencial'">Assinar</button>
                </div>

                <div class="plan-card popular">
                    <div class="popular-badge">Mais Popular</div>
                    <h4>Pro</h4>
                    <div class="plan-price">R$ 1.290<span>/mês</span></div>
                    <p class="plan-description">Para escolas em crescimento</p>
                    <ul class="plan-features">
                        <li>Até 300 alunos</li>
                        <li>Simulados ilimitados</li>
                        <li>5 redações por aluno/semana</li>
                        <li>Suporte prioritário</li>
                        <li>Painel do diretor</li>
                    </ul>
                    <button type="button" class="btn-plan" onclick="window.location.href='../planos/assinatura.php?plano=pro'">Assinar</button>
                </div>

                <div class="plan-card">
                    <h4>Premium</h4>
                    <div class="plan-price">R$ 2.990<span>/mês</span></div>
                    <p class="plan-description">Solução completa</p>
                    <ul class="plan-features">
                        <li>Até 800 alunos</li>
                        <li>Simulados ilimitados</li>
                        <li>Redações ilimitadas</li>
                        <li>Suporte dedicado</li>
                        <li>Painel do diretor</li>
                    </ul>
                    <button type="button" class="btn-plan" onclick="window.location.href='../planos/assinatura.php?plano=premium'">Assinar</button>
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
                    <?php if (!empty($dadosEscola['diretor_nome'])): ?>
                    <div class="info-item">
                        <span class="info-label">Diretor:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dadosEscola['diretor_nome']); ?></span>
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
                <strong>Informação:</strong> Estes dados são fornecidos pela sua escola e vinculados através do código de associação.
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
    // Função para máscara de CPF
    function mascaraCPF(campo) {
        let cpf = campo.value.replace(/\D/g, '');
        cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
        cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
        cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        campo.value = cpf;
    }

    // Aplicar máscara ao CPF quando a página carregar
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

    // Função para alternar o modo de edição do campo codigoEscola
    function toggleEditCodigoEscola() {
        const input = document.getElementById('codigoEscola');
        const button = document.getElementById('btnEditCodigoEscola');
        const isReadonly = input.readOnly;

        if (isReadonly) {
            // Habilita a edição
            input.readOnly = false;
            input.focus();
            // Altera o ícone para um de 'salvar' ou 'confirmar' (usarei um de 'check' para indicar que está editável/pronto para salvar)
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.style.backgroundColor = '#28a745'; // Cor verde para indicar edição ativa
            button.style.borderColor = '#28a745';
        } else {
            // Desabilita a edição. O valor será salvo ao submeter o formulário principal.
            input.readOnly = true;
            // Retorna o ícone para 'editar' ou 'adicionar'
            button.innerHTML = '<?php echo empty($usuario['codigoEscola']) ? '<i class="fas fa-plus"></i>' : '<i class="fas fa-pencil-alt"></i>'; ?>';
            button.style.backgroundColor = '#007bff';
            button.style.borderColor = '#007bff';
        }
    }
</script>
    
</body>
</html>