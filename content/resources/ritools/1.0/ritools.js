riTools = {
    message: function(messages){
        if($.isArray(messages)){
            $.each(messages, function(index, value){
                $.gritter.add({
                    // (string | mandatory) the heading of the notification
                    title: value.type,
                    // (string | mandatory) the text inside the notification
                    text: value.message
                });
            });
        }
    }
}