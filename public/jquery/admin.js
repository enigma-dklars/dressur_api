
$(document).ready(function () {
    $("#envButtonOK").on("click", function () {
        $(this).attr("disabled", "");
        let commissionBonus = $("#commissionBonus").val();
        let versionApp = $("#versionApp").val();
        let importantUpdate = $("#importantUpdate").val();
        let doBoostPayant = $("#doBoostPayant").val();
        let linkLocalServer = $("#linkLocalServer").val();

        $.ajax({
            type: "POST",
            url: "/admin/interface/upadte_env_value",
            data: {
                commissionBonus:commissionBonus,
                versionApp:versionApp,
                importantUpdate:importantUpdate,
                doBoostPayant:doBoostPayant,
                linkLocalServer:linkLocalServer,
            },
            error: function (response) {
               alert("ERREUR");
            }
        });
        $(this).removeAttr("disabled");
    });
});