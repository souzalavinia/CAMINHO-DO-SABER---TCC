<?php
class Conecta {
    protected $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO('mysql:host=localhost;dbname=renant49_bdcaminho', 'renant49_master', 'cQm9dZ8~aNMK');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }

    public function geraChaveAcesso($email) {
        // Verifica se o usuário existe
        $stmt = $this->pdo->prepare("SELECT id, nomeCompleto, email, senha FROM tb_usuario WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Cria uma chave única
            $chave = md5(uniqid(rand(), true));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Remove tokens antigos
            $this->pdo->prepare("DELETE FROM redefinicao_senha WHERE usuario_id = ?")
                      ->execute([$usuario['id']]);
            
            // Insere o novo token
            $stmt = $this->pdo->prepare("INSERT INTO redefinicao_senha (usuario_id, chave, expira_em) VALUES (?, ?, ?)");
            $stmt->execute([$usuario['id'], $chave, $expira]);
            
            return [
                'email' => $usuario['email'],
                'nome' => $usuario['nomeCompleto'], // Usando nomeCompleto conforme sua tabela
                'chave' => $chave
            ];
        }
        return false;
    }

    public function validarToken($email, $token) {
        $stmt = $this->pdo->prepare("SELECT u.id 
                                   FROM redefinicao_senha r
                                   JOIN tb_usuario u ON r.usuario_id = u.id
                                   WHERE u.email = ? AND r.chave = ? AND r.expira_em > NOW()");
        $stmt->execute([$email, $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizarSenha($userId, $novaSenha) {
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE tb_usuario SET senha = ? WHERE id = ?");
        $stmt->execute([$senhaHash, $userId]);
        
        // Remove o token após uso
        $this->pdo->prepare("DELETE FROM redefinicao_senha WHERE usuario_id = ?")
                 ->execute([$userId]);
    }
}
?>