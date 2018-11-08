$('.reservation').daterangepicker({
    "timePicker": true,
    "timePicker24Hour": true,
    "dateLimit": {
        "days": 14
    },
    timePickerIncrement: 30,
    locale: {
        format: 'DD/MM/YYYY h:mm A'
    }
});

$("#form_ajax").select2({
    ajax: {
        url: "https://api.github.com/search/repositories",
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return {
                q: params.term, // search term
                page: params.page
            };
        },
        processResults: function (data, params) {
            params.page = params.page || 1;
            return {
                results: data.items,
                pagination: {
                    more: (params.page * 30) < data.total_count
                }
            };
        },
        cache: true
    },
    escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
    minimumInputLength: 1,
    templateResult: formatRepo, // omitted for brevity, see the source of this page
    templateSelection: formatRepoSelection // omitted for brevity, see the source of this page
});

function formatRepo (repo) {
    if (repo.loading) return repo.text;
    var markup = repo.full_name;
    return markup;
}

function formatRepoSelection (repo) {
    return repo.full_name || repo.text;
}
