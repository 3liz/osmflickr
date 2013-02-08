<form action="{formurl 'osmflickr~auth:index'}" method="post" id="searchForm" class="form-inline form-search">
  <label class="radio">
    <input type="radio" name="hasgeo" value="-1"{if $form->getData('hasgeo') == '-1'} checked{/if}></input> All
  </label>
  <label class="radio">
    <input type="radio" name="hasgeo" value="1"{if $form->getData('hasgeo') == '1'} checked{/if}></input> Geotagged
  </label>
  <label class="radio">
    <input type="radio" name="hasgeo" value="0"{if $form->getData('hasgeo') == '0'} checked{/if}></input> Ungeotagged
  </label>
  <div class="input-append">
    <input type="text" class="input-large search-query" placeholder="Text" name="q" value="{$form->getData('q')}"></input>
    <input type="submit" class="btn" name="submit" value="Search"></input>
  </div>
  <input type="submit" class="btn" name="clear" value="Clear"></input>
  <input type="hidden" name="search" value="1"></input>
  <input type="hidden" name="__JFORMS_TOKEN__" value="{$form->createNewToken()}"></input>
</form>
<ul class="thumbnails">
  {foreach $photos as $p}
  <li class="span4">
    <a href="{jurl 'osmflickr~photo:index',array('photo_id'=>$p->id,'secret'=>$p->secret)}" class="thumbnail" title="{$p->title}">
      <img src="{$p->buildURL('medium')}" alt="">
    </a>
  </li>
  {/foreach}
</ul>
