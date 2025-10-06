<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    
    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $error = "Este email já está cadastrado!";
    } else {
        // Criar novo usuário
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$nome, $email, $senha_hash, $tipo])) {
            $_SESSION['success'] = "Conta criada com sucesso! Faça login para continuar.";
            header('Location: login.php');
            exit;
        } else {
            $error = "Erro ao criar conta. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Academia FitPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        <?php include 'css/register.css'; ?>
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <h1 class="card-title">Criar Conta</h1>
            <p class="card-subtitle">Junte-se à nossa academia</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="nome">
                        <i class="fas fa-user"></i> Nome Completo
                    </label>
                    <div class="input-group">
                        <input type="text" id="nome" name="nome" required placeholder="Digite seu nome completo">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> E-mail
                    </label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" required placeholder="seu@email.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="senha">
                        <i class="fas fa-lock"></i> Senha
                    </label>
                    <div class="input-group">
                        <input type="password" id="senha" name="senha" required placeholder="Crie uma senha segura">
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar" id="passwordStrength"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tipo">
                        <i class="fas fa-user-tag"></i> Tipo de Usuário
                    </label>
                    <div class="input-group">
                        <select id="tipo" name="tipo" required>
                            <option value="">Selecione seu perfil...</option>
                            <option value="instrutor">Instrutor</option>
                            <option value="aluno">Aluno</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success" id="submitBtn">
                    <i class="fas fa-user-plus"></i> Criar Minha Conta
                </button>
            </form>
            
            <div class="login-link">
                <p>Já tem uma conta? <a href="login.php">Fazer login</a></p>
            </div>
        </div>
    </div>

    <script>
        // Validação de força da senha
        const senhaInput = document.getElementById('senha');
        const strengthBar = document.getElementById('passwordStrength');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('registerForm');

        senhaInput.addEventListener('input', function() {
            const senha = this.value;
            let strength = 0;

            if (senha.length >= 6) strength++;
            if (senha.match(/[a-z]/) && senha.match(/[A-Z]/)) strength++;
            if (senha.match(/\d/)) strength++;
            if (senha.match(/[^a-zA-Z\d]/)) strength++;

            strengthBar.className = 'strength-bar';
            if (strength > 0) {
                strengthBar.classList.add(
                    strength === 1 ? 'strength-weak' :
                    strength === 2 ? 'strength-medium' :
                    strength === 3 ? 'strength-medium' :
                    'strength-strong'
                );
            }
        });

        // Animação no submit
        form.addEventListener('submit', function() {
            submitBtn.classList.add('btn-loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Criando conta...';
        });

        // Animações nos inputs
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('input-animate');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('input-animate');
            });
        });
    </script>
</body>
</html>