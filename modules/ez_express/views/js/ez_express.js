var ez_express1 = {
    init : function() {
        console.log("m here");
/*
        if ($("input.delivery_option_radio:checked").val() == express_delivery_id + ',') {
            $("#express_delivery").show();
        } else {
            $("#express_delivery").hide();
        }
*/
      /*  $(document).on('change', 'textarea[name=express_delivery]', function(e) {
            var express_delivery = $(this).val();

            $.ajax({
                type: 'POST',
                headers: { "cache-control": "no-cache" },
                url: "{$base_dir}modules/sr_delevery/controller/ajax.php",
                async: true,
                cache: false,
                dataType: 'json',
                data: 'action=saveFreightComplanyDetails'
                    + '&details=' + express_delivery,
                success: function(jsonData)
                {
        
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    if (textStatus !== 'abort')
                        alert("TECHNICAL ERROR: unable to save your Freight Company details \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
                }
            });
        });*/
    },
}

//when document is loaded...
$(document).ready(function(){
    ez_express1.init();
})