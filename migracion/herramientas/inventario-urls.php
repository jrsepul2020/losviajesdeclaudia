<?php
$out = fopen($args[0], 'w');
fputcsv($out, ['tipo','id','url','estado','titulo']);
$q = new WP_Query(['post_type'=>['post','page'],'post_status'=>'publish','posts_per_page'=>-1,'no_found_rows'=>true]);
foreach ($q->posts as $p) fputcsv($out, [$p->post_type,$p->ID,get_permalink($p),$p->post_status,$p->post_title]);
foreach (['category','post_tag'] as $tx) foreach (get_terms(['taxonomy'=>$tx,'hide_empty'=>false]) as $t) {
  fputcsv($out, [$tx,$t->term_id,get_term_link($t),'count='.$t->count,$t->name]);
  $pp = (int)get_option('posts_per_page'); $pages = $pp ? (int)ceil($t->count/$pp) : 1;
  for ($i=2;$i<=$pages;$i++) fputcsv($out, [$tx.'-paged',$t->term_id,trailingslashit(get_term_link($t)).'page/'.$i.'/','',$t->name]);
}
foreach (get_users(['has_published_posts'=>true]) as $u) fputcsv($out, ['author',$u->ID,get_author_posts_url($u->ID),'',$u->display_name]);
$att = get_posts(['post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>-1,'fields'=>'ids']);
foreach ($att as $id) fputcsv($out, ['attachment',$id,get_permalink($id),'','']);
