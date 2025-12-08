CREATE DATABASE battleship;
USE battleship;

CREATE TABLE cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    row_index INT NOT NULL,
    col_index INT NOT NULL,
    bateau_id INT DEFAULT 0,
    touched TINYINT(1) DEFAULT 0
);
