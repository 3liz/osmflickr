<form action="{formurl 'osmflickr~auth:search'}" method="post" id="searchForm" class="form-inline form-search">
  <label class="radio">
    <input type="radio" name="hasgeo" value="-1"{if $form->getData('hasgeo') == '-1'} checked{/if}></input> {@osmflickr~default.search.hasgeo.all@}
  </label>
  <label class="radio">
    <input type="radio" name="hasgeo" value="1"{if $form->getData('hasgeo') == '1'} checked{/if}></input> {@osmflickr~default.search.hasgeo.geotagged@}
  </label>
  <label class="radio">
    <input type="radio" name="hasgeo" value="0"{if $form->getData('hasgeo') == '0'} checked{/if}></input> {@osmflickr~default.search.hasgeo.ungeotagged@}
  </label>
    <input type="text" class="input-large search-query" placeholder="{@osmflickr~default.search.q.placeholder@}" name="q" value="{$form->getData('q')}"></input>
  <label class="radio">
  {@osmflickr~default.search.hasosm.label@}
  </label>
  <label class="radio">
    <input type="radio" name="hasosm" value="-1"{if $form->getData('hasosm') == '-1'} checked{/if}></input> {@osmflickr~default.search.hasosm.none@}
  </label>
  <label class="radio">
    <input type="radio" name="hasosm" value="1"{if $form->getData('hasosm') == '1'} checked{/if}></input> {@osmflickr~default.search.hasosm.with@}
  </label>
  <label class="radio">
    <input type="radio" name="hasosm" value="0"{if $form->getData('hasosm') == '0'} checked{/if}></input> {@osmflickr~default.search.hasosm.without@}
  </label>
  <input type="submit" class="btn btn-primary" name="submit" value="{@osmflickr~default.search.submit.value@}"></input>
  <input type="submit" class="btn" name="clear" value="{@osmflickr~default.search.clear.value@}"></input>
  <input type="hidden" name="search" value="1"></input>
  <input type="hidden" name="__JFORMS_TOKEN__" value="{$form->createNewToken()}"></input>
</form>
<ul class="thumbnails">
  {foreach $photos as $p}
  <li class="span2">
    <div class="ofphoto">
    <a href="{jurl 'osmflickr~photo:index',array('photo_id'=>$p->id,'secret'=>$p->secret)}" class="thumbnail" title="{$p->title}">
      <img src="{$p->buildURL('lsquare')}" alt="">
    </a>
    {if $p->hasLocation()}
    <span class="oflocation"></span>
    {/if}
    {if $p->hasOsmTags()}
    <span class="ofosmtags"></span>
    </div>
    {/if}
  </li>
  {/foreach}
</ul>
