function waitButton(obj) {
    obj.removeClass('btn-success').addClass('btn-default').attr('disabled', true).html('<img src="/images/ajax-loader.gif">');
}

$(document).ready(function(){

    $('[data-toggle="tooltip"]').tooltip();

    // Also a similar version in admin.js
    $(".modal-content").on("click", ".modal-submit", function(event) {
        event.preventDefault();
        var modalForm = $(".modal-body form");
        var errors = false;
        modalForm.find('input, select').each(function(){
            if($(this).prop('required') == true && errors == false) {
                if (!$(this).val()) {
                    if ($(this).attr('data-name') == undefined) {
                        alert("Please fill out all required fields." + $(this).attr('id'));
                    } else {
                        alert("Please fill out the "+$(this).attr('data-name')+" field");
                    }
                    errors = true;
                }
            }
        });
        if (errors == false) {
            modalForm.submit();
            waitButton($(this));
        }
    });

});

// Open modal-link URLs in the modal
$(document).delegate(".modal-link", "click", function(event) {
    event.preventDefault();
    //if (pageLoadComplete == false) {
    //    alert("Please wait for the page to finish loading and then try again.");
    //    return false;
    //}
    var modalUrl = $(this).attr("href");
    var modalWrapper = $('#modal-wrapper');
    $('.modal-content', modalWrapper).load(modalUrl, function() {
        modalWrapper.modal('show');
        modalWrapper.on('shown.bs.modal', function() {
            modalWrapper.find(".modal-body input:first").focus();
            setUpSelectMenus();
        });
    });
    return false;
});


function setUpSelectMenus() {
    // blank, due to calls in payment.js used by admin
}

// ALSO A SIMILAR FUNCTION IN admin.js
var barcode = '';
$(document).keypress(function(e){
    setTimeout(resetBarcode, 1000);
    if (e.which == 13 && barcode != '') {
        if ($(".check-in-row-id").html()) {
            // We're on the check in page, do a different thing with barcodes
            var loanRowId = $("#check-in-"+barcode).attr("data-loan-row-id");
            // There's a check in button for this item
            if (loanRowId) {
                console.log("Found barcode to check in item "+barcode);
                document.location.href = '/loan-row/'+loanRowId+'/check-in/';
            } else {
                alert("Scanning to check-in item ...\nCould not find an item on this loan with barcode or ID '"+barcode+"'");
                barcode = '';
            }
        } else {
            console.log("Found barcode for search, let's go! "+barcode);
            document.location.href = '/products?search='+barcode;
        }
    }
    barcode = barcode + e.key;
});
function resetBarcode() {
    barcode = '';
}