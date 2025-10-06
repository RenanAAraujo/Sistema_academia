<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Verificar se o usuário está logado e é instrutor
redirectIfNotInstrutor();

// Inicializar variáveis
$alunos = [];
$exercicios_por_grupo = [];
$error = '';

try {
    // Buscar alunos
    $alunos = getAlunos($pdo);
    
    $aluno_id = $_GET['aluno_id'] ?? '';

    // Buscar exercícios do banco
    $stmt = $pdo->prepare("SELECT * FROM exercicios ORDER BY grupo_muscular, nome");
    $stmt->execute();
    $exercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar exercícios por grupo muscular
    $exercicios_por_grupo = [];
    foreach ($exercicios as $exercicio) {
        $grupo = $exercicio['grupo_muscular'];
        if (!isset($exercicios_por_grupo[$grupo])) {
            $exercicios_por_grupo[$grupo] = [];
        }
        $exercicios_por_grupo[$grupo][] = $exercicio;
    }

} catch (Exception $e) {
    $error = "Erro ao carregar dados: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aluno_id = $_POST['aluno_id'] ?? '';
    $objetivo = $_POST['objetivo'] ?? '';
    $nivel = $_POST['nivel'] ?? '';
    $frequencia = $_POST['frequencia'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    $exercicios_selecionados = $_POST['exercicios'] ?? [];
    
    // Validações básicas
    if (empty($aluno_id) || empty($objetivo) || empty($nivel) || empty($frequencia)) {
        $error = "Preencha todos os campos obrigatórios!";
    } elseif (empty($exercicios_selecionados)) {
        $error = "Selecione pelo menos um exercício!";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Inserir ficha de treino
            $stmt = $pdo->prepare("
                INSERT INTO fichas_treino (aluno_id, instrutor_id, objetivo, nivel, frequencia_semanal, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$aluno_id, $_SESSION['user_id'], $objetivo, $nivel, $frequencia, $observacoes]);
            $ficha_id = $pdo->lastInsertId();
            
            // Inserir exercícios da ficha
            $stmt = $pdo->prepare("
                INSERT INTO ficha_exercicios (ficha_id, exercicio_id, dia, series, repeticoes, descanso, ordem) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $ordem = 1;
            foreach ($exercicios_selecionados as $exercicio_data) {
                $dados = json_decode($exercicio_data, true);
                
                if (!is_array($dados) || !isset($dados['exercicio_id'])) {
                    continue; // ignora dados inválidos
                }

                $stmt->execute([
                    $ficha_id,
                    $dados['exercicio_id'],
                    $dados['dia'],
                    $dados['series'],
                    $dados['repeticoes'],
                    $dados['descanso'],
                    $ordem++
                ]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Ficha de treino criada com sucesso!";
            header('Location: dashboard.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erro ao criar ficha: " . $e->getMessage();
        }
    }
}

// Função auxiliar para ícones
function getGrupoIcon($grupo) {
    $icons = [
        'peito' => 'heart',
        'costas' => 'user',
        'pernas' => 'walking',
        'ombros' => 'arrow-up',
        'biceps' => 'fist-raised',
        'triceps' => 'hand-point-up',
        'cardio' => 'running',
        'abdominal' => 'user-circle'
    ];
    return $icons[$grupo] ?? 'dumbbell';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Ficha de Treino - Academia FitPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        <?php 
        // Incluir o CSS diretamente para evitar problemas de caminho
        include 'css/criar_treino.css'; 
        ?>
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Academia FitPro</h1>
            <p class="subtitle">Criar Ficha de Treino Personalizada</p>
        </div>
    </header>
    
    <div class="container">
        <div class="user-info">
            <div>
                <span><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário'); ?></span> - 
                <span class="user-role">
                    <i class="fas fa-chalkboard-teacher"></i> Instrutor
                </span>
            </div>
            <a href="dashboard.php" class="btn btn-warning">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-dumbbell"></i> Criar Nova Ficha de Treino
            </h2>
            <form method="POST" id="formTreino">
                <div class="form-group">
                    <label for="aluno_id">
                        <i class="fas fa-user-graduate"></i> Selecionar Aluno
                    </label>
                    <select id="aluno_id" name="aluno_id" required>
                        <option value="">Selecione um aluno</option>
                        <?php foreach ($alunos as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>" <?php echo ($aluno_id == $aluno['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($aluno['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="objetivo">
                        <i class="fas fa-bullseye"></i> Objetivo Principal
                    </label>
                    <select id="objetivo" name="objetivo" required>
                        <option value="">Selecione um objetivo</option>
                        <option value="emagrecimento">🏃 Emagrecimento</option>
                        <option value="hipertrofia">💪 Hipertrofia (Ganho de Massa)</option>
                        <option value="forca">🏋️ Força</option>
                        <option value="condicionamento">⚡ Condicionamento Físico</option>
                        <option value="definicao">🔍 Definição Muscular</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nivel">
                        <i class="fas fa-chart-line"></i> Nível de Experiência
                    </label>
                    <select id="nivel" name="nivel" required>
                        <option value="">Selecione o nível</option>
                        <option value="iniciante">🟢 Iniciante (até 3 meses)</option>
                        <option value="intermediario">🟡 Intermediário (3 meses a 2 anos)</option>
                        <option value="avancado">🔴 Avançado (mais de 2 anos)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="frequencia">
                        <i class="fas fa-calendar-week"></i> Frequência Semanal de Treino
                    </label>
                    <select id="frequencia" name="frequencia" required>
                        <option value="">Selecione a frequência</option>
                        <option value="2">2 dias por semana</option>
                        <option value="3">3 dias por semana</option>
                        <option value="4">4 dias por semana</option>
                        <option value="5">5 dias por semana</option>
                        <option value="6">6 dias por semana</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-list-check"></i> Selecionar Exercícios
                        <small style="color: var(--gray); font-weight: normal; margin-left: 8px;">
                            (Selecione os exercícios e defina o dia de treino)
                        </small>
                    </label>
                    <div id="selecao-exercicios">
                        <?php if (!empty($exercicios_por_grupo)): ?>
                            <?php foreach ($exercicios_por_grupo as $grupo => $exercicios_grupo): ?>
                                <div class="grupo-exercicios" data-grupo="<?php echo $grupo; ?>">
                                    <h4>
                                        <i class="fas fa-<?php echo getGrupoIcon($grupo); ?>"></i>
                                        <?php echo getGrupoMuscularTexto($grupo); ?>
                                        <small style="color: var(--gray); font-weight: normal; margin-left: 8px;">
                                            (<?php echo count($exercicios_grupo); ?> exercícios)
                                        </small>
                                    </h4>
                                    <div class="exercicios-lista">
                                        <?php foreach ($exercicios_grupo as $exercicio): ?>
                                            <div class="exercicio-item">
                                                <input type="checkbox" 
                                                       class="exercicio-checkbox" 
                                                       data-exercicio-id="<?php echo $exercicio['id']; ?>"
                                                       data-series="<?php echo htmlspecialchars($exercicio['series_padrao']); ?>"
                                                       data-repeticoes="<?php echo htmlspecialchars($exercicio['repeticoes_padrao']); ?>"
                                                       data-descanso="<?php echo htmlspecialchars($exercicio['descanso_padrao']); ?>">
                                                <label><?php echo htmlspecialchars($exercicio['nome']); ?></label>
                                                <select class="exercicio-dia" disabled>
                                                    <option value="A">📅 Dia A</option>
                                                    <option value="B">📅 Dia B</option>
                                                    <option value="C">📅 Dia C</option>
                                                    <option value="D">📅 Dia D</option>
                                                    <option value="E">📅 Dia E</option>
                                                    <option value="F">📅 Dia F</option>
                                                </select>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: var(--gray);">
                                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 15px;"></i>
                                <p>Nenhum exercício cadastrado no sistema.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="observacoes">
                        <i class="fas fa-sticky-note"></i> Observações Adicionais
                    </label>
                    <textarea id="observacoes" name="observacoes" rows="3" placeholder="Digite observações específicas para o aluno..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-save"></i> Criar Ficha de Treino
                </button>
            </form>
        </div>
    </div>

    <div id="exerciciosCounter" class="exercicios-counter" style="display: none;">
        <i class="fas fa-dumbbell"></i>
        <span id="counterText">0 exercícios selecionados</span>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.exercicio-checkbox');
    const form = document.getElementById('formTreino');
    const counter = document.getElementById('exerciciosCounter');
    const counterText = document.getElementById('counterText');
    
    function updateCounter() {
        const selected = document.querySelectorAll('.exercicio-checkbox:checked').length;
        counterText.textContent = `${selected} exercício${selected !== 1 ? 's' : ''} selecionado${selected !== 1 ? 's' : ''}`;
        
        if (selected > 0) {
            counter.style.display = 'flex';
        } else {
            counter.style.display = 'none';
        }
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const select = this.parentNode.querySelector('.exercicio-dia');
            select.disabled = !this.checked;
            updateCounter();
        });
    });
    
    form.addEventListener('submit', function(e) {
        const checkboxesMarcados = document.querySelectorAll('.exercicio-checkbox:checked');
        
        if (checkboxesMarcados.length === 0) {
            e.preventDefault();
            alert('❌ Selecione pelo menos um exercício!');
            return;
        }
        
        // Remover inputs antigos antes de adicionar novos
        document.querySelectorAll('input[name="exercicios[]"]').forEach(el => el.remove());
        
        // Criar um input hidden para cada exercício selecionado
        checkboxesMarcados.forEach(checkbox => {
            const select = checkbox.parentNode.querySelector('.exercicio-dia');
            const exercicioData = {
                exercicio_id: checkbox.dataset.exercicioId,
                dia: select.value,
                series: checkbox.dataset.series,
                repeticoes: checkbox.dataset.repeticoes,
                descanso: checkbox.dataset.descanso
            };
            
            const inputHidden = document.createElement('input');
            inputHidden.type = 'hidden';
            inputHidden.name = 'exercicios[]';
            inputHidden.value = JSON.stringify(exercicioData);
            form.appendChild(inputHidden);
        });
    });

    // Inicializar contador
    updateCounter();
});
</script>
</body>
</html>