
-- MySQL version of the database schema

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    full_name VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE academic_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE semesters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    academic_year_id INT,
    name VARCHAR(50) NOT NULL,
    FOREIGN KEY(academic_year_id) REFERENCES academic_years(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE programs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY(department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT,
    name VARCHAR(50) NOT NULL,
    size INT,
    semester_id INT,
    FOREIGN KEY(program_id) REFERENCES programs(id),
    FOREIGN KEY(semester_id) REFERENCES semesters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    department_id INT,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL,
    title VARCHAR(200) NOT NULL,
    program_id INT,
    FOREIGN KEY(program_id) REFERENCES programs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE teacher_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT,
    course_id INT,
    class_id INT,
    FOREIGN KEY(teacher_id) REFERENCES teachers(id),
    FOREIGN KEY(course_id) REFERENCES courses(id),
    FOREIGN KEY(class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    is_predefined TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    day VARCHAR(20) NOT NULL,
    start_time VARCHAR(10) NOT NULL,
    end_time VARCHAR(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE desiderata (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT,
    slot_id INT,
    semester_id INT,
    is_preferred TINYINT(1) DEFAULT 1,
    status VARCHAR(20) DEFAULT 'submitted',
    FOREIGN KEY(teacher_id) REFERENCES teachers(id),
    FOREIGN KEY(slot_id) REFERENCES slots(id),
    FOREIGN KEY(semester_id) REFERENCES semesters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE timetable (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT,
    course_id INT,
    teacher_id INT,
    room_id INT,
    slot_id INT,
    semester_id INT,
    week_number INT,
    date_passage DATE,
    group_name VARCHAR(10),
    type VARCHAR(20) DEFAULT 'CM',
    FOREIGN KEY(class_id) REFERENCES classes(id),
    FOREIGN KEY(course_id) REFERENCES courses(id),
    FOREIGN KEY(teacher_id) REFERENCES teachers(id),
    FOREIGN KEY(room_id) REFERENCES rooms(id),
    FOREIGN KEY(slot_id) REFERENCES slots(id),
    FOREIGN KEY(semester_id) REFERENCES semesters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50),
    table_name VARCHAR(50),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    `key` VARCHAR(50) PRIMARY KEY,
    value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Initial Data
INSERT INTO users (username, password, role, full_name) VALUES ('admin', '$2y$10$yIqcx1TjZg.FzO.p.h.p.e.p.h.p.e.p.h.p.e.p.h.p.e.p.h.p.e', 'admin', 'Administrateur Système');

INSERT INTO academic_years (name) VALUES ('2025/2026');
INSERT INTO semesters (academic_year_id, name) VALUES (1, 'Semestre 1');
INSERT INTO semesters (academic_year_id, name) VALUES (1, 'Semestre 2');

INSERT INTO slots (day, start_time, end_time) VALUES ('Lundi', '08:00', '11:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Lundi', '11:30', '14:30');
INSERT INTO slots (day, start_time, end_time) VALUES ('Lundi', '15:00', '18:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Mardi', '08:00', '11:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Mardi', '11:30', '14:30');
INSERT INTO slots (day, start_time, end_time) VALUES ('Mardi', '15:00', '18:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Mercredi', '08:00', '11:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Mercredi', '11:30', '14:30');
INSERT INTO slots (day, start_time, end_time) VALUES ('Mercredi', '15:00', '18:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Jeudi', '08:00', '11:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Jeudi', '11:30', '14:30');
INSERT INTO slots (day, start_time, end_time) VALUES ('Jeudi', '15:00', '18:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Vendredi', '08:00', '11:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Vendredi', '11:30', '14:30');
INSERT INTO slots (day, start_time, end_time) VALUES ('Vendredi', '15:00', '18:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Samedi', '08:00', '11:00');
INSERT INTO slots (day, start_time, end_time) VALUES ('Samedi', '11:30', '14:30');
INSERT INTO slots (day, start_time, end_time) VALUES ('Samedi', '15:00', '18:00');

-- =================================================================
-- DONNEES ICTL2 (SEMESTRE 1 2025-2026)
-- =================================================================

-- Departement & Filiere
INSERT INTO departments (id, name) VALUES (1, 'Informatique');
INSERT INTO programs (id, department_id, name) VALUES (1, 1, 'ICT4D');

-- Classe (ICTL2, Semestre 1)
INSERT INTO classes (id, program_id, name, size, semester_id) VALUES (1, 1, 'ICTL2', 100, 1);

-- Salles
INSERT INTO rooms (id, name, capacity) VALUES (1, 'S003', 50);
INSERT INTO rooms (id, name, capacity) VALUES (2, 'S008', 50);

-- Enseignants (Comptes utilisateurs & Profils)
-- Note: Admin est ID 1. Les enseignants commencent à ID 2.
INSERT INTO users (id, username, password, role, full_name) VALUES (2, 'monthe', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'MONTHE');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (1, 2, 'MONTHE', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (3, 'nkouandou', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'NKOUANDOU');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (2, 3, 'NKOUANDOU', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (4, 'nkondock', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'NKONDOCK');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (3, 4, 'NKONDOCK', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (5, 'musima', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'MUSIMA');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (4, 5, 'MUSIMA', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (6, 'biyong', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'BIYONG');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (5, 6, 'BIYONG', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (7, 'eone', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'EONE');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (6, 7, 'EONE', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (8, 'mossebo', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'MOSSEBO');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (7, 8, 'MOSSEBO', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (9, 'videme', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'VIDEME');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (8, 9, 'VIDEME', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (10, 'kwette', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'KWETTE');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (9, 10, 'KWETTE', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (11, 'mbous', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'MBOUS');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (10, 11, 'MBOUS', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (12, 'sevany', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'SEVANY');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (11, 12, 'SEVANY', 1);

INSERT INTO users (id, username, password, role, full_name) VALUES (13, 'ekono', '$2y$10$s7y/W2V.i5v.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q.U.q', 'teacher', 'EKONO');
INSERT INTO teachers (id, user_id, name, department_id) VALUES (12, 13, 'EKONO', 1);

-- Cours (Unités d'Enseignement)
INSERT INTO courses (id, code, title, program_id) VALUES (1, 'ICT207', 'ICT207', 1), (2, 'ICT217', 'ICT217', 1), (3, 'ICT201', 'ICT201', 1), (4, 'ENG203', 'English', 1), (5, 'FRA203', 'Français', 1), (6, 'ICT203', 'ICT203', 1), (7, 'ICT213', 'ICT213', 1), (8, 'ICT215', 'ICT215', 1), (9, 'ICT205', 'ICT205', 1);

-- Emploi du temps
-- Lundi
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 1, 1, 1, 2, 1, 'G1');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 2, 2, 1, 3, 1, 'G1');
-- Mardi
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 1, 1, 2, 4, 1, 'G2');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 3, 3, 1, 5, 1, 'G1');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 4, 4, 2, 6, 1, 'G2');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 5, 5, 1, 6, 1, 'G1');
-- Mercredi
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 6, 6, 2, 7, 1, 'G2');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 7, 7, 1, 8, 1, 'G1');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 8, 8, 1, 9, 1, 'G1');
-- Jeudi
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 9, 9, 2, 10, 1, 'G2');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 8, 10, 2, 11, 1, 'G2');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 2, 2, 2, 12, 1, 'G2');
-- Vendredi
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 9, 9, 1, 13, 1, 'G1');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 3, 3, 2, 14, 1, 'G2');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name, week_number) VALUES (1, 7, 12, 2, 15, 1, 'G2', 1);
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name, week_number) VALUES (1, 5, 5, 2, 15, 1, 'G2', 2);
-- Samedi
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 4, 4, 1, 16, 1, 'G1');
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) VALUES (1, 6, 11, 1, 17, 1, 'G1');
