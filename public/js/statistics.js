$(document).ready(function () {
    $('#category,#week, #month, #year').on('change', function () {
        $('#loadingOverlay').addClass('active');
        $('#filterForm').submit();
    });

    // Extra säkerhet: visa spinner även om formuläret skickas på annat sätt
    $('#filterForm').on('submit', function () {
        $('#loadingOverlay').addClass('active');
    });
});