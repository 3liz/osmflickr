<ul class="thumbnails">
  {foreach $photos as $p}
  <li class="span4">
    <a href="{jurl 'osmflickr~photo:index',array('photo_id'=>$p->id,'secret'=>$p->secret)}" class="thumbnail" title="{$p->title}">
      <img src="{$p->buildURL('medium')}" alt="">
    </a>
  </li>
  {/foreach}
</ul>
