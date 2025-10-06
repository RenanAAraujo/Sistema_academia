<?php
require_once 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isInstrutor() {
    return isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'instrutor';
}

function isAluno() {
    return isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'aluno';
}

function redirectIfNotLogged() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function redirectIfNotInstrutor() {
    redirectIfNotLogged();
    if (!isInstrutor()) {
        header('Location: dashboard.php');
        exit;
    }
}

function redirectIfNotAluno() {
    redirectIfNotLogged();
    if (!isAluno()) {
        header('Location: dashboard.php');
        exit;
    }
}
?>