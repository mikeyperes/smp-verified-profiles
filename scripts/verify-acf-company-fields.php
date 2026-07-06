<?php

namespace smp_verified_profiles;

defined( 'ABSPATH' ) || exit;

$group = acf_get_local_field_group( 'group_67e39e4171b16' );
$fields = acf_get_local_fields( 'group_67e39e4171b16' );

$field_order = array_values( array_map(
	static function ( $field ) {
		return $field['name'] ?? '';
	},
	is_array( $fields ) ? $fields : []
) );

$field_by_name = [];
foreach ( is_array( $fields ) ? $fields : [] as $field ) {
	if ( isset( $field['name'] ) ) {
		$field_by_name[ $field['name'] ] = $field;
	}
}

$company = acf_get_field( 'field_smp_vp_company_details' );
if ( ! is_array( $company ) ) {
	$company = $field_by_name['company_details'] ?? [];
}
$company_widths = [];
foreach ( $company['sub_fields'] ?? [] as $sub_field ) {
	$company_widths[ $sub_field['name'] ?? '' ] = $sub_field['wrapper']['width'] ?? null;
}

$education = $field_by_name['personal_education'] ?? [];

$shortcodes = [
	'title' => do_shortcode( '[verified_profile field="title" post_id="560671"]' ),
	'profile_type' => do_shortcode( '[verified_profile field="profile_type" post_id="560671"]' ),
	'founder_link' => do_shortcode( '[verified_profile field="company_details.founder_profile" output="link" post_id="560671"]' ),
	'founder_title' => do_shortcode( '[verified_profile field="company_details.founder_profile" post_id="560671"]' ),
];

echo wp_json_encode(
	[
		'group_loaded' => is_array( $group ),
		'order_first_four' => array_slice( $field_order, 0, 4 ),
		'profile_type_before_featured' => array_search( 'profile_type', $field_order, true ) < array_search( 'featured', $field_order, true ),
		'company_widths' => $company_widths,
		'company_all_100' => ! in_array( false, array_map( static fn( $width ) => '100' === (string) $width, $company_widths ), true ),
		'education_conditional_logic' => $education['conditional_logic'] ?? null,
		'education_person_only' => isset( $education['conditional_logic'][0][0]['field'], $education['conditional_logic'][0][0]['value'] )
			&& 'field_smp_vp_profile_type' === $education['conditional_logic'][0][0]['field']
			&& 'person' === $education['conditional_logic'][0][0]['value'],
		'instructions' => [
			'profile_type' => $field_by_name['profile_type']['instructions'] ?? '',
			'company_details' => $company['instructions'] ?? '',
			'personal_education' => $education['instructions'] ?? '',
		],
		'shortcodes' => $shortcodes,
	],
	JSON_PRETTY_PRINT
);
