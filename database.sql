-- ============================================
-- Base de datos: sistema de roles y permisos
-- ============================================

CREATE DATABASE IF NOT EXISTS roles_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE roles_app;

-- Tabla de roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

-- Tabla de permisos
CREATE TABLE permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

-- Relación roles <-> permisos
CREATE TABLE roles_permisos (
    rol_id INT NOT NULL,
    permiso_id INT NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    token_recuperacion VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Tabla de productos (contenido principal)
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    creado_por INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

-- Tabla de historial de acceso
CREATE TABLE historial_acceso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    email VARCHAR(150),
    accion VARCHAR(255),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- ============================================
-- Datos iniciales
-- ============================================

-- Roles
INSERT INTO roles (nombre, descripcion) VALUES
('Administrador', 'Acceso completo al sistema'),
('Editor', 'Puede gestionar productos, no usuarios'),
('Usuario', 'Solo puede ver productos');

-- Permisos
INSERT INTO permisos (nombre, descripcion) VALUES
('leer', 'Ver recursos'),
('escribir', 'Crear y editar recursos'),
('eliminar', 'Eliminar recursos'),
('gestionar_usuarios', 'Administrar usuarios y roles');

-- Asignar permisos a roles
-- Administrador: todos los permisos
INSERT INTO roles_permisos (rol_id, permiso_id) VALUES
(1,1),(1,2),(1,3),(1,4);

-- Editor: leer, escribir, eliminar
INSERT INTO roles_permisos (rol_id, permiso_id) VALUES
(2,1),(2,2),(2,3);

-- Usuario: solo leer
INSERT INTO roles_permisos (rol_id, permiso_id) VALUES
(3,1);

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol_id) VALUES
('Administrador', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Productos de ejemplo
INSERT INTO productos (nombre, descripcion, precio, stock, creado_por) VALUES
('Laptop Lenovo IdeaPad', 'Procesador i5, 8GB RAM, 512GB SSD', 12999.00, 15, 1),
('Mouse Inalámbrico Logitech', 'Mouse ergonómico con receptor USB', 450.00, 50, 1),
('Teclado Mecánico', 'Switches azules, retroiluminado RGB', 1200.00, 30, 1);
