<?php
class ApiToken
{
    private $conn;
    private $table_name = "api_tokens";

    public $id;
    public $user_id;
    public $token;
    public $expires_at;
    public $revoked;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, token=:token, expires_at=:expires_at, revoked=:revoked";

        $stmt = $this->conn->prepare($query);

        $this->revoked = $this->revoked ?? false;

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":token", $this->token);
        $stmt->bindParam(":expires_at", $this->expires_at);
        $stmt->bindParam(":revoked", $this->revoked, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function generateToken($userId, $hoursValid = 24)
    {
        $this->user_id = $userId;
        $this->token = bin2hex(random_bytes(32));
        $this->expires_at = date('Y-m-d H:i:s', strtotime("+{$hoursValid} hours"));
        $this->revoked = false;

        if ($this->create()) {
            return [
                'access_token' => $this->token,
                'expires_at' => $this->expires_at
            ];
        }
        return false;
    }

    public function validateToken($token)
    {
        $query = "SELECT t.id, t.user_id, t.token, t.expires_at, t.revoked, t.created_at,
                         u.username, u.email, u.status
                  FROM " . $this->table_name . " t
                  INNER JOIN api_users u ON t.user_id = u.id
                  WHERE t.token = :token 
                  AND t.revoked = FALSE
                  AND t.expires_at > NOW()
                  AND u.status = 'ACTIVE'
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->token = $row['token'];
            $this->expires_at = $row['expires_at'];
            $this->revoked = $row['revoked'];
            $this->created_at = $row['created_at'];
            return [
                'valid' => true,
                'user_id' => $row['user_id'],
                'username' => $row['username'],
                'email' => $row['email']
            ];
        }
        return ['valid' => false];
    }

    public function revokeToken($token)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET revoked = TRUE 
                  WHERE token = :token";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);

        return $stmt->execute();
    }

    public function revokeAllUserTokens($userId)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET revoked = TRUE 
                  WHERE user_id = :user_id AND revoked = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    public function cleanExpiredTokens()
    {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE expires_at < NOW() OR revoked = TRUE";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>
