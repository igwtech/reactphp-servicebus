/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$( document ).ready(function() {
    var MAX_POINTS=100;
    var REFRESH_TIME=1000;
    var datasets = [
        {
            "label":"Memory",
            "data":[]
        },
        {
            "label":"Peak Memory",
            "data":[]
        }
    ];
    
    var counters = {
        totalErr:0,
        totalMsg:0,
        totalSent:0,
        lastTime: (new Date().getTime()/1000),
        avgErr:0,
        avgMsg:0,
        avgSent:0
    };
    window.updateTotals=function (json) {

            console.log(json);
            var terr=0,tmsg=0,tsent=0;
            
            for(route in json['routes']) {
                terr+=json['routes'][route].status.err
                tmsg+=json['routes'][route].status.msg
                tsent+=json['routes'][route].status.sent
            }
            if( counters.totalMsg == 0) {
                counters.totalMsg=tmsg;
            }
            if( counters.totalErr == 0) {
                counters.totalErr=terr;
            }
            if( counters.totalSent == 0) {
                counters.totalSent=tsent;
            }
            
            var cavgMsg=(tmsg - counters.totalMsg)/(json.timestamp - counters.lastTime);
            var cavgErr=(terr - counters.totalErr)/(json.timestamp - counters.lastTime);
            var cavgSent=(tsent - counters.totalSent)/(json.timestamp - counters.lastTime);
            // Poderated avg.
            var pavgMsg=(counters.avgMsg*(MAX_POINTS-1) + cavgMsg) / MAX_POINTS;
            var pavgErr=(counters.avgErr*(MAX_POINTS-1) + cavgErr) / MAX_POINTS;
            var pavgSent=(counters.avgSent*(MAX_POINTS-1) + cavgSent) / MAX_POINTS;
            
            
            $('#status-processed').html(pavgMsg.toFixed(1));
            $('#status-warn').html(pavgErr.toFixed(1));
            $('#status-sent').html(pavgSent.toFixed(1));
            //Update counters
            counters.avgMsg=pavgMsg;
            counters.avgErr=pavgErr;
            counters.avgSent=pavgSent;
            counters.totalErr= terr;
            counters.totalMsg= tmsg;
            counters.totalSent=tsent;
            counters.lastTime= json.timestamp;

            updateChart(json);
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
            
        }
    });
    
    window.refresher=function() {
        console.log('refreshing...');
        $.ajax({ url: "/status",type: "GET",dataType : "json",success: window.updateTotals});

        window.setTimeout(window.refresher,REFRESH_TIME);
        
    };
    
    var plot = $.plot("#memory-area-chart", getAreaData(null) ,
    {
            series: {
                    shadowSize: 0,	// Drawing is faster without shadows
                    //bars: { show: true, fill: true, fillColor: "rgba(0, 255, 255, 0.8)" },
            },
            yaxis: {

                    show: true,
                    max: 16*1024*1024,
                    min: 0
            },
            xaxis: {
                    show: true,
                    mode: "time",
                    timeformat: "%H:%M:%S "
            }
    });
    function getAreaData(json) {    
        var i=-MAX_POINTS,j=-MAX_POINTS;
        while(datasets[0].data.length < MAX_POINTS) {
            datasets[0].data.push([new Date().getTime()+ (i++)*REFRESH_TIME,0]);
        }
        while(datasets[1].data.length < MAX_POINTS) {
            datasets[1].data.push([new Date().getTime()+ (j++)*REFRESH_TIME,0]);
        }
        if(json!==null){
            if(datasets[1].data.length >= MAX_POINTS) {
                datasets[0].data=datasets[0].data.slice(1);
            }
            datasets[0].data.push([json.timestamp*1000,json.memory]);
            if(datasets[1].data.length >= MAX_POINTS) {
                datasets[1].data=datasets[1].data.slice(1);
            }
            datasets[1].data.push([json.timestamp*1000,json.peak]);
            //datasets.memory.data.push([json.memory,json.timestamp]);
        }
        return datasets;
    };
    function updateChart(json) {
            
            plot.setData(getAreaData(json));
            plot.setupGrid();
            // Since the axes don't change, we don't need to call plot.setupGrid()

            plot.draw();
    };
    window.setTimeout(window.refresher,REFRESH_TIME);

});
