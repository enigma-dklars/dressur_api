$(function () {
    "use strict";

    $(document).ready(function () {
        $('.data-table').DataTable({
            pageLength: 25,
            dom:
                "<'row mb-2'<'col-sm-6'l><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'p>>"                  +
                "<'row'<'col-sm-12'tr>>"                 +
                "<'row mt-1'<'col-sm-5'i><'col-sm-7'p>>"
        });
    });

    $(document).ready(function () {
        var table = $('#example2').DataTable({
            lengthChange: false,
            buttons: ['copy', 'excel', 'pdf', 'print']
        });

        table.buttons().container()
            .appendTo('#example2_wrapper .col-md-6:eq(0)');
    });

});
