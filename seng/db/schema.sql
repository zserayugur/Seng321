CREATE DATABASE IF NOT EXISTS assessment_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE assessment_db;

-- Basit users tablosu (mock). Login sisteminiz varsa sadece id=1 yeter.
CREATE TABLE IF NOT EXISTS users (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL
);

INSERT INTO users(email, password_hash)
VALUES ('demo@demo.com', '$2y$10$abcdefghijklmnopqrstuv')
ON DUPLICATE KEY UPDATE email=email;

CREATE TABLE IF NOT EXISTS passages (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS questions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  type ENUM('GRAMMAR','VOCAB','READING') NOT NULL,
  reading_stage TINYINT NULL,          -- READING için 1 veya 2
  passage_id BIGINT NULL,              -- READING için
  prompt TEXT NOT NULL,
  choice_a VARCHAR(500) NOT NULL,
  choice_b VARCHAR(500) NOT NULL,
  choice_c VARCHAR(500) NOT NULL,
  choice_d VARCHAR(500) NOT NULL,
  correct_choice CHAR(1) NOT NULL,     -- A/B/C/D
  explanation TEXT NULL,
  CONSTRAINT fk_questions_passage
    FOREIGN KEY (passage_id) REFERENCES passages(id)
    ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS attempts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  test_type ENUM('GRAMMAR','VOCAB','READING') NOT NULL,
  reading_stage TINYINT NULL,
  reading_group CHAR(36) NULL,         -- stage1 ve stage2 bağlamak için

  status ENUM('IN_PROGRESS','PAUSED','SUBMITTED') NOT NULL DEFAULT 'IN_PROGRESS',

  duration_sec INT NOT NULL,
  started_at DATETIME NOT NULL,
  expires_at DATETIME NULL,            -- PAUSED iken NULL
  remaining_sec INT NULL,              -- PAUSED iken dolu
  paused_at DATETIME NULL,

  submit_reason ENUM('MANUAL','TIME_EXPIRED','PAUSE_POLICY') NULL,
  submitted_at DATETIME NULL,

  total_count INT NULL,
  correct_count INT NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attempt_questions (
  attempt_id BIGINT NOT NULL,
  question_id BIGINT NOT NULL,
  ord INT NOT NULL,
  PRIMARY KEY (attempt_id, question_id),
  CONSTRAINT fk_aq_attempt FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_aq_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS answers (
  attempt_id BIGINT NOT NULL,
  question_id BIGINT NOT NULL,
  selected_choice CHAR(1) NOT NULL,
  saved_at DATETIME NOT NULL,
  PRIMARY KEY (attempt_id, question_id),
  CONSTRAINT fk_ans_attempt FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ans_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- ----------------- SEED DATA -----------------

INSERT INTO passages(title, body) VALUES
('Reading Passage (Stage 1)',
'Many cities are redesigning streets to prioritize walking and cycling. These changes can reduce congestion and improve air quality, but they require careful planning to ensure accessibility for everyone.'),
('Reading Passage (Stage 2)',
'Scientific progress often depends on collaboration across disciplines. Breakthroughs occur when researchers share data, replicate results, and challenge assumptions through rigorous peer review.');

-- Reading Stage 1 (10)
INSERT INTO questions(type, reading_stage, passage_id, prompt, choice_a, choice_b, choice_c, choice_d, correct_choice, explanation) VALUES
('READING',1,1,'What is one benefit of redesigning streets?', 'More congestion', 'Improved air quality', 'Higher fuel use', 'Less accessibility', 'B', 'The passage mentions improved air quality.'),
('READING',1,1,'What must these changes ensure?', 'Faster cars only', 'No planning needed', 'Accessibility for everyone', 'Fewer sidewalks', 'C', 'The passage says ensure accessibility for everyone.'),
('READING',1,1,'What is prioritized in redesign?', 'Walking and cycling', 'Only buses', 'Only trucks', 'Parking expansion', 'A', NULL),
('READING',1,1,'Redesign can reduce what?', 'Air quality', 'Congestion', 'Sidewalks', 'Planning', 'B', NULL),
('READING',1,1,'These changes require what?', 'Careful planning', 'No budget', 'No rules', 'No feedback', 'A', NULL),
('READING',1,1,'Planning is needed to ensure?', 'Randomness', 'Accessibility', 'Higher prices', 'Longer commutes', 'B', NULL),
('READING',1,1,'A potential outcome is?', 'Improved air', 'More pollution', 'More noise', 'No change', 'A', NULL),
('READING',1,1,'Who should benefit?', 'Everyone', 'Only drivers', 'Only cyclists', 'Only tourists', 'A', NULL),
('READING',1,1,'The main topic is?', 'Urban street redesign', 'Ocean currents', 'Space travel', 'Farming', 'A', NULL),
('READING',1,1,'The passage suggests planning is?', 'Optional', 'Unnecessary', 'Careful', 'Forbidden', 'C', NULL);

-- Reading Stage 2 (10)
INSERT INTO questions(type, reading_stage, passage_id, prompt, choice_a, choice_b, choice_c, choice_d, correct_choice, explanation) VALUES
('READING',2,2,'Progress often depends on what?', 'Isolation', 'Collaboration', 'Luck only', 'Secrecy', 'B', 'Collaboration across disciplines is stated.'),
('READING',2,2,'Breakthroughs occur when researchers?', 'Hide data', 'Share data', 'Avoid review', 'Ignore results', 'B', NULL),
('READING',2,2,'Peer review helps to?', 'Remove rigor', 'Challenge assumptions', 'Stop replication', 'Ban data', 'B', NULL),
('READING',2,2,'Replication is described as?', 'Unnecessary', 'Part of progress', 'Illegal', 'Only for students', 'B', NULL),
('READING',2,2,'Collaboration is across?', 'Disciplines', 'Only one lab', 'Only one country', 'Only companies', 'A', NULL),
('READING',2,2,'Researchers should?', 'Challenge assumptions', 'Never question', 'Skip testing', 'Copy blindly', 'A', NULL),
('READING',2,2,'Data sharing helps?', 'Progress', 'Secrecy', 'Errors grow', 'Less review', 'A', NULL),
('READING',2,2,'Rigor is associated with?', 'Peer review', 'Rumors', 'Marketing', 'Silence', 'A', NULL),
('READING',2,2,'The passage emphasizes?', 'Scientific process', 'Cooking', 'Sports', 'Fashion', 'A', NULL),
('READING',2,2,'Main idea is?', 'Collaboration drives progress', 'Secrecy drives progress', 'Review is harmful', 'Replication is bad', 'A', NULL);

-- Grammar (20)
INSERT INTO questions(type, prompt, choice_a, choice_b, choice_c, choice_d, correct_choice, explanation) VALUES
('GRAMMAR','He ____ to school every day.','go','goes','gone','going','B','3rd person singular takes -s.'),
('GRAMMAR','I have lived here ____ 2020.','for','since','during','from','B',NULL),
('GRAMMAR','If it ____ tomorrow, we will stay home.','rain','rains','rained','raining','B',NULL),
('GRAMMAR','She is ____ than her brother.','tall','taller','tallest','more tall','B',NULL),
('GRAMMAR','They ____ finished the work.','has','have','having','had been','B',NULL),
('GRAMMAR','This is the book ____ I told you about.','who','which','where','when','B',NULL),
('GRAMMAR','We ____ dinner when he arrived.','eat','were eating','eaten','are eat','B',NULL),
('GRAMMAR','I don’t know ____ he said.','what','where','who','which','A',NULL),
('GRAMMAR','There ____ many people at the event.','was','were','is','be','B',NULL),
('GRAMMAR','She ____ speak French.','can','must','should','will','A',NULL),
('GRAMMAR','By next week, I ____ the project.','finish','will finish','will have finished','finished','C',NULL),
('GRAMMAR','Neither Tom nor Anna ____ here.','are','is','were','be','B',NULL),
('GRAMMAR','The cake was ____ by my mother.','make','made','making','makes','B',NULL),
('GRAMMAR','You ____ smoke here.','must not','can','may','will','A',NULL),
('GRAMMAR','I wish I ____ more time.','have','had','having','has','B',NULL),
('GRAMMAR','He asked me where I ____.','live','lived','living','lives','B',NULL),
('GRAMMAR','The car, ____ is red, is mine.','who','which','what','where','B',NULL),
('GRAMMAR','Hardly ____ started when it stopped.','had it','has it','it had','it has','A',NULL),
('GRAMMAR','Not only ____ late, but he also forgot.','he was','was he','he is','is he','B',NULL),
('GRAMMAR','I will call you as soon as I ____.','arrive','arrived','will arrive','arriving','A',NULL);

-- Vocabulary (20)
INSERT INTO questions(type, prompt, choice_a, choice_b, choice_c, choice_d, correct_choice, explanation) VALUES
('VOCAB','Choose the synonym of "quick".','slow','rapid','weak','late','B',NULL),
('VOCAB','Choose the antonym of "increase".','raise','grow','decrease','expand','C',NULL),
('VOCAB','"Reliable" means:','cannot be trusted','can be trusted','very old','very noisy','B',NULL),
('VOCAB','Choose the synonym of "assist".','help','ignore','delay','hide','A',NULL),
('VOCAB','Antonym of "ancient" is:','modern','old','historic','past','A',NULL),
('VOCAB','"Purchase" means:','sell','buy','lose','forget','B',NULL),
('VOCAB','Synonym of "begin" is:','start','end','break','fall','A',NULL),
('VOCAB','Antonym of "scarce" is:','rare','plentiful','few','limited','B',NULL),
('VOCAB','"Outcome" means:','result','problem','question','method','A',NULL),
('VOCAB','Synonym of "choose" is:','select','drop','push','burn','A',NULL),
('VOCAB','Antonym of "strong" is:','powerful','weak','tough','solid','B',NULL),
('VOCAB','"Improve" means:','make better','make worse','destroy','pause','A',NULL),
('VOCAB','Synonym of "silent" is:','noisy','quiet','angry','bright','B',NULL),
('VOCAB','"Accurate" means:','correct','random','careless','slow','A',NULL),
('VOCAB','Antonym of "accept" is:','admit','reject','allow','take','B',NULL),
('VOCAB','"Benefit" means:','advantage','damage','rule','delay','A',NULL),
('VOCAB','Synonym of "challenge" is:','difficulty','holiday','furniture','color','A',NULL),
('VOCAB','"Essential" means:','optional','necessary','tiny','dangerous','B',NULL),
('VOCAB','Antonym of "empty" is:','vacant','full','hollow','thin','B',NULL),
('VOCAB','"Maintain" means:','keep','break','erase','lose','A',NULL);
