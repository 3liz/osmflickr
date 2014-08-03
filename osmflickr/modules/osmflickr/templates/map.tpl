{meta_html csstheme 'css/main.css'}
{meta_html csstheme 'css/map.css'}
{meta_html csstheme 'css/media.css'}

<div id="header">
  <div id="logo">
    {if $photo}
    <h1>OsmFlickr - {$photo->title}</h1>
    {else}
    <h1>OsmFlickr - Map</h1>
    {/if}
  </div>
</div>

<div id="headermenu" class="navbar navbar-fixed-top">
  <div id="auth" class="navbar-inner">
  <div class="pull-right">
    <form id="nominatim-search" class="navbar-search pull-left dropdown">
      <input id="search-query" type="text" class="search-query" placeholder="{@osmflickr~map.search.nominatim.placeholder@}"></input>
      <span class="search-icon">
        <button class="icon nav-search" type="submit" tabindex="-1">
          <span>{@osmflickr~map.search.nominatim.button@}</span>
        </button>
      </span>
      <div class="dropdown-menu pull-right">
        <div class="dropdown-caret">
          <div class="caret-outer"></div>
          <div class="caret-inner"></div>
        </div>
        <div class="dropdown-inner">
          <ul class="items"></ul>
        </div>
      </div>
    </form>
    <ul class="nav">
      {if $photo}
      <li class="osm load">
        <a id="loadOsmData" href="#" rel="tooltip" data-original-title="{@osmflickr~map.osm.data.load@}" data-placement="bottom">
          <span class="icon"></span>
        </a>
      </li>
      <li class="osm remove">
        <a id="removeOsmData" href="#" rel="tooltip" data-original-title="{@osmflickr~map.osm.data.remove@}" data-placement="bottom">
          <span class="icon"></span>
        </a>
      </li>
      {/if}
      {if $isConnected}
      <li class="user dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
          <span class="icon"></span>
          <b id="info-user-login" class="text">{$user->name|eschtml}</b>
          <span class="caret"></span>
        </a>
        <ul class="dropdown-menu pull-right">
          <li><a href="https://www.flickr.com/photos/{$user->nsid}/">{@osmflickr~default.header.flickr.account@}</a></li>
          <li><a href="{jurl 'osmflickr~auth:index'}">{@osmflickr~default.header.flickr.user.photos@}</a></li>
          <li><a href="{jurl 'osmflickr~auth:out'}">{@osmflickr~default.header.disconnect@}</a></li>
        </ul>
      </li>
      {else}
      <li class="login">
        <a href="{jurl 'osmflickr~auth:in'}">
          <span class="icon"></span>
          <span class="text">{@osmflickr~default.header.connect@}</span>
        </a>
      </li>
      {/if}
    </ul>
  </div>
  </div>

</div>

<div id="content">

  <span class="ui-icon ui-icon-open-menu" style="display:none;" title="{@osmflickr~map.menu.show.hover@}"></span>

  <div id="menu">
    {$INFO}
    <div id="baselayer-menu">
      <h3><span class="title">{@osmflickr~map.baselayermenu.title@}</span></h3>
      <div class="menu-content">
        <div class="baselayer-select">
          <select id="baselayer-select" class="label"></select>
        </div>
      </div>
    </div>
  </div>
  <div id="map-content">
    <div id="map"></div>
    <span id="navbar">
      <button class="pan ui-state-select" title="{@osmflickr~map.navbar.pan.hover@}"></button><br/>
      <button class="zoom" title="{@osmflickr~map.navbar.zoom.hover@}"></button><br/>
      <button class="zoom-extent" title="{@osmflickr~map.navbar.zoomextent.hover@}"></button><br/>
      <button class="zoom-in" title="{@osmflickr~map.navbar.zoomin.hover@}"></button><br/>
      <div class="slider" title="{@osmflickr~map.navbar.slider.hover@}"></div>
      <button class="zoom-out" title="{@osmflickr~map.navbar.zoomout.hover@}"></button>
      <div class="history"><button class="previous" title="{@osmflickr~map.navbar.previous.hover@}"></button><button class="next" title="{@osmflickr~map.navbar.next.hover@}"></button></div>
      <span id="zoom-in-max-msg" class="ui-widget-content ui-corner-all" style="display:none;">{@osmflickr~map.message.zoominmax@}</span>
    </span>
    <div id="overview-box">
      <div id="overviewmap" title="{@osmflickr~map.overviewmap.hover@}"></div>
      <div id="overview-bar">
        <div id="scaleline" class="olControlScaleLine" style="width:100px; position:relative; bottom:0; top:0; left:0;" title="{@osmflickr~map.overviewbar.scaleline.hover@}">
        </div>
        <div id="scaletext" class="label" style="position:absolue; bottom:0; top:0; left:100px; right:20px; position:absolute; text-align:center; padding:0.7em 0 0 0;" title="{@osmflickr~map.overviewbar.scaletext.hover@}">{@osmflickr~map.overviewbar.scaletext.title@}</div>
        <button class="button" title="{@osmflickr~map.overviewbar.displayoverview.hover@}"></button>
      </div>
    </div>
    <div id="attribution-box">
      <span id="attribution"></span>
      {image $j_basepath.'css/img/logo_footer.png'}
    </div>
    <div id="message" class="span6">{jmessage_bootstrap}</div>
  </div>
</div>

<div id="loading" class="ui-dialog-content ui-widget-content" title="{@osmflickr~map.loading.title@}">
  <p>
    {image $j_themepath.'css/img/loading.gif'}
  </p>
</div>
