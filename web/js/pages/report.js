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
            { extend: 'copy', className: 'btn btn-default btn-xs'},
            { extend: 'csv', className: 'btn btn-default btn-xs' },
            {
                extend: 'print',
                className: 'btn btn-default btn-xs',
                customize: function ( win ) {
                    $(win.document.body).find('table').addClass('compact').css('font-size','inherit');
                    $(win.document.body).find('h1').css('font-size','14px');
                }
            }
        ]
    });
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
        pageLength: 50,
        "columns": [
            null,
            null,
            null,
            null,
            null,
            null,
            null
        ],
        buttons: [
            { extend: 'copy', className: 'btn btn-default btn-xs'},
            { extend: 'csv', className: 'btn btn-default btn-xs' },
            {
                extend: 'print',
                className: 'btn btn-default btn-xs',
                exportOptions: {
                    stripNewlines: false,
                    stripHtml: false
                },
                customize: function ( win ) {
                    $(win.document.body).find('table').addClass('compact').css('font-size','inherit');
                    $(win.document.body).find('h1').css('font-size','14px');
                }
            }
        ],
        "order": [[ 0, "desc" ]]
    });

    // Sum item loans columns
    var loanedItemsTable = $('#report-items-by-custom-field').DataTable({
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
            { extend: 'copy', className: 'btn btn-default btn-xs'},
            { extend: 'csv', className: 'btn btn-default btn-xs' },
            {
                extend: 'print',
                className: 'btn btn-default btn-xs',
                customize: function ( win ) {
                    $(win.document.body).find('table').addClass('compact').css('font-size','inherit');
                    $(win.document.body).find('h1').css('font-size','14px');
                }
            }
        ]
    });
    loanedItemsTable.columns('.sum').every( function () {
        if ( $("#report-items-by-custom-field").find('tr').size() > 3 ) {
            var sum = this
                .data()
                .reduce( function (a,b) {
                    return a*1 + b*1;
                } );
            $(this.footer()).html(sum.toFixed(2));
        }
    } );

    var loanedItemsTableByName = $('#report-items-by-name').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        "bLengthChange": false,
        "bPaginate": false,
        "columns": [
            null,
            { className: "sum" },
            { className: "sum" },
            { className: "sum" },
            { className: "sum" },
            { className: "sum" },
            { className: "sum" }
        ],
        buttons: [
            { extend: 'copy', className: 'btn btn-default btn-xs'},
            { extend: 'csv', className: 'btn btn-default btn-xs' },
            {
                extend: 'print',
                className: 'btn btn-default btn-xs',
                customize: function ( win ) {
                    $(win.document.body).find('table').addClass('compact').css('font-size','inherit');
                    $(win.document.body).find('h1').css('font-size','14px');
                }
            }
        ]
    });
    loanedItemsTableByName.columns('.sum').every( function () {
        if ( $("#report-items-by-name").find('tr').size() > 3 ) {
            var sum = this
                .data()
                .reduce( function (a,b) {
                    return a*1 + b*1;
                } );
            $(this.footer()).html(sum.toFixed(2));
        }
    } );

    var loanedItemsTableById = $('#report-items-by-id').DataTable({
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
            { className: "sum" },
            { className: "sum" },
            { className: "sum" },
            { className: "sum" },
            { className: "sum" }
        ],
        buttons: [
            { extend: 'copy', className: 'btn btn-default btn-xs'},
            { extend: 'csv', className: 'btn btn-default btn-xs' },
            {
                extend: 'print',
                className: 'btn btn-default btn-xs',
                customize: function ( win ) {
                    $(win.document.body).find('table').addClass('compact').css('font-size','inherit');
                    $(win.document.body).find('h1').css('font-size','14px');
                }
            }
        ]
    });
    loanedItemsTableById.columns('.sum').every( function () {
        if ( $("#report-items-by-id").find('tr').size() > 3 ) {
            var sum = this
                .data()
                .reduce( function (a,b) {
                    return a*1 + b*1;
                } );
            $(this.footer()).html(sum.toFixed(2));
        }
    } );


    // --- MEMBERSHIP REPORT ---

    $('#data-table-membership').DataTable({
        dom: 'lfBrtip',
        ordering: false,
        serverSide: true,
        autoWidth: false,
        pageLength: 50,
        ajax: "/admin/dt/membership/list?date_from="+dFrom+"&date_to="+dTo+"&memberType="+$("#memberType").val(),
        "oSearch": {
            "sSearch": ""
        },
        "language": {
            "infoFiltered": ""
        },
        buttons: [
            { extend: 'copy', className: 'btn btn-default btn-xs'},
            { extend: 'csv', className: 'btn btn-default btn-xs' },
            {
                extend: 'print',
                className: 'btn btn-default btn-xs',
                customize: function ( win ) {
                    $(win.document.body).find('table').addClass('compact').css('font-size','inherit');
                    $(win.document.body).find('h1').css('font-size','14px');
                }
            }
        ]
    });

    $("#data-table-membership_filter label input").attr("placeholder", "Name, Email, Status, Type");
    $("#data-table-membership_filter label input").css("width", "220px");

    // --- Non-loaned items report ---

    $('#report-non-loaned-items-table').DataTable({
        dom: 'lfBrtip',
        ordering: true,
        autoWidth: false,
        // "bLengthChange": false,
        // "bPaginate": true,
        pageLength: 50,
        buttons: [
            { extend: 'copy', className: 'btn btn-default btn-xs'},
            { extend: 'csv', className: 'btn btn-default btn-xs' },
            {
                extend: 'print',
                className: 'btn btn-default btn-xs',
                exportOptions: {
                    stripNewlines: false,
                    stripHtml: false
                },
                customize: function ( win ) {
                    $(win.document.body).find('table').addClass('compact').css('font-size','inherit');
                    $(win.document.body).find('h1').css('font-size','14px');
                }
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