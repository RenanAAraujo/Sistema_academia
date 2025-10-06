<?php
require_once 'config.php';

function getObjetivoTexto($objetivo) {
    $objetivos = [
        'emagrecimento' => 'Emagrecimento',
        'hipertrofia' => 'Hipertrofia (Ganho de Massa)',
        'forca' => 'Força',
        'condicionamento' => 'Condicionamento Físico',
        'definicao' => 'Definição Muscular'
    ];
    return $objetivos[$objetivo] ?? $objetivo;
}

function getNivelTexto($nivel) {
    $niveis = [
        'iniciante' => 'Iniciante',
        'intermediario' => 'Intermediário',
        'avancado' => 'Avançado'
    ];
    return $niveis[$nivel] ?? $nivel;
}

function getGrupoMuscularTexto($grupo) {
    $grupos = [
        'peito' => 'Peito',
        'costas' => 'Costas',
        'pernas' => 'Pernas',
        'ombros' => 'Ombros',
        'biceps' => 'Bíceps',
        'triceps' => 'Tríceps',
        'cardio' => 'Cardio',
        'abdominal' => 'Abdominal'
    ];
    return $grupos[$grupo] ?? $grupo;
}

function getAlunos($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE tipo = 'aluno' ORDER BY nome");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFichaAluno($pdo, $aluno_id) {
    $stmt = $pdo->prepare("
        SELECT ft.*, u.nome as instrutor_nome 
        FROM fichas_treino ft 
        JOIN usuarios u ON ft.instrutor_id = u.id 
        WHERE ft.aluno_id = ? 
        ORDER BY ft.data_criacao DESC 
        LIMIT 1
    ");
    $stmt->execute([$aluno_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getExerciciosFicha($pdo, $ficha_id) {
    $stmt = $pdo->prepare("
        SELECT fe.*, e.nome as exercicio_nome, e.grupo_muscular 
        FROM ficha_exercicios fe 
        JOIN exercicios e ON fe.exercicio_id = e.id 
        WHERE fe.ficha_id = ? 
        ORDER BY fe.dia, fe.ordem
    ");
    $stmt->execute([$ficha_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>