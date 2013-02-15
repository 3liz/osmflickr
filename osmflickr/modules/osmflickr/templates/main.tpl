{meta_html csstheme 'css/main.css'}
{meta_html csstheme 'css/media.css'}

<div id="header">
  <div id="logo">
    <h1>OsmFlickr</h1>
  </div>
</div>

<div id="headermenu" class="navbar navbar-fixed-top">
  <div id="auth" class="navbar-inner">
    <ul class="nav pull-right">
      <li class="map">
        <a rel="tooltip" title="{@osmflickr~default.header.map.title@}" href="{jurl 'osmflickr~default:index'}">
          <span class="icon"></span>
        </a>
      </li>
      {if $isConnected}
      <li class="user dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
          <span class="icon"></span>
          <b id="info-user-login" class="text">{$user->name|eschtml}</b>
          <span class="caret"></span>
        </a>
        <ul class="dropdown-menu pull-right">
          <li><a href="http://www.flickr.com/photos/{$user->nsid}/">{@osmflickr~default.header.flickr.account@}</a></li>
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

<div id="content" class="container">
{jmessage_bootstrap}
{$MAIN}
<footer class="footer">
  {if $page}
  <div class="pull-left">
    {pagelinks_flickr_bootstrap 'osmflickr~auth:index',array(),$pages,$page,'page',array('area-size'=>2)}
  </div>
  {/if}
  <p class="pull-right">
    {image $j_basepath.'css/img/logo_footer.png'}
  </p>
</footer>
</div>
