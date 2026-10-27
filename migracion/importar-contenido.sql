-- Importación limpia de contenido: oldsrc (copia de losviajesdeclaudia) -> local (WordPress nuevo)
-- Conserva IDs de entradas, páginas, adjuntos, términos, comentarios y usuarios para no alterar URLs.
SET NAMES utf8mb4;
SET SESSION sql_mode = '';  -- el contenido antiguo tiene fechas 0000-00-00 en borradores

-- 1. Vaciar contenido de ejemplo del WordPress nuevo
DELETE FROM local.wp_posts; DELETE FROM local.wp_postmeta;
DELETE FROM local.wp_comments; DELETE FROM local.wp_commentmeta;
DELETE FROM local.wp_terms; DELETE FROM local.wp_term_taxonomy; DELETE FROM local.wp_term_relationships; DELETE FROM local.wp_termmeta;

-- 2. Usuarios: el admin del sitio nuevo pasa a un ID libre; los usuarios antiguos conservan su ID (autoría)
SET @nuevo_admin = (SELECT MAX(ID)+1 FROM oldsrc.wp_users);
UPDATE local.wp_usermeta SET user_id=@nuevo_admin WHERE user_id=1;
UPDATE local.wp_users SET ID=@nuevo_admin WHERE ID=1;
INSERT INTO local.wp_users SELECT * FROM oldsrc.wp_users;
INSERT INTO local.wp_usermeta (user_id,meta_key,meta_value)
  SELECT user_id,meta_key,meta_value FROM oldsrc.wp_usermeta
  WHERE meta_key IN ('nickname','first_name','last_name','description','rich_editing','syntax_highlighting','comment_shortcuts','admin_color','use_ssl','show_admin_bar_front','locale','wp_capabilities','wp_user_level','twitter','facebook','instagram','youtube','tiktok','wpseo_title','wpseo_metadesc','wpseo_noindex_author');

-- 3. Entradas, páginas, adjuntos y bloques reutilizables (sin revisiones, menús, Elementor, Content Views…)
INSERT INTO local.wp_posts SELECT * FROM oldsrc.wp_posts
  WHERE post_type IN ('post','page','attachment','wp_block') AND post_status IN ('publish','draft','private','future','pending','inherit');

-- 4. Metadatos útiles (lista blanca)
INSERT INTO local.wp_postmeta (post_id,meta_key,meta_value)
  SELECT m.post_id, IF(m.meta_key='penci_post_views_count','_lvc_views',m.meta_key), m.meta_value
  FROM oldsrc.wp_postmeta m JOIN local.wp_posts p ON p.ID=m.post_id
  WHERE m.meta_key IN ('_wp_attached_file','_wp_attachment_metadata','_wp_attachment_image_alt','_wp_attachment_backup_sizes',
                       '_thumbnail_id','_wp_old_slug','_wp_old_date','footnotes','penci_post_views_count')
     OR (m.meta_key LIKE '\_yoast\_wpseo\_%' AND m.meta_key NOT IN ('_yoast_wpseo_content_score','_yoast_wpseo_linkdex',
         '_yoast_wpseo_wordproof_timestamp','_yoast_wpseo_seo_title_score','_yoast_wpseo_meta_description_score','_yoast_wpseo_focuskw_text_input'));

-- 5. Categorías y etiquetas (mismos IDs)
INSERT INTO local.wp_term_taxonomy SELECT * FROM oldsrc.wp_term_taxonomy WHERE taxonomy IN ('category','post_tag');
INSERT INTO local.wp_terms SELECT t.* FROM oldsrc.wp_terms t JOIN local.wp_term_taxonomy tt USING(term_id);
INSERT INTO local.wp_term_relationships SELECT r.* FROM oldsrc.wp_term_relationships r
  JOIN local.wp_term_taxonomy tt USING(term_taxonomy_id) JOIN local.wp_posts p ON p.ID=r.object_id;

-- 6. Comentarios
INSERT INTO local.wp_comments SELECT c.* FROM oldsrc.wp_comments c JOIN local.wp_posts p ON p.ID=c.comment_post_ID;
INSERT INTO local.wp_commentmeta (comment_id,meta_key,meta_value) SELECT m.comment_id,m.meta_key,m.meta_value
  FROM oldsrc.wp_commentmeta m JOIN local.wp_comments c ON c.comment_ID=m.comment_id WHERE m.meta_key NOT LIKE 'akismet%';

-- 7. Ajustes de lectura, permalinks, medios y SEO
DELETE FROM local.wp_options WHERE option_name IN ('blogname','blogdescription','permalink_structure','category_base','tag_base',
  'posts_per_page','posts_per_rss','rss_use_excerpt','date_format','time_format','timezone_string','gmt_offset','start_of_week','WPLANG',
  'show_on_front','page_on_front','page_for_posts','sticky_posts','default_category','blog_public',
  'default_comment_status','default_ping_status','comment_registration','require_name_email','comment_moderation','comment_previously_approved',
  'thread_comments','thread_comments_depth','page_comments','comments_per_page','default_comments_page','comment_order','show_avatars','avatar_default',
  'close_comments_for_old_posts','close_comments_days_old','uploads_use_yearmonth_folders',
  'thumbnail_size_w','thumbnail_size_h','thumbnail_crop','medium_size_w','medium_size_h','medium_large_size_w','medium_large_size_h','large_size_w','large_size_h',
  'wpseo','wpseo_titles','wpseo_social','wpseo_taxonomy_meta','redirection_options');
INSERT INTO local.wp_options (option_name,option_value,autoload) SELECT option_name,option_value,autoload FROM oldsrc.wp_options
  WHERE option_name IN ('blogname','blogdescription','permalink_structure','category_base','tag_base',
  'posts_per_page','posts_per_rss','rss_use_excerpt','date_format','time_format','timezone_string','gmt_offset','start_of_week','WPLANG',
  'show_on_front','page_on_front','page_for_posts','sticky_posts','default_category','blog_public',
  'default_comment_status','default_ping_status','comment_registration','require_name_email','comment_moderation','comment_previously_approved',
  'thread_comments','thread_comments_depth','page_comments','comments_per_page','default_comments_page','comment_order','show_avatars','avatar_default',
  'close_comments_for_old_posts','close_comments_days_old','uploads_use_yearmonth_folders',
  'thumbnail_size_w','thumbnail_size_h','thumbnail_crop','medium_size_w','medium_size_h','medium_large_size_w','medium_large_size_h','large_size_w','large_size_h',
  'wpseo','wpseo_titles','wpseo_social','wpseo_taxonomy_meta','redirection_options');

-- 8. Redirecciones (ejecutar tras instalar el plugin Redirection: wp redirection database install)
--    ¡Después repetir el search-replace de dominio! Las redirecciones guardan URLs absolutas.
-- DELETE FROM local.wp_redirection_items; DELETE FROM local.wp_redirection_groups;
-- INSERT INTO local.wp_redirection_groups SELECT * FROM oldsrc.wp_redirection_groups;
-- INSERT INTO local.wp_redirection_items  SELECT * FROM oldsrc.wp_redirection_items;
