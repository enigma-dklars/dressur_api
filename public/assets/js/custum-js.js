$(document).ready(function () {
    $(document).on("click", ".menu-element", function () {
        let url = $(this).attr("url");
        $.ajax({
            type: "POST",
            url: url,
            data: "data",
            beforeSend: function (response) {
                $(".page-content").html(`
                    <div id="parent">
                        <div id="loader-wrapper">
                            <div id="loader"></div>
                            <div class="loader-section section-left"></div>
                            <div class="loader-section section-right"></div>
                        </div>
                    </div>
                `);
            },
            success: function (response) {
                // response = JSON.parse(response)
                console.log(response);
                if(response.error == false){
                    $(".page-content").html(response.content); 
                } else {
                    window.location.reload()
                }
            },
            error: function (response) {
                window.location.reload()
            }
        });
    });
});