
-- MySQL version of the database schema

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    full_name VARCHAR(100)
);

CREATE TABLE academic_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL
);

CREATE TABLE semesters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    academic_year_id INT,
    name VARCHAR(50) NOT NULL,
    FOREIGN KEY(academic_year_id) REFERENCES academic_years(id)
);

CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE programs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY(department_id) REFERENCES departments(id)
);

CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT,
    name VARCHAR(50) NOT NULL,
    size INT,
    semester_id INT,
    FOREIGN KEY(program_id) REFERENCES programs(id),
    FOREIGN KEY(semester_id) REFERENCES semesters(id)
);

CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    department_id INT,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(department_id) REFERENCES departments(id)
);

CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL,
    title VARCHAR(200) NOT NULL,
    program_id INT,
    FOREIGN KEY(program_id) REFERENCES programs(id)
);

CREATE TABLE teacher_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT,
    course_id INT,
    class_id INT,
    FOREIGN KEY(teacher_id) REFERENCES teachers(id),
    FOREIGN KEY(course_id) REFERENCES courses(id),
    FOREIGN KEY(class_id) REFERENCES classes(id)
);

CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    is_predefined TINYINT(1) DEFAULT 1
);

CREATE TABLE slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    day VARCHAR(20) NOT NULL,
    start_time VARCHAR(10) NOT NULL,
    end_time VARCHAR(10) NOT NULL
);

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
);

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
    FOREIGN KEY(class_id) REFERENCES classes(id),
    FOREIGN KEY(course_id) REFERENCES courses(id),
    FOREIGN KEY(teacher_id) REFERENCES teachers(id),
    FOREIGN KEY(room_id) REFERENCES rooms(id),
    FOREIGN KEY(slot_id) REFERENCES slots(id),
    FOREIGN KEY(semester_id) REFERENCES semesters(id)
);

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
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE settings (
    `key` VARCHAR(50) PRIMARY KEY,
    value TEXT
);

-- Initial Data
INSERT INTO users (username, password, role, full_name) VALUES ('admin', 'admin123', 'admin', 'Administrateur Système');

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
