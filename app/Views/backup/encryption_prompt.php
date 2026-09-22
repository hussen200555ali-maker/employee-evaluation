<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">ًں”گ ط¥ط¯ط®ط§ظ„ ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط±</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>ظ…ط¹ظ„ظˆظ…ط§طھ ط§ظ„ظ†ط³ط®ط©:</h6>
                        <p><strong>ط§ظ„ط§ط³ظ…:</strong> <?php echo htmlspecialchars($backup->backup_name); ?></p>
                        <p><strong>ط§ظ„طھط§ط±ظٹط®:</strong> <?php echo date('Y-m-d H:i', strtotime($backup->created_at)); ?></p>
                        <p><strong>ط§ظ„ط­ط¬ظ…:</strong> <?php echo round($backup->file_size / 1024 / 1024, 2); ?> MB</p>
                    </div>
                    
                    <p class="text-muted">ظ‡ط°ظ‡ ط§ظ„ظ†ط³ط®ط© ظ…ط´ظپط±ط©. ظٹط±ط¬ظ‰ ط¥ط¯ط®ط§ظ„ ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط± ظ„طھط­ظ…ظٹظ„ظ‡ط§.</p>
                    
                    <form method="POST">
                <?= csrf_field() ?>
                         <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <div class="mb-3">
                            <label for="encryption_key" class="form-label">ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط±</label>
                            <input type="password" class="form-control" id="encryption_key" 
                                   name="encryption_key" required placeholder="ط£ط¯ط®ظ„ ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط±...">
                            <div class="form-text">ط£ط¯ط®ظ„ ط§ظ„ظ…ظپطھط§ط­ ط§ظ„ط°ظٹ طھظ… ط§ط³طھط®ط¯ط§ظ…ظ‡ ظ„طھط´ظپظٹط± ظ‡ط°ظ‡ ط§ظ„ظ†ط³ط®ط©.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-unlock"></i> ظپظƒ ط§ظ„طھط´ظپظٹط± ظˆط§ظ„طھط­ظ…ظٹظ„
                            </button>
                            <a href="<?php echo APP_URL; ?>/backup" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i> ط±ط¬ظˆط¹ ط¥ظ„ظ‰ ظ‚ط§ط¦ظ…ط© ط§ظ„ظ†ط³ط®
                            </a>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>ظ…ظ„ط§ط­ط¸ط©:</strong> ط¥ط°ط§ ظپظ‚ط¯طھ ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط±طŒ ظ„ظ† طھطھظ…ظƒظ† ظ…ظ† ظپطھط­ ظ‡ط°ظ‡ ط§ظ„ظ†ط³ط®ط©.
                                طھط£ظƒط¯ ظ…ظ† ط­ظپط¸ ط§ظ„ظ…ظپط§طھظٹط­ ظپظٹ ظ…ظƒط§ظ† ط¢ظ…ظ†.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->view('layouts/footer');
?>
