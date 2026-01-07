CREATE TABLE IF NOT EXISTS user_visits (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    public_id CHAR(6) NOT NULL,
    visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip VARBINARY(16) NULL,
    user_agent VARCHAR(255) NULL,
    page VARCHAR(255) NULL,
    INDEX idx_public_id (public_id),
    INDEX idx_visited_at (visited_at)
);






