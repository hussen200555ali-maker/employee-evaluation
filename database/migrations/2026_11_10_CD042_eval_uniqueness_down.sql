-- CD-042 rollback
ALTER TABLE evaluations
  DROP INDEX uq_eval_once,
  DROP COLUMN context_key;
