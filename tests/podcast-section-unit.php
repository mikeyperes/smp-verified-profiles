<?php
require __DIR__ . '/bootstrap-runtime-unit.php';
function update_option($key,$value,$autoload=null) { $GLOBALS['smp_vp_test_options'][$key]=$value; return true; }
use SMP\VerifiedProfiles\ProfileTypes\ProfileTypes;
use SMP\VerifiedProfiles\ProfileTypes\PodcastSection;
use SMP\VerifiedProfiles\ContentTypes\VerifiedProfileStructures;
function expect_podcast($ok,$label) { if (!$ok) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
$def=VerifiedProfileStructures::content_types()->definition('podcast-guest');
expect_podcast($def['post_type']['rewrite_slug']==='guest', 'Separate guest rewrite');
expect_podcast(!$def['enabled_default'], 'Guest type off by default');
expect_podcast(ProfileTypes::guest_type()==='', 'Disabled guest selection');
PodcastSection::set_enabled(true);
expect_podcast(ProfileTypes::guest_type()==='podcast-guest', 'Enabled guest type uses registry storage');
expect_podcast(!empty(get_option('smp_vp_content_type_settings')['podcast-guest']['field_groups']['podcaster-fields']), 'Existing Podcaster field group enabled');
PodcastSection::set_enabled(false);
expect_podcast(ProfileTypes::guest_type()==='', 'Turning off hides guest selection');
expect_podcast(in_array('podcast-guest',ProfileTypes::keys(),true), 'Turning off preserves configured type');
ob_start(); PodcastSection::render(false,'podcast'); $html=ob_get_clean();
expect_podcast($html==='<div id="smp-vp-podcast-settings"></div>', 'Placeholder only, no screen design');
expect_podcast(isset($GLOBALS['smp_vp_test_hooks']['wp_ajax_smp_verified_profiles_save_podcast']), 'Authenticated save endpoint registered');

function current_user_can($cap) { return $GLOBALS['test_podcast_admin'] ?? false; }
function check_ajax_referer($action,$field) { if (($_POST[$field]??'')!=='valid') { throw new RuntimeException('invalid nonce'); } }
function wp_send_json_error($data,$status=200) { throw new RuntimeException('error:'.$status); }
function wp_send_json_success($data) { throw new RuntimeException('success:'.json_encode($data)); }
foreach ([[false,'valid','1','error:403'],[true,'bad','1','invalid nonce'],[true,'valid','invalid','error:400'],[true,'valid','1','success:'],[true,'valid','0','success:']] as [$admin,$nonce,$enabled,$expected]) {
    $GLOBALS['test_podcast_admin']=$admin; $_POST=['nonce'=>$nonce,'enabled'=>$enabled];
    try { PodcastSection::save(); throw new RuntimeException('No response'); } catch (RuntimeException $e) { expect_podcast(strpos($e->getMessage(),$expected)===0,'Save boundary '.$expected.' enabled='.$enabled); }
}
