<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

redirectIfNotLogged();

$user_tipo = $_SESSION['user_tipo'];
$user_nome = $_SESSION['user_nome'];

if (isInstrutor()) {
    $alunos = getAlunos($pdo);
} else {
    $ficha = getFichaAluno($pdo, $_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Academia FitPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        <?php include 'css/dashboard.css'; ?>
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Academia FitPro</h1>
            <p class="subtitle">Sistema de Gestão de Treinos Personalizados</p>
        </div>
    </header>
    
    <div class="container">
        <div class="user-info">
            <div>
                <span><?php echo htmlspecialchars($user_nome); ?></span> - 
                <span class="user-role">
                    <i class="fas <?php echo $user_tipo === 'instrutor' ? 'fa-chalkboard-teacher' : 'fa-user-graduate'; ?>"></i>
                    <?php echo $user_tipo === 'instrutor' ? 'Instrutor' : 'Aluno'; ?>
                </span>
            </div>
            <a href="logout.php" class="btn btn-warning">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>

        <?php if (isInstrutor()): ?>
            <!-- Dashboard do Instrutor -->
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-dumbbell"></i> Criar Nova Ficha de Treino
                </h2>
                <a href="criar_treino.php" class="btn btn-primary btn-block">
                    <i class="fas fa-plus-circle"></i> Criar Ficha de Treino
                </a>
            </div>
            
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-users"></i> Alunos Cadastrados
                </h2>
                <div class="lista-alunos">
                    <?php if (empty($alunos)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--gray);">
                            <i class="fas fa-users-slash" style="font-size: 3rem; margin-bottom: 15px;"></i>
                            <p>Nenhum aluno cadastrado.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($alunos as $aluno): ?>
                            <?php $ficha_aluno = getFichaAluno($pdo, $aluno['id']); ?>
                            <div class="aluno-item">
                                <div class="aluno-info">
                                    <h3><?php echo htmlspecialchars($aluno['nome']); ?></h3>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($aluno['email']); ?></p>
                                    <div class="treino-status <?php echo $ficha_aluno ? 'status-ativo' : 'status-pendente'; ?>">
                                        <i class="fas <?php echo $ficha_aluno ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                        <?php echo $ficha_aluno ? 'Treino Ativo' : 'Sem Treino'; ?>
                                    </div>
                                </div>
                                <div class="aluno-actions">
                                    <?php if ($ficha_aluno): ?>
                                        <a href="ver_treino.php?aluno_id=<?php echo $aluno['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> Ver Treino
                                        </a>
                                    <?php endif; ?>
                                    <a href="criar_treino.php?aluno_id=<?php echo $aluno['id']; ?>" class="btn btn-success btn-sm">
                                        <i class="fas <?php echo $ficha_aluno ? 'fa-edit' : 'fa-plus'; ?>"></i>
                                        <?php echo $ficha_aluno ? 'Editar' : 'Criar'; ?> Treino
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Dashboard do Aluno -->
            <div class="aluno-dashboard">
                <div class="dashboard-card">
                    <h3 class="card-title">
                        <i class="fas fa-user-circle"></i> Meus Dados
                    </h3>
                    <div class="aluno-dados">
                        <p>
                            <strong><i class="fas fa-user"></i> Nome:</strong> 
                            <?php echo htmlspecialchars($user_nome); ?>
                        </p>
                        <p>
                            <strong><i class="fas fa-envelope"></i> Email:</strong> 
                            <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                        </p>
                        <?php if ($ficha): ?>
                            <p>
                                <strong><i class="fas fa-bullseye"></i> Objetivo:</strong> 
                                <?php echo getObjetivoTexto($ficha['objetivo']); ?>
                            </p>
                            <p>
                                <strong><i class="fas fa-chart-line"></i> Nível:</strong> 
                                <?php echo getNivelTexto($ficha['nivel']); ?>
                            </p>
                            <p>
                                <strong><i class="fas fa-calendar-week"></i> Frequência:</strong> 
                                <?php echo $ficha['frequencia_semanal']; ?> dias/semana
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3 class="card-title">
                        <i class="fas fa-dumbbell"></i> Meu Treino
                    </h3>
                    <div class="treino-info">
                        <?php if ($ficha): ?>
                            <div style="margin-bottom: 20px;">
                                <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success); margin-bottom: 15px;"></i>
                                <p>Você possui um treino personalizado!</p>
                            </div>
                            <div class="treino-status status-ativo pulse">
                                <i class="fas fa-fire"></i> Treino Ativo
                            </div>
                            <div style="margin: 20px 0;">
                                <p><strong>Objetivo:</strong> <?php echo getObjetivoTexto($ficha['objetivo']); ?></p>
                                <p><strong>Próxima avaliação:</strong> <?php echo date('d/m/Y', strtotime('+30 days')); ?></p>
                            </div>
                            <a href="ver_treino.php" class="btn btn-primary btn-block">
                                <i class="fas fa-file-alt"></i> Ver Ficha Completa
                            </a>
                        <?php else: ?>
                            <div style="margin-bottom: 20px;">
                                <i class="fas fa-clock" style="font-size: 3rem; color: var(--warning); margin-bottom: 15px;"></i>
                                <p>Você ainda não possui um treino personalizado.</p>
                            </div>
                            <div class="treino-status status-pendente">
                                <i class="fas fa-exclamation-triangle"></i> Sem Treino
                            </div>
                            <p style="margin-top: 20px; color: var(--gray);">
                                Entre em contato com seu instrutor para criar um treino personalizado.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Animações interativas
        document.addEventListener('DOMContentLoaded', function() {
            // Efeito de digitação no título
            const titles = document.querySelectorAll('.card-title');
            titles.forEach(title => {
                title.style.animationDelay = '0.2s';
            });

            // Efeito de hover nos cards
            const cards = document.querySelectorAll('.card, .dashboard-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>