-- friendship_migration.sql
-- Run this on your 'socialnet' database to add friend-connection support.

USE socialnet;

CREATE TABLE IF NOT EXISTS friendship (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    requester_id  INT NOT NULL,          -- the one who sent the request
    receiver_id   INT NOT NULL,          -- the one who received it
    status        ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_pair (requester_id, receiver_id),
    FOREIGN KEY (requester_id) REFERENCES account(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id)  REFERENCES account(id) ON DELETE CASCADE
);
