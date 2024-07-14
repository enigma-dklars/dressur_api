$(document).ready(function () {
    let network_id;
    let service_network_id;

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

    let actualiseContent = function (url){
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
                if(response.error == false){
                    $(".page-content").html(response.content); 
                } else {
                    // window.location.reload()
                    $(".page-content").html(response); 
                }
            },
            error: function (response) {
                // window.location.reload()
                $(".page-content").html(response); 
            }
        });
    }

    $(document).on("change", ".form-select", function () {
        elementMsgError = $("#msgError")
        if(elementMsgError.text()){
            elementMsgError.toggle(800, function () {
                $(this).html("");
            })
        }
        $(".msgError").each(function() {
            elementMsgError = $(this)
            if(elementMsgError.text()){
                elementMsgError.toggle(800, function () {
                    $(this).html("");
                })
            }
        });
    });

    $(document).on("focus", ".getInfo", function () {
        elementMsgError = $("#msgError")
        if(elementMsgError.text()){
            elementMsgError.toggle(800, function () {
                $(this).html("");
            })
        }
        $(".msgError").each(function() {
            elementMsgError = $(this)
            if(elementMsgError.text()){
                elementMsgError.toggle(800, function () {
                    $(this).html("");
                })
            }
        });
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

    $("#envoyer").on("click", function () {
        traitementContact("envoyer", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let nomPrenom = $("#nom-prenom").val();
        let inputEmail = $("#e-mail").val();
        let objet = $("#objet").val();
        let message = $("#message").val();

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
            traitementContact("envoyer", "fin", "Envoyer")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/sendMailToDressur",
            data: {
                langUserPhone : 'fr',
                name : nomPrenom,
                email : inputEmail,
                objet : objet,
                message : message,
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
                    msgError = "Message reçu. Nous vous répondrons dans les plus brefs délais. Merci."
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
                }
                traitementContact("envoyer", "fin", "Envoyer")
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

    $(document).on("click", ".shar-actu", function () {
        let pseudoAnnonceur = $(this).attr("pseudoAnnonceur");
        let description = $(this).attr("description");
        let whatsappNumber = $(this).attr("whatsappNumber");
        let image = $(this).attr("image");
        
        let dressurUrlPlaystore = "https://play.google.com/store/apps/details?id=com.dressur.ds";
        let messageShare = '';
    
        messageShare = `Bonjour/Bonsoir *${pseudoAnnonceur}*, j'ai une question concernant la promotion ci-dessous: \n\n`;
    
        if (description.length >= 100) {
            messageShare += `<<${description.substring(0, 100)}...>>\n\n*Depuis Dressur.*`;
        } else {
            messageShare += `<<${description}>>\n\n*Depuis Dressur.*`;
        }
    
        messageShare += "\n\n";
        messageShare += "Depuis Dressur : ";
        messageShare += dressurUrlPlaystore;
    
        const imageUrl = `/promotion/${image}`;
    
        if (navigator.share) {
            console.log("API Web Share disponible");
            navigator.share({
                title: 'Partager Promotion!',
                text: messageShare,
                url: imageUrl
            }).then(() => {
                console.log('Partage réussi');
            }).catch((error) => {
                console.error('Erreur de partage', error);
            });
        } else {
            console.warn('API Web Share non supportée sur ce navigateur.');
            alert('API Web Share non supportée sur ce navigateur.');
        }
    });
    
    $(document).on("change", "#formule-campage", function () {
        let value = JSON.parse($(this).val())
        let id = value[0];
        let prix = value[1];
        let nombremail = value[2];
        let msg = "Cette formule vous offre une Campage Mail vers 10 à "+nombremail+" mails au maximum à "+prix+" FCFA."
        $("#description-formule-mail").html(msg);
    });

    $(document).on("click", "#newcampagnemail", function () {
        traitementContact("newcampagnemail", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let formule_campage = JSON.parse($("#formule-campage").val())
        let titre = $("#titre").val();
        let sujet = $("#sujet").val();
        let destinataires = $("#destinataires").val();
        let contenu = $("#contenu-mail").val();
        let reply_to = $("#reply-to").val();
        let uid = $("#uid").val();

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
            traitementContact("newcampagnemail", "fin", "Envoyer")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/newCampagneMail",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                idFormuleCampagneMail : formule_campage[0],
                titre : titre,
                sujet : sujet,
                replyto : reply_to,
                sendto : destinataires,
                contentmail : contenu,
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
                    msgError = "Votre campagne a été enregistrée, vous passerez au paiement si elle est acceptée."
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
                }
                traitementContact("newcampagnemail", "fin", "Envoyer")
            }
        });
    });

    $(document).on("click", ".payerCampageMail", function () {
        $(".msgError").each(function() {
            elementMsgError = $(this)
            if(elementMsgError.text()){
                elementMsgError.toggle(800, function () {
                    $(this).html("");
                })
            }
        });

        let idCampagneMail = $(this).attr("payerCampageMail");
        traitementContact("payerCampageMail-"+idCampagneMail, "debut", "")
        
        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError-"+idCampagneMail).text()
        
        
        let uid = $("#uid-"+idCampagneMail).val();
        let valueMethodePaiement = $("#moyen-paiement-"+idCampagneMail).val();
        let tel = $("#numero-paiement-"+idCampagneMail).val();
        console.log(idCampagneMail, uid, valueMethodePaiement, tel)
        
        $(".getInfo-"+idCampagneMail).each(function() {
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

        if(msgError != "Veuillez renseigner :"){
            if(!msgErrorHtml){
                $("#msgError-"+idCampagneMail).html(`
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
                $("#msgError-"+idCampagneMail).toggle(800)
            }
            traitementContact("payerCampageMail-"+idCampagneMail, "fin", "Payer")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/newCampageMailPayant/paiement",
            data: {
                uid : uid,
                idCampagneMail : idCampagneMail,
                langUserPhone : 'fr',
                valueMethodePaiement : valueMethodePaiement,
                tel : tel
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgError-"+idCampagneMail).html(`
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
                    $("#msgError-"+idCampagneMail).toggle(800)
                } else {
                    msgError = "Après confirmation du paiement, veuillez consulter la liste de vos campagnes mails."
                    $("#msgError-"+idCampagneMail).html(`
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
                    $("#msgError-"+idCampagneMail).toggle(800);
                }
                traitementContact("payerCampageMail-"+idCampagneMail, "fin", "Payer")
            }
        });
    });

    $(document).on("change", "#socialNetwork", function (){
        network_id = $(this).val();
        $(".lesFormulesFils").attr("hidden", "");
        $("#fils-"+network_id).removeAttr("hidden");
        $('#quantity').val(0);
        $('#price').val(0);
    });

    $(document).on("change", ".select-service-network", function () {
        service_network_id = $(this).val();
        $(".unfils").attr("hidden", "");
        $("#unfils-"+service_network_id).removeAttr("hidden");
        $('#quantity').val(0);
        $('#price').val(0);
    });

    $(document).on('input', '#quantity', function () {
        let unfils = $("#unfils-"+service_network_id);

        let prix = unfils.attr("unfils-prix");
        let qte = unfils.attr("unfils-qte");
        let qteMin = unfils.attr("unfils-qteMin");
        let qteMax = unfils.attr("unfils-qteMax");

        let qteDemander = parseInt($(this).val());
        $(this).val(qteDemander);
        
        if (qteDemander >= qteMin && qteDemander <= qteMax) {
            let prixQteDemander = ((prix * qteDemander) / qte).toFixed(0);
            $('#price').val(prixQteDemander);
            $('#message').addClass('d-none');
            console.log("pas error qte");
        } else {
            console.log("error qte");
            $('#message').text(`La quantité doit être comprise entre ${qteMin} et ${qteMax}.`).removeClass('d-none');
        }
    });

    $(document).on("click", "#newPromoReseau", function () {
        traitementContact("newPromoReseau", "debut", "")

        let msgError = "Veuillez renseigner :"

        let qteDemander = $("#quantity").val();
        let prixQteDemander = $("#price").val();
        let link = $("#link").val();
        let paymentMethod = $("#paymentMethod").val();
        let tel = $("#tel").val();
        let uid = $("#uid").val();

        $.ajax({
            type: "POST",
            url: "/api/newPromoReseau",
            data: {
                langUserPhone : 'fr',
                uid : uid,
                idFormulePromoReseau : service_network_id,
                qteDemander : qteDemander,
                prixQteDemander : prixQteDemander,
                lien : link,
                valueMethodePaiement : paymentMethod,
                tel : tel,
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
                    msgError = "Votre campagne a été enregistrée, vous passerez au paiement si elle est acceptée."
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
                }
                traitementContact("newPromoReseau", "fin", "Payer et Démarrer")
            }
        });
    });
    
    $(document).on('change', '#image', function () {
        const imageInput = this.files[0];
        if (imageInput) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#imagePreview').html('<img src="' + e.target.result + '" class="card-img-top" />');
            };
            reader.readAsDataURL(imageInput);
        } else {
            $('#imagePreview').empty();
        }
    });

    $(document).on('submit', '#promotionForm', function (event) {
        event.preventDefault();
        traitementContact("btn-promotionForm", "debut", "")

        const imageInput = $('#image')[0].files[0];
        const description = $('#description').val();
        const uid = $('#uid').val();

        let message = '';

        if (!description || !imageInput) {
            message = 'Attention !!!. Veuillez entrer un texte et sélectionner une image.';
        }

        const fileSizeInMB = imageInput.size / (1024 * 1024);

        if (fileSizeInMB > 1) {
            message = "Attention !!! La taille de l'image ne peut pas dépasser 1 Mo.";
        }

        function isImageSquare(imageFile) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.src = URL.createObjectURL(imageFile);
                img.onload = () => {
                    const width = img.width;
                    const height = img.height;
                    const aspectRatio = width / height;
                    resolve(aspectRatio >= 0.8 && aspectRatio <= 1.2);
                };
                img.onerror = reject;
            });
        }

        isImageSquare(imageInput).then(isSquare => {
            if (!isSquare) {
                message = "Attention !!! L'image doit être proche d'un carré.";
            }

            if (message) {
                $("#msgError").html(`
                    <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                    <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                    <div class="ms-3">
                        <div class="text-danger">` + message + `</div>
                    </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                $('html, body').animate({ scrollTop: 0 }, 1000);
                $("#msgError").toggle(800);
                traitementContact("btn-promotionForm", "fin", "Envoyer")
                return;
            }

            const formData = new FormData();
            formData.append('text', description);
            formData.append('uid', uid);
            formData.append('langUserPhone', "fr");
            formData.append('image', imageInput);

            $.ajax({
                url: '/api/newPromotion',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.error) {
                        $("#msgError").html(`
                            <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                            <div class="d-flex align-items-center">
                            <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                            <div class="ms-3">
                                <div class="text-danger">` + response.titre + ` ` + response.message + `</div>
                            </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                        $('html, body').animate({ scrollTop: 0 }, 1000);
                        $("#msgError").toggle(800);
                        traitementContact("btn-promotionForm", "fin", "Envoyer")
                        return;
                    } else {
                        msgError = "Good. Votre demande de promotion a été enregistrée, vous passerez au paiement si elle est acceptée.";
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
                        $('html, body').animate({ scrollTop: 0 }, 1000);
                        $("#msgError").toggle(800);
                        traitementContact("btn-promotionForm", "fin", "Envoyer")
                        $('#description').val('');
                        $('#image').val('');
                        $('#imagePreview').empty();
                        return;
                    }
                },
                error: function (error) {
                    message = "Attention !!! Erreur : " + error.status;
                    $("#msgError").html(`
                        <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                        <div class="d-flex align-items-center">
                        <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="ms-3">
                            <div class="text-danger">` + message + `</div>
                        </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $('html, body').animate({ scrollTop: 0 }, 1000);
                    $("#msgError").toggle(800);
                    traitementContact("btn-promotionForm", "fin", "Envoyer")
                    return;
                }
            });
        }).catch(() => {
            message = "Attention !!! Erreur lors de la vérification de l'image.";
            $("#msgError").html(`
                <div class="alert border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                <div class="d-flex align-items-center">
                <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                <div class="ms-3">
                    <div class="text-danger">` + message + `</div>
                </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);
            $('html, body').animate({ scrollTop: 0 }, 1000);
            $("#msgError").toggle(800);
            traitementContact("btn-promotionForm", "fin", "Envoyer")
            return;
        });
    });

    $(document).on("click", ".boostpromoaffaire", function () {
        $(".msgError").each(function() {
            elementMsgError = $(this)
            if(elementMsgError.text()){
                elementMsgError.toggle(800, function () {
                    $(this).html("");
                })
            }
        });

        let idPromoAffaire = $(this).attr("payerpromoaffaire");
        traitementContact("boostpromoaffaire-"+idPromoAffaire, "debut", "")
        
        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError-"+idPromoAffaire).text()
        
        
        let uid = $("#uid-booster-"+idPromoAffaire).val();
        let idFormulBoost = $(".formulBoost-"+idPromoAffaire).val();
        
        $(".getInfoBoost-"+idPromoAffaire).each(function() {
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

        if(msgError != "Veuillez renseigner :"){
            if(!msgErrorHtml){
                $("#msgError-"+idPromoAffaire).html(`
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
                $("#msgError-"+idPromoAffaire).toggle(800)
            }
            traitementContact("boostpromoaffaire-"+idPromoAffaire, "fin", "BOOSTER")
            return 0;
        }

        console.log({
            uid : uid,
            langUserPhone : 'fr',
            idPromotion : idPromoAffaire,
            idFormulBoost : idFormulBoost
        });

        $.ajax({
            type: "POST",
            url: "/api/newPromo",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                idPromotion : idPromoAffaire,
                idFormulBoost : idFormulBoost
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgError-"+idPromoAffaire).html(`
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
                    $("#msgError-"+idPromoAffaire).toggle(800)
                } else {
                    msgError = "Votre Promo a déja démarer."
                    $("#msgError-"+idPromoAffaire).html(`
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
                    $("#msgError-"+idPromoAffaire).toggle(800);

                    setTimeout(() => {
                        $("#modal_payer_bonus_promoaffaire_"+idPromoAffaire).modal("hide");
                    }, 2500);

                    setTimeout(() => {
                        actualiseContent("/listepromoaffaire");
                    }, 3300);
                }
                traitementContact("boostpromoaffaire-"+idPromoAffaire, "fin", "BOOSTER")
            }
        });
    });

    $(document).on("click", ".payerpromoaffaire", function () {
        $(".msgError").each(function() {
            elementMsgError = $(this)
            if(elementMsgError.text()){
                elementMsgError.toggle(800, function () {
                    $(this).html("");
                })
            }
        });

        let idPromoAffaire = $(this).attr("payerpromoaffaire");
        traitementContact("payerpromoaffaire-"+idPromoAffaire, "debut", "")
        
        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $(".msgError-"+idPromoAffaire).text()
        
        let uid = $("#uidPayant-"+idPromoAffaire).val();
        let idFormulBoost = $(".formulBoostPayant-"+idPromoAffaire).val();
        let valueMethodePaiement = $(".moyenPaiementPayant-"+idPromoAffaire).val();
        let tel = $(".numeroPaiementPayant-"+idPromoAffaire).val();
                
        $(".getInfoBoostPayant-"+idPromoAffaire).each(function() {
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
        
        if(msgError != "Veuillez renseigner :"){
            if(!msgErrorHtml){
                $(".msgError-"+idPromoAffaire).html(`
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
                $(".msgError-"+idPromoAffaire).toggle(800)
            }
            traitementContact("payerpromoaffaire-"+idPromoAffaire, "fin", "PAYER et BOOSTER")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/newPromoPayant",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                idPromotion : idPromoAffaire,
                idFormulBoost : idFormulBoost,
                valueMethodePaiement : valueMethodePaiement,
                tel : tel
            },
            success: function (response) {
                if(response.error == true){
                    $(".msgError-"+idPromoAffaire).html(`
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
                    $(".msgError-"+idPromoAffaire).toggle(800)
                } else {
                    msgError = "Après confirmation du paiement, veuillez consulter la liste de vos promotions affaires."
                    $(".msgError-"+idPromoAffaire).html(`
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
                    $(".msgError-"+idPromoAffaire).toggle(800);

                    setTimeout(() => {
                        $("#modal_payerpromoaffaire_"+idPromoAffaire).modal("hide");
                    }, 15000);

                    setTimeout(() => {
                        actualiseContent("/listepromoaffaire");
                    }, 25000);
                }
                traitementContact("payerpromoaffaire-"+idPromoAffaire, "fin", "PAYER et BOOSTER")
            }
        });
    });

    $(document).on("click", "#enregistrerProfil", function () {
        traitementContact("enregistrerProfil", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let uid = $("#uid").val();
        let inputPseudo = $("#inputPseudo").val();
        let inputTelWhatsApp = $("#inputTelWhatsApp").val();
        let inputEmail = $("#inputEmail").val();
        let inputNomPrenom = $("#inputNomPrenom").val();
        let inputTiktok = $("#inputTiktok").val();
        let inputInstagram = $("#inputInstagram").val();
        let inputFacebook = $("#inputFacebook").val();
        let inputYoutube = $("#inputYoutube").val();
        let inputAPropos = $("#inputAPropos").val();

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
            traitementContact("enregistrerProfil", "fin", "ENREGISTRER")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/updateUserInfo",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                tel : inputTelWhatsApp,
                mail : inputEmail,
                nom : inputNomPrenom,
                pseudo : inputPseudo,
                apropos : inputAPropos,
                tiktok : inputTiktok,
                instagram : inputInstagram,
                facebook : inputFacebook,
                youtube : inputYoutube,
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
                    msgError = "Profil mis à jour..."
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
                    $("#msgError").toggle(800);
                }
                traitementContact("enregistrerProfil", "fin", "ENREGISTRER")
            }
        });
    });

    $(document).on("click", "#editMdp", function () {
        traitementContact("editMdp", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let uid = $("#uid").val();
        let inputAncienMdp = $("#inputAncienMdp").val();
        let inputNewMdp = $("#inputNewMdp").val();
        let inputConfNewMdp = $("#inputConfNewMdp").val();

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
            traitementContact("editMdp", "fin", "MODIFIER")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/updateUserPassword",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                currentPassword : inputAncienMdp,
                newPassword : inputNewMdp,
                confirmNewPassword : inputConfNewMdp,
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
                    msgError = "Mot de passe modifié avec succès."
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
                }
                traitementContact("editMdp", "fin", "MODIFIER")
            }
        });
    });

    $(document).on("click", "#validerCodeParrainage", function () {
        traitementContact("validerCodeParrainage", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgErrorParrainage").text()

        let uid = $("#uid").val();
        let codeParrainage = $("#codeParrainage").val();

        $(".getInfoParrainage").each(function() {
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

        if(msgError != "Veuillez renseigner :"){
            if(!msgErrorHtml){
                $("#msgErrorParrainage").html(`
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
                $("#msgErrorParrainage").toggle(800)
            }
            traitementContact("validerCodeParrainage", "fin", "Valider")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/addParrain",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                codeBonus : codeParrainage,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgErrorParrainage").html(`
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
                    $("#msgErrorParrainage").toggle(800)
                } else {
                    msgError = "C'est Valider..."
                    $("#msgErrorParrainage").html(`
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
                    $("#msgErrorParrainage").toggle(800);
                    setTimeout(() => {
                        $("#modal_parrainage").modal("hide");
                        actualiseContent("/invitezVosAmis");
                    }, 5000);
                }
                traitementContact("validerCodeParrainage", "fin", "Valider")
            }
        });
    });

    $(document).on("click", "#validerCodePromo", function () {
        traitementContact("validerCodePromo", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgErrorPromo").text()

        let uid = $("#uid").val();
        let codePromo = $("#codePromo").val();

        $(".getInfoPromo").each(function() {
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

        if(msgError != "Veuillez renseigner :"){
            if(!msgErrorHtml){
                $("#msgErrorPromo").html(`
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
                $("#msgErrorPromo").toggle(800)
            }
            traitementContact("validerCodePromo", "fin", "Valider")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/addBonusPromo",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                codePromo : codePromo,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgErrorPromo").html(`
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
                    $("#msgErrorPromo").toggle(800)
                } else {
                    msgError = "C'est Valider..."
                    $("#msgErrorPromo").html(`
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
                    $("#msgErrorPromo").toggle(800);
                    setTimeout(() => {
                        $("#modal_promo").modal("hide");
                        actualiseContent("/invitezVosAmis");
                    }, 5000);
                }
                traitementContact("validerCodePromo", "fin", "Valider")
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
                if(response.error == false){
                    $(".page-content").html(response.content); 
                } else {
                    // window.location.reload()
                    $(".page-content").html(response); 
                }
            },
            error: function (response) {
                // window.location.reload()
                $(".page-content").html(response); 
            }
        });
    });
});