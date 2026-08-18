$(document).ready(function () {
    $('.filter-button').on('change', function () {
        $('#loadingOverlay').addClass('active');
        $('#filterForm').submit();
    });

    // Extra säkerhet: visa spinner även om formuläret skickas på annat sätt
    $('#filterForm').on('submit', function () {
        $('#loadingOverlay').addClass('active');
    });

    // Initiera Select2 på önskat id
    $('#category').select2({
        placeholder: "Alla kategorier",
        allowClear: true
    });

    $('#week').select2({
        placeholder: "Alla veckor",
        allowClear: true
    });

    $('#month').select2({
        placeholder: "Alla månader",
        allowClear: true
    });

    $('#year').select2({
        placeholder: "Alla år",
        allowClear: true
    });
});