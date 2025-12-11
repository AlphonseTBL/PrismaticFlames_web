$("#contactForm").on("submit", function (event) {
    event.preventDefault();
    submitForm();
});


function submitForm(){
    var name = $("#name").val();
    var email = $("#email").val();
    var subject = $("#subject").val();
    var message = $("#message").val();

    if(!name || !email || !subject || !message){
        submitMSG(false, "Por favor completa todos los campos.");
        formError();
        return;
    }

    $.ajax({
        type: "POST",
        url: "php/contact-save.php",
        data: "name=" + encodeURIComponent(name) + "&email=" + encodeURIComponent(email) + "&subject=" + encodeURIComponent(subject) + "&message=" + encodeURIComponent(message),
        success : function(){
            formSuccess();
        },
        error: function(){
            formError();
            submitMSG(false,"No se pudo enviar el mensaje. Intenta de nuevo.");
        }
    });
}

function formSuccess(){
    $("#contactForm")[0].reset();
    submitMSG(true, "Mensaje enviado correctamente.");
}

function formError(){
    $("#contactForm").removeClass().addClass('shake animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
        $(this).removeClass();
    });
}

function submitMSG(valid, msg){
    var msgClasses = valid ? "h3 text-center tada animated text-success" : "h3 text-center text-danger";
    $("#msgSubmit").removeClass().addClass(msgClasses).text(msg);
}