jQuery(function() {
    // Asynchronously Load the map API 
    var script = document.createElement('script');
    script.src = "//maps.googleapis.com/maps/api/js?sensor=false&callback=initialize&key=AIzaSyAxmfvalx5celAYk6sB4VEhPvzhCr7G0H8";
    document.body.appendChild(script);
});

var marker;
var radiomarkers={};

function initialize() {
    var map;
    var bounds = new google.maps.LatLngBounds();
    var mapOptions = {
        mapTypeId: 'roadmap'
    };
                    
    // Display a map on the page
    map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
    map.setTilt(45);    
   
 
        
    // Display multiple markers on a map
  //  var infoWindow = new google.maps.InfoWindow(); 
    var i;
    var markers1 = [];

    
    jQuery('#coolrunner-search-results input[type=radio]').on('click', function(){

        var input_id = jQuery(this).attr('id'); // ID - which we use to connect radio-btns and markers
      
       
          jQuery(".coolrunner-dao-detail").hide();
          jQuery("#coolrunner-dao-pre").hide();
      
          if(typeof radiomarkers[input_id] !== 'undefined'){
              jQuery("#dp"+input_id).show();
      
              for(key in radiomarkers){
                  if(radiomarkers.hasOwnProperty(key) ){
                              if( key === input_id || input_id === 'showall'){ 
                                  radiomarkers[key].setAnimation(google.maps.Animation.BOUNCE);
                                    map.setCenter(radiomarkers[key].getPosition());
                              }
                              else {
                                  radiomarkers[key].setAnimation(null);
                              }
                      
                  }
              }
          }
      });

    
    // Loop through our array of markers & place each one on the map  
    for( i = 0; i < markers.length; i++ ) {
        var position = new google.maps.LatLng(markers[i][1], markers[i][2]);
        bounds.extend(position);
        var city = markers[i];  
        marker = new google.maps.Marker({
            position: position,
            map: map,
            title: markers[i][0]
        });

         radiomarkers[city[3]] = marker;
  
 /*
            markers1.push(marker);
            marker.addListener('click', function() {
            for (var i = 0; i < markers1.length; i++) {
                markers1[i].setAnimation(null);
            }
            toggleBounce(this);
              map.setCenter(marker.getPosition());
            });
    
    */    
        // Allow each marker to have an info window    
        google.maps.event.addListener(marker, 'click', (function(marker, i) {
            return function() {
                infoWindow.setContent(infoWindowContent[i][0]);
                infoWindow.open(map, marker);
            }
        })(marker, i));     

        // Automatically center the map fitting all markers on the screen
        map.fitBounds(bounds);

    }
       

    // Override our map zoom level once our fitBounds function runs (Make sure it only runs once)
    var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
      //  this.setZoom(3);
        google.maps.event.removeListener(boundsListener);
    });
    
}

function toggleBounce(ele) {
    if (ele.getAnimation() !== null) {
        ele.setAnimation(null);
    } else {
        ele.setAnimation(google.maps.Animation.BOUNCE);
    }
  }
