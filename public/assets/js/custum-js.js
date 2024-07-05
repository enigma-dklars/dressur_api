$(document).ready(function () {
    let setUidCookie = function (uid) {
        var d = new Date();
        d.setTime(d.getTime() + (365*24*60*60*1000)); // 365 jours en millisecondes
        var expires = "expires=" + d.toUTCString();
        document.cookie = "uid=" + uid + ";" + expires + ";path=/";
    }
        
    let traitementContact = function (elementId, option, htmlbtn){ 
        if(option == "debut"){
            $("#"+elementId).html("Patientez ... <div class='spinner-border spinner-btn spinner-border-sm' role='status'><span class='visually-hidden'>Loading...</span></div>").attr('disabled', '')
        } else {
            $("#"+elementId).html(htmlbtn).removeAttr('disabled')
        }
    }

    $(".form-select").on("change", function () {
        elementMsgError = $("#msgError")
        if(elementMsgError.text()){
            elementMsgError.toggle(800, function () {
                $(this).html("");
            })
        }
    });

    $(".getInfo").on("focus", function () {
        elementMsgError = $("#msgError")
        if(elementMsgError.text()){
            elementMsgError.toggle(800, function () {
                $(this).html("");
            })
        }
    });

    $("#inscription").on("click", function () {
        traitementContact("inscription", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let inputPseudo = $("#inputPseudo").val();
        let inputNumeroWhatsApp = $("#inputNumeroWhatsApp").val();
        let inputEmail = $("#inputEmail").val();
        let inputMotPasse = $("#inputMotPasse").val();
        let inputConfirmerMotPasse = $("#inputConfirmerMotPasse").val();

        $(".getInfo").each(function() {
            let titre = $(this).prev().text();
            if(!titre){ titre = $(this).attr("placeholder"); }
            let value = $(this).val();
            if(!value){ 
                if(msgError == "Veuillez renseigner :") {
                    msgError += " " + titre
                } else {
                    msgError += ", " + titre
                }
            }
        });

        if (!inputEmail.match(/[a-z0-9_\-\.]+@[a-z0-9_\-\.]+\.[a-z]+/i)) {
            if(msgError == "Veuillez renseigner :") {
                if(inputEmail){
                    msgError = "<b>" + inputEmail + "</b> n'est pas une adresse e-mail valide.";
                }
            } else {
                if(inputEmail){
                    msgError = msgError + "<br> <b>" + inputEmail + "</b> n'est pas une adresse e-mail valide.";
                }
            }
        }

        if(msgError != "Veuillez renseigner :"){
            if(!msgErrorHtml){
                $("#msgError").html(`
                    <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                    <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-danger">`+msgError+`</div>
                    </div>
                    </div>
                    </div>
                `);
                $("#msgError").toggle(800)
            }
            traitementContact("inscription", "fin", "INSCRIPTION")
            return 0;
        }
        console.log({
            inputPseudo : inputPseudo,
            inputNumeroWhatsApp : inputNumeroWhatsApp,
            inputEmail : inputEmail,
            inputMotPasse : inputMotPasse,
            inputConfirmerMotPasse : inputConfirmerMotPasse,
        });
        $.ajax({
            type: "POST",
            url: "/api/inscriptionDS",
            data: {
                langUserPhone : 'fr',
                pseudo : inputPseudo,
                tel : inputNumeroWhatsApp,
                mail : inputEmail,
                password : inputMotPasse,
                confirmPassword : inputConfirmerMotPasse,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgError").html(`
                        <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                        <div class="d-flex align-items-center">
                        <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="text-danger">`+response.message+`</div>
                        </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $("#msgError").toggle(800)
                } else {
                    let uid = response.user.uid
                    setUidCookie(uid);
                    msgError = "Inscription réussie, vous serez redirigé dans quelques secondes. <span class='decompte'>5</span>s"
                    $("#msgError").html(`
                        <div class="alert border-0 border-success border-start border-4 bg-light-success alert-dismissible fade show py-2">
                        <div class="d-flex align-items-center">
                        <div class="fs-3 text-success"><i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="text-success">`+msgError+`</div>
                        </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $(".getInfo").val("");
                    $("#msgError").toggle(800);
                    let timerr = setInterval(() => {
                        let temps = parseInt($('.decompte').text());
                        if(temps >= 1){
                            temps = temps - 1
                            $('.decompte').text(""+temps+"")
                        } else {
                            clearInterval(timerr)
                            $('.decompte').text(0)
                            location.href = "/private"
                            return;
                        }
                    }, 1000);
                }
                traitementContact("inscription", "fin", "INSCRIPTION")
            }
        });
    });

    $("#connexion").on("click", function () {
        traitementContact("connexion", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let inputEmail = $("#inputEmail").val();
        let inputMotPasse = $("#inputMotPasse").val();

        $(".getInfo").each(function() {
            let titre = $(this).prev().text();
            if(!titre){ titre = $(this).attr("placeholder"); }
            let value = $(this).val();
            if(!value){ 
                if(msgError == "Veuillez renseigner :") {
                    msgError += " " + titre
                } else {
                    msgError += ", " + titre
                }
            }
        });

        if (!inputEmail.match(/[a-z0-9_\-\.]+@[a-z0-9_\-\.]+\.[a-z]+/i)) {
            if(msgError == "Veuillez renseigner :") {
                if(inputEmail){
                    msgError = "<b>" + inputEmail + "</b> n'est pas une adresse e-mail valide.";
                }
            } else {
                if(inputEmail){
                    msgError = msgError + "<br> <b>" + inputEmail + "</b> n'est pas une adresse e-mail valide.";
                }
            }
        }

        if(msgError != "Veuillez renseigner :"){
            if(!msgErrorHtml){
                $("#msgError").html(`
                    <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                    <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-danger">`+msgError+`</div>
                    </div>
                    </div>
                    </div>
                `);
                $("#msgError").toggle(800)
            }
            traitementContact("connexion", "fin", "CONNEXION")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/connect",
            data: {
                langUserPhone : 'fr',
                mail : inputEmail,
                password : inputMotPasse,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgError").html(`
                        <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                        <div class="d-flex align-items-center">
                        <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="text-danger">`+response.message+`</div>
                        </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $("#msgError").toggle(800)
                } else {
                    let uid = response.user.uid
                    setUidCookie(uid);
                    msgError = "Connexion réussie, vous serez redirigé dans quelques secondes. <span class='decompte'>5</span>s"
                    $("#msgError").html(`
                        <div class="alert border-0 border-success border-start border-4 bg-light-success alert-dismissible fade show py-2">
                        <div class="d-flex align-items-center">
                        <div class="fs-3 text-success"><i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="text-success">`+msgError+`</div>
                        </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $(".getInfo").val("");
                    $("#msgError").toggle(800);
                    let timerr = setInterval(() => {
                        let temps = parseInt($('.decompte').text());
                        if(temps >= 1){
                            temps = temps - 1
                            $('.decompte').text(""+temps+"")
                        } else {
                            clearInterval(timerr)
                            $('.decompte').text(0)
                            location.href = "/private"
                            return;
                        }
                    }, 1000);
                }
                traitementContact("connexion", "fin", "CONNEXION")
            }
        });
    });

    $("#passe4get").on("click", function () {
        traitementContact("passe4get", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let inputEmail = $("#inputEmail").val();

        $(".getInfo").each(function() {
            let titre = $(this).prev().text();
            if(!titre){ titre = $(this).attr("placeholder"); }
            let value = $(this).val();
            if(!value){ 
                if(msgError == "Veuillez renseigner :") {
                    msgError += " " + titre
                } else {
                    msgError += ", " + titre
                }
            }
        });

        if (!inputEmail.match(/[a-z0-9_\-\.]+@[a-z0-9_\-\.]+\.[a-z]+/i)) {
            if(msgError == "Veuillez renseigner :") {
                if(inputEmail){
                    msgError = "<b>" + inputEmail + "</b> n'est pas une adresse e-mail valide.";
                }
            } else {
                if(inputEmail){
                    msgError = msgError + "<br> <b>" + inputEmail + "</b> n'est pas une adresse e-mail valide.";
                }
            }
        }

        if(msgError != "Veuillez renseigner :"){
            if(!msgErrorHtml){
                $("#msgError").html(`
                    <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                    <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-danger">`+msgError+`</div>
                    </div>
                    </div>
                    </div>
                `);
                $("#msgError").toggle(800)
            }
            traitementContact("passe4get", "fin", "CONFIRMER")
            return 0;
        }
        
        $.ajax({
            type: "POST",
            url: "/api/sendMailPassForgot",
            data: {
                langUserPhone : 'fr',
                mail : inputEmail,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgError").html(`
                        <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                        <div class="d-flex align-items-center">
                        <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="text-danger">`+response.message+`</div>
                        </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $("#msgError").toggle(800)
                } else {
                    msgError = `Mail Envoyé!<br><br>Nous vous avons envoyé un nouveau mot de passe par mail. Utilisez-le pour vous connecter, n'oubliez pas de le changer une fois connecter.<br><br>Vous serez redirigé dans quelques secondes. <span class='decompte'>30</span>s<br><br><a href="/connexion" class="badge bg-success text-white">Allez sur la page Connexion</a>`
                    $("#msgError").html(`
                        <div class="alert border-0 border-success border-start border-4 bg-light-success alert-dismissible fade show py-2">
                        <div class="d-flex align-items-center">
                        <div class="fs-3 text-success"><i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="ms-3">
                            <div class="text-success">`+msgError+`</div>
                        </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $(".getInfo").val("");
                    $("#msgError").toggle(800);
                    let timerr = setInterval(() => {
                        let temps = parseInt($('.decompte').text());
                        if(temps >= 1){
                            temps = temps - 1
                            $('.decompte').text(""+temps+"")
                        } else {
                            clearInterval(timerr)
                            $('.decompte').text(0)
                            location.href = "/connexion"
                            return;
                        }
                    }, 1000);
                }
                traitementContact("passe4get", "fin", "CONFIRMER")
            }
        });
    });

    /**
     * Clique sur un element du menu
     */
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