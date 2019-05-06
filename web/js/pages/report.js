$(document).ready(function(){

    // https://rawgit.com/longbill/jquery-date-range-picker/master/index.html
    var datePickerField = $(".report-date-picker");
    datePickerField.dateRangePicker({
        format: 'MMM D YYYY',
        autoClose: true,
        selectForward: true,
        setValue: function(s) {
            if(!$(this).attr('readonly') && !$(this).is(':disabled') && s != $(this).val()) {
                console.log($(this).val(s));
            }
        },
        showShortcuts: true,
        customShortcuts:
            [
                {
                    name: 'Today',
                    dates : function()
                    {
                        var start = moment().toDate();
                        var end   = moment().toDate();
                        return [start,end];
                    }
                }
            ]
    }).bind('datepicker-change',function(event,obj){
        $("#date_from").val( moment(obj.date1).format('YYYY-MM-DD') );
        $("#date_to").val( moment(obj.date2).format('YYYY-MM-DD') );
    });

    datePickerField.data('dateRangePicker').setDateRange(dateFrom, dateTo, true);

    // Generic report table with two columns
    var dataTable = $('#report-table').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        "bLengthChange": false,
        "bPaginate": false,
        "order": [[ 1, "desc" ]],
        "columns": [
            null,
            { className: "sum" }
        ],
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV'
            }
        ]
    });
    dataTable.buttons().container().appendTo('#report-table_length').css('padding-right', '20px');
    dataTable.columns('.sum').every( function () {
        if ( $("#report-table").find('tr').size() > 3 ) {
            var sum = this
                .data()
                .reduce( function (a,b) {
                    return a*1 + b*1;
                } );
            $(this.footer()).html(sum);
        }
    } );

    // Sum payment columns
    var paymentsTable = $('#payment-report-table').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        "bLengthChange": false,
        "bPaginate": true,
        pageLength: 50,
        "columns": [
            null,
            null,
            null,
            null,
            null,
            null
        ],
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV'
            }
        ],
        "order": [[ 0, "desc" ]]
    });
    paymentsTable.buttons().container().appendTo('#payment-report-table_length').css('padding-right', '20px');

    // Sum cost columns
    var costsTable = $('#costs-report-table').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        "bLengthChange": false,
        "bPaginate": false,
        "columns": [
            null,
            null,
            null,
            null,
            { className: "sum" },
            null
        ],
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV'
            }
        ]
    });
    costsTable.columns('.sum').every( function () {
        if ( $("#costs-report-table").find('tr').size() > 3 ) {
            var sum = this
                .data()
                .reduce( function (a,b) {
                    return a*1 + b*1;
                } );
            $(this.footer()).html(sum.toFixed(2));
        }
    } );

    // Sum cost columns when grouped by item
    var costsTableGrouped = $('#costs-report-table-grouped').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        "bLengthChange": false,
        "bPaginate": false,
        "columns": [
            null,
            null,
            null,
            { className: "sum" }
        ],
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV'
            },
            {
                extend: 'print',
                text: 'Print',
                message: 'From '+dateFrom+" to "+dateTo
            }
        ]
    });
    costsTableGrouped.columns('.sum').every( function () {
        if ( $("#costs-report-table-grouped").find('tr').size() > 3 ) {
            var sum = this
                .data()
                .reduce( function (a,b) {
                    return a*1 + b*1;
                } );
            $(this.footer()).html(sum.toFixed(2));
        }
    } );


    // Sum item loans columns
    var loanedItemsTable = $('#report-items-table').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        "bLengthChange": false,
        "bPaginate": false,
        "columns": [
            null,
            { className: "sum" },
            { className: "sum" }
        ],
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV'
            }
        ]
    });
    loanedItemsTable.columns('.sum').every( function () {
        if ( $("#report-items-table").find('tr').size() > 3 ) {
            var sum = this
                .data()
                .reduce( function (a,b) {
                    return a*1 + b*1;
                } );
            $(this.footer()).html(sum.toFixed(2));
        }
    } );

    var nonLoanedItemsTable = $('#report-non-loaned-items-table').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        "bLengthChange": false,
        "bPaginate": true,
        pageLength: 50,
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV'
            }
        ]
    });

    // Set up site search report
    var siteSearch = $('#report-loanrows-table').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        "bLengthChange": false,
        "bPaginate": false,
        "order": [[ 0, "desc" ]],
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV'
            }
        ]
    });


});