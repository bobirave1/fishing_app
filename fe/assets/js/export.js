/**
 * FISHINGLORY — Export catches JS.
 */
(function () {
    'use strict';

    var isBg = (document.documentElement.lang || '').startsWith('bg');

    function init() {
        var gpxBtn = document.getElementById('exportGpxBtn');
        var pdfBtn = document.getElementById('exportPdfBtn');

        if (gpxBtn) gpxBtn.addEventListener('click', exportGpx);
        if (pdfBtn) pdfBtn.addEventListener('click', exportPdf);
    }

    function getFilters() {
        var params = new URLSearchParams();
        var dateFrom = (document.getElementById('exportDateFrom') || {}).value;
        var dateTo = (document.getElementById('exportDateTo') || {}).value;
        var species = (document.getElementById('exportSpecies') || {}).value;

        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
        if (species) params.set('species', species);
        return params.toString();
    }

    function exportGpx() {
        var filters = getFilters();
        var url = resolvePath('api/export/gpx') + (filters ? '?' + filters : '');
        window.location.href = url;
    }

    function exportPdf() {
        var filters = getFilters();
        var url = resolvePath('api/export/pdf') + (filters ? '?' + filters : '');
        window.open(url, '_blank');
    }

    document.addEventListener('DOMContentLoaded', init);
})();
