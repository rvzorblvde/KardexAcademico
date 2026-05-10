CREATE DATABASE IF NOT EXISTS kardex_academico;
USE kardex_academico;

-- Carreras
CREATE TABLE Carrera (
    id_carrera INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave_carrera VARCHAR(3) NOT NULL UNIQUE,
    Nombre VARCHAR(80)
);

-- Alumnos
CREATE TABLE Alumno (
    id_alumno INT UNSIGNED PRIMARY KEY,
    Nombres VARCHAR(80) NOT NULL,
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),
    id_carrera INT UNSIGNED NOT NULL,
    foto VARCHAR(255),
    fecha_nacimiento DATE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, -- Para login de alumnos
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_alumno_carrera FOREIGN KEY (id_carrera) REFERENCES Carrera(id_carrera)
);

-- Profesores
CREATE TABLE Profesor (
    id_profesor INT UNSIGNED PRIMARY KEY,
    Nombres VARCHAR(80) NOT NULL,
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),
    foto VARCHAR(255),
    fecha_nacimiento DATE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, 
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

-- Administradores
CREATE TABLE Administrador (
    id_admin VARCHAR(11) PRIMARY KEY,
    Nombres VARCHAR(100) NOT NULL,
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),
    password_hash VARCHAR(255) NOT NULL, 
    Activo BOOLEAN NOT NULL DEFAULT TRUE
);

-- Materias
CREATE TABLE Materia (
    clave_materia VARCHAR(10) PRIMARY KEY,
    id_carrera INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    creditos INT UNSIGNED NOT NULL,
    num_parciales INT UNSIGNED NOT NULL,
    CONSTRAINT fk_materia_carrera FOREIGN KEY (id_carrera) REFERENCES Carrera(id_carrera), 
    CONSTRAINT chk_parciales_rango CHECK (num_parciales BETWEEN 3 AND 4)
);

-- Semestres
CREATE TABLE Semestre (
    id_semestre VARCHAR(10) PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    activo BOOLEAN NOT NULL DEFAULT FALSE
);

-- Grupos
CREATE TABLE Grupo (
    num_grupo INT UNSIGNED NOT NULL,
    id_profesor INT UNSIGNED NOT NULL,
    clave_materia VARCHAR(10) NOT NULL,
    id_semestre VARCHAR(10) NOT NULL,
    salon VARCHAR(15),
    cupo INT UNSIGNED NOT NULL,
    CONSTRAINT pk_grupo PRIMARY KEY (num_grupo, id_profesor, clave_materia, id_semestre),
    CONSTRAINT fk_id_profesor FOREIGN KEY (id_profesor) REFERENCES Profesor(id_profesor),
    CONSTRAINT fk_id_materia FOREIGN KEY (clave_materia) REFERENCES Materia(clave_materia),
    CONSTRAINT fk_id_semestre FOREIGN KEY (id_semestre) REFERENCES Semestre(id_semestre)
);

-- Horario
CREATE TABLE Horario(
    id_horario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    num_grupo INT UNSIGNED NOT NULL,
    id_profesor INT UNSIGNED NOT NULL,
    clave_materia VARCHAR(10) NOT NULL,
    id_semestre VARCHAR(10) NOT NULL,
    dia ENUM ('Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    CONSTRAINT fk_horario_grupo FOREIGN KEY (num_grupo, id_profesor, clave_materia, id_semestre) 
        REFERENCES Grupo (num_grupo, id_profesor, clave_materia, id_semestre),
    CONSTRAINT chk_horario_horas CHECK (hora_fin > hora_inicio)
);

-- Inscripcion
CREATE TABLE Inscripcion (
    id_alumno INT UNSIGNED NOT NULL,
    num_grupo INT UNSIGNED NOT NULL,
    id_profesor INT UNSIGNED NOT NULL,
    clave_materia VARCHAR(10) NOT NULL,
    id_semestre VARCHAR(10) NOT NULL,
    Estado ENUM ('Activa', 'Baja', 'Aprobada', 'Reprobada') NOT NULL DEFAULT 'Activa',
    CONSTRAINT pk_inscripcion PRIMARY KEY (id_alumno, num_grupo, id_profesor, clave_materia, id_semestre),
    CONSTRAINT fk_inscripcion_alumno FOREIGN KEY (id_alumno) REFERENCES Alumno (id_alumno),
    CONSTRAINT fk_inscripcion_grupo FOREIGN KEY (num_grupo, id_profesor, clave_materia, id_semestre) 
        REFERENCES Grupo (num_grupo, id_profesor, clave_materia, id_semestre)
);

-- Calificacion
CREATE TABLE Calificacion (
    id_cal INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT UNSIGNED NOT NULL,
    num_grupo INT UNSIGNED NOT NULL,
    id_profesor INT UNSIGNED NOT NULL,
    clave_materia VARCHAR(10) NOT NULL,
    id_semestre VARCHAR(10) NOT NULL,
    tipo ENUM ('Parcial', 'EO', 'EE', 'ET', 'ER') NOT NULL,
    num_parcial TINYINT UNSIGNED NULL,
    calificacion DECIMAL(4, 2) NOT NULL,
    inasistencias INT UNSIGNED DEFAULT 0,
    CONSTRAINT fk_calificacion_inscripcion FOREIGN KEY (id_alumno, num_grupo, id_profesor, clave_materia, id_semestre) 
        REFERENCES Inscripcion (id_alumno, num_grupo, id_profesor, clave_materia, id_semestre),
    CONSTRAINT chk_tipo_parcial CHECK ((Tipo = 'Parcial' AND num_parcial IS NOT NULL) OR (Tipo <> 'Parcial' AND num_parcial IS NULL)),
    CONSTRAINT chk_calificacion_rango CHECK (calificacion BETWEEN 0 AND 10),
    CONSTRAINT uq_calificacion UNIQUE (id_alumno, num_grupo, id_profesor, clave_materia, id_semestre, Tipo, num_parcial)
);