-- CD-026.1: تنظيف كلمات المرور النصية القديمة من audit_logs
-- يحل: 3 صفوف قديمة (id 364-366) ما زالت تحوي "password" في details JSON
-- آمن: يستخدم JSON_REMOVE لإزالة المفتاح فقط، يحافظ على بقية البيانات

-- إزالة مفتاح "password" و "password_confirm" من جميع صفوف audit_logs
-- حيث details يُخزَّن كـ JSON text
UPDATE audit_logs
SET details = JSON_REMOVE(details, '$.password', '$.password_confirm')
WHERE details IS NOT NULL
  AND details != ''
  AND JSON_VALID(details)
  AND (JSON_EXTRACT(details, '$.password') IS NOT NULL
       OR JSON_EXTRACT(details, '$.password_confirm') IS NOT NULL);

-- التحقق: يجب ألا يُرجع أي صف
-- SELECT id, details FROM audit_logs
-- WHERE details LIKE '%password%' AND details NOT LIKE '%password_changed%';
-- المتوقع: 0 صفوف
