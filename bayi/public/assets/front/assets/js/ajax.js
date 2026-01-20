$("#marka").change(function (){
    var id = $(this).val();

        $.getJSON('/modelgetir', {id:id}, function (data){
            $("#model").empty();
            $.each(data, function(key, value) {
                $("#model").append('<option class="" value="'+value.id+'">'+value.model_adi+'</option>');
            });
        });
});


