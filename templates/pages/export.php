<?php
/**
 * Export catches page template.
 */
$isBg = ($_SESSION['lang'] ?? 'en') === 'bg';
?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-download me-2"></i><?= $isBg ? 'Експорт на улови' : 'Export Catches' ?></h2>
            <p class="text-muted"><?= $isBg ? 'Изтеглете вашите улови като GPX файл или PDF' : 'Download your catches as GPX file or PDF' ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-8 col-lg-6">
            <div class="search-filters">
                <h5 class="mb-3"><i class="fas fa-filter me-2"></i><?= $isBg ? 'Филтри' : 'Filters' ?></h5>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label"><?= $isBg ? 'От дата' : 'Date From' ?></label>
                        <input type="date" id="exportDateFrom" class="form-control">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label"><?= $isBg ? 'До дата' : 'Date To' ?></label>
                        <input type="date" id="exportDateTo" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= $isBg ? 'Вид риба' : 'Fish Species' ?></label>
                    <input type="text" id="exportSpecies" class="form-control"
                           placeholder="<?= $isBg ? 'Оставете празно за всички видове' : 'Leave empty for all species' ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-icon text-success">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h4>GPX</h4>
                <p class="text-muted"><?= $isBg ? 'Експорт като GPX файл с GPS координати. Отворете в Google Earth, Garmin или друга GPS апликация.' : 'Export as GPX file with GPS coordinates. Open in Google Earth, Garmin, or other GPS apps.' ?></p>
                <button id="exportGpxBtn" class="btn btn-success btn-lg">
                    <i class="fas fa-download me-2"></i><?= $isBg ? 'Изтегли GPX' : 'Download GPX' ?>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="export-card">
                <div class="export-icon text-primary">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <h4>PDF</h4>
                <p class="text-muted"><?= $isBg ? 'Генерирайте красив дневник на улова за принтиране или запазване като PDF.' : 'Generate a beautiful catch journal for printing or saving as PDF.' ?></p>
                <button id="exportPdfBtn" class="btn btn-primary btn-lg">
                    <i class="fas fa-print me-2"></i><?= $isBg ? 'Отвори за печат' : 'Open for Print' ?>
                </button>
            </div>
        </div>
    </div>
</div>
