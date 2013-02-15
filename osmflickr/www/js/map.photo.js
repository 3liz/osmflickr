
function updateOsmTags() {
  $.get(getOsmTagsUrl,function(data) {
    $('#osmtagbar').replaceWith(data);
    $(window).resize();
    var map = ofMap.map;
    var osmtagLayer = map.getLayersByName('osmtag')[0];
    var osmLayer = map.getLayersByName('osmvector')[0];
    $('#osmtagbar ul.inline li a.delete-x').click(function() {
      var self = $(this);
      var fid = self.parent().attr('id').replace('-','.');
      $('#loading').dialog('open');
      $.get(self.attr('href'), function(data) {
        if (data.type == 'success') {
          var feat = osmtagLayer.getFeatureByFid( fid );
          var newFeat = feat.clone();
          newFeat.fid = feat.fid;
          newFeat.osm_id = feat.osm_id;
          osmLayer.addFeatures([newFeat]);
          osmtagLayer.destroyFeatures([feat]);
          updateOsmTags();
        } 
        $('#loading').dialog('close');
        $('#message').html('<div class="alert alert-block alert-'+data.type+' fade in" data-alert="alert"><a class="close" data-dismiss="alert" href="#">×</a><p>'+data.message+'</p></div>');
      }, 'json');
      $('#loading').dialog('close');
      return false;
    });
  },'html');
}

// Flickr Photo
ofMap.events.on({
  'layersadded':function(evt){
    var map = evt.map;

    var imgLayer = new OpenLayers.Layer.Vector('img', {
      styleMap: new OpenLayers.StyleMap({
        pointRadius: 6,
        fillColor: "#FF0084",
        fillOpacity: 0.4,
        strokeColor: "#0063DC",
        strokeWidth: 1
      })
    });
    map.addLayer(imgLayer);

    var osmtagLayer = new OpenLayers.Layer.Vector('osmtag', {
      styleMap: new OpenLayers.StyleMap({
        pointRadius: 6,
        fillColor: "#0063DC",
        fillOpacity: 0.4,
        strokeColor: "#FF0084",
        strokeWidth: 1
      })
    });
    map.addLayer(osmtagLayer);

    var osmFormat = new OpenLayers.Format.OSM();
    $('#osmtagbar ul.inline li').each(function() {
      var id = $(this).attr('id').split('-');
      $.get(osmUrl, {"osm_type":id[0],"osm_id":id[1]}, function(data) {
        var osmFeat = osmFormat.read(data)[0];
        osmFeat.geometry.transform(
                new OpenLayers.Projection('EPSG:4326'),
                map.getProjectionObject()
              );
        osmtagLayer.addFeatures([osmFeat]);
      }, 'text');
    });

    var osmLayer = new OpenLayers.Layer.Vector('osmvector', {
      /*
      styleMap: new OpenLayers.StyleMap({
        pointRadius: 6,
        fillColor: "#0063DC",
        fillOpacity: 0.4,
        strokeColor: "#FF0084",
        strokeWidth: 1
      })
      */
    });
    map.addLayer(osmLayer);
    $('#osmtagbar ul.inline li a.delete-x').click(function() {
      var self = $(this);
      var fid = self.parent().attr('id').replace('-','.');
      $('#loading').dialog('open');
      $.get(self.attr('href'), function(data) {
        $('#loading').dialog('close');
        if (data.type == 'success') {
          var feat = osmtagLayer.getFeatureByFid( fid );
          var newFeat = feat.clone();
          newFeat.fid = feat.fid;
          newFeat.osm_id = feat.osm_id;
          osmLayer.addFeatures([newFeat]);
          osmtagLayer.destroyFeatures([feat]);
          updateOsmTags();
        } 
        $('#loading').dialog('close');
        $('#message').html('<div class="alert alert-block alert-'+data.type+' fade in" data-alert="alert"><a class="close" data-dismiss="alert" href="#">×</a><p>'+data.message+'</p></div>');
      }, 'json');
      return false;
    });
  }
  ,'uicreated':function(evt){
    var map = evt.map;
    var osmtag = map.getLayersByName('osmtag')[0];
    if (osmtag.features.length != 0)
      map.zoomToExtent(osmtag.getDataExtent());

    var osmvector = map.getLayersByName('osmvector')[0];
    var drawBox = new OpenLayers.Control.DrawFeature(
        osmvector,
        OpenLayers.Handler.RegularPolygon, {
          handlerOptions: {
            sides: 4,
            irregular: true
          }
        });
    var selectOsm = new OpenLayers.Control.SelectFeature(
        osmvector,
        {includeXY:true}
        );
    map.addControls([drawBox,selectOsm]);
    selectOsm.activate();
    osmvector.events.includeXY = true;
    osmvector.events.on({
      "sketchcomplete": function(evt) {
        var b = evt.feature.geometry.getBounds().transform(map.getProjectionObject(), new OpenLayers.Projection('EPSG:4326'));
        // limit the size of the bbox
        if (b.getWidth()*b.getHeight() > 0.00001) {
          $('#message').html('<div class="alert alert-block alert-error fade in" data-alert="alert"><a class="close" data-dismiss="alert" href="#">×</a><p>'+dict['osm.load.extent.toobig']+'</p><p>'+dict['osm.load.extent.smaller']+'</p></div>');
          return false;
        }

        $('#loading').dialog('open');
        $.get(mapapiUrl, {'bbox':b.toBBOX()}, function(data) {
          var tagLayer = map.getLayersByName('osmtag')[0];
          var osmLayer = map.getLayersByName('osmvector')[0];
          var osmFormat = new OpenLayers.Format.OSM({
            checkTags:true,
            externalProjection: new OpenLayers.Projection('EPSG:4326'),
            internalProjection: map.getProjectionObject()
          });
          var osmFeats = osmFormat.read(data);
          var feats = [];
          for (var i=0, len= osmFeats.length; i<len; i++) {
            var feat = osmFeats[i];
            if ( ('natural' in feat.attributes) && !('name' in feat.attributes) )
              continue;
            if ( ('landuse' in feat.attributes) && !('name' in feat.attributes) )
              continue;
            if ( tagLayer.getFeatureByFid( feat.fid ) )
              continue;
            if ( osmLayer.getFeatureByFid( feat.fid ) )
              continue;
            feats.push(feat);
          }
          // sorting features based on the geometry
          feats.sort(function(a, b) {
            if (a.geometry.CLASS_NAME == b.geometry.CLASS_NAME) {
              if (a.geometry.CLASS_NAME == 'OpenLayers.Geometry.Point')
                return 0;
              if (a.geometry.CLASS_NAME == 'OpenLayers.Geometry.LineString')
                return 0;
              return b.geometry.getArea() - a.geometry.getArea();
            }
            if (a.geometry.CLASS_NAME == 'OpenLayers.Geometry.Point')
              return 1;
            if (b.geometry.CLASS_NAME == 'OpenLayers.Geometry.Point')
              return -1;
            if (a.geometry.CLASS_NAME == 'OpenLayers.Geometry.LineString')
              return 1;
            if (b.geometry.CLASS_NAME == 'OpenLayers.Geometry.LineString')
              return -1;
            return 0;
          });
          osmLayer.addFeatures(feats);
          $('#loading').dialog('close');
          $('#loadOsmData').click();
        });
        return false;
      },
        "featureunselected": function(evt) {
          if (map.popups.length != 0)
            map.removePopup(map.popups[0]);
      },
        "featureselected": function(evt) {
          var feat = evt.feature;
          var fid = feat.fid.split('.');
          var text = '<h4>OSM element '+fid[0]+'</h4>';
          text += '<div class="lizmapPopupDiv">';
          text += '<button class="btn osmtag-add" title="Add"><span class="icon"></span><span class="text">'+dict['osm.tagmachine.add']+'</span></button>'
          text += '<table class="lizmapPopupTable">';
          text += '<thead><tr><th class="left">key</th><th>value</th></tr></thead>';
          text += '<tbody>';
          var urlRegex = /^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
          var emailRegex = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/;
          var imageRegex = /\.(jpg|jpeg|png|gif|bmp)$/i;
          var attr = feat.attributes;
          for (var k in attr) {
            var v = attr[k];
            if ( imageRegex.test(v) )
              text += '<tr><td colspan="2"><img src="'+v+'" width="300" border="0"/></td></tr>';
            else {
              text += '<tr><th>'+k+'</th>';
              if ( urlRegex.test(v) )
                text += '<td><a href="'+v+'" target="_blank">'+v+'<a></td></tr>';
              else if ( emailRegex.test(v) )
                text += '<td><a href="mailto:'+v+'">'+v+'</a></td></tr>';
              else
                text += '<td>'+v+'</td></tr>';
            }
          }
          text += '</tbody>';
          text += '</table>';
          text += '</div>';

          if (map.popups.length != 0)
            map.removePopup(map.popups[0]);

          OpenLayers.Popup.ofMapAnchored = OpenLayers.Class(OpenLayers.Popup.Anchored, {
            'displayClass': 'olPopup lizmapPopup'
            ,'contentDisplayClass': 'olPopupContent lizmapPopupContent'
          });
          var popup = new OpenLayers.Popup.ofMapAnchored(
              "liz_layer_popup",
              map.getLonLatFromPixel(selectOsm.handlers.feature.down),
              null,
              text,
              null,
              true,
              function() {
                map.removePopup(this);
                selectOsm.unselectAll();
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
          $('#liz_layer_popup_contentDiv button.osmtag-add').click(function() {
            var tag = 'osm:'+fid[0]+'='+fid[1];
            $('#loading').dialog('open');
            $.get(addTagUrl, {'tag':tag}, function(data) {
              map.removePopup(popup);
              selectOsm.unselectAll();
              if (data.type == 'success') {
                var newFeat = feat.clone();
                newFeat.fid = feat.fid;
                newFeat.osm_id = feat.osm_id;
                map.getLayersByName('osmtag')[0].addFeatures([newFeat]);
                feat.layer.destroyFeatures([feat]);
                updateOsmTags();
              }
              $('#loading').dialog('close');
              $('#message').html('<div class="alert alert-block alert-'+data.type+' fade in" data-alert="alert"><a class="close" data-dismiss="alert" href="#">×</a><p>'+data.message+'</p></div>');
            },'json');
          });
        }
    });
    // Toggle loading OSM data
    $('#loadOsmData').click(function(){
      if (drawBox.active) {
        drawBox.deactivate();
        selectOsm.activate();
      } else {
        selectOsm.deactivate();
        drawBox.activate();
      }
      return false;
    });
    // Remove loaded OSM data
    $('#removeOsmData').click(function(){
      osmvector.destroyFeatures();
      return false;
    });
    // Search with nominatim
    $('#nominatim-search').submit(function(){
      $('#nominatim-search .dropdown-inner .items').html('');
      $(this).data('value',$('#search-query').val());
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

    map.events.on({
      moveend : function() {
        $.cookie('bbox',map.getExtent().transform(map.getProjectionObject(), new OpenLayers.Projection('EPSG:4326')).toBBOX());
      }
    });
  }
});
