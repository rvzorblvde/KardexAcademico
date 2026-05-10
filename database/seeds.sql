-- SEEDER PARA KÁRDEX ACADÉMICO
USE kardex_academico;

-- 1. Carrera
INSERT INTO Carrera (clave_carrera, Nombre) 
VALUES ('IC', 'Ingeniería en Computación');

-- 2. Alumno (Contraseña: 123456)
INSERT INTO Alumno (id_alumno, Nombres, Apellido1, Apellido2, id_carrera, foto, fecha_nacimiento, password_hash)
VALUES (332508, 'Guillermo Jair', 'Muñoz', 'Amaro', 1, 'perfil.jpg', '2002-10-13', '$2y$10$8K1p/a06v7S1qW9K5Y5uOe6m1S8p1R8h7Y9J2/g1f1G1/1S1.1S1.');

-- 3. Profesores
INSERT INTO Profesor (id_profesor, Nombres, Apellido1, Apellido2, fecha_nacimiento, password_hash) VALUES 
(10001, 'Alberto', 'Ramos', 'Blanco', '1980-01-01', '$2y$10$8K1p/a06v7S1qW9K5Y5uOe6m1S8p1R8h7Y9J2/g1f1G1/1S1.1S1.'),
(10002, 'Francisco Javier', 'Torres', 'Reyes', '1975-05-15', '$2y$10$8K1p/a06v7S1qW9K5Y5uOe6m1S8p1R8h7Y9J2/g1f1G1/1S1.1S1.');

-- 4. Administrador
INSERT INTO Administrador (id_admin, Nombres, Apellido1, password_hash)
VALUES ('sysadmin67', 'Admin', 'Sistema', '$2y$10$8K1p/a06v7S1qW9K5Y5uOe6m1S8p1R8h7Y9J2/g1f1G1/1S1.1S1.');

-- 5. Materias de ejemplo
INSERT INTO Materia (clave_materia, id_carrera, nombre, creditos, num_parciales) VALUES 
('COMP-101', 1, 'Programación Web', 8, 3),
('COMP-202', 1, 'Bases de Datos', 8, 3);

-- 6. Semestre y Grupos
INSERT INTO Semestre (id_semestre, nombre, activo) VALUES ('2026-I', 'Ciclo 2026-I', TRUE);

INSERT INTO Grupo (num_grupo, id_profesor, clave_materia, id_semestre, salon, cupo) 
VALUES (1, 10002, 'COMP-101', '2026-I', 'L-14', 30);

-- 7. Inscripción tuya al grupo
INSERT INTO Inscripcion (id_alumno, num_grupo, id_profesor, clave_materia, id_semestre, Estado) 
VALUES (332508, 1, 10002, 'COMP-101', '2026-I', 'Activa');