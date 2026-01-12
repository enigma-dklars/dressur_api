$(document).ready(function () {

    $("img.lazy").lazyload({
        placeholder : "/assets/images/placeholder.png", // image à afficher avant le chargement de l'image réelle
        effect : "fadeIn" // effet de transition lorsque l'image est chargée
    });
    
    const services = [
        "Bot WhatsApp",
        "Boost Contact (Add)",
        "Campagne Mail",
        "Promotion des Produits",
        "Promotion des Services",
        "Demande d'emploi",
        "Offre d'emploi",
        "Carte de visite numérique",
        "Social Média Marketing"
    ];

    const animatedService = document.getElementById("animated-service");
    let serviceIndex = 0; // Index of the current service
    let charIndex = 0; // Index of the current character
    const typingSpeed = 50; // Speed of typing in ms
    const delayBetweenServices = 2000; // Delay before switching to the next service in ms

    function typeService() {
        if (charIndex < services[serviceIndex].length) {
            animatedService.textContent += services[serviceIndex][charIndex];
            charIndex++;
            setTimeout(typeService, typingSpeed);
        } else {
            // Wait and then erase the text
            setTimeout(eraseService, delayBetweenServices);
        }
    }

    function eraseService() {
        if (charIndex > 0) {
            animatedService.textContent = services[serviceIndex].substring(0, charIndex - 1);
            charIndex--;
            setTimeout(eraseService, typingSpeed);
        } else {
            // Move to the next service
            serviceIndex = (serviceIndex + 1) % services.length;
            setTimeout(typeService, typingSpeed);
        }
    }
    
    let network_id;
    let service_network_id;

    let setUidCookie = function (uid) {
        var d = new Date();
        d.setTime(d.getTime() + (365*24*60*60*1000)); // 365 jours en millisecondes
        var expires = "expires=" + d.toUTCString();
        document.cookie = "uid=" + uid + ";" + expires + ";path=/";
    }

    let setThemeCookie = function (theme) {
        var d = new Date();
        d.setTime(d.getTime() + (365*24*60*60*1000)); // 365 jours en millisecondes
        var expires = "expires=" + d.toUTCString();
        document.cookie = "theme=" + theme + ";" + expires + ";path=/";
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

    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
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
            
            img.onerror = () => {
                reject(new Error("Erreur lors du chargement de l'image. Assurez-vous que l'image est valide."));
            };
        });
    }

    $(document).on('input', '#destinataires', function () {
        var emails = $(this).val().split(',');
        var validEmailCount = emails.filter(function(email) {
            return validateEmail(email.trim());
        }).length;
        $('#nbr-mails').text(validEmailCount);
    });

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

        // let inputPseudo = $("#inputPseudo").val();
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
                // pseudo : inputPseudo,
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
            let titre = $(this).attr("placeholder");
            if(!titre){ titre = $(this).prev().text(); }
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
                    if (response.direct == false) {
                        window.open(response.url, '_blank');
                    }
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
                    if (response.direct == false) {
                        window.open(response.url, '_blank');
                    }
                    msgError = "Confirmer le paiement pour démarrer votre promotion réseau."
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
                $('#imagePreview').html('<img src="' + e.target.result + '" class="card-img-top" style="height: 325px; object-fit: contain;" />');
            };
            reader.readAsDataURL(imageInput);
        } else {
            $('#imagePreview').empty();
        }
    });

    $(document).on('change', '.imagePromoServiceProduit', function () {
        let id_promo_affaire = $(this).attr("id_promo_affaire");
        const imageInput = this.files[0];
        if (imageInput) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#imagePreview_'+id_promo_affaire).html('<img src="' + e.target.result + '" class="card-img-top" style="height: 325px; object-fit: contain;" />');
            };
            reader.readAsDataURL(imageInput);
        } else {
            $('#imagePreview_'+id_promo_affaire).empty();
        }
    });

    $(document).on('change', '#modeBoostContact', function () {
        if ($(this).is(':checked')) {
            $(this).removeClass('bg-success').addClass('bg-danger');
            $("#boostContactPayant").removeAttr("hidden");
            $('#boostContactGratuit').attr('hidden', true);
        } else {
            $(this).removeClass('bg-danger').addClass('bg-success');
            $("#boostContactGratuit").removeAttr("hidden");
            $('#boostContactPayant').attr('hidden', true);
        }
    });

    $(document).on('change', '#flexSwitchCheckCheckedDanger', function () {
        if ($(this).is(':checked')) {
            $(this).removeClass('bg-success').addClass('bg-danger'); // Rouge quand coché
            $("#parti_paiement").removeAttr("hidden");
        } else {
            $(this).removeClass('bg-danger').addClass('bg-success'); // Vert quand décoché
            $('#parti_paiement').attr('hidden', true); // Cache la div paiement
        }
    });

    $(document).on('submit', '#promotionForm', function (event) {
        event.preventDefault();
        traitementContact("btn-promotionForm", "debut", "")
        let mode = "gratuit";
        let paymentMethod = "";
        let tel = "";

        if ($("#flexSwitchCheckCheckedDanger").is(':checked')) {
            mode = "payant";
            paymentMethod = $("#paymentMethod").val();
            tel = $("#tel").val();
        } else {
            mode = "gratuit";
            paymentMethod = "";
            tel = "";
        }

        const imageInput = $('#image')[0].files[0];
        const description = $('#description').val();
        const uid = $('#uid').val();
        const idFormulePromoAffaire = JSON.parse($('#formule-promo-page-new-affaire').val());

        let message = '';

        if (!idFormulePromoAffaire) {
            message = 'Attention !!!. Veuillez choisir une formule de promotion.';
            Swal.fire({icon: "error", title: "Oops...", text: message,});
            traitementContact("btn-promotionForm", "fin", "Envoyer");
            return 0;
        }

        if (!description || !imageInput || !imageInput.size) {
            message = 'Attention !!!. Veuillez entrer un texte et sélectionner une image.';
            Swal.fire({icon: "error", title: "Oops...", text: message,});
            traitementContact("btn-promotionForm", "fin", "Envoyer");
            return 0;
        }

        const fileSizeInMB = imageInput.size / (1024 * 1024);

        if (fileSizeInMB > 1) {
            message = "Attention !!! La taille de l'image ne peut pas dépasser 1 Mo.";
            Swal.fire({icon: "error", title: "Oops...", text: message,});
            traitementContact("btn-promotionForm", "fin", "Envoyer");
            return 0;
        }
        
        // Utilisation
        isImageSquare(imageInput)
        .then(isSquare => {
            let message = "";
            if (!isSquare) {
                message = "Attention !!! L'image doit être proche d'un carré.";
                Swal.fire({icon: "error", title: "Oops...", text: message,});
                traitementContact("btn-promotionForm", "fin", "Envoyer");
                return 0;
            }
    
            // Si l'image est carrée, poursuivre avec l'envoi du formulaire
            const formData = new FormData();
            formData.append('idFormulePromoAffaire', idFormulePromoAffaire[0]);
            formData.append('text', description);
            formData.append('uid', uid);
            formData.append('langUserPhone', "fr");
            formData.append('image', imageInput);
            formData.append('mode', mode);
            formData.append('paymentMethod', paymentMethod);
            formData.append('tel', tel);
    
            $.ajax({
                url: '/api/addProduitService',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.error) {
                        Swal.fire({icon: "error", title: "Oops...", text: response.titre + ` ` + response.message,});
                        traitementContact("btn-promotionForm", "fin", "Envoyer");
                    } else {
                        if (response.direct == false) {
                            window.open(response.url, '_blank');
                        }
                        let successMessage = "Good. Votre demande de promotion a été enregistrée. Elle sera diffusée si elle est acceptée par un administrateur. Dans le cas contraire, vous devrez la modifier en tenant compte des remarques.";
                        Swal.fire({icon: "success", title: "Good...", text: successMessage,});
                        traitementContact("btn-promotionForm", "fin", "Envoyer");
                        $('#description').val('');
                        $('#image').val('');
                        $('#imagePreview').empty();
                    }
                },
                error: function (error) {
                    message = "Attention !!! Erreur : " + error.status;
                    Swal.fire({icon: "error", title: "Oops...", text: message,});
                    traitementContact("btn-promotionForm", "fin", "Envoyer");
                }
            });
        })
        .catch(error => {
            message = "Attention !!! Erreur lors de la vérification de l'image : " + error.message;
            Swal.fire({icon: "error", title: "Oops...", text: message,});
            traitementContact("btn-promotionForm", "fin", "Envoyer");
        });
    });

    $(document).on("click", ".accepterPromoAffaire", function () {
        thisElement = $(this)
        let id_promo_affaire = $(this).attr("id_promo_affaire");
        let route_accepter = $("#accepter_"+id_promo_affaire).val();

        Swal.fire({
            title: "Etes-vous sûre ?",
            text: "Acceptez-vous vraiment cette promotion affaire ?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#0c971a",
            cancelButtonColor: "#d33",
            confirmButtonText: "Oui, Accepter!",
            cancelButtonText: "Non"
        }).then((result) => {
            if (result.isConfirmed) {
                thisElement.parent().parent().parent().addClass("bg-warning");
                $.ajax({
                    type: "GET",
                    url: route_accepter,
                    success: function (response) {
                        if(response == "Yes") {
                            thisElement.parent().parent().parent().remove()
                        } else {
                            Swal.fire({icon: "error", title: "Oops...", text: response,});
                        }
                    }
                });
            }
        });
    });

    $(document).on("click", ".refuserPromoAffaire", function () {
        thisElement = $(this)
        let id_promo_affaire = $(this).attr("id_promo_affaire");
        let route_accepter = $("#refuser_"+id_promo_affaire).val();
        console.log(this, id_promo_affaire, route_accepter);

        Swal.fire({
            title: "Etes-vous sûre ?",
            text: "Refusez-vous vraiment cette promotion affaire ?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#0c971a",
            confirmButtonText: "Oui, Refuser!",
            cancelButtonText: "Non"
        }).then(async (result) => {
            if (result.isConfirmed) {
                const { value: text } = await Swal.fire({
                    input: "textarea",
                    inputLabel: "Motif de refus",
                    inputPlaceholder: "Ecrivez le motif de refus ici...",
                    inputAttributes: {
                      "aria-label": "Ecrivez le motif de refus ici"
                    },
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#0c971a",
                    confirmButtonText: "Valider le Refus!",
                    cancelButtonText: "Annuler"
                });
                if (text) {
                    thisElement.parent().parent().parent().addClass("bg-warning");
                    $.ajax({
                        type: "GET",
                        url: route_accepter+"/"+text,
                        success: function (response) {
                            if(response == "Yes") {
                                thisElement.parent().parent().parent().remove()
                            } else {
                                Swal.fire({icon: "error", title: "Oops...", text: response,});
                            }
                        }
                    });
                }
            }
        });
    });

    $(document).on("click", ".modifierpromoaffaire", function (event) {
        event.preventDefault();
        let id_promo_affaire = $(this).attr("id_promo_affaire");

        traitementContact("modifierpromoaffaire-"+id_promo_affaire, "debut", "")

        const imageInput = $('#image_'+id_promo_affaire)[0].files[0];
        const description = $('#description_'+id_promo_affaire).val();
        const uid = $('#uid').val();

        let message = '';

        if (!description) {
            message = 'Attention !!!. Veuillez entrer la description de la promotion.';
        }

        if (imageInput != null) {
            const fileSizeInMB = imageInput.size / (1024 * 1024);

            if (fileSizeInMB > 1) {
                message = "Attention !!! La taille de l'image ne peut pas dépasser 1 Mo.";
            }

            isImageSquare(imageInput)
            .then(isSquare => {
                let message = "";
                if (!isSquare) {
                    message = "Attention !!! L'image doit être proche d'un carré.";
                }
            })
            .catch(error => {
                const message = "Attention !!! Erreur lors de la vérification de l'image : " + error.message;
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
                traitementContact("modifierpromoaffaire-"+id_promo_affaire, "fin", "Modifier");
            });
        }

        if (message) {
            Swal.fire({icon: "error", title: "Oops...", text: message,});
            traitementContact("modifierpromoaffaire-"+id_promo_affaire, "fin", "Modifier");
            return;
        }

        const formData = new FormData();
        formData.append('idPromoAffaire', id_promo_affaire);
        formData.append('text', description);
        formData.append('uid', uid);
        formData.append('langUserPhone', "fr");
        formData.append('image', imageInput);

        $.ajax({
            url: '/api/editProduitService',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.error) {
                    Swal.fire({icon: "error", title: "Oops...", text: response.titre + ` ` + response.message,});
                    traitementContact("modifierpromoaffaire-"+id_promo_affaire, "fin", "Modifier");
                } else {
                    let successMessage = "Votre promotion a été modifiée";
                    Swal.fire({icon: "success", title: "Good...", text: successMessage,});
                    traitementContact("modifierpromoaffaire-"+id_promo_affaire, "fin", "Modifier");
                    $("#modal_modifier_promoaffaire_"+id_promo_affaire).modal("hide");
                    actualiseContent("/listepromoaffaire")
                }
            },
            error: function (error) {
                let messageErrorNow = "Attention !!! Erreur : " + error.status;
                Swal.fire({icon: "error", title: "Oops...", text: messageErrorNow,});
                traitementContact("modifierpromoaffaire-"+id_promo_affaire, "fin", "Modifier");
            }
        });
    });

    $(document).on("click", "#add_dmd_emploi", function () {
        traitementContact("add_dmd_emploi", "debut", "")

        let msgError = "Veuillez renseigner tous les champs..."
        let msgErrorHtml = $(".msgError").text()

        let uid = $("#uid").val();
        let titre_demande_poste_rechercher = $("#titre_demande_poste_rechercher").val();
        let niveau_experience = $("#niveau_experience").val();
        let secteur_activite_rechercher = $("#secteur_activite_rechercher").val();
        let type_contrat_rechercher = $("#type_contrat_rechercher").val();
        let localisation_souhaite = $("#localisation_souhaite").val();
        let salaire_souhaite = $("#salaire_souhaite").val();
        let lien_portfolio = $("#lien_portfolio").val();
        let description_profil_demandeur = $("#description_profil_demandeur").val();
        let competence_qualification = $("#competence_qualification").val();
        let langues_parle = $("#langues_parle").val();
        let coordonne_demandeur = $("#coordonne_demandeur").val();

        if(!titre_demande_poste_rechercher || !niveau_experience || !secteur_activite_rechercher || !type_contrat_rechercher || !localisation_souhaite || !salaire_souhaite || !lien_portfolio || !description_profil_demandeur || !competence_qualification || !langues_parle || !coordonne_demandeur){
            Swal.fire({icon: "error", title: "Oops...", text: msgError,});
            traitementContact("add_dmd_emploi", "fin", "Envoyer")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/newDmdEmploi",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                titre_demande_poste_rechercher : titre_demande_poste_rechercher,
                niveau_experience : niveau_experience,
                secteur_activite_rechercher : secteur_activite_rechercher,
                type_contrat_rechercher : type_contrat_rechercher,
                localisation_souhaite : localisation_souhaite,
                salaire_souhaite : salaire_souhaite,
                lien_portfolio : lien_portfolio,
                description_profil_demandeur : description_profil_demandeur,
                competence_qualification : competence_qualification,
                langues_parle : langues_parle,
                coordonne_demandeur : coordonne_demandeur,
            },
            success: function (response) {
                if(response.error == true){                    
                    Swal.fire({icon: "error", title: "Oops...", text: response.message,});
                } else {
                    msgError = "Votre demande d'emploi a été enregistrée et sera publiée après accord d'un des administrateurs de Dressur."
                    Swal.fire({icon: "success", title: "Good...", text: msgError,});

                    $("#titre_demande_poste_rechercher").val("");
                    $("#niveau_experience").val("");
                    $("#secteur_activite_rechercher").val("");
                    $("#type_contrat_rechercher").val("");
                    $("#localisation_souhaite").val("");
                    $("#salaire_souhaite").val("");
                    $("#lien_portfolio").val("");
                    $("#description_profil_demandeur").val("");
                    $("#competence_qualification").val("");
                    $("#langues_parle").val("");
                    $("#coordonne_demandeur").val("");
                }
                traitementContact("add_dmd_emploi", "fin", "Envoyer")
            }
        });
    });

    $(document).on("click", "#add_offre_emploi", function () {
        traitementContact("add_offre_emploi", "debut", "")

        $(".msgError").html("");
        let msgError = "Veuillez renseigner tous les champs..."
        let msgErrorHtml = $(".msgError").text()

        let uid = $("#uid").val();
        let titre_poste = $("#titre_poste").val();
        let description_poste = $("#description_poste").val();
        let competences_requises = $("#competences_requises").val();
        let type_contrat = $("#type_contrat").val();
        let lieu_travail = $("#lieu_travail").val();
        let salaire = $("#salaire").val();
        let niveau_experience = $("#niveau_experience_offre").val();
        let horaire_travail = $("#horaire_travail").val();
        let avantages = $("#avantages").val();
        let dure_contrat_not_cdi = $("#dure_contrat_not_cdi").val();
        let contact_emploiyeur = $("#contact_emploiyeur").val();
        let date_limite_candidature = $("#date_limite_candidature").val();
        let lien_information_otionel = $("#lien_information_otionel").val();        

        if(!titre_poste || !description_poste || !competences_requises || !type_contrat || !lieu_travail || !salaire || !niveau_experience || !horaire_travail || !avantages || !dure_contrat_not_cdi || !contact_emploiyeur || !date_limite_candidature || !lien_information_otionel){
            Swal.fire({icon: "error", title: "Oops...", text: msgError,});
            traitementContact("add_offre_emploi", "fin", "Envoyer")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/newOffreEmploi",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                titre_poste : titre_poste,
                description_poste : description_poste,
                competences_requises : competences_requises,
                type_contrat : type_contrat,
                lieu_travail : lieu_travail,
                salaire : salaire,
                niveau_experience : niveau_experience,
                horaire_travail : horaire_travail,
                avantages : avantages,
                dure_contrat_not_cdi : dure_contrat_not_cdi,
                contact_emploiyeur : contact_emploiyeur,
                date_limite_candidature : date_limite_candidature,
                lien_information_otionel : lien_information_otionel,
            },
            success: function (response) {
                if(response.error == true){
                    Swal.fire({icon: "error", title: "Oops...", text: response.message,});
                } else {
                    msgError = "Votre offre d'emploi a été enregistrée et sera publiée après accord d'un des administrateurs de Dressur."
                    Swal.fire({icon: "success", title: "Good...", text: msgError,});

                    $("#titre_poste").val("");
                    $("#description_poste").val("");
                    $("#competences_requises").val("");
                    $("#type_contrat").val("");
                    $("#lieu_travail").val("");
                    $("#salaire").val("");
                    $("#niveau_experience_offre").val("");
                    $("#horaire_travail").val("");
                    $("#avantages").val("");
                    $("#dure_contrat_not_cdi").val("");
                    $("#contact_emploiyeur").val("");
                    $("#date_limite_candidature").val("");
                    $("#lien_information_otionel").val("");
                }
                traitementContact("add_offre_emploi", "fin", "Envoyer")
            }
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
                    if (response.direct == false) {
                        window.open(response.url, '_blank');
                    }
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

    $(document).on("click", "#envoyerCodeMail", function () {
        traitementContact("envoyerCodeMail", "debut", "")
        let uid = $("#uid").val();
        $.ajax({
            type: "POST",
            url: "/api/sendMailVerification",
            data: {
                uid : uid,
                langUserPhone : 'fr',
            },
            success: function (response) {
                if(response.error == true){
                    $("#envoyerCodeMail").removeClass("btn-primary").addClass("btn-danger").text("ERREUR").attr("disabled", "");
                } else {
                    $("#envoyerCodeMail").removeClass("btn-primary").addClass("btn-success").text("Code Envoyer").attr("disabled", "");
                }
            }
        });
    });

    $(document).on("click", "#validerVerifMail", function () {
        traitementContact("validerVerifMail", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgErrorValideMail").text()

        let uid = $("#uid").val();
        let codeVerifMail = $("#codeVerifMail").val();

        $(".getInfoVerifMail").each(function() {
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
                $("#msgErrorValideMail").html(`
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
                $("#msgErrorValideMail").toggle(800)
            }
            traitementContact("validerVerifMail", "fin", "Valider")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/mailVerification",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                codeForVerifMail : codeVerifMail,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgErrorValideMail").html(`
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
                    $("#msgErrorValideMail").toggle(800)
                } else {
                    msgError = "C'est Valider..."
                    $("#msgErrorValideMail").html(`
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
                    $("#msgErrorValideMail").toggle(800);
                    setTimeout(() => {
                        window.location.reload()
                    }, 2000);
                }
                traitementContact("validerVerifMail", "fin", "Valider")
            }
        });
    });

    $(document).on("change", "#formule-boost-gratuit", function () {
        let value = JSON.parse($(this).val())
        let id = value[0];
        let prix = value[1];
        let nbrJour = value[2];
        let msg = "Cette formule vous offre un boost de "+nbrJour+" jour(s) pour "+prix+" Bonus."
        $("#description-boost-gratuit").html(msg).removeClass("bg-info").addClass("bg-success");
    });

    $(document).on("click", "#newBoostGratuit", function () {
        traitementContact("newBoostGratuit", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgErrorBoostGratuit").text()

        let uid = $("#uid").val();

        $.ajax({
            type: "POST",
            url: "/api/newBoost",
            data: {
                uid : uid,
                langUserPhone : 'fr',
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgErrorBoostGratuit").html(`
                        <div class="alert mt-3 border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
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
                    $("#msgErrorBoostGratuit").toggle(800)
                } else {
                    msgError = "Votre boost a été enregistrée."
                    $("#msgErrorBoostGratuit").html(`
                        <div class="alert mt-3 border-0 border-success border-start border-4 bg-light-success alert-dismissible fade show py-2">
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
                    $(".getInfoBoostGratuit").val("");
                    $("#msgErrorBoostGratuit").toggle(800);
                }
                traitementContact("newBoostGratuit", "fin", "Demander un Boost Gratuit")
            }
        });
    });

    $(document).on("change", "#formule-boost-payant", function () {
        let value = JSON.parse($(this).val())
        let id = value[0];
        let prix = value[1];
        let nbrJour = value[2];
        let msg = "Cette formule vous offre un boost de "+nbrJour+" jour(s) pour "+prix+" FCFA."
        $("#description-boost-payant").html(msg).removeClass("bg-info").addClass("bg-success");
    });

    $(document).on("change", "#formule-promo-page-new-affaire", function () {
        let value = JSON.parse($(this).val())
        let id = value[0];
        let prix = value[1];
        let nbrJour = value[2];
        let msg = "Cette formule vous offre une promotion affaire de "+nbrJour+" jour(s) pour "+prix+" Bonus ou "+prix+" FCFA."
        $("#description-boost-payant").html(msg).removeClass("bg-info").addClass("bg-success");
    });

    $(document).on("change", "#type-promo-affaire", function () {
        let value = $(this).val();
        console.log(value);
        $(".type_promo_affaire").attr("hidden", "");
        $("#"+value).removeAttr("hidden");
    });

    $(document).on("click", "#newBoostPayant", function () {
        traitementContact("newBoostPayant", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgErrorBoostPayant").text()

        let formule_boost_payant = JSON.parse($("#formule-boost-payant").val())
        let paymentMethod = $("#paymentMethod").val();
        let tel = $("#tel").val();
        let uid = $("#uid").val();

        $(".getInfoPayant").each(function() {
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
                $("#msgErrorBoostPayant").html(`
                    <div class="alert mt-3 border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                    <div class="fs-3 text-danger"><i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-danger">`+msgError+`</div>
                    </div>
                    </div>
                    </div>
                `);
                $("#msgErrorBoostPayant").toggle(800)
            }
            traitementContact("newBoostPayant", "fin", "PAYER & BOOSTER")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/newBoostPayant",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                idFormulBoost : formule_boost_payant[0],
                valueMethodePaiement : paymentMethod,
                tel : tel,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgErrorBoostPayant").html(`
                        <div class="alert mt-3 border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
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
                    $("#msgErrorBoostPayant").toggle(800)
                } else {
                    if (response.direct == false) {
                        window.open(response.url, '_blank');
                    }
                    msgError = "Votre boost a été enregistrée."
                    $("#msgErrorBoostPayant").html(`
                        <div class="alert mt-3 border-0 border-success border-start border-4 bg-light-success alert-dismissible fade show py-2">
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
                    $(".getInfoPayant").val("");
                    $("#msgErrorBoostPayant").toggle(800);
                }
                traitementContact("newBoostPayant", "fin", "PAYER & BOOSTER")
            }
        });
    });

    $(document).on("click", "#addSuggerer", function () {
        traitementContact("addSuggerer", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let suggestion = $("#suggestion").val();
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
                    <div class="alert mt-3 border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
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
            traitementContact("addSuggerer", "fin", "SUGGERER")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/addSuggestion",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                suggestion : suggestion,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgError").html(`
                        <div class="alert mt-3 border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
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
                    msgError = "Votre suggestion a été enregistrée."
                    $("#msgError").html(`
                        <div class="alert mt-3 border-0 border-success border-start border-4 bg-light-success alert-dismissible fade show py-2">
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
                traitementContact("addSuggerer", "fin", "SUGGERER")
            }
        });
    });

    $(document).on("click", "#addSignaler", function () {
        traitementContact("addSignaler", "debut", "")

        let msgError = "Veuillez renseigner :"
        let msgErrorHtml = $("#msgError").text()

        let telSignaler = $("#telSignaler").val();
        let motifSignaler = $("#motifSignaler").val();
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
                    <div class="alert mt-3 border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
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
            traitementContact("addSignaler", "fin", "SIGNALER")
            return 0;
        }

        $.ajax({
            type: "POST",
            url: "/api/addSignalement",
            data: {
                uid : uid,
                langUserPhone : 'fr',
                telSignaler : telSignaler,
                motifSignaler : motifSignaler,
            },
            success: function (response) {
                if(response.error == true){
                    $("#msgError").html(`
                        <div class="alert mt-3 border-0 border-danger border-start border-4 bg-light-danger alert-dismissible fade show py-2">
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
                    msgError = "Votre signalement a été enregistrée. Merci."
                    $("#msgError").html(`
                        <div class="alert mt-3 border-0 border-success border-start border-4 bg-light-success alert-dismissible fade show py-2">
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
                traitementContact("addSignaler", "fin", "SIGNALER")
            }
        });
    });

    $(document).on("click", ".un-pays", function () {
        if($(this).hasClass("btn-success")){
            $(this).removeClass("btn-success");
            $(this).addClass("btn-light");
        } else {
            $(this).removeClass("btn-light");
            $(this).addClass("btn-success");
        }
    });

    $(document).on("click", ".savedPaysCgoisie", function () {
        $(".savedPaysCgoisie").html("Patientez ... <div class='spinner-border spinner-btn spinner-border-sm' role='status'><span class='visually-hidden'>Loading...</span></div>").attr('disabled', '')

        let langUserPhone = 'fr';
        let uid = $("#uid").val();
        let paysChoisies = [];

        $(".un-pays").each(function() {
            if($(this).hasClass("btn-success")){
                indicatif = $(this).attr("indicatif")
                paysChoisies.push(indicatif)
            }
        });

        let paysChoisieJson = JSON.stringify(paysChoisies);

        $.ajax({
            url: `api/updateUserPaysChoisies/${uid}/${langUserPhone}/${paysChoisieJson}`,
            method: 'POST',
            success: function (response) {
                if(response.error == true){
                    alert("Erreur, Envoyez une capture a l'assistance...")
                } else {
                    $(".savedPaysCgoisie").text("Modification effectuée").removeClass("btn-primary").addClass("btn-success");
                }
            }
        });
    });

    $(document).on("click", ".validePromoAffaireByAdmin", function () {
        $(".msgError").each(function() {
            elementMsgError = $(this)
            if(elementMsgError.text()){
                elementMsgError.toggle(800, function () {
                    $(this).html("");
                })
            }
        });

        let idPromoAffaire = $(this).attr("payerpromoaffaire");
        traitementContact("validePromoAffaireByAdmin-"+idPromoAffaire, "debut", "")
        
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
            traitementContact("validePromoAffaireByAdmin-"+idPromoAffaire, "fin", "BOOSTER")
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

                    $("#modal_payer_bonus_promoaffaire_"+idPromoAffaire).modal("hide");
                    actualiseContent("/accepterSansSuite");
                }
                traitementContact("validePromoAffaireByAdmin-"+idPromoAffaire, "fin", "BOOSTER")
            }
        });
    });

    /**
     * changement de theme
     */
    $(document).on("click", ".change-theme", function () {
        if($(".ici-theme").hasClass("fa-moon")){
            $(".ici-theme").removeClass("fa-moon");
            $("html").removeClass("dark-theme");
            $(".ici-theme").addClass("fa-sun");
            $("html").addClass("light-theme");
            setThemeCookie("light-theme")
        } else {
            $(".ici-theme").removeClass("fa-sun");
            $("html").removeClass("light-theme");
            $(".ici-theme").addClass("fa-moon");
            $("html").addClass("dark-theme");
            setThemeCookie("dark-theme")
        }
    });

    $(document).on("click", "#ajouter_tous_les_contacts", function () {
        let url = $(this).attr("url");
        $.ajax({
            type: "POST",
            url: url,
            data: "data",
            beforeSend: function (response) {
                traitementContact("ajouter_tous_les_contacts", "debut", "")
            },
            success: function (response) {
                $.ajax({
                    type: "POST",
                    url: "/contact",
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
                    }
                });
            },
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
    
    /**
     * Les codes JS a executer en dernier
     */
    typeService(); // Start the animation
});