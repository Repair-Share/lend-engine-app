// Main JS file for the site

var AdminLTEOptions = {
    animationSpeed: 80,
    controlSidebarOptions: {
        //Which button should trigger the open/close event
        toggleBtnSelector: "[data-toggle='control-sidebar']",
        //The sidebar selector
        selector: ".control-sidebar",
        //Enable slide over content
        slide: false
    }
};

var dateToday = '';

// Character limit display
(function($) {
    $.fn.extend( {
        limiter: function(limit) {
            $(this).on("keyup focus", function() {
                var e = $(this).next(".limiter");
                setCount(this, e);
            });
            function setCount(src, elem) {
                var chars = src.value.length;
                if (chars > limit) {
                    src.value = src.value.substr(0, limit);
                    chars = limit;
                }
                elem.html( limit - chars + " characters left");
            }
        }
    });
})(jQuery);

// Open modal-link URLs in the modal
$(document).delegate(".modal-link", "click", function(event) {
    event.preventDefault();
    loadModal($(this).attr("href"));
    return false;
});

function loadModal(modalUrl) {
    if (pageLoadComplete == false) {
        alert("Please wait for the page to finish loading and then try again.");
        return false;
    }
    var modalWrapper = $('#modal-wrapper');
    $('.modal-content', modalWrapper).load(modalUrl, function() {
        modalWrapper.modal('show');
        modalWrapper.on('shown.bs.modal', function() {
            modalWrapper.find(".modal-body input:first").focus();
            setUpSelectMenus();
        });
    });
}

$(document).delegate(".note-delete", "click", function(event) {
    event.preventDefault();
    deleteNote( $(this).attr('data-id') );
    return false;
});

$(document).ready(function(){

    var content = $('.content');

    // Add a div to hold "x characters left" under a form field
    $(".limited").after('<div class="limiter"></div>');

    dateToday = moment().format('ddd MMM D YYYY');

    $('#data-table').DataTable({
        pageLength: 100,
        ordering: false
    });

    $('.tab-table').DataTable({
        pageLength: 25,
        ordering: true
    });

    setUpSelectMenus();

    content.on('click', '#show-filters', function () {
        $('#primary-filter').fadeIn(500);
        $('#show-filters').fadeOut(500);
        setUpSelectMenus();
    });

    content.on('click', ".delete-link", function() {
        if (window.confirm("Are you sure you want to delete?")) {
            return true;
        } else {
            return false;
        }
    });

    // AJAX tabs
    $('[data-toggle="tabajax"]').click(function (e) {
        var $this = $(this), loadurl = $this.attr('href'), targ = $this.attr('data-target');
        $.get(loadurl, function (data) {
            $(targ).html(data);
        });
        $this.tab('show');
        return false;
    });

});

function setUpSelectMenus() {

    $('select').not(".ajax").not(".child select").select2({
        minimumResultsForSearch: 10
    });

    $(".contact-add").select2({
        ajax: {
            url: selectContactPath,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            text: item.text,
                            id: item.id
                        }
                    })
                };
            }
        },
        minimumInputLength: 2
    });

    var singleDatePickerField = $(".single-date-picker");
    var dateField = $("#"+singleDatePickerField.attr('id')+"_data");
    dateField.val(moment().format('YYYY-MM-DD'));
    if (singleDatePickerField.length > 0) {
        singleDatePickerField.dateRangePicker({
            format: 'ddd MMM D YYYY',
            autoClose: true,
            singleDate: true,
            singleMonth: true,
            showShortcuts: false,
            setValue: function(s) {
                if(!$(this).attr('readonly') && !$(this).is(':disabled') && s != $(this).val()) {
                    $(this).val(s);
                }
            }
        }).bind('datepicker-change',function(event,obj){
            var dateChosen = moment(obj.date1).format('YYYY-MM-DD');
            dateField.val(dateChosen);
        });
        singleDatePickerField.data('dateRangePicker').setDateRange(dateToday, dateToday);
    }
}

function disableButton(button) {
    button.attr('disabled', true);
    console.log("...");
}

function deleteNote(id) {
    if (window.confirm("Delete this note?")) {
        $.get(
            noteDeletePath,
            { id:id, entity:'Note' },
            function(data){
                if (data == "OK") {
                    $("#note-"+id).remove();
                } else {
                    alert("Sorry! Couldn't delete ... "+data);
                }
                console.log(data);
            },
            "json"
        );
    }
}

function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

// HTML5 does not validate required fields for the form loaded via AJAX, so do it here
$(".modal-content").delegate(".modal-submit", "click", function(event) {
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

function waitButton(obj) {
    obj.removeClass('bg-green').addClass('btn-default').attr('disabled', true).before('<img src="/images/ajax-loader.gif" id="spinner" style="padding-right: 10px">');
}

function unWaitButton(obj) {
    $("#spinner").remove();
    obj.addClass('bg-green').removeClass('btn-default').attr('disabled', false).html(obj.data('text'));
}

var barcode = '';
$(document).keypress(function(e){
    setTimeout(resetBarcode, 600);
    if (e.which == 13 && barcode.length > 3) {
        console.log("Code entered over 3 char : "+barcode);
        if (!isNaN(barcode)) {
            document.location.href = '/admin/item/list?search='+barcode;
            console.log("Found barcode, let's go!");
        }
    }
    barcode = barcode + e.key;
});
function resetBarcode() {
    barcode = '';
}