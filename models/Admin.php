<?php
class Admin
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function login($email, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            return true;
        }
        return false;
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['admin_id']);
    }

    public function logout()
    {
        session_unset();
        session_destroy();
    }
}
