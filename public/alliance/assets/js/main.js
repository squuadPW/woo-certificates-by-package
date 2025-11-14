jQuery(document).ready(function($) {

    function edusystem_wc_price(price) {
        let number = parseFloat(price);

        if (isNaN(number)) {
            return price;
        }

        const p = edusystem_alliance_data.wc_format_params;

        const decimals = p.currency_format_num_decimals;
        const decimalSep = p.currency_format_decimal_sep;
        const thousandSep = p.currency_format_thousand_sep;
        const symbol = p.currency_format_symbol;
        // La cadena de formato, ej: '%1$s%2$s' o '%2$s %1$s'
        let format = p.currency_format;

        // Formatear el número (separadores y decimales)
        let formattedNumber = number.toFixed(decimals);

        let parts = formattedNumber.split('.');
        let integerPart = parts[0];
        let decimalPart = parts.length > 1 ? decimalSep + parts[1] : '';

        // Aplicar separador de miles
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);

        let priceString = integerPart + decimalPart;

        // Aplicar el formato de moneda usando los placeholders %1$s y %2$s
        let finalPrice = format
            .replace('%1$s', symbol)
            .replace('%2$s', priceString);

        return finalPrice;
    };

    $('body').on('click', '#toggle-table', function () {
        let elem = $(this);
        let table_invoices = $("#table-invoices");
        let table_payments = $("#table-payments");
        let card_alliance_orders = $("#content-card-alliance-orders");
        let card_alliance_transactions = $("#content-card-alliance-transactions");

        if (elem.text().trim() === edusystem_alliance_data.messages.show_payments) {
            table_invoices.attr('style', 'display: none;');
            table_payments.attr('style', 'display: table;');
            elem.text(edusystem_alliance_data.messages.show_orders);
            card_alliance_orders.attr('style', 'display: none;');
            card_alliance_transactions.attr('style', 'display: initial;');
        } else {
            table_invoices.attr('style', 'display: table;');
            table_payments.attr('style', 'display: none;');
            elem.text(edusystem_alliance_data.messages.show_payments);
            card_alliance_orders.attr('style', 'display: initial;');
            card_alliance_transactions.attr('style', 'display: none;');
        }
    });

    $('body').on('change', '#alliance-typeFilter', function () {
        let elem = $(this);
        if (elem.val() === 'custom') {
            $("#edusystem-content-custom").attr('style', 'display: initial;');
            $("#flatpickr-filter-custom").attr('required', true);
            $("#flatpickr-filter-custom").trigger("focus");
        } else {
            $("#edusystem-content-custom").attr('style', 'display: none;');
            $("#flatpickr-filter-custom").attr('required', false);
        }
    });

    $("#flatpickr-filter-custom").flatpickr({
        mode: "range",
        dateFormat: "m/d/Y",
        defaultDate: [
            edusystem_alliance_data.messages.start_date,
            edusystem_alliance_data.messages.start_date
        ],
        maxDate: edusystem_alliance_data.messages.start_date,
    });

    $('body').on('click', '#update_data', function (e) {
        e.preventDefault();

        const filter = $('#alliance-typeFilter').val();
        const alliance_id = edusystem_alliance_data.alliance_id;
        let custom = $('#flatpickr-filter-custom').val();

        if (filter === "today") {
            custom = edusystem_alliance_data.messages.start_date;
        }

        const btn = $(this);
        btn.prop('disabled', true).text('Actualizando...');

        $.ajax({
            url: edusystem_alliance_data.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_list_fee_alliance',
                nonce: edusystem_alliance_data.nonce,
                filter: filter,
                custom: custom,
                alliance_id: alliance_id
            },
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    // console.log('Datos actualizados:', data);
                    $('#card-alliance-balance').html(data.current_invoice.total);
                    $('#card-alliance-paid').html(data.transactions.total_paid);
                    $('#card-alliance-pending').html(data.transactions.total_pending);
                    $('#card-alliance-orders').html(data.current_invoice.total);
                    $('#card-alliance-transactions').html(data.transactions.total);

                    if (data.current_invoice.orders.length > 0) {
                        $("#table_tbody-invoices").html("");
                        for (let i = 0; i < data.current_invoice.orders.length; i++) {
                            const order = data.current_invoice.orders[i];
                            let row = '<tr class="woocommerce-MyAccount-invoices-table__row">' +
                                '<td data-title="'+ edusystem_alliance_data.messages.payment_id +'">' +
                                    '# ' + order.order_id
                                '</td>' +
                                '<td data-title="'+ edusystem_alliance_data.messages.customer +'">' + order.customer +'</td>' +
                                '<td data-title="'+ edusystem_alliance_data.messages.fee +'">' + edusystem_wc_price(order.fee) + '</td>' +
                                '<td data-title="'+ edusystem_alliance_data.messages.created +'"><b>' + order.created_at +'</b></td>' +
                            '</tr>';
                            $("#table_tbody-invoices").append(row);
                        }
                    } else {
                        $("#table_tbody-invoices").html('<tr class="woocommerce-MyAccount-alliances-table__row">'+
                            '<td colspan="4" style="text-align:center;">'+ edusystem_alliance_data.messages.not_records +'</td>'+
                        '</tr>');
                    }

                    if (data.transactions.orders.length > 0) {
                        $("#table_tbody-payments").html("");
                        for (let i = 0; i < data.transactions.orders.length; i++) {
                            const order = data.transactions.orders[i];
                            let row = '<tr class="woocommerce-MyAccount-invoices-table__row">' +
                                '<td data-title="'+ edusystem_alliance_data.messages.status +'">' + order.status + '</td>' +
                                '<td data-title="'+ edusystem_alliance_data.messages.month +'">' + order.month + '</td>' +
                                '<td data-title="'+ edusystem_alliance_data.messages.amount +'">' + edusystem_wc_price(order.amount) + '</td>' +
                                '<td data-title="'+ edusystem_alliance_data.messages.total_orders +'"><b>' + order.total_orders + '</b></td>' +
                            '</tr>';
                            $("#table_tbody-payments").append(row);
                        }
                    } else {
                        $("#table_tbody-payments").html('<tr class="woocommerce-MyAccount-alliances-table__row">'+
                            '<td colspan="4" style="text-align:center;">'+ edusystem_alliance_data.messages.not_records +'</td>'+
                        '</tr>');
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            },
            complete: function () {
                btn.prop('disabled', false).text(edusystem_alliance_data.messages.update_data);
            }
        });
    });

});