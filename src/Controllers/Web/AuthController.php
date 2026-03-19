<?php

class AuthController
{
    public function logout(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        AuthMiddleware::logout();
        header('Location: login.php');
        exit;
    }
}
