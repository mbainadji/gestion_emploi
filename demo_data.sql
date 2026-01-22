
-- Demo Data for ICT-L2
INSERT INTO departments (name) VALUES ('Informatique');
INSERT INTO programs (department_id, name) VALUES (1, 'ICT4D');
INSERT INTO classes (program_id, name, size, semester_id) VALUES (1, 'ICT-L2', 120, 1);

-- Teachers
INSERT INTO users (username, password, role, full_name) VALUES ('monthe', 'pass123', 'teacher', 'MONTHE');
INSERT INTO teachers (user_id, name, department_id) VALUES (2, 'MONTHE', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('eone', 'pass123', 'teacher', 'EONE');
INSERT INTO teachers (user_id, name, department_id) VALUES (3, 'EONE', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('kwette', 'pass123', 'teacher', 'KWETTE');
INSERT INTO teachers (user_id, name, department_id) VALUES (4, 'KWETTE', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('musima', 'pass123', 'teacher', 'MUSIMA');
INSERT INTO teachers (user_id, name, department_id) VALUES (5, 'MUSIMA', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('nkondock', 'pass123', 'teacher', 'NKONDOCK');
INSERT INTO teachers (user_id, name, department_id) VALUES (6, 'NKONDOCK', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('mossebo', 'pass123', 'teacher', 'MOSSEBO');
INSERT INTO teachers (user_id, name, department_id) VALUES (7, 'MOSSEBO', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('mbous', 'pass123', 'teacher', 'MBOUS');
INSERT INTO teachers (user_id, name, department_id) VALUES (8, 'MBOUS', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('sevany', 'pass123', 'teacher', 'SEVANY');
INSERT INTO teachers (user_id, name, department_id) VALUES (9, 'SEVANY', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('nkouandou', 'pass123', 'teacher', 'NKOUANDOU');
INSERT INTO teachers (user_id, name, department_id) VALUES (10, 'NKOUANDOU', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('biyong', 'pass123', 'teacher', 'BIYONG');
INSERT INTO teachers (user_id, name, department_id) VALUES (11, 'BIYONG', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('videme', 'pass123', 'teacher', 'VIDEME');
INSERT INTO teachers (user_id, name, department_id) VALUES (12, 'VIDEME', 1);

INSERT INTO users (username, password, role, full_name) VALUES ('ekono', 'pass123', 'teacher', 'EKONO');
INSERT INTO teachers (user_id, name, department_id) VALUES (13, 'EKONO', 1);

-- Courses
INSERT INTO courses (code, title, program_id) VALUES ('ICT207', 'UE ICT207', 1);
INSERT INTO courses (code, title, program_id) VALUES ('ICT203', 'UE ICT203', 1);
INSERT INTO courses (code, title, program_id) VALUES ('ICT205', 'UE ICT205', 1);
INSERT INTO courses (code, title, program_id) VALUES ('ENG203', 'UE ENG203', 1);
INSERT INTO courses (code, title, program_id) VALUES ('ICT201', 'UE ICT201', 1);
INSERT INTO courses (code, title, program_id) VALUES ('ICT213', 'UE ICT213', 1);
INSERT INTO courses (code, title, program_id) VALUES ('ICT215', 'UE ICT215', 1);
INSERT INTO courses (code, title, program_id) VALUES ('ICT217', 'UE ICT217', 1);
INSERT INTO courses (code, title, program_id) VALUES ('FRA203', 'UE FRA203', 1);

-- Rooms
INSERT INTO rooms (name, capacity) VALUES ('S003', 200);
INSERT INTO rooms (name, capacity) VALUES ('S008', 200);

-- Timetable entries (Partial demo based on image)
-- Lundi 11:30 ICT207-G1 MONTHE
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) 
VALUES (1, (SELECT id FROM courses WHERE code='ICT207'), (SELECT id FROM teachers WHERE name='MONTHE'), 1, (SELECT id FROM slots WHERE day='Lundi' AND start_time='11:30'), 1, 'G1');

-- Mardi 08:00 ICT207-G2 MONTHE
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) 
VALUES (1, (SELECT id FROM courses WHERE code='ICT207'), (SELECT id FROM teachers WHERE name='MONTHE'), 1, (SELECT id FROM slots WHERE day='Mardi' AND start_time='08:00'), 1, 'G2');

-- Mercredi 08:00 ICT203-G2 EONE
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) 
VALUES (1, (SELECT id FROM courses WHERE code='ICT203'), (SELECT id FROM teachers WHERE name='EONE'), 1, (SELECT id FROM slots WHERE day='Mercredi' AND start_time='08:00'), 1, 'G2');

-- Add some more for Samedi as requested in the prompt demo description
-- Samedi 08:00 ENG203-G1 MUSIMA
INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name) 
VALUES (1, (SELECT id FROM courses WHERE code='ENG203'), (SELECT id FROM teachers WHERE name='MUSIMA'), 2, (SELECT id FROM slots WHERE day='Samedi' AND start_time='08:00'), 1, 'G1');
