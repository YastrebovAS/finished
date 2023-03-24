CREATE TABLE author(
    id INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id),
    username varchar(20) NOT NULL,
    email varchar(20) NOT NULL,
    pas varchar(20) NOT NULL,
    reg_date date DEFAULT NULL,
);
CREATE TABLE deletion(
    invention_name varchar(20) NOT NULL,
    deletion_date date NOT NULL,
    reason varchar(20) NOT NULL,
    id_a INT NOT NULL,
);
CREATE TABLE evaluator(
    id INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id),
    username varchar(20) NOT NULL,
    passwd varchar(20) NOT NULL,
    email varchar(20) NOT NULL
);
CREATE TABLE redactor(
    id INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id),
    username varchar(20) NOT NULL,
    passwd varchar(20) NOT NULL,
    email varchar(20) NOT NULL
);
CREATE TABLE version(
    id INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id),
    name varchar(20),
    stat INT NOT NULL,
    version_number INT NOT NULL
    approved BOOLEAN DEFAULT NULL
);
CREATE TABLE send_recieve(
    id_a INT NOT NULL,
    FOREIGN KEY (id_a)  REFERENCES author(id) ON DELETE CASCADE,
    id_ver INT NOT NULL,
    FOREIGN KEY (id_ver)  REFERENCES version(id) ON DELETE CASCADE,
);

CREATE TABLE problem_list(
    id INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id),
    txt TEXT,
    id_ver INT NOT NULL,
    FOREIGN KEY (id_ver)  REFERENCES version (id) ON DELETE CASCADE
    sender varchar(20) NOT NULL
);
CREATE TABLE problem_list_red(
    id INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id),
    txt TEXT,
    id_ver INT NOT NULL,
    FOREIGN KEY (id_ver)  REFERENCES version (id) ON DELETE CASCADE

);
CREATE TABLE problem_list_ev(
    id INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id),
    txt TEXT,
    id_ver INT NOT NULL,
    FOREIGN KEY (id_ver)  REFERENCES version (id) ON DELETE CASCADE

);
CREATE TABLE version_redactor(
    id_ver INT NOT NULL,
    FOREIGN KEY (id_ver)  REFERENCES version (id) ON DELETE CASCADE,
    id_red INT NOT NULL,
    FOREIGN KEY (id_red)  REFERENCES redactor(id) ON DELETE CASCADE

);


CREATE TABLE version_evaluator(
    id_ver INT NOT NULL,
    FOREIGN KEY (id_ver)  REFERENCES version (id) ON DELETE CASCADE,
    id_ev INT NOT NULL,
    FOREIGN KEY (id_ev)  REFERENCES evaluator(id) ON DELETE CASCADE
);

CREATE TABLE statistics (
    active  INT NOT NULL,
    approved INT NOT NULL,
    deleted INT NOT NULL,
)

DELIMITER @@;
CREATE TRIGGER active_count
AFTER insert ON version FOR EACH ROW
BEGIN
SELECT COUNT(*) INTO @a FROM version WHERE approved IS NULL;
UPDATE statistics SET active = @a; 
END;
@@;DELIMITER @@;
CREATE TRIGGER approved_count
AFTER update ON version FOR EACH ROW
BEGIN
SELECT COUNT(*) INTO @a FROM version WHERE approved IS TRUE;
UPDATE statistics SET approved = @a;
END;
@@;DELIMITER @@;
CREATE TRIGGER deleted_count
AFTER INSERT ON deletion FOR EACH ROW
BEGIN
SELECT COUNT(*) INTO @a FROM deletion;
UPDATE statistics SET deleted = @a;
END;
@@;

INSERT INTO author(username, email, pas, reg_date) VALUES
    ('newauthor', 'new_author@mail.ru', 'new12345', '2023-03-03'),
    ('blita', 'blita@mail.com', 'blitapass', '2023-02-02');
INSERT INTO redactor(username, passwd, email) VALUES
    ('redactor1','red1','red1@mail.ru');
    ('redactor2', 'red2', 'red2@mail.ru')
INSERT INTO evaluator(username, passwd, email) VALUES
    ('evaluator1', 'ev1', 'ev1@mail.ru'),
    ('evaluator2', 'ev2', 'ev2@mail.ru');
INSERT INTO version(name, stat, version_number,approved) VALUES
    ('newvera', 1, 0, TRUE),
    ('newverb', 2, 0, FALSE),
    ('newverc', 3, 0, FALSE),
    ('newverd', 1, 0, FALSE),
INSERT INTO send_recieve(id_a, id_ver) VALUES
    (1, (SELECT id FROM version WHERE name='newvera')),
    (1, (SELECT id FROM version WHERE name='newverb')),
    (1, (SELECT id FROM version WHERE name='newverc')),
    (1, (SELECT id FROM version WHERE name='newverd'));
INSERT INTO version_redactor(id_red,id_ver) VALUES
	(1, (SELECT id FROM version WHERE name='newversionc'));
INSERT INTO version_evaluator(id_ev,id_ver) VALUES
	(1, (SELECT id FROM version WHERE name='newverb')),
        (1, (SELECT id FROM version WHERE name='newverc'));

