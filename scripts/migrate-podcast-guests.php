<?php
/** Explicit WP-CLI migration. Plan first; apply consumes the exact reviewed JSON. */
if (!defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('WP-CLI only.'); }
use SMP\Podcast\Settings\PodcastSettings;
use SMP\VerifiedProfiles\ProfileTypes\PodcastSection;
function podcast_guest_plan(): array {
    $profiles = get_posts(['post_type'=>'profile','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']);
    $guests = array_map(static fn($p)=>['id'=>$p->ID,'slug'=>$p->post_name,'name'=>$p->post_title,'old_url'=>get_permalink($p),'role'=>$p->ID===18191?'host':'guest'], $profiles);
    $episodes=[];
    foreach(get_posts(PodcastSettings::scoped_query_args(['post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC'])) as $p) {
        $rows=[]; for($i=0;$i<(int)get_post_meta($p->ID,'profiles',true);$i++) { $rows[]=absint(get_post_meta($p->ID,"profiles_{$i}_profile",true)); }
        $legacy=[]; for($i=0;$i<(int)get_post_meta($p->ID,'guests',true);$i++) {
            $user_id=absint(get_post_meta($p->ID,"guests_{$i}_guest",true)); $u=get_user_by('id',$user_id);
            $matches=array_values(array_filter($guests, static fn($g)=>$u && sanitize_title($g['name'])===sanitize_title($u->display_name)));
            if(count($matches)!==1 || !in_array($matches[0]['id'],$rows,true)) { throw new RuntimeException('Legacy guest has no exact existing profile match: episode '.$p->ID.', user '.$user_id); }
            $legacy[]=['index'=>$i,'user_id'=>$user_id,'profile_id'=>$matches[0]['id']];
        }
        $episodes[]=['id'=>$p->ID,'title'=>$p->post_title,'profiles'=>$rows,'hosts'=>get_post_meta($p->ID,'hosts',true),'legacy'=>$legacy];
    }
    return ['site'=>home_url(),'guests'=>$guests,'episodes'=>$episodes,'content_types'=>get_option('smp_vp_content_type_settings')];
}
$plan=podcast_guest_plan();
if(($args[0]??'plan')==='plan') { echo wp_json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); return; }
$reviewed=json_decode(file_get_contents($args[1]??''),true);
if($reviewed!==$plan) { throw new RuntimeException('Live state differs from reviewed migration plan.'); }
if(count($plan['guests'])!==160 || $plan['site']!=='https://podcast.michaelperes.com') { throw new RuntimeException('Unexpected migration scope.'); }
PodcastSection::set_enabled(true);
foreach($plan['guests'] as $guest) {
    update_post_meta($guest['id'],'_smp_vp_guest_migrated',1);
    update_post_meta($guest['id'],'podcast_role',$guest['role']);
    update_post_meta($guest['id'],'_podcast_role','field_smp_vp_podcast-guest_podcast_role');
    $result=wp_update_post(['ID'=>$guest['id'],'post_type'=>'podcast-guest'],true);
    if(is_wp_error($result)) { throw new RuntimeException($result->get_error_message()); }
}
foreach($plan['episodes'] as $episode) {
    foreach($episode['legacy'] as $legacy) {
        $i=$legacy['index'];
        delete_post_meta($episode['id'],"guests_{$i}_guest"); delete_post_meta($episode['id'],"_guests_{$i}_guest");
    }
    if($episode['legacy']) { delete_post_meta($episode['id'],'guests'); delete_post_meta($episode['id'],'_guests'); }
}
// The user identified every profile on this site as podcast content.
$registry=\SMP\VerifiedProfiles\ContentTypes\VerifiedProfileStructures::content_types();
$registry->store()->save($registry->definition('profile'),['enabled'=>false]);
flush_rewrite_rules(false);
echo 'Moved '.count($plan['guests']).' guests preserving IDs; verified and retired '.count(array_filter($plan['episodes'],static fn($e)=>!empty($e['legacy']))).' legacy guest fields.'.PHP_EOL;
