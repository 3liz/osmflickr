<div id="photo-menu">
  <h3><span class="title">{$photo->title}</span></h3>
  <div class="menu-content">
    <div id="photo-info">
      <a href="{$photo->getPhotopageURL()}" target="_blank" title="{@osmflickr~map.photo.info.link@}">
        <img src="{$photo->buildURL('medium')}" alt="{$photo->title}">
      </a>
      <div id="photo-info-content">
      <p id="photo-info-desc">{$photo->info['description']}</p>
      {if $photo->hasLocation()}
      {assign $loc = $photo->getLocation()}
      <p id="photo-info-loc">{@osmflickr~map.photo.info.taken@} {$loc->locality}, {$loc->county}, {$loc->region}, {$loc->country}</p>
      <script type="text/javascript">
      {literal}
        lizMap.events.on({
          'uicreated':function(evt){
            var imgLayer = lizMap.map.getLayersByName('img')[0];
            var imgFeat = new OpenLayers.Feature.Vector(
              (new OpenLayers.Geometry.Point(
      {/literal}
                {$loc->lon},{$loc->lat}
      {literal}
              )).transform(
                new OpenLayers.Projection('EPSG:4326'),
                lizMap.map.getProjectionObject()
              ), {
      {/literal}
                lon:{$loc->lon}
               ,lat:{$loc->lat}
               ,acc:{$loc->acc}
      {literal}
              });
            //lizMap.map.addLayer(imgLayer);
            imgLayer.addFeatures([imgFeat]);
            var imgGeom = imgFeat.geometry;
            lizMap.map.setCenter(
              new OpenLayers.LonLat(imgGeom.x, imgGeom.y),
              imgFeat.attributes.acc
            );
          }
        });
      {/literal}
      </script>
      {/if}
      {if $photo->hasSimpleTags()}
      <div>{@osmflickr~map.photo.info.tags@}
      <ul class="inline">
      {foreach $photo->getSimpleTags() as $t}
        <li>{$t->title}</li>
      {/foreach}
      </ul>
      </div>
      {/if}
      </div>
    </div>
  </div>
</div>
