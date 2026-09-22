<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">ًں”گ ط¥ط¯ط®ط§ظ„ ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط± ظ„ظ„ط§ط³طھط¹ط§ط¯ط©</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>ظ…ط¹ظ„ظˆظ…ط§طھ ط§ظ„ظ†ط³ط®ط©:</h6>
                        <p><strong>ط§ظ„ط§ط³ظ…:</strong> <?php echo htmlspecialchars($backup->backup_name); ?></p>
                        <p><strong>ط§ظ„طھط§ط±ظٹط®:</strong> <?php echo date('Y-m-d H:i', strtotime($backup->created_at)); ?></p>
                        <p><strong>ط§ظ„ظ†ظˆط¹:</strong> <?php echo $backup->backup_type === 'full' ? 'ظƒط§ظ…ظ„ط©' : 'طھط²ط§ظٹط¯ظٹط©'; ?></p>
                    </div>
                    
                    <p class="text-muted">ظ‡ط°ظ‡ ط§ظ„ظ†ط³ط®ط© ظ…ط´ظپط±ط©. ظٹط±ط¬ظ‰ ط¥ط¯ط®ط§ظ„ ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط± ظ„ط§ط³طھط¹ط§ط¯طھظ‡ط§.</p>
                    
                    <form method="POST">
                <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="encryption_key" class="form-label">ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط±</label>
                            <input type="password" class="form-control" id="encryption_key" 
                                   name="encryption_key" required placeholder="ط£ط¯ط®ظ„ ظ…ظپطھط§ط­ ط§ظ„طھط´ظپظٹط±...">
                            <div class="form-text">ط£ط¯ط®ظ„ ط§ظ„ظ…ظپطھط§ط­ ط§ظ„ط°ظٹ طھظ… ط§ط³طھط®ط¯ط§ظ…ظ‡ ظ„طھط´ظپظٹط± ظ‡ط°ظ‡ ط§ظ„ظ†ط³ط®ط©.</div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> طھط­ط°ظٹط± ظ…ظ‡ظ…:</h6>
                            <ul>
                                <li>ط§ط³طھط¹ط§ط¯ط© ط§ظ„ظ†ط³ط®ط© ط§ظ„ط§ط­طھظٹط§ط·ظٹط© ط³طھط­ط°ظپ ط¬ظ…ظٹط¹ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط­ط§ظ„ظٹط©</li>
                                <li>ط³ظٹطھظ… ط§ط³طھط¨ط¯ط§ظ„ ط¬ظ…ظٹط¹ ط§ظ„ط¬ط¯ط§ظˆظ„ ط§ظ„ط­ط§ظ„ظٹط©</li>
                                <li>ظ‡ط°ظ‡ ط§ظ„ط¹ظ…ظ„ظٹط© ظ„ط§ ظٹظ…ظƒظ† ط§ظ„طھط±ط§ط¬ط¹ ط¹ظ†ظ‡ط§</li>
                                <li>طھط£ظƒط¯ ظ…ظ† ظˆط¬ظˆط¯ ظ†ط³ط®ط© ط§ط­طھظٹط§ط·ظٹط© ط­ط¯ظٹط«ط© ظ‚ط¨ظ„ ط§ظ„ظ…طھط§ط¨ط¹ط©</li>
                            </ul>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-undo"></i> ظپظƒ ط§ظ„طھط´ظپظٹط± ظˆط§ظ„ط§ط³طھط¹ط§ط¯ط©
                            </button>
                            <a href="<?php echo APP_URL; ?>/backup" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i> ط±ط¬ظˆط¹ ط¥ظ„ظ‰ ظ‚ط§ط¦ظ…ط© ط§ظ„ظ†ط³ط®
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->view('layouts/footer');
?>
