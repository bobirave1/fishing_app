<?php
/**
 * Advanced Search page template.
 */
$isBg = ($_SESSION['lang'] ?? 'en') === 'bg';
?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-search me-2"></i><?= $isBg ? 'Разширено търсене' : 'Advanced Search' ?></h2>
        </div>
    </div>

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-md-4 col-lg-3 mb-4">
            <form id="advancedSearchForm" class="search-filters">
                <!-- Search Query -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= $isBg ? 'Ключова дума' : 'Keyword' ?></label>
                    <input type="text" id="searchQuery" class="form-control"
                           placeholder="<?= $isBg ? 'Търсене...' : 'Search...' ?>"
                           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </div>

                <!-- Type Filter -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= $isBg ? 'Категория' : 'Category' ?></label>
                    <select id="searchType" class="form-select">
                        <option value="all"><?= $isBg ? 'Всички' : 'All' ?></option>
                        <option value="posts"><?= $isBg ? 'Публикации' : 'Posts' ?></option>
                        <option value="users"><?= $isBg ? 'Потребители' : 'Users' ?></option>
                        <option value="spots"><?= $isBg ? 'Водоеми' : 'Fishing Spots' ?></option>
                        <option value="catches"><?= $isBg ? 'Улови' : 'Catches' ?></option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= $isBg ? 'Период' : 'Date Range' ?></label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" id="searchDateFrom" class="form-control form-control-sm"
                                   placeholder="<?= $isBg ? 'От' : 'From' ?>">
                        </div>
                        <div class="col-6">
                            <input type="date" id="searchDateTo" class="form-control form-control-sm"
                                   placeholder="<?= $isBg ? 'До' : 'To' ?>">
                        </div>
                    </div>
                </div>

                <!-- Species -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= $isBg ? 'Вид риба' : 'Fish Species' ?></label>
                    <input type="text" id="searchSpecies" class="form-control"
                           placeholder="<?= $isBg ? 'напр. шаран, сом...' : 'e.g. carp, catfish...' ?>">
                </div>

                <!-- Location -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= $isBg ? 'Местоположение' : 'Location' ?></label>
                    <input type="hidden" id="searchLat">
                    <input type="hidden" id="searchLon">
                    <button type="button" id="useMyLocation" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                        <i class="fas fa-map-marker-alt"></i> <?= $isBg ? 'Моята локация' : 'My Location' ?>
                    </button>
                    <button type="button" id="clearLocation" class="btn btn-outline-danger btn-sm w-100 d-none">
                        <i class="fas fa-times"></i> <?= $isBg ? 'Изчисти' : 'Clear' ?>
                    </button>
                    <select id="searchRadius" class="form-select form-select-sm mt-2">
                        <option value="10">10 km</option>
                        <option value="25">25 km</option>
                        <option value="50" selected>50 km</option>
                        <option value="100">100 km</option>
                        <option value="200">200 km</option>
                    </select>
                </div>

                <!-- Sort -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= $isBg ? 'Подреждане' : 'Sort By' ?></label>
                    <select id="searchSort" class="form-select">
                        <option value="relevance"><?= $isBg ? 'Релевантност' : 'Relevance' ?></option>
                        <option value="date_desc"><?= $isBg ? 'Най-нови' : 'Newest First' ?></option>
                        <option value="date_asc"><?= $isBg ? 'Най-стари' : 'Oldest First' ?></option>
                        <option value="likes"><?= $isBg ? 'Най-харесвани' : 'Most Liked' ?></option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-search me-1"></i> <?= $isBg ? 'Търси' : 'Search' ?>
                </button>
            </form>
        </div>

        <!-- Results -->
        <div class="col-md-8 col-lg-9">
            <div id="searchResults">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-search fa-3x mb-3" style="opacity: 0.3;"></i>
                    <p><?= $isBg ? 'Използвайте филтрите, за да намерите резултати' : 'Use the filters to find results' ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
