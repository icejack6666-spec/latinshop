<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class Comments
{
    private Database $db;
    private static ?Comments $instance = null;

    private function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getInstance(): Comments
    {
        if (self::$instance === null) {
            self::$instance = new Comments();
        }
        return self::$instance;
    }


    

    public function add(int $user_id, string $page_slug, string $content, string $user_role): array
    {
        if (!in_array($user_role, ['client', 'verified', 'admin'], true)) {
            return [
                'success' => false,
                'error'   => 'Tu cuenta aún no ha sido verificada. Un administrador debe aprobarte primero.'
            ];
        }

        $content = trim($content);

        if (strlen($content) < 3) {
            return ['success' => false, 'error' => 'El comentario es demasiado corto.'];
        }

        if (strlen($content) > 1000) {
            return ['success' => false, 'error' => 'El comentario no puede superar los 1000 caracteres.'];
        }

        $page_slug = trim($page_slug);
        if (empty($page_slug) || strlen($page_slug) > 100) {
            return ['success' => false, 'error' => 'Página no válida.'];
        }

        $pendientes = $this->db->count(
            "SELECT COUNT(*) FROM comments WHERE user_id = ? AND status = 'pending'",
            [$user_id]
        );

        if ($pendientes >= 3) {
            return [
                'success' => false,
                'error'   => 'Tienes comentarios pendientes de revisión. Espera a que sean aprobados antes de enviar más.'
            ];
        }

        $status = ($user_role === 'admin') ? 'approved' : 'pending';

        $comment_id = $this->db->insert(
            "INSERT INTO comments (user_id, page_slug, content, status, created_at)
             VALUES (?, ?, ?, ?, NOW())",
            [$user_id, $page_slug, $content, $status]
        );

        $mensaje = ($status === 'approved')
            ? 'Comentario publicado.'
            : 'Comentario enviado. Será visible una vez que un administrador lo apruebe.';

        return [
            'success'    => true,
            'message'    => $mensaje,
            'comment_id' => $comment_id,
        ];
    }


    public function getApproved(string $page_slug, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT
                c.id,
                c.content,
                c.rating,
                c.created_at,
                c.page_slug,
                u.username,
                u.avatar_url,
                u.role AS user_role
             FROM comments c
             INNER JOIN users u ON c.user_id = u.id
             WHERE c.page_slug = ? AND c.status = 'approved'
             ORDER BY c.created_at DESC
             LIMIT ?",
            [$page_slug, $limit]
        );
    }


    public function getPending(int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT
                c.id,
                c.content,
                c.page_slug,
                c.status,
                c.created_at,
                u.id       AS user_id,
                u.username,
                u.email,
                u.avatar_url,
                u.role     AS user_role
             FROM comments c
             INNER JOIN users u ON c.user_id = u.id
             WHERE c.status = 'pending'
             ORDER BY c.created_at ASC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public function countPending(): int
    {
        return $this->db->count(
            "SELECT COUNT(*) FROM comments WHERE status = 'pending'"
        );
    }


    public function getAll(int $limit = 20, int $offset = 0, string $status = ''): array
    {
        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return $this->db->fetchAll(
                "SELECT
                    c.id, c.content, c.page_slug, c.status,
                    c.admin_note, c.created_at, c.updated_at,
                    u.id AS user_id, u.username, u.email, u.avatar_url
                 FROM comments c
                 INNER JOIN users u ON c.user_id = u.id
                 WHERE c.status = ?
                 ORDER BY c.created_at DESC
                 LIMIT ? OFFSET ?",
                [$status, $limit, $offset]
            );
        }

        return $this->db->fetchAll(
            "SELECT
                c.id, c.content, c.page_slug, c.status,
                c.admin_note, c.created_at, c.updated_at,
                u.id AS user_id, u.username, u.email, u.avatar_url
             FROM comments c
             INNER JOIN users u ON c.user_id = u.id
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public function countAll(string $status = ''): int
    {
        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return $this->db->count(
                "SELECT COUNT(*) FROM comments WHERE status = ?",
                [$status]
            );
        }
        return $this->db->count("SELECT COUNT(*) FROM comments");
    }


    public function approve(int $comment_id, string $admin_note = ''): bool
    {
        $afectadas = $this->db->update(
            "UPDATE comments
             SET status = 'approved', admin_note = ?, updated_at = NOW()
             WHERE id = ? AND status = 'pending'",
            [$admin_note, $comment_id]
        );
        return $afectadas > 0;
    }

    public function reject(int $comment_id, string $admin_note = ''): bool
    {
        $afectadas = $this->db->update(
            "UPDATE comments
             SET status = 'rejected', admin_note = ?, updated_at = NOW()
             WHERE id = ?",
            [$admin_note, $comment_id]
        );
        return $afectadas > 0;
    }

    public function delete(int $comment_id): bool
    {
        $afectadas = $this->db->update(
            "DELETE FROM comments WHERE id = ?",
            [$comment_id]
        );
        return $afectadas > 0;
    }


    public function getByUser(int $user_id, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT id, page_slug, content, status, admin_note, created_at
             FROM comments
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$user_id, $limit]
        );
    }


    public static function timeAgo(string $datetime): string
    {
        $ahora     = new DateTime();
        $fecha     = new DateTime($datetime);
        $diff      = $ahora->diff($fecha);

        if ($diff->i < 1 && $diff->h === 0 && $diff->d === 0) {
            return 'hace un momento';
        } elseif ($diff->h === 0 && $diff->d === 0) {
            $min = $diff->i;
            return 'hace ' . $min . ' ' . ($min === 1 ? 'minuto' : 'minutos');
        } elseif ($diff->d === 0) {
            $h = $diff->h;
            return 'hace ' . $h . ' ' . ($h === 1 ? 'hora' : 'horas');
        } elseif ($diff->d < 7) {
            $d = $diff->d;
            return 'hace ' . $d . ' ' . ($d === 1 ? 'día' : 'días');
        } else {
            return $fecha->format('d/m/Y');
        }
    }

    public static function getInitials(string $username): string
    {
        $partes = explode(' ', trim($username));
        if (count($partes) >= 2) {
            return mb_strtoupper(mb_substr($partes[0], 0, 1) . substr($partes[1], 0, 1));
        }
        return mb_strtoupper(mb_substr($username, 0, 2));
    }

    public static function slugToLabel(string $slug): string
    {
        $partes = explode('/', $slug);
        $partes = array_map(function($p) {
            return ucwords(str_replace('-', ' ', $p));
        }, $partes);
        return implode(' / ', $partes);
    }

    



    public function setRating(int $comment_id, int $rating): bool
    {
        if ($rating < 1 || $rating > 5) return false;

        $afectadas = $this->db->update(
            "UPDATE comments SET rating = ? WHERE id = ?",
            [$rating, $comment_id]
        );

        return $afectadas > 0;
    }

    public function getAverageRating(string $page_slug): float
    {
        $result = $this->db->fetch(
            "SELECT AVG(rating) as avg_rating, COUNT(rating) as total
             FROM comments
             WHERE page_slug = ? AND status = 'approved' AND rating IS NOT NULL",
            [$page_slug]
        );

        return $result ? round((float)$result['avg_rating'], 1) : 0.0;
    }


    public function vote(int $comment_id, int $user_id, string $type): array
    {
        if (!in_array($type, ['like', 'dislike'], true)) {
            return ['success' => false, 'error' => 'Tipo de voto inválido.'];
        }

        $existing = $this->db->fetch(
            "SELECT id, type FROM comment_likes WHERE comment_id = ? AND user_id = ?",
            [$comment_id, $user_id]
        );

        if ($existing) {
            if ($existing['type'] === $type) {
                $this->db->update(
                    "DELETE FROM comment_likes WHERE id = ?",
                    [$existing['id']]
                );
                $action = 'removed';
            } else {
                $this->db->update(
                    "UPDATE comment_likes SET type = ?, created_at = NOW() WHERE id = ?",
                    [$type, $existing['id']]
                );
                $action = 'changed';
            }
        } else {
            $this->db->insert(
                "INSERT INTO comment_likes (comment_id, user_id, type, created_at)
                 VALUES (?, ?, ?, NOW())",
                [$comment_id, $user_id, $type]
            );
            $action = 'added';
        }

        $counts = $this->getVoteCounts($comment_id);

        return [
            'success' => true,
            'action'  => $action,
            'likes'   => $counts['likes'],
            'dislikes'=> $counts['dislikes'],
        ];
    }

    public function getVoteCounts(int $comment_id): array
    {
        $likes = $this->db->count(
            "SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND type = 'like'",
            [$comment_id]
        );
        $dislikes = $this->db->count(
            "SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND type = 'dislike'",
            [$comment_id]
        );

        return ['likes' => $likes, 'dislikes' => $dislikes];
    }

    public function getUserVote(int $comment_id, int $user_id): ?string
    {
        $result = $this->db->fetch(
            "SELECT type FROM comment_likes WHERE comment_id = ? AND user_id = ?",
            [$comment_id, $user_id]
        );

        return $result ? $result['type'] : null;
    }

    public function getUserVotesForPage(string $page_slug, int $user_id): array
    {
        $rows = $this->db->fetchAll(
            "SELECT cl.comment_id, cl.type
             FROM comment_likes cl
             INNER JOIN comments c ON cl.comment_id = c.id
             WHERE c.page_slug = ? AND cl.user_id = ?",
            [$page_slug, $user_id]
        );

        $votes = [];
        foreach ($rows as $row) {
            $votes[$row['comment_id']] = $row['type'];
        }
        return $votes;
    }

}
