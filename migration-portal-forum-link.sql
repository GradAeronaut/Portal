CREATE TABLE IF NOT EXISTS portal_kneeboard_link (
    public_id     CHAR(6) NOT NULL PRIMARY KEY,
    kneeboard_user_id INT NOT NULL UNIQUE,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);







