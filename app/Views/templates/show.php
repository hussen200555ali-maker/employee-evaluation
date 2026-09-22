<?php 
// طھط¶ظ…ظٹظ† ط§ظ„ظ‡ظٹط¯ط± ط§ظ„ظ…ط´طھط±ظƒ.
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <div>
        <!-- ط¹ظ†ظˆط§ظ† ط§ظ„طµظپط­ط© ط§ظ„ط±ط¦ظٹط³ظٹ -->
        <h2><i class="fas fa-tasks"></i> ط¥ط¯ط§ط±ط© ظ…ط¹ط§ظٹظٹط± ط§ظ„طھظ‚ظٹظٹظ…</h2>
        <!-- ط¹ظ†ظˆط§ظ† ظپط±ط¹ظٹ ظٹظˆط¶ط­ ط§ط³ظ… ط§ظ„ظ‚ط§ظ„ط¨ ط§ظ„ط­ط§ظ„ظٹ -->
        <p class="page-subtitle">ط£ظ†طھ طھظ‚ظˆظ… ط¨طھط¹ط¯ظٹظ„ ط§ظ„ظ‚ط§ظ„ط¨: "<?php echo htmlspecialchars($template->name); ?>"</p>
    </div>
    <a href="<?php echo APP_URL; ?>/templates" class="btn-back">
        <i class="fas fa-arrow-right"></i> ط§ظ„ط¹ظˆط¯ط© ط¥ظ„ظ‰ ظ‚ط§ط¦ظ…ط© ط§ظ„ظ‚ظˆط§ظ„ط¨
    </a>
</div>

<!-- ط­ط§ظˆظٹط© ط±ط¦ظٹط³ظٹط© ظ„ظ„طµظپط­ط© ظ…ظ‚ط³ظ…ط© ط¥ظ„ظ‰ ط¹ظ…ظˆط¯ظٹظ† -->
<div class="two-column-layout">
    <!-- ط§ظ„ط¹ظ…ظˆط¯ ط§ظ„ط£ظٹط³ط±: ظ‚ط§ط¦ظ…ط© ط§ظ„ظ…ط¹ط§ظٹظٹط± ط§ظ„ط­ط§ظ„ظٹط© -->
    <div class="main-column">
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-stream"></i> ط§ظ„ظ…ط¹ط§ظٹظٹط± ط§ظ„ط­ط§ظ„ظٹط©</h3>
                <!-- ط³ظ†ط¶ظٹظپ ظ‡ظ†ط§ ط¥ط¬ظ…ط§ظ„ظٹ ط§ظ„ط£ظˆط²ط§ظ† ظ„ط§ط­ظ‚ط§ظ‹ -->
                <span class="badge-count" id="total-weight-badge">
                    ط¥ط¬ظ…ط§ظ„ظٹ ط§ظ„ط£ظˆط²ط§ظ†: <?php 
                        $totalWeight = 0;
                        foreach ($criteria as $criterion) {
                            $totalWeight += $criterion->weight_percent;
                        }
                        echo $totalWeight;
                    ?>%
                </span>
            </div>
            <div class="card-body">
                <div id="criteria-list">
                    <?php if (!empty($criteria)): ?>
                        <?php foreach ($criteria as $criterion): ?>
                            <!-- ط¨ط·ط§ظ‚ط© ظ„ظƒظ„ ظ…ط¹ظٹط§ط± -->
                          <div class="criterion-item" data-id="<?php echo $criterion->id; ?>">
                            <div class="criterion-info">
                                <span class="criterion-name"><?php echo htmlspecialchars($criterion->name); ?></span>
                                <span class="criterion-weight"><?php echo htmlspecialchars($criterion->weight_percent); ?>%</span>
                            </div>
                            <div class="criterion-actions">
                                <!-- ===> ط§ظ„طھط­ط¯ظٹط« ظ‡ظ†ط§: ط¥ط¶ط§ظپط© data attributes <=== -->
                               <a href="<?php echo APP_URL . '/templates/' . $template->id . '/criteria/' . $criterion->id; ?>" class="btn-action-text" title="ط¥ط¯ط§ط±ط© ط§ظ„ط£ظ‚ط³ط§ظ… ط§ظ„طھظپطµظٹظ„ظٹط©">
                                  <i class="fas fa-list-ul"></i> ط¥ط¯ط§ط±ط© ط§ظ„ط£ظ‚ط³ط§ظ…
                               </a>
                                <button class="btn-icon-sm btn-edit-criterion" 
                                        data-id="<?php echo $criterion->id; ?>"
                                        data-name="<?php echo htmlspecialchars($criterion->name); ?>"
                                        data-weight="<?php echo htmlspecialchars($criterion->weight_percent); ?>"
                                        title="طھط¹ط¯ظٹظ„">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-icon-sm btn-delete-criterion" 
                                        data-id="<?php echo $criterion->id; ?>"
                                        data-name="<?php echo htmlspecialchars($criterion->name); ?>"
                                        title="ط­ط°ظپ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data-sm">ظ„ظ… ظٹطھظ… ط¥ط¶ط§ظپط© ط£ظٹ ظ…ط¹ط§ظٹظٹط± ط¨ط¹ط¯. ط§ط¨ط¯ط£ ط¨ط¥ط¶ط§ظپط© ظ…ط¹ظٹط§ط± ظ…ظ† ط§ظ„ظ†ظ…ظˆط°ط¬ ط§ظ„ظ…ط¬ط§ظˆط±.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ط§ظ„ط¹ظ…ظˆط¯ ط§ظ„ط£ظٹظ…ظ†: ظ†ظ…ظˆط°ط¬ ط¥ط¶ط§ظپط©/طھط¹ط¯ظٹظ„ ظ…ط¹ظٹط§ط± -->
    <div class="sidebar-column">
        <div class="content-card">
            <div class="card-header">
                <h3 id="criterion-form-title"><i class="fas fa-plus-circle"></i> ط¥ط¶ط§ظپط© ظ…ط¹ظٹط§ط± ط¬ط¯ظٹط¯</h3>
            </div>
            <div class="card-body">
                <!-- ظ†ظ…ظˆط°ط¬ ط¥ط¶ط§ظپط© ظ…ط¹ظٹط§ط± ط¬ط¯ظٹط¯ -->
                <form action="<?php echo APP_URL; ?>/criteria" method="POST" id="criterion-form">
                <?= csrf_field() ?>
                    <!-- ط­ظ‚ظ„ ظ…ط®ظپظٹ ظ„ظ…ط¹ط±ظپ ط§ظ„ظ‚ط§ظ„ط¨ -->
                    <input type="hidden" name="template_id" value="<?php echo $template->id; ?>">
                    <!-- ط­ظ‚ظ„ ظ…ط®ظپظٹ ظ„ظ…ط¹ط±ظپ ط§ظ„ظ…ط¹ظٹط§ط± (ط³ظ†ط³طھط®ط¯ظ…ظ‡ ظپظٹ ط§ظ„طھط¹ط¯ظٹظ„) -->
                    <input type="hidden" name="id" id="criterion-id">

                    <div class="form-group mb-3">
                        <label for="criterion-name" class="form-label">ط§ط³ظ… ط§ظ„ظ…ط¹ظٹط§ط±</label>
                        <input type="text" class="form-control" id="criterion-name" name="name" placeholder="ظ…ط«ط§ظ„: ط§ظ„ظ…ظ‡ط§ط±ط§طھ ط§ظ„ظپظ†ظٹط©" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="criterion-weight" class="form-label">ط§ظ„ظˆط²ظ† ط§ظ„ظ†ط³ط¨ظٹ (%)</label>
                        <input type="number" class="form-control" id="criterion-weight" name="weight_percent" min="1" max="100" placeholder="ظ…ط«ط§ظ„: 40" required>
                        <div class="form-text">ظٹط¬ط¨ ط£ظ† ظٹظƒظˆظ† ظ…ط¬ظ…ظˆط¹ ط£ظˆط²ط§ظ† ظƒظ„ ط§ظ„ظ…ط¹ط§ظٹظٹط± 100%.</div>
                    </div>
                    <div class="form-actions-split">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <span id="criterion-submit-btn-text">ط­ظپط¸ ط§ظ„ظ…ط¹ظٹط§ط±</span>
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancel-edit-btn" style="display: none;">
                            ط¥ظ„ط؛ط§ط، ط§ظ„طھط¹ط¯ظٹظ„
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
// طھط¶ظ…ظٹظ† ط§ظ„ظپظˆطھط± ط§ظ„ظ…ط´طھط±ظƒ.
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>

