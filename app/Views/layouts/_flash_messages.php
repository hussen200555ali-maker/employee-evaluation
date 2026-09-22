<?php
// نستدعي دالة Flash::get() لجلب كل الرسائل المخزنة (إن وجدت).
// هذه الدالة ستحذف الرسائل من الجلسة بعد جلبها.
$messages = App\Core\Flash::get();

// نتحقق مما إذا كانت هناك رسائل لعرضها.
if ($messages):
?>
    <!-- حاوية لتغليف كل الإشعارات -->
    <div class="flash-messages-container">
        <?php 
        // نقوم بالمرور على كل رسالة في مصفوفة الرسائل.
        foreach ($messages as $message): 
        ?>
            <!-- 
                نقوم بإنشاء عنصر div لكل رسالة.
                الكلاس الأول هو 'flash-message' للتنسيق العام.
                الكلاس الثاني هو ديناميكي ويعتمد على نوع الرسالة (مثل 'flash-success' أو 'flash-error').
            -->
            <div class="flash-message flash-<?php echo htmlspecialchars($message['type']); ?>">
                <!-- طباعة نص الرسالة -->
                <span><?php echo htmlspecialchars($message['message']); ?></span>
                <!-- زر لإغلاق الإشعار (وظيفة JavaScript اختيارية) -->
                <button class="flash-close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php 
        endforeach; // نهاية حلقة foreach
        ?>
    </div>
<?php 
endif; // نهاية شرط التحقق من وجود رسائل
?>
