// Define the overlay, derived from google.maps.OverlayView
function Target(opt_options) {
  this.setValues(opt_options);

  var div = this.div_ = document.createElement('div');
  var canvas = this.canvas_ = document.createElement('canvas');

  div.style.cssText = 'z-index: 9999; position: absolute; display: block';
  canvas.style.cssText = 'display: block; position:relative;';

  this.geocoding_ = false;
};

Target.prototype = new google.maps.OverlayView();

// Implement onAdd
Target.prototype.onAdd = function() {
  var pane = this.getPanes().floatPane;
  pane.appendChild(this.div_);
  this.div_.appendChild(this.canvas_);
  
  // Ensures the label is redrawn if the text or position is changed.
  var me = this;
  this.listeners_ = [
    google.maps.event.addListener(this, 'center_changed', function() { me.draw(); }),
    google.maps.event.addListener(this, 'mouseover', function() { console.log('mouse move'); }),
  ];
};


// Implement onRemove
Target.prototype.onRemove = function() {
  this.div_.parentNode.removeChild(this.div_);

  // Target is removed from the map, stop updating its position/text.
  for (var i = 0, I = this.listeners_.length; i < I; ++i) {
    google.maps.event.removeListener(this.listeners_[i]);
  }
};

// Implement draw
Target.prototype.draw = function() {
  var projection = this.getProjection();
  var position = projection.fromLatLngToDivPixel(this.get('center'));
  
  // graphics
  var div = this.div_;
  var canvas = this.canvas_;

  var mapBounds = this.getMap().getBounds();
  var mapNE = projection.fromLatLngToDivPixel(mapBounds.getNorthEast());
  var mapSW = projection.fromLatLngToDivPixel(mapBounds.getSouthWest());
  var mapSize = new google.maps.Size(mapNE.x - mapSW.x, mapSW.y - mapNE.y);

  div.style.left = position.x + 'px';
  div.style.top = position.y + 'px'; 
  div.style.width = mapSize.width + 'px';
  div.style.height = mapSize.height + 'px';
  canvas.width = mapSize.width;
  canvas.height = mapSize.height;
  canvas.style.left = - mapSize.width * .5 + 'px';
  canvas.style.top = - mapSize.height * .5 + 'px';

  canvas.width = canvas.width;
  var ctx = canvas.getContext("2d");
  ctx.beginPath();

  ctx.moveTo(0, 0);
  ctx.lineTo(mapSize.width, 0);
  ctx.lineTo(mapSize.width, mapSize.height);
  ctx.lineTo(0, mapSize.height);
  ctx.closePath();

  // arc en counterclockwise pour generer un trou
  ctx.arc(mapSize.width * .5, mapSize.height * .5, 60, 0, 2 * Math.PI, true);
  ctx.closePath();
  ctx.fillStyle = "rgba(255,255,255,0.55)";
  ctx.fill();

  ctx.beginPath();
  ctx.arc(mapSize.width * .5, mapSize.height * .5, 60, 0, 2 * Math.PI);
  ctx.moveTo(mapSize.width * .5 - 20, mapSize.height * .5);
  ctx.lineTo(mapSize.width * .5 +20, mapSize.height * .5);
  ctx.moveTo(mapSize.width * .5, mapSize.height * .5 - 20);
  ctx.lineTo(mapSize.width * .5, mapSize.height * .5 + 20);
  ctx.closePath();

  ctx.strokeStyle = "rgba(141,187,28,1)";
  ctx.lineWidth = 2;
  ctx.stroke();

};

Target.prototype.GeodecodeAddress = function() {
  // geocoded address
  if(!this.geocoding_){
    this.geocoding_ = true;
    var geocoder = new google.maps.Geocoder();
    var that = this;
    geocoder.geocode({'latLng': this.getMap().getCenter()}, function(results, status) {
      that.geocoding_ = false;

      if (status == google.maps.GeocoderStatus.OK) {
        if (results[1]) {
          var canvas = that.canvas_;
          var ctx = canvas.getContext("2d");
          ctx.font = "18px Roboto, Arial, sans-serif";
          ctx.lineWidth = 3.5;
          ctx.fillStyle = "rgba(141,187,28,1)";
          ctx.strokeStyle = "rgba(255,255,255,1)";
          
          var strs = results[1].formatted_address.split(",");
          var str, i = 0;
          while(str = strs.shift()) {
            ctx.strokeText(str.trim(), 90, 50 + 25 * i);
            ctx.fillText(str.trim(), 90, 50 + 25 * i++);
          }
        }
      }
    });
  }
}