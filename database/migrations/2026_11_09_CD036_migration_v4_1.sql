-- ============================================================================
-- CD-036 : Training Module — Migration v4.1  (CORRECTED, execution-ready)
-- Date   : 2026-11-09
-- Status : FINAL v4.1  (hotfix vs v4: COLLATE on §5 join)
-- Author : Lead Developer
-- Ref    : CD-036 · authoritative field investigation by the new Assistant
-- ----------------------------------------------------------------------------
-- WHAT CHANGED vs v3 (and why):
--   1) FK columns are now SIGNED INT (not INT UNSIGNED) to match the legacy
--      referenced tables branches/departments/employees/users which are all
--      int(11) signed. (v3's UNSIGNED FKs failed with error 1215.)
--   2) Catalog column is `title` in BOTH course_catalog & workshop_catalog
--      (NOT `name`). v4 maps legacy title -> new training_catalogs.name.
--      (The prior report claiming `name` was inaccurate; this is confirmed
--       by INFORMATION_SCHEMA.)
--   3) Charset alignment: course_catalog=utf8mb4_unicode_ci,
--      workshop_catalog=utf8mb4_general_ci. v4 applies COLLATE
--      utf8mb4_unicode_ci on every cross-catalog string compare/ingest so
--      UNION/JOIN/INSERT do not fail on collation mismatch.
--   4) Robust id-based mapping via a new `legacy_id` column on
--      training_programs. Participant & evaluation remap now join on
--      (type, legacy_id) — NOT on title — so multi-program-per-title
--      (e.g. catalog_id 1 -> 3 programs) no longer corrupts mappings.
--   5) Correct verification expectations: courses=9, workshops=3,
--      participants=5, workshop_parts=0 (NOT 21/23 — those were
--      AUTO_INCREMENT values, not row counts).
--   6) Preserves catalog.category + created_by (-> created_by_user_id).
-- ----------------------------------------------------------------------------
-- CONFIRMED LIVE SCHEMA (authoritative):
--   course_catalog(id PK, title UNI varchar, description, category, created_by,
--                  created_at, updated_at)  charset utf8mb4_unicode_ci
--   workshop_catalog(id PK, title varchar, description, category, created_by,
--                    created_at, updated_at)  charset utf8mb4_general_ci  (no UNIQUE on title)
--   branches/departments/employees/users : id int(11) SIGNED
--   Counts: training_courses=9, training_workshops=3,
--           course_participants=5, workshop_participants=0
--   No duplicate titles within/across catalogs; but several programs share
--   one catalog title (catalog_id 1->3, 4->2, 5->2).
-- ----------------------------------------------------------------------------
-- >>> A verified backup already exists (per Assistant report, 276,074 bytes,
--     UTF-8 valid). Confirm it before proceeding. <<<
-- ============================================================================


-- ===========================================================================
-- 1) UNIFIED CATALOG : training_catalogs  (INT signed; name <- legacy title)
-- ===========================================================================
CREATE TABLE IF NOT EXISTS training_catalogs (
  id                 INT NOT NULL AUTO_INCREMENT,        -- signed (matches legacy id style)
  name               VARCHAR(255) NOT NULL,              -- design column; legacy `title` is ingested here
  type               ENUM('course','workshop','both') NOT NULL DEFAULT 'both',
  description        TEXT NULL,
  category           VARCHAR(100) NULL,                  -- preserve legacy catalog.category
  status             ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_by_user_id INT NULL,                           -- <- legacy catalog.created_by (signed)
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_catalog_name_type (name, type),
  KEY idx_catalog_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 1b) Ingest course_catalog (unicode_ci source) -> type='course'
INSERT IGNORE INTO training_catalogs
  (name, type, description, category, status, created_by_user_id, created_at, updated_at)
SELECT
   cc.title COLLATE utf8mb4_unicode_ci,
   'course',
   cc.description,
   cc.category,
   'active',
   cc.created_by,
   COALESCE(cc.created_at, CURRENT_TIMESTAMP),
   COALESCE(cc.updated_at, CURRENT_TIMESTAMP)
FROM course_catalog cc
WHERE cc.title IS NOT NULL AND cc.title <> '';


-- 1c) Ingest workshop_catalog (general_ci source -> COLLATE) -> type='workshop'
INSERT IGNORE INTO training_catalogs
  (name, type, description, category, status, created_by_user_id, created_at, updated_at)
SELECT
   wc.title COLLATE utf8mb4_unicode_ci,
   'workshop',
   wc.description COLLATE utf8mb4_unicode_ci,
   wc.category COLLATE utf8mb4_unicode_ci,
   'active',
   wc.created_by,
   COALESCE(wc.created_at, CURRENT_TIMESTAMP),
   COALESCE(wc.updated_at, CURRENT_TIMESTAMP)
FROM workshop_catalog wc
WHERE wc.title IS NOT NULL AND wc.title <> '';


-- 1d) Promote names present in BOTH source catalogs to type='both'
--     (COLLATE on both sides because the two catalogs differ in collation)
UPDATE training_catalogs
SET type = 'both'
WHERE name IN (
   SELECT cc.title COLLATE utf8mb4_unicode_ci FROM course_catalog cc
     WHERE cc.title IS NOT NULL AND cc.title <> ''
   INTERSECT
   SELECT wc.title COLLATE utf8mb4_unicode_ci FROM workshop_catalog wc
     WHERE wc.title IS NOT NULL AND wc.title <> ''
);


-- ===========================================================================
-- 2) UNIFIED PROGRAMS : training_programs  (INT signed + legacy_id for robust mapping)
-- ===========================================================================
CREATE TABLE IF NOT EXISTS training_programs (
  id                      INT NOT NULL AUTO_INCREMENT,   -- signed
  type                    ENUM('course','workshop') NOT NULL,
  catalog_id              INT NULL,                      -- signed -> training_catalogs.id
  legacy_id               INT NULL,                      -- preserves training_courses.id / training_workshops.id
  title                   VARCHAR(255) NOT NULL,         -- denormalized from catalog title
  description             TEXT NULL,
  executing_branch_id     INT NOT NULL,                  -- signed -> branches.id (int(11))
  executing_department_id INT NULL,                      -- signed -> departments.id
  start_date              DATE NOT NULL,
  end_date                DATE NOT NULL,
  responsible_person      VARCHAR(150) NOT NULL,         -- NEW (was missing on legacy)
  status                  ENUM('scheduled','ongoing','completed','canceled')
                          NOT NULL DEFAULT 'scheduled',
  created_by              INT NULL,                      -- signed -> users.id
  created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_program_legacy (type, legacy_id),        -- 1:1 legacy mapping (multiple NULLs allowed)
  KEY idx_program_type    (type),
  KEY idx_program_branch  (executing_branch_id),
  KEY idx_program_dept    (executing_department_id),
  KEY idx_program_catalog (catalog_id),
  CONSTRAINT fk_program_catalog FOREIGN KEY (catalog_id)
      REFERENCES training_catalogs(id) ON DELETE SET NULL,
  CONSTRAINT fk_program_branch FOREIGN KEY (executing_branch_id)
      REFERENCES branches(id) ON DELETE RESTRICT,
  CONSTRAINT fk_program_dept FOREIGN KEY (executing_department_id)
      REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2b) Migrate courses -> training_programs (type='course', legacy_id = tc.id)
INSERT INTO training_programs
  (type, catalog_id, legacy_id, title, description,
   executing_branch_id, executing_department_id,
   start_date, end_date, responsible_person, status, created_by, created_at, updated_at)
SELECT
   'course',
   NULL,                                  -- remapped in step 4 (by title -> catalog name)
   tc.id,                                 -- legacy_id for robust participant/evaluation mapping
   COALESCE(cc.title COLLATE utf8mb4_unicode_ci, CONCAT('دورة #', tc.id)),
   tc.description,
   tc.branch_id,                          -- 0 NULLs confirmed -> safe for NOT NULL target
   tc.department_id,
   tc.start_date,
   tc.end_date,
   'غير محدد',                             -- responsible_person missing in source -> backfill later
   tc.status,
   tc.created_by,
   tc.created_at, tc.updated_at
FROM training_courses tc
LEFT JOIN course_catalog cc ON cc.id = tc.catalog_id;


-- 2c) Migrate workshops -> training_programs (type='workshop', legacy_id = tw.id)
INSERT INTO training_programs
  (type, catalog_id, legacy_id, title, description,
   executing_branch_id, executing_department_id,
   start_date, end_date, responsible_person, status, created_by, created_at, updated_at)
SELECT
   'workshop',
   NULL,
   tw.id,
   COALESCE(wc.title COLLATE utf8mb4_unicode_ci, CONCAT('ورشة #', tw.id)),
   NULL,                                  -- workshops had no description
   tw.branch_id,
   tw.department_id,
   tw.start_date,
   tw.end_date,
   'غير محدد',
   tw.status,
   tw.created_by,
   tw.created_at,
   COALESCE(tw.created_at, CURRENT_TIMESTAMP)   -- workshops have no updated_at
FROM training_workshops tw
LEFT JOIN workshop_catalog wc ON wc.id = tw.catalog_id;


-- ===========================================================================
-- 3) UNIFIED PARTICIPANTS : program_participants  (INT signed; drops dead enrolled_at)
-- ===========================================================================
CREATE TABLE IF NOT EXISTS program_participants (
  id            INT NOT NULL AUTO_INCREMENT,
  program_id    INT NOT NULL,             -- signed -> training_programs.id
  employee_id   INT NOT NULL,             -- signed -> employees.id (int(11))
  status        ENUM('pending','completed','absent','cancelled') NOT NULL DEFAULT 'pending',
  final_score   DECIMAL(5,2) NULL,
  completed_at  TIMESTAMP NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_participant (program_id, employee_id),
  KEY idx_participant_employee (employee_id),
  CONSTRAINT fk_participant_program FOREIGN KEY (program_id)
      REFERENCES training_programs(id) ON DELETE CASCADE,
  CONSTRAINT fk_participant_employee FOREIGN KEY (employee_id)
      REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- NOTE: legacy `enrolled_at` is intentionally NOT carried over (dead column).


-- 3b) Migrate course_participants -> program_participants (ROBUST: join on legacy_id)
INSERT IGNORE INTO program_participants
  (program_id, employee_id, status, completed_at, created_at, updated_at)
SELECT
   tp.id,
   cp.employee_id,
   cp.status,
   cp.completed_at,
   cp.enrolled_at,                        -- reuse as created_at (it is the creation ts)
   COALESCE(cp.completed_at, cp.enrolled_at)
FROM course_participants cp
JOIN training_courses tc  ON tc.id = cp.course_id
JOIN training_programs tp ON tp.type = 'course' AND tp.legacy_id = tc.id;


-- 3c) Migrate workshop_participants -> program_participants (ROBUST: join on legacy_id)
INSERT IGNORE INTO program_participants
  (program_id, employee_id, status, completed_at, created_at, updated_at)
SELECT
   tp.id,
   wp.employee_id,
   wp.status,
   wp.completed_at,
   wp.enrolled_at,
   COALESCE(wp.completed_at, wp.enrolled_at)
FROM workshop_participants wp
JOIN training_workshops tw  ON tw.id = wp.workshop_id
JOIN training_programs tp   ON tp.type = 'workshop' AND tp.legacy_id = tw.id;


-- ===========================================================================
-- 4) REMAP catalog_id  (link programs to the unified catalog by name=title)
--     Title-match is SAFE here: many programs -> one catalog entry is correct.
-- ===========================================================================
UPDATE training_programs tp
JOIN training_catalogs tc
  ON tc.name = tp.title AND (tc.type = tp.type OR tc.type = 'both')
SET tp.catalog_id = tc.id
WHERE tp.catalog_id IS NULL;


-- ===========================================================================
-- 5) REMAP evaluations.context_id  (CD-029 linking — ROBUST via legacy_id)
--    context_type IN ('course','workshop') now references training_programs.id
-- ===========================================================================
UPDATE evaluations e
JOIN training_programs tp
  ON tp.type = e.context_type COLLATE utf8mb4_unicode_ci
 AND tp.legacy_id = e.context_id
SET e.context_id = tp.id
WHERE e.context_type IN ('course','workshop');


-- ===========================================================================
-- 6) VERIFICATION QUERIES  (run these, confirm, THEN decide on drops)
-- ===========================================================================
-- 6a) Catalog unified?
--   SELECT type, COUNT(*) FROM training_catalogs GROUP BY type;
--   -- expect: course / workshop / both rows, matching source title totals.

-- 6b) Programs match source totals?
--   SELECT type, COUNT(*) FROM training_programs GROUP BY type;
--   -- expect: course = 9, workshop = 3   (NOT 21/3 — those were AUTO_INCREMENT)

-- 6c) Participants migrated?
--   SELECT COUNT(*) FROM program_participants;            -- expect 5 (course_parts=5 + workshop_parts=0)
--   SELECT status, COUNT(*) FROM program_participants GROUP BY status;

-- 6d) catalog_id linkage complete?
--   SELECT COUNT(*) FROM training_programs WHERE catalog_id IS NULL;   -- expect 0

-- 6e) Branch still mandatory (no NULLs)?
--   SELECT COUNT(*) FROM training_programs WHERE executing_branch_id IS NULL;  -- expect 0

-- 6f) responsible_person backfill needed?
--   SELECT COUNT(*) FROM training_programs WHERE responsible_person = 'غير محدد';  -- expect 12 (9+3)

-- 6g) Evaluations context remapped (every context_id now exists in training_programs)?
--   SELECT COUNT(*) FROM evaluations e
--   WHERE e.context_type IN ('course','workshop')
--     AND e.context_id NOT IN (SELECT id FROM training_programs);  -- expect 0

-- 6h) legacy_id mapping unique?
--   SELECT type, legacy_id, COUNT(*) FROM training_programs
--   WHERE legacy_id IS NOT NULL GROUP BY type, legacy_id HAVING COUNT(*)>1;  -- expect 0 rows


-- ===========================================================================
-- 7) DROP LEGACY TABLES  (UNCOMMENT only after 6a-6h all pass + backup confirmed)
-- ===========================================================================
-- DROP TABLE IF EXISTS course_participants;
-- DROP TABLE IF EXISTS workshop_participants;
-- DROP TABLE IF EXISTS training_courses;
-- DROP TABLE IF EXISTS training_workshops;
-- DROP TABLE IF EXISTS course_catalog;
-- DROP TABLE IF EXISTS workshop_catalog;

-- Optional: drop the legacy_id + uq_program_legacy unique key after all
-- remapping is verified (legacy_id is no longer needed post-migration):
-- ALTER TABLE training_programs DROP INDEX uq_program_legacy;
-- ALTER TABLE training_programs DROP COLUMN legacy_id;


-- ============================================================================
-- ROLLBACK (if anything goes wrong before drops):
--   mysql -u root -p employee_evaluation_uat < backup_pre_cd036_*.sql
--   (the new training_* tables are harmless extras over a restored dump; drop
--    them first for a clean restore: DROP TABLE IF EXISTS program_participants,
--    training_programs, training_catalogs;)
-- ============================================================================
