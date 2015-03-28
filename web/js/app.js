/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$( document ).ready(function() {
    window.updateTotals=function (json) {

            console.log(json);
            var terr,tmsg,tsent;
            terr=tmsg=tsent=0;
            for(route in json) {
                terr+=json[route].status.err
                tmsg+=json[route].status.msg
                tsent+=json[route].status.sent
            }
            $('#status-processed').html(tmsg);
            $('#status-errors').html(terr);
            $('#status-sent').html(tsent);
    };
    console.log('Starting App');
    // Using the core $.ajax() method
    $.ajax({

        // The URL for the request
        url: "/status",

        // The data to send (will be converted to a query string)
        data: {
            id: 123
        },

        // Whether this is a POST or GET request
        type: "GET",

        // The type of data we expect back
        dataType : "json",

        // Code to run if the request succeeds;
        // the response is passed to the function
        success: window.updateTotals,

        // Code to run if the request fails; the raw request and
        // status codes are passed to the function
        error: function( xhr, status, errorThrown ) {
            alert( "Sorry, there was a problem!" );
            console.log( "Error: " + errorThrown );
            console.log( "Status: " + status );
            console.dir( xhr );
        },

        // Code to run regardless of success or failure
        complete: function( xhr, status ) {
            //alert( "The request is complete!" );
            $('#page-wrapper .row').fadeIn();
        }
    });
    
    window.refresher=function() {
        console.log('refreshing...');
        $.ajax({ url: "/status",type: "GET",dataType : "json",success: window.updateTotals});
        setTimeout(10,window.refresher);
    };
    window.refresher();
});
