<?php

namespace App\Interfaces;

interface AuthInterface
{
    public function register(array $data);
    public function login(array $credentials);
    public function logout();
    public function refresh();
}
