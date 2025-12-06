CREATE TABLE IF NOT EXISTS account_deletions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    deletion_date DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES subscribers(sId)
);
