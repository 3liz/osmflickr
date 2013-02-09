<div id="osmtagbar">
  <h3><span class="title">{@osmflickr~map.osm.tagmachine@}</span></h3>
  <div class="menu-content">
  {jmessage_bootstrap}
  <ul class="inline">
  {foreach $photo->getOsmTags() as $t}
    <li id="{$t->osm_type}-{$t->osm_id}"><a href="http://www.openstreetmap.org/browse/{$t->osm_type}/{$t->osm_id}" target="_blank" title="{@osmflickr~map.osm.tagmachine.link@}">{$t->title}</a> <a href="{jurl 'osmflickr~photo:removeTag',array('tag_id'=>$t->id)}" class="delete-x" title="{@osmflickr~map.osm.tagmachine.remove@}">x</a></li>
  {/foreach}
  </ul>
  </div>
</div>
