<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

redirectIfNotLogged();

if (isInstrutor()) {
    $aluno_id = $_GET['aluno_id'] ?? $_SESSION['user_id'];
} else {
    $aluno_id = $_SESSION['user_id'];
}

$ficha = getFichaAluno($pdo, $aluno_id);

if (!$ficha) {
    header('Location: dashboard.php');
    exit;
}

$exercicios = getExerciciosFicha($pdo, $ficha['id']);

// Agrupar exercícios por dia
$exercicios_por_dia = [];
foreach ($exercicios as $exercicio) {
    $dia = $exercicio['dia'];
    if (!isset($exercicios_por_dia[$dia])) {
        $exercicios_por_dia[$dia] = [];
    }
    $exercicios_por_dia[$dia][] = $exercicio;
}

// Função para ícones de grupos musculares
function getGrupoIcon($grupo) {
    $icons = [
        'peito' => '💖',
        'costas' => '👤',
        'pernas' => '🦵',
        'ombros' => '⬆️',
        'biceps' => '💪',
        'triceps' => '✋',
        'cardio' => '🏃',
        'abdominal' => '🔍'
    ];
    return $icons[$grupo] ?? '🏋️';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Treino - Academia FitPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        <?php include 'css/ver_treino.css'; ?>
    </style>
</head>
<body>
    <div class="progress-indicator"></div>
    
    <header>
        <div class="container">
            <h1>Academia FitPro</h1>
            <p class="subtitle">Ficha de Treino Personalizada</p>
        </div>
    </header>
    
    <div class="container">
        <div class="user-info">
            <div>
                <span><?php echo htmlspecialchars($_SESSION['user_nome']); ?></span> - 
                <span class="user-role">
                    <i class="fas <?php echo isInstrutor() ? 'fa-chalkboard-teacher' : 'fa-user-graduate'; ?>"></i>
                    <?php echo isInstrutor() ? 'Instrutor' : 'Aluno'; ?>
                </span>
            </div>
            <a href="dashboard.php" class="btn btn-warning">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>

        <div class="ficha-treino">
            <div class="ficha-header">
                <h2>FICHA DE TREINO PERSONALIZADA</h2>
                <p>
                    <strong><i class="fas fa-user-graduate"></i> Aluno:</strong> 
                    <?php echo htmlspecialchars($ficha['aluno_id'] == $_SESSION['user_id'] ? $_SESSION['user_nome'] : 'Aluno'); ?>
                    
                    <strong><i class="fas fa-chalkboard-teacher"></i> Instrutor:</strong> 
                    <?php echo htmlspecialchars($ficha['instrutor_nome']); ?>
                    
                    <strong><i class="fas fa-bullseye"></i> Objetivo:</strong> 
                    <?php echo getObjetivoTexto($ficha['objetivo']); ?>
                    
                    <strong><i class="fas fa-chart-line"></i> Nível:</strong> 
                    <?php echo getNivelTexto($ficha['nivel']); ?>
                    
                    <strong><i class="fas fa-calendar-week"></i> Frequência:</strong> 
                    <?php echo $ficha['frequencia_semanal']; ?> dias/semana
                    <span class="info-badge badge-primary">
                        <i class="fas fa-dumbbell"></i> 
                        <?php echo count($exercicios); ?> exercícios
                    </span>
                </p>
            </div>
            
            <div class="dias-treino">
                <?php foreach ($exercicios_por_dia as $dia => $exercicios_dia): ?>
                    <div class="dia-treino">
                        <h3 class="dia-titulo">
                            Dia <?php echo $dia; ?>
                            <span class="info-badge badge-success">
                                <i class="fas fa-list-check"></i>
                                <?php echo count($exercicios_dia); ?> exercícios
                            </span>
                        </h3>
                        
                        <?php
                        // Agrupar exercícios por grupo muscular no mesmo dia
                        $grupos_dia = [];
                        foreach ($exercicios_dia as $exercicio) {
                            $grupo = $exercicio['grupo_muscular'];
                            if (!isset($grupos_dia[$grupo])) {
                                $grupos_dia[$grupo] = [];
                            }
                            $grupos_dia[$grupo][] = $exercicio;
                        }
                        ?>
                        
                        <?php foreach ($grupos_dia as $grupo => $exercicios_grupo): ?>
                            <h4>
                                <?php echo getGrupoIcon($grupo); ?>
                                <?php echo getGrupoMuscularTexto($grupo); ?>
                                <small style="color: var(--gray); font-weight: normal; margin-left: 8px;">
                                    (<?php echo count($exercicios_grupo); ?> exercícios)
                                </small>
                            </h4>
                            <?php foreach ($exercicios_grupo as $exercicio): ?>
                                <div class="exercicio">
                                    <div class="exercicio-nome">
                                        <i class="fas fa-dumbbell" style="color: var(--secondary); margin-right: 8px;"></i>
                                        <?php echo htmlspecialchars($exercicio['exercicio_nome']); ?>
                                    </div>
                                    <div class="exercicio-detalhes">
                                        <span style="color: var(--primary); font-weight: 600;">
                                            <?php echo $exercicio['series']; ?> séries
                                        </span>
                                        <span style="color: var(--success); font-weight: 600;">
                                            × <?php echo $exercicio['repeticoes']; ?> reps
                                        </span>
                                        <span style="color: var(--warning);">
                                            ⏱️ <?php echo $exercicio['descanso']; ?> descanso
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($ficha['observacoes']): ?>
                <div class="observacoes">
                    <h3>
                        <i class="fas fa-sticky-note"></i> Observações Importantes
                    </h3>
                    <p><?php echo nl2br(htmlspecialchars($ficha['observacoes'])); ?></p>
                </div>
            <?php endif; ?>
            
            <button onclick="window.print()" class="btn btn-block print-btn">
                <i class="fas fa-print"></i> Imprimir Ficha
            </button>
        </div>
    </div>

    <script>
        // Efeito de scroll suave
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar classe de animação aos elementos
            const elementos = document.querySelectorAll('.dia-treino, .exercicio');
            elementos.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });

            // Efeito de impressão melhorado
            const printBtn = document.querySelector('.print-btn');
            printBtn.addEventListener('click', function() {
                // Adicionar mensagem antes de imprimir
                setTimeout(() => {
                    alert('📄 Preparando para impressão...\nCertifique-se de que a orientação da página está em "Retrato" para melhor resultado.');
                }, 100);
            });
        });

        // Contador de exercícios totais
        const totalExercicios = <?php echo count($exercicios); ?>;
        console.log(`🏋️ Ficha com ${totalExercicios} exercícios distribuídos em ${<?php echo count($exercicios_por_dia); ?>} dias`);
    </script>
</body>
</html>