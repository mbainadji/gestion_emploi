
-- Database schema for University Timetable Management System

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL, -- 'admin', 'teacher', 'student'
    full_name TEXT
);

CREATE TABLE academic_years (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name TEXT NOT NULL -- e.g., '2025/2026'
);

CREATE TABLE semesters (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    academic_year_id INTEGER,
    name TEXT NOT NULL, -- 'Semestre 1', 'Semestre 2'
    FOREIGN KEY(academic_year_id) REFERENCES academic_years(id)
);

CREATE TABLE departments (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name TEXT NOT NULL
);

CREATE TABLE programs (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    department_id INTEGER,
    name TEXT NOT NULL, -- 'ICT4D', etc.
    FOREIGN KEY(department_id) REFERENCES departments(id)
);

CREATE TABLE classes (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    program_id INTEGER,
    name TEXT NOT NULL, -- 'ICT-L2'
    size INTEGER,
    semester_id INTEGER,
    FOREIGN KEY(program_id) REFERENCES programs(id),
    FOREIGN KEY(semester_id) REFERENCES semesters(id)
);

CREATE TABLE teachers (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER,
    name TEXT NOT NULL,
    email TEXT,
    department_id INTEGER,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(department_id) REFERENCES departments(id)
);

CREATE TABLE courses (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    code TEXT NOT NULL,
    title TEXT NOT NULL,
    program_id INTEGER,
    FOREIGN KEY(program_id) REFERENCES programs(id)
);

-- Mapping teacher to UE and class
CREATE TABLE teacher_courses (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    teacher_id INTEGER,
    course_id INTEGER,
    class_id INTEGER,
    FOREIGN KEY(teacher_id) REFERENCES teachers(id),
    FOREIGN KEY(course_id) REFERENCES courses(id),
    FOREIGN KEY(class_id) REFERENCES classes(id)
);

CREATE TABLE rooms (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name TEXT NOT NULL,
    capacity INTEGER NOT NULL,
    is_predefined TINYINT(1) DEFAULT 1
);

CREATE TABLE slots (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    day TEXT NOT NULL, -- 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'
    start_time TEXT NOT NULL, -- '08:00'
    end_time TEXT NOT NULL    -- '11:00'
);

CREATE TABLE desiderata (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    teacher_id INTEGER,
    slot_id INTEGER,
    semester_id INTEGER,
    is_preferred TINYINT(1) DEFAULT 1,
    status TEXT DEFAULT 'submitted', -- 'submitted', 'modified_by_admin'
    FOREIGN KEY(teacher_id) REFERENCES teachers(id),
    FOREIGN KEY(slot_id) REFERENCES slots(id),
    FOREIGN KEY(semester_id) REFERENCES semesters(id)
);

CREATE TABLE timetable (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    class_id INTEGER,
    course_id INTEGER,
    teacher_id INTEGER,
    room_id INTEGER,
    slot_id INTEGER,
    semester_id INTEGER,
    week_number INTEGER,
    date_passage DATE,
    group_name TEXT, -- 'G1', 'G2'
    FOREIGN KEY(class_id) REFERENCES classes(id),
    FOREIGN KEY(course_id) REFERENCES courses(id),
    FOREIGN KEY(teacher_id) REFERENCES teachers(id),
    FOREIGN KEY(room_id) REFERENCES rooms(id),
    FOREIGN KEY(slot_id) REFERENCES slots(id),
    FOREIGN KEY(semester_id) REFERENCES semesters(id)
);

CREATE TABLE history (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER,
    action TEXT, -- 'CREATE', 'UPDATE', 'DELETE'
    table_name TEXT,
    record_id INTEGER,
    old_value TEXT,
    new_value TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE settings (
    key TEXT PRIMARY KEY,
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
