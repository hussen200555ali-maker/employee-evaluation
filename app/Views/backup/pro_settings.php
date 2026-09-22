<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط§ط­طھط±ط§ظپظٹط© ظ„ظ„ظ†ط³ط® ط§ظ„ط§ط­طھظٹط§ط·ظٹ</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo APP_URL; ?>
                <?= csrf_field() ?>/backup/settings/save">
                        <div class="row">
                            <!-- ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط£ط³ط§ط³ظٹط© -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط£ط³ط§ط³ظٹط©</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">ظ†ظˆط¹ ط§ظ„ظ†ط³ط® ط§ظ„ط§ط­طھظٹط§ط·ظٹ</label>
                                            <select name="backup_type" class="form-control">
                                                <option value="full" <?= ($settings->backup_type ?? 'full') == 'full' ? 'selected' : '' ?>>ظ†ط³ط® ظƒط§ظ…ظ„</option>
                                                <option value="incremental" <?= ($settings->backup_type ?? 'full') == 'incremental' ? 'selected' : '' ?>>ظ†ط³ط® طھط²ط§ظٹط¯ظٹ</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">ظپطھط±ط© ط§ظ„ط§ط­طھظپط§ط¸ (ط£ظٹط§ظ…)</label>
                                            <input type="number" name="retention_days" class="form-control" 
                                                   value="<?= $settings->retention_days ?? 30 ?>" min="1" max="365">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط¬ط¯ظˆظ„ط© -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>ط§ظ„ط¬ط¯ظˆظ„ط© ط§ظ„طھظ„ظ‚ط§ط¦ظٹط©</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">ظ†ظˆط¹ ط§ظ„ط¬ط¯ظˆظ„ط©</label>
                                            <select name="schedule_type" class="form-control" id="schedule_type">
                                                <option value="manual" <?= ($settings->schedule_type ?? 'manual') == 'manual' ? 'selected' : '' ?>>ظٹط¯ظˆظٹ</option>
                                                <option value="daily" <?= ($settings->schedule_type ?? 'manual') == 'daily' ? 'selected' : '' ?>>ظٹظˆظ…ظٹ</option>
                                                <option value="weekly" <?= ($settings->schedule_type ?? 'manual') == 'weekly' ? 'selected' : '' ?>>ط£ط³ط¨ظˆط¹ظٹ</option>
                                                <option value="monthly" <?= ($settings->schedule_type ?? 'manual') == 'monthly' ? 'selected' : '' ?>>ط´ظ‡ط±ظٹ</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">ظˆظ‚طھ ط§ظ„طھط´ط؛ظٹظ„</label>
                                            <input type="time" name="schedule_time" class="form-control" 
                                                   value="<?= $settings->schedule_time ?? '02:00' ?>">
                                        </div>
                                        
                                        <div class="mb-3" id="schedule_day_container" style="display: none;">
                                            <!-- ط³ظٹطھظ… طھط¹ط¨ط¦طھظ‡ ط¨ط§ظ„ط¬ط§ظپط§ط³ظƒط±ظٹط¨طھ -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ط¥ط¹ط¯ط§ط¯ط§طھ ظ…طھظ‚ط¯ظ…ط© -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ظ…طھظ‚ط¯ظ…ط©</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="encryption_enabled" 
                                                           id="encryption_enabled" <?= ($settings->encryption_enabled ?? 0) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="encryption_enabled">طھظپط¹ظٹظ„ ط§ظ„طھط´ظپظٹط±</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="compression_enabled" 
                                                           id="compression_enabled" <?= ($settings->compression_enabled ?? 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="compression_enabled">طھظپط¹ظٹظ„ ط§ظ„ط¶ط؛ط·</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="auto_cleanup" 
                                                           id="auto_cleanup" <?= ($settings->auto_cleanup ?? 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="auto_cleanup">ط§ظ„طھظ†ط¸ظٹظپ ط§ظ„طھظ„ظ‚ط§ط¦ظٹ</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">ط­ظپط¸ ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ</button>
                            <a href="<?php echo APP_URL; ?>/backup" class="btn btn-secondary">ط§ظ„ط¹ظˆط¯ط©</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('schedule_type').addEventListener('change', function() {
    const container = document.getElementById('schedule_day_container');
    if (this.value !== 'manual') {
        container.style.display = 'block';
        updateScheduleDayOptions(this.value);
    } else {
        container.style.display = 'none';
    }
});

function updateScheduleDayOptions(scheduleType) {
    const container = document.getElementById('schedule_day_container');
    let html = '<label class="form-label">ظٹظˆظ… ط§ظ„طھط´ط؛ظٹظ„</label>';
    
    if (scheduleType === 'weekly') {
        html += `
            <select name="schedule_day" class="form-control">
                <option value="sunday">ط§ظ„ط£ط­ط¯</option>
                <option value="monday">ط§ظ„ط¥ط«ظ†ظٹظ†</option>
                <option value="tuesday">ط§ظ„ط«ظ„ط§ط«ط§ط،</option>
                <option value="wednesday">ط§ظ„ط£ط±ط¨ط¹ط§ط،</option>
                <option value="thursday">ط§ظ„ط®ظ…ظٹط³</option>
                <option value="friday">ط§ظ„ط¬ظ…ط¹ط©</option>
                <option value="saturday">ط§ظ„ط³ط¨طھ</option>
            </select>`;
    } else if (scheduleType === 'monthly') {
        html += `
            <select name="schedule_day" class="form-control">
                <option value="1">ط§ظ„ظٹظˆظ… ط§ظ„ط£ظˆظ„ ظ…ظ† ط§ظ„ط´ظ‡ط±</option>
                <option value="15">ظ…ظ†طھطµظپ ط§ظ„ط´ظ‡ط± (15)</option>
                <option value="28">ظ†ظ‡ط§ظٹط© ط§ظ„ط´ظ‡ط± (28)</option>
            </select>`;
    } else {
        html = '';
    }
    
    container.innerHTML = html;
}

// ط§ظ„طھظ‡ظٹط¦ط© ط§ظ„ط£ظˆظ„ظٹط©
document.addEventListener('DOMContentLoaded', function() {
    const scheduleType = document.getElementById('schedule_type').value;
    if (scheduleType !== 'manual') {
        document.getElementById('schedule_day_container').style.display = 'block';
        updateScheduleDayOptions(scheduleType);
    }
});
</script>

<?php
$this->view('layouts/footer');
?>
