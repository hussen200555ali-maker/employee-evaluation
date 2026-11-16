-- CD-043 M3 rollback: إعادة target_type إلى القيم الثمانية السابقة
-- تحذير: التراجع ممكن فقط إذا لم تُنشأ صفوف بأنواع محذوفة أثناء سريان M3
-- (لا يمكن استرجاع course/workshop/unit/admin لأنها لم تعد مقبولة في M3).

ALTER TABLE form_templates
    MODIFY COLUMN target_type
        ENUM('employee','employee_in_course','employee_in_workshop','course','workshop','unit','admin','skill')
        NOT NULL DEFAULT 'employee';
