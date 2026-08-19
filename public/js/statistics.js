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

    $('#category, #week, #year, #discount_percent').select2({
        width: '200px',
        placeholder: 'Alla',
    });

    $(document).on('click', '.product-row', function () {
        const productId = $(this).data('product-id');
        const filterQuery = window.location.search;

        $.getJSON(`/statistics/product/${productId}${filterQuery}`)
            .done(function (data) {
                $('#modalTitle').text(data.name ? `${data.name} (${data.product_id})` : data.product_id);

                const rows = data.events.map(event => `
                    <tr>
                        <td>${event.scanned_at}</td>
                        <td>${event.regular_price} kr</td>
                        <td>${event.reduced_price} kr</td>
                        <td>${event.discount_amount} kr</td>
                        <td>${event.discount_percent}%</td>
                    </tr>
                `).join('');

                $('#modalTableBody').html(rows);
                $('#productModal').addClass('active');
            })
            .fail(function () {
                $('#modalTableBody').html('<tr><td colspan="5">Could not load product data.</td></tr>');
                $('#productModal').addClass('active');
            });
    });

    $(document).on('click', '#closeModal', function () {
        $('#productModal').removeClass('active');
    });

    // Stäng modal om man klickar utanför boxen
    $(document).on('click', '#productModal', function (e) {
        if (e.target.id === 'productModal') {
            $('#productModal').removeClass('active');
        }
    });

    /** Compare dateintervals */
    function updatePeriodTypeVisibility() {
        const selectedType = $('input[name="period_type"]:checked').val();
        $('.period-select').hide();
        $(`.period-select[data-type="${selectedType}"]`).show();
    }

    function syncHiddenFields() {
        const selectedType = $('input[name="period_type"]:checked').val();
        const valueA = $(`.period-a-input[data-type="${selectedType}"]`).val();
        const valueB = $(`.period-b-input[data-type="${selectedType}"]`).val();

        $('#period_a_hidden').val(valueA);
        $('#period_b_hidden').val(valueB);
    }

    $('input[name="period_type"]').on('change', function () {
        updatePeriodTypeVisibility();
        syncHiddenFields();
    });

    $('.period-a-input, .period-b-input').on('change', syncHiddenFields);

    $('#compareForm').on('submit', function () {
        syncHiddenFields();
    });

    // Initial state vid sidladdning
    updatePeriodTypeVisibility();
    syncHiddenFields();
});