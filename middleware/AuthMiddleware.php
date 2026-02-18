<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ApiToken.php';

class AuthMiddleware
{
    private static $db;
    private static $token;

    private static function init()
    {
        if (self::$db === null) {
            $database = new Database();
            self::$db = $database->getConnection();
            self::$token = new ApiToken(self::$db);
        }
    }

    public static function authenticate()
    {
        self::init();

        header("Content-Type: application/json");

        // Obtener el token del header Authorization
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(array("message" => "Token de autenticación no proporcionado"));
            exit();
        }

        // Extraer el token del formato "Bearer {token}"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(array("message" => "Formato de token inválido. Use: Bearer {token}"));
            exit();
        }

        $token = $matches[1];

        // Validar el token
        $validation = self::$token->validateToken($token);

        if (!$validation['valid']) {
            http_response_code(401);
            echo json_encode(array("message" => "Token inválido o expirado"));
            exit();
        }

        // Token válido, retornar información del usuario
        return $validation;
    }

    public static function getAuthenticatedUser()
    {
        return self::authenticate();
    }
}
?>
