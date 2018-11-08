$(document).ready(function() {

    var activeRow = '';
    var loanTable = $("#loan-items");

    // Validate the form
    $("#loan_form").submit(function(event) {
        var formAction = $("#form_action").val();
        if ( $("#contactId").val() == '' || $("#contactId").val() == null ) {
            alert('Please select a member ...');
            return false;
        }
        if ($("#payment_amount").val() > 0 && $("#payment_method").val() == "" && formAction == "checkout") {
            alert("Please choose a payment method, or set the amount paid to zero.");
            return false;
        }
    });

    // Start the checkout process by validating the form
    $("#page-controls").delegate("#start_checkout", "click", function(event) {
        $('#checkout-modal').modal('show');
        $('#checkout-modal').on('shown.bs.modal', function() {
            setUpSelectMenus();
        });
    });

    loanTable.delegate(".row-remove", "click", function() {
        $(this).closest("tr").remove();
        var rowId = $(this).closest("tr").attr("id");
        $("#loan-items").after('<input type="hidden" name="remove_rows[]" value="'+rowId+'">');
        calculateTotalFee();
        loanSave("save");
    });

    // Disable / enable fields based on loan status
    if (loanStatus == 'ACTIVE' || loanStatus == 'CLOSED' || loanStatus == 'OVERDUE' || loanStatus == 'CANCELLED') {
        $("#loan_form .form-control").prop("disabled", true);
    }
    if (doAction == 'extend') {
        $("#loan_form .extend-field").prop("disabled", false);
    }
    if (loanStatus == 'ACTIVE' || loanStatus == 'OVERDUE') {
        $("#loan_form .checkin-field").prop("disabled", false);
    }

    loanTable.delegate(".cell-fee", "keyup", function() {
        calculateTotalFee();
    });

    calculateTotalFee();

});

function calculateTotalFee() {
    var totalFee = 0;
    $(".cell-fee").each( function(index, element) {
        totalFee = totalFee + $(element).val() * 1;
    });
    $(".extra-fee").each( function(index, element) {
        totalFee = totalFee + $(element).val() * 1;
    });
    $(".cell-total-fee").html(totalFee.toFixed(2));
    $(".input-total-fee").val(totalFee.toFixed(2));
}

// Submit handler
function loanSave(action) {
    $("#form_action").val(action);
    $("#loan_form").submit();
    waitButton($(".btn-loading"));
}