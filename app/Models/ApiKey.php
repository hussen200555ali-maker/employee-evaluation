<?php
namespace App\Models;

use App\Core\Model;

class ApiKey extends Model
{
    protected string $table = 'api_keys';

    /**
     * Find an API key record by the key value
     */
    public function findByKey(string $key): ?object
    {
        $this->db->query("SELECT * FROM {$this->table} WHERE api_key = :key AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
        $this->db->bind(':key', $key);
        $result = $this->db->fetch();
        return $result ?: null;
    }

    /**
     * Check if the API key has a specific permission
     */
    public function hasPermission(object $apiKey, string $permission): bool
    {
        if (empty($apiKey->permissions)) {
            return false;
        }
        $perms = json_decode($apiKey->permissions, true);
        return in_array($permission, $perms);
    }

    /**
     * Update last_used_at timestamp
     */
    public function touch(int $id): void
    {
        $this->db->query("UPDATE {$this->table} SET last_used_at = NOW() WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }

    /**
     * Generate a new API key
     */
    public function generate(int $userId, string $name, array $permissions = [], ?string $expiresAt = null): string
    {
        $key = bin2hex(random_bytes(32));
        $this->db->query("INSERT INTO {$this->table} (user_id, api_key, name, permissions, expires_at) VALUES (:uid, :key, :name, :perms, :exp)");
        $this->db->bind(':uid', $userId);
        $this->db->bind(':key', $key);
        $this->db->bind(':name', $name);
        $this->db->bind(':perms', json_encode($permissions));
        $this->db->bind(':exp', $expiresAt);
        $this->db->execute();
        return $key;
    }

    /**
     * Get all keys for a user
     */
    public function getUserKeys(int $userId): array
    {
        $this->db->query("SELECT * FROM {$this->table} WHERE user_id = :uid ORDER BY created_at DESC");
        $this->db->bind(':uid', $userId);
        return $this->db->fetchAll();
    }

    /**
     * Revoke a key
     */
    public function revoke(int $id): void
    {
        $this->db->query("UPDATE {$this->table} SET is_active = 0 WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}
