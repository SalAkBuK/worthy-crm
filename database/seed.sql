-- Seed default users (upserts). Uses bcrypt password hashes.
-- IMPORTANT: This does NOT remove anything. Safe to run multiple times.

INSERT INTO users (username, password_hash, role, employee_code, created_at)
VALUES
('kiran', '$2y$10$IUAaYkTVcZ4adyxPr.3XJuueTXCVuvS1b5nIZewYtOi7xDjO7afiy', 'ADMIN', (SELECT employee_code FROM employees WHERE LOWER(TRIM(employee_name)) LIKE 'kiran%' LIMIT 1), NOW()),
('abbas', '$2y$10$LGlqIxT.joedTnaAJt6qNOkjFEn1rPD3/mpDrN69oro8j6Ny8eynK', 'CEO', NULL, NOW()),
('afi', '$2y$10$oX1nqyb3BzOe0u/e.1ovzOqnxQ7tnxFSjslBH1dlWgpwKOUYx/jKS', 'AGENT', (SELECT employee_code FROM employees WHERE LOWER(TRIM(employee_name)) LIKE 'afi%' LIMIT 1), NOW()),
('altaleb', '$2y$10$oX1nqyb3BzOe0u/e.1ovzOqnxQ7tnxFSjslBH1dlWgpwKOUYx/jKS', 'AGENT', (SELECT employee_code FROM employees WHERE LOWER(TRIM(employee_name)) LIKE 'altaleb%' LIMIT 1), NOW()),
('aurangzeb', '$2y$10$oX1nqyb3BzOe0u/e.1ovzOqnxQ7tnxFSjslBH1dlWgpwKOUYx/jKS', 'AGENT', (SELECT employee_code FROM employees WHERE LOWER(TRIM(employee_name)) LIKE 'aurangzeb%' LIMIT 1), NOW()),
('benyam', '$2y$10$oX1nqyb3BzOe0u/e.1ovzOqnxQ7tnxFSjslBH1dlWgpwKOUYx/jKS', 'AGENT', (SELECT employee_code FROM employees WHERE LOWER(TRIM(employee_name)) LIKE 'benyam%' LIMIT 1), NOW()),
('mohamed', '$2y$10$oX1nqyb3BzOe0u/e.1ovzOqnxQ7tnxFSjslBH1dlWgpwKOUYx/jKS', 'AGENT', (SELECT employee_code FROM employees WHERE LOWER(TRIM(employee_name)) LIKE 'mohamed%' LIMIT 1), NOW()),
('raunak', '$2y$10$oX1nqyb3BzOe0u/e.1ovzOqnxQ7tnxFSjslBH1dlWgpwKOUYx/jKS', 'AGENT', (SELECT employee_code FROM employees WHERE LOWER(TRIM(employee_name)) LIKE 'raunak%' LIMIT 1), NOW()),
('sanaya', '$2y$10$oX1nqyb3BzOe0u/e.1ovzOqnxQ7tnxFSjslBH1dlWgpwKOUYx/jKS', 'AGENT', (SELECT employee_code FROM employees WHERE LOWER(TRIM(employee_name)) LIKE 'sanaya%' LIMIT 1), NOW())
ON DUPLICATE KEY UPDATE
  password_hash=VALUES(password_hash),
  role=VALUES(role),
  employee_code=VALUES(employee_code);
