<?php

declare(strict_types=1);

/**
 * SupportSchema
 *
 * Schema SQLite para las tablas del sistema de soporte.
 * Se usa en DatabaseStub::loadSchema() antes de correr tests de integración.
 *
 * Diferencias con MySQL:
 *  - Sin UNSIGNED, sin ENGINE=InnoDB, sin COLLATE
 *  - ENUM reemplazado por TEXT + CHECK constraint
 *  - AUTO_INCREMENT → INTEGER PRIMARY KEY AUTOINCREMENT
 *  - DATETIME → TEXT (SQLite guarda fechas como texto ISO)
 */
final class SupportSchema
{
    public static function get(): string
    {
        return <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            username     TEXT NOT NULL UNIQUE,
            email        TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role         TEXT NOT NULL DEFAULT 'client'
                         CHECK(role IN ('pending','client','verified','admin','banned')),
            avatar_url   TEXT NULL,
            created_at   TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS support_tickets (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            assigned_to  INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
            subject      TEXT NOT NULL,
            status       TEXT NOT NULL DEFAULT 'open'
                         CHECK(status IN ('open','pending','answered','closed')),
            priority     TEXT NOT NULL DEFAULT 'medium'
                         CHECK(priority IN ('low','medium','high','urgent')),
            category     TEXT NOT NULL DEFAULT 'other'
                         CHECK(category IN ('technical','billing','account','other')),
            last_reply_at TEXT NULL,
            closed_at    TEXT NULL,
            created_at   TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS support_messages (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id   INTEGER NOT NULL REFERENCES support_tickets(id) ON DELETE CASCADE,
            user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            body        TEXT NOT NULL,
            is_internal INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS support_attachments (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            message_id    INTEGER NOT NULL REFERENCES support_messages(id) ON DELETE CASCADE,
            ticket_id     INTEGER NOT NULL REFERENCES support_tickets(id) ON DELETE CASCADE,
            uploaded_by   INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            original_name TEXT NOT NULL,
            stored_name   TEXT NOT NULL,
            mime_type     TEXT NOT NULL,
            size_bytes    INTEGER NOT NULL,
            deleted_at    TEXT NULL,
            created_at    TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS ticket_action_log (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id  INTEGER NOT NULL REFERENCES support_tickets(id) ON DELETE CASCADE,
            user_id    INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
            action     TEXT NOT NULL,
            detail     TEXT NULL,
            ip_address TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS rate_limits (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            action_key TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS api_keys (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            name         TEXT NOT NULL,
            key_hash     TEXT NOT NULL UNIQUE,
            key_prefix   TEXT NOT NULL,
            scope        TEXT NOT NULL DEFAULT '[]',
            is_active    INTEGER NOT NULL DEFAULT 1,
            last_used_at TEXT NULL,
            expires_at   TEXT NULL,
            created_at   TEXT NOT NULL DEFAULT (datetime('now'))
        );
        SQL;
    }
}