<?php
require_once dirname(__DIR__, 2) . '/config/languages.php';
?>
<footer class="footer">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <h5 class="fw-bold mb-1" style="letter-spacing: 2px;">FISHINGLORY</h5>
                <p class="small opacity-75 mb-0"><?= __('footer_tagline') ?></p>
            </div>
            <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                <p class="small mb-0">&copy; <?= date('Y') ?> FISHINGLORY. <?= __('all_rights_reserved') ?></p>
            </div>
        </div>
    </div>
</footer>