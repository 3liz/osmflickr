<div id="photo-menu">
  <h3>
    <span class="title">Photos
    <form action="{formurl 'osmflickr~service:osmflickrmap'}" method="post" id="photo-search" class="form-inline form-search" style="display:inline;">
      <div class="input-append">
        <input id="photo-search-query" type="text" class="input-small search-query" placeholder="{@osmflickr~default.search.q.placeholder@}" name="q" value="{$form->getData('q')}"></input>
        <button id="photo-search-button" class="btn" type="button">Go!</button>
      </div>
    </form></span>
  </h3>
  <div class="menu-content">
    <div id="photo-info">
    </div>
  </div>
</div>
