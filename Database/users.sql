-- Import this SQL in phpMyAdmin ONCE after your urbansync database exists.
-- Admin login required for marking: username admin / password admin.
-- IMPORTANT: The password is stored as a bcrypt HASH, not plain text.
-- This demo credential must be changed before any public deployment.
USE urbansync;

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERT IGNORE keeps an existing admin account unchanged if reimported.
INSERT IGNORE INTO users (username, password_hash)
VALUES ('admin', '$2y$12$ZBlbw.IF.Buh.rejWpse5eN5chJQE82fgW7zCP6l/yhvaR7yyPE/.');
