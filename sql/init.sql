-- Employee Management System
-- Database: exam_db
-- Run once on container startup via Docker entrypoint

CREATE DATABASE IF NOT EXISTS exam_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE exam_db;

CREATE TABLE IF NOT EXISTS employees (
    id            INT            AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(30)    NOT NULL UNIQUE,
    first_name    VARCHAR(100)   NOT NULL,
    last_name     VARCHAR(100)   NOT NULL,
    email         VARCHAR(150)   NOT NULL UNIQUE,
    phone         VARCHAR(30)    DEFAULT NULL,
    department    VARCHAR(100)   NOT NULL,
    position      VARCHAR(100)   NOT NULL,
    salary        DECIMAL(10, 2) DEFAULT 0.00,
    hire_date     DATE           NOT NULL,
    status        ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP
);
