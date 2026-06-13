<?php
// includes/auth_check.php

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function checkRole($role) {
    checkAuth();
    if ($_SESSION['role'] !== $role) {
        // Redirect to correct dashboard
        if ($_SESSION['role'] === 'employer') {
            header('Location: /employer/dashboard.php');
        } else {
            header('Location: /seeker/dashboard.php');
        }
        exit;
    }
}
