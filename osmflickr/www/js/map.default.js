
// Flickr Photos
ofMap.events.on({
  'layersadded':function(evt){
    var map = evt.map;

    /**
     * A specific format for parsing Flickr API JSON responses.
     */
    OpenLayers.Format.Flickr = OpenLayers.Class(OpenLayers.Format, {
      read: function(obj) {
        obj = (new OpenLayers.Format.JSON()).read(obj);
        if(obj.stat === 'fail') {
          throw new Error(
            ['Flickr failure response (',
              obj.code,
              '): ',
            obj.message].join(''));
        }
        if(!obj || !obj.photos ||
          !OpenLayers.Util.isArray(obj.photos.photo)) {
            throw new Error(
              'Unexpected Flickr response');
          }
        var photos = obj.photos.photo, photo,
                     x, y, point,
                     feature, features = [];
        for(var i=0,l=photos.length; i<l; i++) {
          photo = photos[i];
          x = photo.longitude;
          y = photo.latitude;
          point = new OpenLayers.Geometry.Point(x, y);
          feature = new OpenLayers.Feature.Vector(point, {
            id: photo.id,
            title: photo.title,
            owner: photo.owner,
            machine_tags: photo.machine_tags,
            url_sq: photo.url_sq,
            url_s: photo.url_s
          });
          feature.fid = photo.id;
          features.push(feature);
        }
        return features;
      }
    });
    var layer = new OpenLayers.Layer.Vector("Flickr", {
      projection: "EPSG:4326",
      strategies: [new OpenLayers.Strategy.BBOX({ratio:1})],
      protocol: new OpenLayers.Protocol.HTTP({
        url: osmflickrmapUrl,
        format: new OpenLayers.Format.Flickr(),
        params: {q:$('#photo-search-query').val()}
      }),
      styleMap: new OpenLayers.StyleMap({
        "default": new OpenLayers.Style({
          pointRadius: 6,
          fillColor: "#FF0084",
          fillOpacity: 0.4,
          strokeColor: "#0063DC",
          strokeWidth: 1
        }),
        "select": new OpenLayers.Style({
          pointRadius: 8,
          fillColor: "#FF0084",
          fillOpacity: 1,
          strokeColor: "#0063DC",
          strokeWidth: 2
        })
      })
    });
    map.addLayers([layer]);
    var selectCtrl = new OpenLayers.Control.SelectFeature(
        layer,
        {includeXY:true}
        );
    map.addControls([selectCtrl]);
    selectCtrl.activate();
    layer.events.on({
      'featuresadded': function(evt) {
        var text = '<ul class="thumbnails">';
        for (var i=0, len=layer.features.length; i<len; i++){
          var feat = layer.features[i];
          text += '<li class="span1">';
          text += '<a id="'+feat.fid+'" href="#" onclick="return false;" class="thumbnail" title="'+feat.attributes.title+'">';
          text += '<img src="'+feat.attributes.url_sq+'" alt="">';
          text += '</a>';
          text += '</li>';
        }
        text += '</ul>';
        $('#photo-info').html(text)
        $('#photo-info a').click(function() {
          var feat = layer.getFeatureByFid($(this).attr('id'));
          selectCtrl.unselectAll();
          selectCtrl.select(feat);
        });
        if (map.popups.length != 0) {
          var id = $(map.popups[0].div).attr('id');
          var feat = layer.getFeatureByFid(id.replace('liz_layer_popup_',''));
          selectCtrl.select(feat);
        }
      },
        "featureunselected": function(evt) {
          if (map.popups.length != 0)
            map.removePopup(map.popups[0]);
      },
      "featureselected": function(evt) {
        var feat = evt.feature;
        if ( $('#liz_layer_popup_'+feat.fid).length !=0 )
          return true;
          var text = '<h4>'+feat.attributes.title+'</h4>';
          text += '<div class="lizmapPopupDiv">';
          text += '<a href="https://www.flickr.com/photos/'+feat.attributes.owner+'/'+feat.attributes.id+'" class="thumbnail" title="'+feat.attributes.title+'" target="_blank">';
          text += '<img src="'+feat.attributes.url_s+'" alt="">';
          text += '</a>';
          var mtags = feat.attributes.machine_tags.split(' ');
          if ( mtags.length != 0 ) {
            text += '<p>';
            var mtagsText = [];
            for (var i=0, len=mtags.length; i<len; i++) {
              var mt = mtags[i];
              if ( mt.match(/^osm:/) ) {
                var mtSplit = mt.split('=');
                var mtText = '<a href="http://www.openstreetmap.org/browse/'+mtSplit[0].replace('osm:','')+'/'+mtSplit[1]+'" target="_blank">'+mt+'</a>';
                mtagsText.push(mtText);
              } else
                mtagsText.push(mt);
            }
            text += mtagsText.join(' ');
            text += '</p>';
          }
          text += '</div>';

          if (map.popups.length != 0)
            map.removePopup(map.popups[0]);

          OpenLayers.Popup.ofMapAnchored = OpenLayers.Class(OpenLayers.Popup.Anchored, {
            'displayClass': 'olPopup lizmapPopup'
            ,'contentDisplayClass': 'olPopupContent lizmapPopupContent'
          });
          var popup = new OpenLayers.Popup.ofMapAnchored(
              "liz_layer_popup_"+feat.fid,
              feat.geometry.getBounds().getCenterLonLat(),
              null,
              text,
              null,
              true,
              function() {
                map.removePopup(this);
                selectCtrl.unselectAll();
              }
              );
          popup.panMapIfOutOfView = true;
          popup.autoSize = true;
          popup.maxSize = new OpenLayers.Size(450, 400);
          map.addPopup(popup);
          // correcting the height
          var contentDivHeight = 0;
          $('#liz_layer_popup_contentDiv').children().each(function(i,e) {
            contentDivHeight += $(e).outerHeight(true);
          });
          if ( $('#liz_layer_popup_contentDiv').height() > contentDivHeight ) {
            $('#liz_layer_popup_contentDiv').height(contentDivHeight)
              $('#liz_layer_popup').height(contentDivHeight)
          }
          if($('#liz_layer_popup').height()<contentDivHeight) {
            $('#liz_layer_popup .olPopupCloseBox').css('right','14px');
          }
      }
    });
    $('#photo-search').submit(function(){
      layer.protocol.options.params['q'] = $('#photo-search-query').val();
      layer.refresh({force:true});
      return false;
    });
    $('#photo-search-button').click(function(){
      $('#photo-search').submit();
    });
    $('#map-content').append('<div id="permalink"><a href="'+window.location+'">Lien permanent</a></div>');

    map.events.on({
      moveend : function() {
        var bbox = map.getExtent().transform(map.getProjectionObject(), new OpenLayers.Projection('EPSG:4326')).toBBOX();
        $.cookie('bbox', bbox);
        $('#photo-info').html('')
        var layer = map.getLayersByName('Flickr')[0];
        layer.refresh({force:true});
      }
    });

    layer.events.on({
      moveend : function() {
        var bbox = map.getExtent().transform(map.getProjectionObject(), new OpenLayers.Projection('EPSG:4326')).toBBOX();
        var href = $('#permalink a').attr('href');
        if (href.indexOf('?') != -1) {
          href = href.substring( 0, href.indexOf('?') );
        }
        var params = {};
        params['bbox'] = bbox;
        params['query'] = layer.protocol.params.q;
        href += '?'+OpenLayers.Util.getParameterString(params);
        $('#permalink a').attr('href', href);
      }
    });
  }
  ,'uicreated':function(evt){
    var map = evt.map;
    // Search with nominatim
    $('#nominatim-search').submit(function(){
      $('#nominatim-search .dropdown-inner .items').html('');
      $('#permalink a').data('value',$('#search-query').val());
      $.get(nominatimUrl
        ,{"query":$('#search-query').val()}
        ,function(data) {
          var text = '';
          $.each(data, function(i, e){
            var bbox = [
              e.boundingbox[2],
              e.boundingbox[0],
              e.boundingbox[3],
              e.boundingbox[1]
            ];
            text += '<li><a href="#'+bbox.join(',')+'">'+e.display_name+'</a></li>';
          });
          if (text != '') {
            $('#nominatim-search .dropdown-inner .items').html(text);
            $('#nominatim-search').addClass('open');
            $('#nominatim-search .dropdown-inner .items li > a').click(function() {
              var bbox = $(this).attr('href').replace('#','');
              var extent = OpenLayers.Bounds.fromString(bbox);
              extent.transform(new OpenLayers.Projection('EPSG:4326'), map.getProjectionObject());
              map.zoomToExtent(extent);
              $('#nominatim-search').removeClass('open');
              return false;
            });
          }
        }, 'json');
      return false;
    });
    $('#search-query').focusin(function() {
      var self = $(this);
      var nomin = $('#nominatim-search');
      if (self.val() != '' && self.val() == nomin.data('value'))
        nomin.addClass('open');
    })
    .keyup(function() {
      var self = $(this);
      var nomin = $('#nominatim-search');
      if ( nomin.hasClass('open') && self.val() != nomin.data('value') )
        nomin.removeClass('open');
      else 
      if (self.val() != '' && self.val() == nomin.data('value'))
        nomin.addClass('open');
    })
    .focusout(function() {
      var nomin = $('#nominatim-search');
      if ( !nomin.is(':hover') ) {
        nomin.removeClass('open');
      }
    });
  }
});
