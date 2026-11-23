-- DIR-BULK-CREATE-FIX-V2.0 — rollback guard.
-- Refuses to restore NOT NULL while any quick-added employee still has emp_code = NULL.
-- Never generates fake codes: NULL rows must be resolved deliberately first.
DROP PROCEDURE IF EXISTS sp_v2_assert_no_null_codes;
DELIMITER //
CREATE PROCEDURE sp_v2_assert_no_null_codes()
BEGIN
  DECLARE n INT DEFAULT 0;
  SELECT COUNT(*) INTO n FROM employees WHERE emp_code IS NULL;
  IF n > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ROLLBACK REFUSED: rows with emp_code IS NULL exist; resolve them first (no fake codes may be generated).';
  END IF;
END//
DELIMITER ;

CALL sp_v2_assert_no_null_codes();
DROP PROCEDURE IF EXISTS sp_v2_assert_no_null_codes;

ALTER TABLE employees
  MODIFY emp_code VARCHAR(50) NOT NULL COMMENT 'الرقم الوظيفي الفريد للموظف';
