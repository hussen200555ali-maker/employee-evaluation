<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ظ†ط³ط® ط§ظ„ط§ط­طھظٹط§ط·ظٹ - ط§ظ„ط¥طµط¯ط§ط± ط§ظ„ظ…ط¨ط³ط·
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- طھظ†ط¨ظٹظ‡ طھط±ط­ظٹط¨ظٹ -->
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-info-circle me-2"></i>ظ…ط±ط­ط¨ط§ظ‹ ط¨ظƒ ظپظٹ ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ظ†ط³ط® ط§ظ„ط§ط­طھظٹط§ط·ظٹ</h6>
                                <p class="mb-0">ظ‡ظ†ط§ ظٹظ…ظƒظ†ظƒ ط¶ط¨ط· ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط£ط³ط§ط³ظٹط© ظ„ظ„ظ†ط³ط® ط§ظ„ط§ط­طھظٹط§ط·ظٹ ط§ظ„طھظ„ظ‚ط§ط¦ظٹ. ظ„ط§ طھظ‚ظ„ظ‚طŒ ظƒظ„ ط§ظ„ط®ظٹط§ط±ط§طھ ظ…ظˆط¶ط­ط© ط¨ظ„ط؛ط© ط¨ط³ظٹط·ط©.</p>
                            </div>

                            <form method="POST" action="<?php echo APP_URL; ?>
                <?= csrf_field() ?>/backup/settings/save" id="backupSettingsForm">
                                
                                <!-- ط§ظ„ظ‚ط³ظ… 1: ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط£ط³ط§ط³ظٹط© -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-sliders-h me-2"></i>
                                            ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط£ط³ط§ط³ظٹط©
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-database me-2"></i>
                                                        ظ†ظˆط¹ ط§ظ„ظ†ط³ط® ط§ظ„ط§ط­طھظٹط§ط·ظٹ
                                                    </label>
                                                    <select name="backup_type" class="form-select" id="backup_type">
                                                        <option value="full" <?= ($settings->backup_type ?? 'full') == 'full' ? 'selected' : '' ?>>
                                                            ًں“¦ ظ†ط³ط® ظƒط§ظ…ظ„ (ظٹط£ط®ط° ظƒظ„ ط§ظ„ط¨ظٹط§ظ†ط§طھ)
                                                        </option>
                                                        <option value="incremental" <?= ($settings->backup_type ?? 'full') == 'incremental' ? 'selected' : '' ?>>
                                                            ًں”„ ظ†ط³ط® طھط²ط§ظٹط¯ظٹ (ظٹط£ط®ط° ط§ظ„طھط؛ظٹظٹط±ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط© ظپظ‚ط·)
                                                        </option>
                                                    </select>
                                                    <div class="form-text">
                                                        <small>
                                                            <i class="fas fa-lightbulb me-1"></i>
                                                            <span id="backup_type_help">
                                                                <?= ($settings->backup_type ?? 'full') == 'full' ? 
                                                                    'ط§ظ„ظ†ط³ط® ط§ظ„ظƒط§ظ…ظ„ ط£ظپط¶ظ„ ظ„ظ„ط£ظ…ط§ظ† ظ„ظƒظ†ظ‡ ظٹط£ط®ط° ظ…ط³ط§ط­ط© ط£ظƒط¨ط±' : 
                                                                    'ط§ظ„ظ†ط³ط® ط§ظ„طھط²ط§ظٹط¯ظٹ ط£ط³ط±ط¹ ظˆظٹظˆظپط± ظ…ط³ط§ط­ط©' ?>
                                                            </span>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-clock me-2"></i>
                                                        ظپطھط±ط© ط§ظ„ط§ط­طھظپط§ط¸ ط¨ط§ظ„ظ†ط³ط®
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="number" name="retention_days" class="form-control" 
                                                               value="<?= $settings->retention_days ?? 30 ?>" min="1" max="365"
                                                               id="retention_days">
                                                        <span class="input-group-text">ط£ظٹط§ظ…</span>
                                                    </div>
                                                    <div class="form-text">
                                                        <small>
                                                            <i class="fas fa-calendar me-1"></i>
                                                            ط¹ط¯ط¯ ط§ظ„ط£ظٹط§ظ… ط§ظ„طھظٹ طھط­طھظپط¸ ظپظٹظ‡ط§ ط¨ط§ظ„ظ†ط³ط® ط§ظ„ظ‚ط¯ظٹظ…ط© طھظ„ظ‚ط§ط¦ظٹط§ظ‹
                                                        </small>
                                                    </div>
                                                    <div class="mt-2">
                                                        <small class="text-muted" id="retention_example">
                                                            ظ…ط«ط§ظ„: ط³ظٹطھظ… ط­ط°ظپ ط§ظ„ظ†ط³ط® ط§ظ„ط£ظ‚ط¯ظ… ظ…ظ† <span id="retention_example_days"><?= $settings->retention_days ?? 30 ?></span> ظٹظˆظ… طھظ„ظ‚ط§ط¦ظٹط§ظ‹
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ط§ظ„ظ‚ط³ظ… 2: ط§ظ„ط¬ط¯ظˆظ„ط© ط§ظ„طھظ„ظ‚ط§ط¦ظٹط© -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-robot me-2"></i>
                                            ط§ظ„ط¬ط¯ظˆظ„ط© ط§ظ„طھظ„ظ‚ط§ط¦ظٹط©
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-calendar-alt me-2"></i>
                                                        ظ†ظˆط¹ ط§ظ„ط¬ط¯ظˆظ„ط©
                                                    </label>
                                                    <select name="schedule_type" class="form-select" id="schedule_type">
                                                        <option value="manual" <?= ($settings->schedule_type ?? 'manual') == 'manual' ? 'selected' : '' ?>>
                                                            ًں‘† ظٹط¯ظˆظٹ (ط£ظ‚ظˆظ… ط¨ط§ظ„ظ†ط³ط® ط¨ظ†ظپط³ظٹ)
                                                        </option>
                                                        <option value="daily" <?= ($settings->schedule_type ?? 'manual') == 'daily' ? 'selected' : '' ?>>
                                                            ًں“… ظٹظˆظ…ظٹ (ظƒظ„ ظٹظˆظ…)
                                                        </option>
                                                        <option value="weekly" <?= ($settings->schedule_type ?? 'manual') == 'weekly' ? 'selected' : '' ?>>
                                                            ًں“† ط£ط³ط¨ظˆط¹ظٹ (ظƒظ„ ط£ط³ط¨ظˆط¹)
                                                        </option>
                                                        <option value="monthly" <?= ($settings->schedule_type ?? 'manual') == 'monthly' ? 'selected' : '' ?>>
                                                            ًں—“ï¸ڈ ط´ظ‡ط±ظٹ (ظƒظ„ ط´ظ‡ط±)
                                                        </option>
                                                    </select>
                                                    <div class="form-text">
                                                        <small>
                                                            <i class="fas fa-question-circle me-1"></i>
                                                            ط§ط®طھط± ظƒظٹظپ طھط±ظٹط¯ ط£ظ† ظٹط¹ظ…ظ„ ط§ظ„ظ†ط¸ط§ظ… طھظ„ظ‚ط§ط¦ظٹط§ظ‹
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-clock me-2"></i>
                                                        ظˆظ‚طھ ط§ظ„طھط´ط؛ظٹظ„ ط§ظ„طھظ„ظ‚ط§ط¦ظٹ
                                                    </label>
                                                    <input type="time" name="schedule_time" class="form-control" 
                                                           value="<?= $settings->schedule_time ?? '02:00' ?>" 
                                                           id="schedule_time">
                                                    <div class="form-text">
                                                        <small>
                                                            <i class="fas fa-moon me-1"></i>
                                                            ط§ط®طھط± ظˆظ‚طھط§ظ‹ ظٹظƒظˆظ† ظپظٹظ‡ ط§ظ„ظ†ط¸ط§ظ… ظ‚ظ„ظٹظ„ ط§ظ„ط§ط³طھط®ط¯ط§ظ… (ظ…ط«ظ„ ظ…ظ†طھطµظپ ط§ظ„ظ„ظٹظ„)
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ط®ظٹط§ط±ط§طھ ط§ظ„ط¬ط¯ظˆظ„ط© ط§ظ„ط¥ط¶ط§ظپظٹط© -->
                        <div class="row schedule-options mt-3" style="display: <?= ($settings->schedule_type ?? 'manual') != 'manual' ? 'block' : 'none' ?>;">
                            <div class="col-12">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-cog me-2"></i>
                                            ط¥ط¹ط¯ط§ط¯ط§طھ ط¥ط¶ط§ظپظٹط© ظ„ظ„ط¬ط¯ظˆظ„ط©
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="schedule_day_container">
                                            <!-- ط³ظٹطھظ… طھط¹ط¨ط¦طھظ‡ ط¨ط§ظ„ط¬ط§ظپط§ط³ظƒط±ظٹط¨طھ -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- طھظ†ط¨ظٹظ‡ ط§ظ„ط¬ط¯ظˆظ„ط© -->
                        <div class="alert alert-success mt-3" id="schedule_summary" style="display: <?= ($settings->schedule_type ?? 'manual') != 'manual' ? 'block' : 'none' ?>;">
                            <h6><i class="fas fa-check-circle me-2"></i>ظ…ظ„ط®طµ ط§ظ„ط¬ط¯ظˆظ„ط©</h6>
                            <p class="mb-0" id="schedule_summary_text">
                                <?php
                                $scheduleText = "ط³ظٹظ‚ظˆظ… ط§ظ„ظ†ط¸ط§ظ… ط¨ط¹ظ…ظ„ ظ†ط³ط® ط§ط­طھظٹط§ط·ظٹ ";
                                if (($settings->schedule_type ?? 'manual') == 'daily') {
                                    $scheduleText .= "ظٹظˆظ…ظٹط§ظ‹ ط§ظ„ط³ط§ط¹ط© " . ($settings->schedule_time ?? '02:00');
                                } elseif (($settings->schedule_type ?? 'manual') == 'weekly') {
                                    $scheduleText .= "ط£ط³ط¨ظˆط¹ظٹط§ظ‹ ظƒظ„ " . ($settings->schedule_day ?? 'ط§ظ„ط£ط­ط¯') . " ط§ظ„ط³ط§ط¹ط© " . ($settings->schedule_time ?? '02:00');
                                } elseif (($settings->schedule_type ?? 'manual') == 'monthly') {
                                    $scheduleText .= "ط´ظ‡ط±ظٹط§ظ‹ ظٹظˆظ… " . ($settings->schedule_day ?? '1') . " ط§ظ„ط³ط§ط¹ط© " . ($settings->schedule_time ?? '02:00');
                                }
                                echo $scheduleText;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                                <!-- ط§ظ„ظ‚ط³ظ… 3: ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ظ…طھظ‚ط¯ظ…ط© (ط·ظٹ ط§ط®طھظٹط§ط±ظٹ) -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-magic me-2"></i>
                                                ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ظ…طھظ‚ط¯ظ…ط© (ط§ط®طھظٹط§ط±ظٹ)
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="collapse" data-bs-target="#advancedSettings">
                                                <i class="fas fa-chevron-down me-1"></i>
                                                ط¥ط¸ظ‡ط§ط±/ط¥ط®ظپط§ط،
                                            </button>
                                        </div>
                                    </div>
                                    <div class="collapse" id="advancedSettings">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="encryption_enabled" 
                                                                   id="encryption_enabled" <?= ($settings->encryption_enabled ?? 0) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="encryption_enabled">
                                                                <i class="fas fa-lock me-2"></i>
                                                                طھط´ظپظٹط± ط§ظ„ظ†ط³ط®
                                                            </label>
                                                        </div>
                                                        <div class="form-text">
                                                            <small>
                                                                <i class="fas fa-shield-alt me-1"></i>
                                                                ظٹط¬ط¹ظ„ ط§ظ„ظ†ط³ط® ط£ظƒط«ط± ط£ظ…ط§ظ†ط§ظ‹ ظˆظ„ظƒظ† ظٹطھط·ظ„ط¨ ظƒظ„ظ…ط© ظ…ط±ظˆط± ظ„ظپطھط­ظ‡ط§
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="compression_enabled" 
                                                                   id="compression_enabled" <?= ($settings->compression_enabled ?? 1) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="compression_enabled">
                                                                <i class="fas fa-compress-alt me-2"></i>
                                                                ط¶ط؛ط· ط§ظ„ظ†ط³ط®
                                                            </label>
                                                        </div>
                                                        <div class="form-text">
                                                            <small>
                                                                <i class="fas fa-hdd me-1"></i>
                                                                ظٹظˆظپط± ظ…ط³ط§ط­ط© طھط®ط²ظٹظ† ظˆظ„ظƒظ† ظ‚ط¯ ظٹط³طھط؛ط±ظ‚ ظˆظ‚طھط§ظ‹ ط£ط·ظˆظ„
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="auto_cleanup" 
                                                                   id="auto_cleanup" <?= ($settings->auto_cleanup ?? 1) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="auto_cleanup">
                                                                <i class="fas fa-broom me-2"></i>
                                                                طھظ†ط¸ظٹظپ طھظ„ظ‚ط§ط¦ظٹ
                                                            </label>
                                                        </div>
                                                        <div class="form-text">
                                                            <small>
                                                                <i class="fas fa-trash-alt me-1"></i>
                                                                ظٹط­ط°ظپ ط§ظ„ظ†ط³ط® ط§ظ„ظ‚ط¯ظٹظ…ط© طھظ„ظ‚ط§ط¦ظٹط§ظ‹ ط­ط³ط¨ ظپطھط±ط© ط§ظ„ط§ط­طھظپط§ط¸
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ط£ط²ط±ط§ط± ط§ظ„ط­ظپط¸ -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="fas fa-save me-2"></i>
                                                    ط­ظپط¸ ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ
                                                </button>
                                                <a href="<?php echo APP_URL; ?>/backup" class="btn btn-secondary">
                                                    <i class="fas fa-arrow-right me-2"></i>
                                                    ط§ظ„ط¹ظˆط¯ط© ظ„ظ„ظ†ط³ط® ط§ظ„ط§ط­طھظٹط§ط·ظٹ
                                                </a>
                                            </div>
                                            <button type="button" class="btn btn-outline-info" onclick="resetToDefaults()">
                                                <i class="fas fa-undo me-2"></i>
                                                ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط§ظپطھط±ط§ط¶ظٹط©
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- ط§ظ„ط´ط±ظٹط· ط§ظ„ط¬ط§ظ†ط¨ظٹ ظ„ظ„ظ…ط¹ظ„ظˆظ…ط§طھ -->
                        <div class="col-md-4">
                            <div class="sticky-top" style="top: 20px;">
                                <!-- ط¨ط·ط§ظ‚ط© ظ…ط¹ظ„ظˆظ…ط§طھ ط³ط±ظٹط¹ط© -->
                                <div class="card bg-light mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            ظ…ط¹ظ„ظˆظ…ط§طھ ط³ط±ظٹط¹ط©
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6>ًں’، ظ†طµط§ط¦ط­ ط³ط±ظٹط¹ط©:</h6>
                                            <ul class="small ps-3 mb-0">
                                                <li>ط§ظ„ظ†ط³ط® ط§ظ„ظٹظˆظ…ظٹ ظ…ظ†ط§ط³ط¨ ظ„ط¨ظٹط§ظ†ط§طھ ظ…ظ‡ظ…ط©</li>
                                                <li>ط§ظ„ظ†ط³ط® ط§ظ„ط£ط³ط¨ظˆط¹ظٹ ظٹظˆظپط± ظ…ط³ط§ط­ط© طھط®ط²ظٹظ†</li>
                                                <li>30 ظٹظˆظ… ظپطھط±ط© ط¬ظٹط¯ط© ظ„ظ„ط§ط­طھظپط§ط¸</li>
                                                <li>ظ…ظ†طھطµظپ ط§ظ„ظ„ظٹظ„ ظˆظ‚طھ ظ…ط«ط§ظ„ظٹ ظ„ظ„ظ†ط³ط®</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- ط­ط§ظ„ط© ط§ظ„ظ†ط³ط® ط§ظ„ط­ط§ظ„ظٹط© -->
                                <div class="card bg-light">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-history me-2"></i>
                                            ط§ظ„ط³ط¬ظ„ ط§ظ„ط­ط§ظ„ظٹ
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($settings): ?>
                                            <div class="mb-3">
                                                <strong>ًں“… ط¢ط®ط± ظ†ط³ط® ط§ط­طھظٹط§ط·ظٹ:</strong><br>
                                                <span class="<?= $settings->last_backup ? 'text-success' : 'text-warning' ?>">
                                                    <?= $settings->last_backup ? 
                                                        '<i class="fas fa-check-circle me-1"></i>' . date('Y-m-d H:i', strtotime($settings->last_backup)) : 
                                                        '<i class="fas fa-clock me-1"></i>ظ„ظ… ظٹطھظ… ط¨ط¹ط¯' ?>
                                                </span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <strong>ًں”„ ط§ظ„ظ†ط³ط® ط§ظ„طھط§ظ„ظٹ:</strong><br>
                                                <span class="<?= $settings->next_backup ? 'text-info' : 'text-secondary' ?>">
                                                    <?= $settings->next_backup ? 
                                                        '<i class="fas fa-calendar-check me-1"></i>' . date('Y-m-d H:i', strtotime($settings->next_backup)) : 
                                                        '<i class="fas fa-ban me-1"></i>ط؛ظٹط± ظ…ط¬ط¯ظˆظ„' ?>
                                                </span>
                                            </div>

                                            <?php if ($settings->last_backup): ?>
                                                <?php
                                                $lastBackupTime = strtotime($settings->last_backup);
                                                $currentTime = time();
                                                $hoursSinceLastBackup = round(($currentTime - $lastBackupTime) / 3600);
                                                ?>
                                                <div class="alert <?= $hoursSinceLastBackup > 24 ? 'alert-warning' : 'alert-success' ?> small mb-0">
                                                    <i class="fas fa-clock me-1"></i>
                                                    ط¢ط®ط± ظ†ط³ط® ظƒط§ظ† ظ…ظ†ط° <?= $hoursSinceLastBackup ?> ط³ط§ط¹ط©
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                ظ„ط§ طھظˆط¬ط¯ ط¥ط¹ط¯ط§ط¯ط§طھ ظ…ط³ط¨ظ‚ط©
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // طھط­ط¯ظٹط« ظˆطµظپ ظ†ظˆط¹ ط§ظ„ظ†ط³ط®
    document.getElementById('backup_type').addEventListener('change', function() {
        const helpText = document.getElementById('backup_type_help');
        if (this.value === 'full') {
            helpText.textContent = 'ط§ظ„ظ†ط³ط® ط§ظ„ظƒط§ظ…ظ„ ط£ظپط¶ظ„ ظ„ظ„ط£ظ…ط§ظ† ظ„ظƒظ†ظ‡ ظٹط£ط®ط° ظ…ط³ط§ط­ط© ط£ظƒط¨ط±';
        } else {
            helpText.textContent = 'ط§ظ„ظ†ط³ط® ط§ظ„طھط²ط§ظٹط¯ظٹ ط£ط³ط±ط¹ ظˆظٹظˆظپط± ظ…ط³ط§ط­ط©';
        }
    });

    // طھط­ط¯ظٹط« ظ…ط«ط§ظ„ ظپطھط±ط© ط§ظ„ط§ط­طھظپط§ط¸
    document.getElementById('retention_days').addEventListener('input', function() {
        document.getElementById('retention_example_days').textContent = this.value;
    });

    // ط§ظ„طھط­ظƒظ… ظپظٹ ط¥ط¸ظ‡ط§ط± ط®ظٹط§ط±ط§طھ ط§ظ„ط¬ط¯ظˆظ„ط©
    document.getElementById('schedule_type').addEventListener('change', function() {
        const scheduleOptions = document.querySelector('.schedule-options');
        const scheduleSummary = document.getElementById('schedule_summary');
        
        if (this.value !== 'manual') {
            scheduleOptions.style.display = 'block';
            scheduleSummary.style.display = 'block';
            updateScheduleDayOptions(this.value);
            updateScheduleSummary();
        } else {
            scheduleOptions.style.display = 'none';
            scheduleSummary.style.display = 'none';
        }
    });

    // طھط­ط¯ظٹط« ظ…ظ„ط®طµ ط§ظ„ط¬ط¯ظˆظ„ط© ط¹ظ†ط¯ طھط؛ظٹظٹط± ط§ظ„ظˆظ‚طھ
    document.getElementById('schedule_time').addEventListener('change', updateScheduleSummary);
});

function updateScheduleDayOptions(scheduleType) {
    const container = document.getElementById('schedule_day_container');
    let html = '';
    
    if (scheduleType === 'weekly') {
        html = `
            <label class="form-label fw-bold">
                <i class="fas fa-calendar-day me-2"></i>
                ط§ط®طھط± ظٹظˆظ… ط§ظ„ط£ط³ط¨ظˆط¹
            </label>
            <select name="schedule_day" class="form-select" onchange="updateScheduleSummary()">
                <option value="sunday" <?= ($settings->schedule_day ?? 'sunday') == 'sunday' ? 'selected' : '' ?>>ط§ظ„ط£ط­ط¯</option>
                <option value="monday" <?= ($settings->schedule_day ?? 'sunday') == 'monday' ? 'selected' : '' ?>>ط§ظ„ط¥ط«ظ†ظٹظ†</option>
                <option value="tuesday" <?= ($settings->schedule_day ?? 'sunday') == 'tuesday' ? 'selected' : '' ?>>ط§ظ„ط«ظ„ط§ط«ط§ط،</option>
                <option value="wednesday" <?= ($settings->schedule_day ?? 'sunday') == 'wednesday' ? 'selected' : '' ?>>ط§ظ„ط£ط±ط¨ط¹ط§ط،</option>
                <option value="thursday" <?= ($settings->schedule_day ?? 'sunday') == 'thursday' ? 'selected' : '' ?>>ط§ظ„ط®ظ…ظٹط³</option>
                <option value="friday" <?= ($settings->schedule_day ?? 'sunday') == 'friday' ? 'selected' : '' ?>>ط§ظ„ط¬ظ…ط¹ط©</option>
                <option value="saturday" <?= ($settings->schedule_day ?? 'sunday') == 'saturday' ? 'selected' : '' ?>>ط§ظ„ط³ط¨طھ</option>
            </select>
            <div class="form-text">
                <small><i class="fas fa-calendar me-1"></i>ط§ط®طھط± ط§ظ„ظٹظˆظ… ط§ظ„ط°ظٹ طھط±ظٹط¯ ط£ظ† ظٹط¹ظ…ظ„ ظپظٹظ‡ ط§ظ„ظ†ط³ط® ط§ظ„طھظ„ظ‚ط§ط¦ظٹ</small>
            </div>
        `;
    } else if (scheduleType === 'monthly') {
        html = `
            <label class="form-label fw-bold">
                <i class="fas fa-calendar-alt me-2"></i>
                ط§ط®طھط± ظٹظˆظ… ط§ظ„ط´ظ‡ط±
            </label>
            <select name="schedule_day" class="form-select" onchange="updateScheduleSummary()">
                <option value="1" <?= ($settings->schedule_day ?? '1') == '1' ? 'selected' : '' ?>>ط§ظ„ظٹظˆظ… ط§ظ„ط£ظˆظ„ ظ…ظ† ط§ظ„ط´ظ‡ط±</option>
                <option value="15" <?= ($settings->schedule_day ?? '1') == '15' ? 'selected' : '' ?>>ظ…ظ†طھطµظپ ط§ظ„ط´ظ‡ط± (15)</option>
                <option value="28" <?= ($settings->schedule_day ?? '1') == '28' ? 'selected' : '' ?>>ظ†ظ‡ط§ظٹط© ط§ظ„ط´ظ‡ط± (28)</option>
            </select>
            <div class="form-text">
                <small><i class="fas fa-calendar me-1"></i>ط§ط®طھط± ط§ظ„ظٹظˆظ… ط§ظ„ط°ظٹ طھط±ظٹط¯ ط£ظ† ظٹط¹ظ…ظ„ ظپظٹظ‡ ط§ظ„ظ†ط³ط® ط§ظ„طھظ„ظ‚ط§ط¦ظٹ ظƒظ„ ط´ظ‡ط±</small>
            </div>
        `;
    } else {
        html = '';
    }
    
    container.innerHTML = html;
}

function updateScheduleSummary() {
    const scheduleType = document.getElementById('schedule_type').value;
    const scheduleTime = document.getElementById('schedule_time').value;
    const scheduleDay = document.querySelector('select[name="schedule_day"]')?.value;
    
    let summaryText = 'ط³ظٹظ‚ظˆظ… ط§ظ„ظ†ط¸ط§ظ… ط¨ط¹ظ…ظ„ ظ†ط³ط® ط§ط­طھظٹط§ط·ظٹ ';
    
    if (scheduleType === 'daily') {
        summaryText += `ظٹظˆظ…ظٹط§ظ‹ ط§ظ„ط³ط§ط¹ط© ${scheduleTime}`;
    } else if (scheduleType === 'weekly') {
        const days = {
            'sunday': 'ط§ظ„ط£ط­ط¯',
            'monday': 'ط§ظ„ط¥ط«ظ†ظٹظ†', 
            'tuesday': 'ط§ظ„ط«ظ„ط§ط«ط§ط،',
            'wednesday': 'ط§ظ„ط£ط±ط¨ط¹ط§ط،',
            'thursday': 'ط§ظ„ط®ظ…ظٹط³',
            'friday': 'ط§ظ„ط¬ظ…ط¹ط©',
            'saturday': 'ط§ظ„ط³ط¨طھ'
        };
        summaryText += `ط£ط³ط¨ظˆط¹ظٹط§ظ‹ ظƒظ„ ${days[scheduleDay]} ط§ظ„ط³ط§ط¹ط© ${scheduleTime}`;
    } else if (scheduleType === 'monthly') {
        summaryText += `ط´ظ‡ط±ظٹط§ظ‹ ظٹظˆظ… ${scheduleDay} ط§ظ„ط³ط§ط¹ط© ${scheduleTime}`;
    }
    
    document.getElementById('schedule_summary_text').textContent = summaryText;
}

function resetToDefaults() {
    if (confirm('ظ‡ظ„ طھط±ظٹط¯ ط§ط³طھط¹ط§ط¯ط© ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط§ظپطھط±ط§ط¶ظٹط©طں ط³ظٹطھظ… ظپظ‚ط¯ط§ظ† ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط­ط§ظ„ظٹط©.')) {
        document.getElementById('backup_type').value = 'full';
        document.getElementById('retention_days').value = '30';
        document.getElementById('schedule_type').value = 'manual';
        document.getElementById('schedule_time').value = '02:00';
        document.getElementById('encryption_enabled').checked = false;
        document.getElementById('compression_enabled').checked = true;
        document.getElementById('auto_cleanup').checked = true;
        
        // طھط­ط¯ظٹط« ط§ظ„ظˆط§ط¬ظ‡ط©
        document.getElementById('backup_type').dispatchEvent(new Event('change'));
        document.getElementById('schedule_type').dispatchEvent(new Event('change'));
        document.getElementById('retention_days').dispatchEvent(new Event('input'));
        
        alert('طھظ… ط§ط³طھط¹ط§ط¯ط© ط§ظ„ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ط§ظپطھط±ط§ط¶ظٹط©. ظ„ط§ طھظ†ط³ ط­ظپط¸ ط§ظ„طھط؛ظٹظٹط±ط§طھ.');
    }
}

// طھظ‡ظٹط¦ط© ط£ظˆظ„ظٹط© ط¹ظ†ط¯ طھط­ظ…ظٹظ„ ط§ظ„طµظپط­ط©
document.addEventListener('DOMContentLoaded', function() {
    updateScheduleDayOptions(document.getElementById('schedule_type').value);
    updateScheduleSummary();
});
</script>

<?php
$this->view('layouts/footer');
?>
