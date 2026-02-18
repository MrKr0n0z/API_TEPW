<?php

require_once '../config/database.php';
require_once '../models/ApiUser.php';
require_once '../models/ApiToken.php';

class LoginResource
{
    private $db;
    private $user;
    private $token;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new ApiUser($this->db);
        $this->token = new ApiToken($this->db);
    }

    // POST /api/v1/login
    public function login()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(array("message" => "Se requiere username y password"));
            return;
        }

        // Buscar usuario
        if (!$this->user->findByUsername($data->username)) {
            http_response_code(401);
            echo json_encode(array("message" => "Credenciales inválidas"));
            return;
        }

        // Verificar que el usuario esté activo
        if (!$this->user->isActive()) {
            http_response_code(403);
            echo json_encode(array("message" => "Usuario inactivo"));
            return;
        }

        // Verificar contraseña
        if (!$this->user->verifyPassword($data->password)) {
            http_response_code(401);
            echo json_encode(array("message" => "Credenciales inválidas"));
            return;
        }

        // Generar token
        $tokenData = $this->token->generateToken($this->user->id, 24);

        if ($tokenData) {
            http_response_code(200);
            echo json_encode($tokenData);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Error al generar token"));
        }
    }

    // POST /api/v1/logout
    public function logout()
    {
        header("Content-Type: application/json");

        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(array("message" => "Token no proporcionado"));
            return;
        }

        $token = $matches[1];

        if ($this->token->revokeToken($token)) {
            http_response_code(200);
            echo json_encode(array("message" => "Sesión cerrada exitosamente"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Error al cerrar sesión"));
        }
    }

    // POST /api/v1/register
    public function register()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->username) || empty($data->email) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(array("message" => "Se requiere username, email y password"));
            return;
        }

        // Verificar si el usuario ya existe
        if ($this->user->findByUsername($data->username)) {
            http_response_code(409);
            echo json_encode(array("message" => "El username ya está en uso"));
            return;
        }

        if ($this->user->findByEmail($data->email)) {
            http_response_code(409);
            echo json_encode(array("message" => "El email ya está registrado"));
            return;
        }

        // Crear usuario
        $this->user->username = $data->username;
        $this->user->email = $data->email;
        $this->user->password_hash = password_hash($data->password, PASSWORD_BCRYPT);
        $this->user->status = 'ACTIVE';

        if ($this->user->create()) {
            http_response_code(201);
            echo json_encode(array(
                "message" => "Usuario registrado exitosamente",
                "user_id" => $this->user->id
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo registrar el usuario"));
        }
    }
}
?>
