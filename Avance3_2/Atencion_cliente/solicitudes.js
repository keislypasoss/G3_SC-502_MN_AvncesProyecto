$(document).ready(function(){
    // Enviar nueva solicitud
    $("#formSolicitud").submit(function(e){
        e.preventDefault(); // Evita recargar

        $.ajax({
            url: "procesar_solicitud.php",
            type: "POST",
            data: $(this).serialize(),
            success: function(response){
                $("#mensaje").html(response).css("color","green");
                $("#formSolicitud")[0].reset(); // limpiar formulario
                cargarSolicitudes(); // opcional: recargar lista
            },
            error: function(){
                $("#mensaje").html("Error al enviar la solicitud").css("color","red");
            }
        });
    });

    // Función para marcar solicitud como "Hecho"
    $(document).on('click', '.btn-marcar-hecho', function(){
        let id_solicitud = $(this).data('id'); // atributo data-id del botón

        $.ajax({
            url: "actualizar_estado.php",
            type: "POST",
            data: { id_solicitud: id_solicitud },
            success: function(response){
                alert(response);
                cargarSolicitudes(); // opcional: recargar lista para reflejar el cambio
            },
            error: function(){
                alert("Error al actualizar estado");
            }
        });
    });

    // Función opcional para recargar la lista de solicitudes
    function cargarSolicitudes(){
        $("#listaSolicitudes").load("verificar_solicitud.php #tablaSolicitudes");
    }
});
