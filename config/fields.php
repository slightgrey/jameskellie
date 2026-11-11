<?php
/**
 * Field schema per settings section (HANDOFF §5.2).
 *
 * One definition drives the admin UI (FieldRenderer), sanitising (Sanitizer)
 * and the default merge (Settings). Sections with 'storage' => 'meta' live in
 * post meta instead of an option.
 *
 * @package jameskellie
 */

defined( 'ABSPATH' ) || exit;

$jk_show   = [
	'key'   => 'enabled',
	'type'  => 'toggle',
	'label' => 'Show section',
];
$jk_anchor = [
	'key'   => 'anchor',
	'type'  => 'slug',
	'label' => 'Anchor ID',
	'help'  => 'Used for in-page links, e.g. /#services. Leave empty for none.',
];

return [
	'general'     => [
		'title'  => 'General',
		'fields' => [
			[
				'key'   => 'phone_display',
				'type'  => 'text',
				'label' => 'Phone (display)',
			],
			[
				'key'   => 'phone_tel',
				'type'  => 'tel',
				'label' => 'Phone (tel link, digits only)',
			],
			[
				'key'   => 'emails',
				'type'  => 'email',
				'label' => 'Enquiry email recipient(s)',
				'help'  => 'Comma-separated. Empty uses the site admin email.',
			],
			[
				'key'       => 'social',
				'type'      => 'repeater',
				'label'     => 'Social links',
				'max'       => 8,
				'row_title' => 'network',
				'fields'    => [
					[
						'key'     => 'network',
						'type'    => 'select',
						'label'   => 'Network',
						'choices' => [
							'facebook'  => 'Facebook',
							'x'         => 'X',
							'instagram' => 'Instagram',
							'linkedin'  => 'LinkedIn',
							'youtube'   => 'YouTube',
							'tiktok'    => 'TikTok',
							'pinterest' => 'Pinterest',
						],
					],
					[
						'key'   => 'url',
						'type'  => 'url',
						'label' => 'URL',
					],
				],
			],
			[
				'key'   => 'calendly_url',
				'type'  => 'url',
				'label' => 'Calendly URL',
			],
			[
				'key'   => 'cta',
				'type'  => 'link',
				'label' => 'Book-a-call button (header)',
			],
			[
				'key'   => 'logo',
				'type'  => 'image',
				'label' => 'Logo image (optional)',
				'help'  => 'Replaces the coded JK lockup when set.',
			],
			[
				'key'   => 'sticky_header',
				'type'  => 'toggle',
				'label' => 'Sticky header',
			],
			[
				'key'   => 'back_to_top',
				'type'  => 'toggle',
				'label' => 'Show back-to-top button',
			],
			[
				'key'   => 'click_hints',
				'type'  => 'toggle',
				'label' => 'Show click hints',
			],
			[
				'key'   => 'turnstile_site',
				'type'  => 'text',
				'label' => 'Cloudflare Turnstile site key',
			],
			[
				'key'   => 'turnstile_secret',
				'type'  => 'text',
				'label' => 'Cloudflare Turnstile secret key',
				'help'  => 'Leave both keys empty to run with the honeypot only.',
			],
			[
				'key'   => 'copyright',
				'type'  => 'text',
				'label' => 'Footer copyright',
				'help'  => '{year} is replaced with the current year.',
			],
			[
				'key'   => 'seo_description',
				'type'  => 'textarea',
				'label' => 'Home SEO description',
				'help'  => 'Only used when no SEO plugin (Yoast, Rank Math) is active.',
			],
			[
				'key'   => 'share_image',
				'type'  => 'image',
				'label' => 'Home share image',
			],
		],
	],
	'layout'      => [
		'title'  => 'Home layout',
		'fields' => [
			[
				'key'     => 'order',
				'type'    => 'order',
				'label'   => 'Section order',
				'choices' => [
					'hero'       => 'Hero',
					'stats'      => 'Stats',
					'services'   => 'Services',
					'experience' => 'Experience',
					'cta'        => 'CTA band',
					'skills'     => 'Skills',
					'contact'    => 'Contact',
				],
			],
		],
	],
	'hero'        => [
		'title'  => 'Hero',
		'fields' => [
			$jk_show,
			$jk_anchor,
			[
				'key'   => 'eyebrow',
				'type'  => 'text',
				'label' => 'Eyebrow',
			],
			[
				'key'   => 'title',
				'type'  => 'text',
				'label' => 'Title (H1)',
			],
			[
				'key'   => 'intro',
				'type'  => 'textarea',
				'label' => 'Intro',
			],
			[
				'key'   => 'primary',
				'type'  => 'link',
				'label' => 'Primary button',
			],
			[
				'key'   => 'show_phone',
				'type'  => 'toggle',
				'label' => 'Show phone button',
			],
			[
				'key'   => 'phone_label',
				'type'  => 'text',
				'label' => 'Phone button prefix',
			],
			[
				'key'   => 'portrait',
				'type'  => 'image',
				'label' => 'Portrait (4:5)',
			],
			[
				'key'   => 'greyscale',
				'type'  => 'toggle',
				'label' => 'Greyscale portrait',
			],
		],
	],
	'stats'       => [
		'title'  => 'Stats',
		'fields' => [
			$jk_show,
			$jk_anchor,
			[
				'key'   => 'label',
				'type'  => 'text',
				'label' => 'Accessible label',
			],
			[
				'key'       => 'items',
				'type'      => 'repeater',
				'label'     => 'Stats',
				'max'       => 4,
				'row_title' => 'number',
				'fields'    => [
					[
						'key'   => 'number',
						'type'  => 'text',
						'label' => 'Number',
					],
					[
						'key'   => 'label',
						'type'  => 'text',
						'label' => 'Label',
					],
				],
			],
		],
	],
	'services'    => [
		'title'  => 'Services',
		'fields' => [
			$jk_show,
			$jk_anchor,
			[
				'key'   => 'eyebrow',
				'type'  => 'text',
				'label' => 'Eyebrow',
			],
			[
				'key'   => 'title',
				'type'  => 'text',
				'label' => 'Heading',
			],
			[
				'key'   => 'side',
				'type'  => 'textarea',
				'label' => 'Side intro',
			],
			[
				'key'   => 'closing',
				'type'  => 'textarea',
				'label' => 'Closing paragraph',
			],
			[
				'key'   => 'book_label',
				'type'  => 'text',
				'label' => '"Book a call about…" label',
				'help'  => 'The service title and an arrow are appended.',
			],
			[
				'key'   => 'hint_label',
				'type'  => 'text',
				'label' => 'Click hint label',
			],
			[
				'key'       => 'items',
				'type'      => 'repeater',
				'label'     => 'Services',
				'max'       => 6,
				'row_title' => 'title',
				'fields'    => [
					[
						'key'   => 'title',
						'type'  => 'text',
						'label' => 'Title',
					],
					[
						'key'   => 'slug',
						'type'  => 'slug',
						'label' => 'Slug',
						'help'  => 'Deep link: /#services-{slug}',
					],
					[
						'key'   => 'stat',
						'type'  => 'text',
						'label' => 'Big stat',
					],
					[
						'key'   => 'stat_label',
						'type'  => 'text',
						'label' => 'Stat label',
					],
					[
						'key'   => 'body',
						'type'  => 'textarea',
						'label' => 'Body',
					],
					[
						'key'   => 'tools',
						'type'  => 'list',
						'label' => 'Tools (one per line)',
					],
				],
			],
		],
	],
	'experience'  => [
		'title'  => 'Experience',
		'fields' => [
			$jk_show,
			$jk_anchor,
			[
				'key'   => 'eyebrow',
				'type'  => 'text',
				'label' => 'Eyebrow',
			],
			[
				'key'   => 'title',
				'type'  => 'text',
				'label' => 'Heading',
			],
			[
				'key'   => 'intro',
				'type'  => 'editor',
				'label' => 'Intro',
			],
			[
				'key'   => 'open_first',
				'type'  => 'toggle',
				'label' => 'Open first role by default',
			],
			[
				'key'   => 'hint_label',
				'type'  => 'text',
				'label' => 'Click hint label',
			],
			[
				'key'       => 'roles',
				'type'      => 'repeater',
				'label'     => 'Roles',
				'max'       => 12,
				'row_title' => 'company',
				'fields'    => [
					[
						'key'   => 'years',
						'type'  => 'text',
						'label' => 'Years',
					],
					[
						'key'   => 'company',
						'type'  => 'text',
						'label' => 'Company',
					],
					[
						'key'   => 'role',
						'type'  => 'text',
						'label' => 'Role',
					],
					[
						'key'   => 'description',
						'type'  => 'textarea',
						'label' => 'Description',
					],
				],
			],
		],
	],
	'cta'         => [
		'title'  => 'CTA band',
		'fields' => [
			$jk_show,
			$jk_anchor,
			[
				'key'       => 'strip',
				'type'      => 'repeater',
				'label'     => 'Service strip tiles',
				'max'       => 4,
				'row_title' => 'name',
				'fields'    => [
					[
						'key'   => 'meta',
						'type'  => 'text',
						'label' => 'Meta line',
					],
					[
						'key'   => 'name',
						'type'  => 'text',
						'label' => 'Name',
					],
					[
						'key'   => 'target',
						'type'  => 'text',
						'label' => 'Target',
						'help'  => 'A service slug (selects that tab) or a URL.',
					],
				],
			],
			[
				'key'   => 'lead',
				'type'  => 'text',
				'label' => 'Lead-in line',
			],
			[
				'key'   => 'heading',
				'type'  => 'text',
				'label' => 'Heading',
			],
			[
				'key'   => 'button',
				'type'  => 'link',
				'label' => 'Round button',
			],
		],
	],
	'skills'      => [
		'title'  => 'Skills',
		'fields' => [
			$jk_show,
			$jk_anchor,
			[
				'key'   => 'eyebrow',
				'type'  => 'text',
				'label' => 'Eyebrow',
			],
			[
				'key'   => 'title',
				'type'  => 'text',
				'label' => 'Heading',
			],
			[
				'key'   => 'side',
				'type'  => 'textarea',
				'label' => 'Side intro',
			],
			[
				'key'   => 'hint_label',
				'type'  => 'text',
				'label' => 'Click hint label',
			],
			[
				'key'       => 'tabs',
				'type'      => 'repeater',
				'label'     => 'Tabs',
				'max'       => 6,
				'row_title' => 'label',
				'fields'    => [
					[
						'key'   => 'label',
						'type'  => 'text',
						'label' => 'Tab label',
					],
					[
						'key'   => 'items',
						'type'  => 'list',
						'label' => 'Skills (one per line)',
					],
				],
			],
			[
				'key'   => 'industries_label',
				'type'  => 'text',
				'label' => 'Industries label',
			],
			[
				'key'   => 'industries',
				'type'  => 'list',
				'label' => 'Industries (one per line)',
			],
		],
	],
	'contact'     => [
		'title'  => 'Contact',
		'fields' => [
			$jk_show,
			$jk_anchor,
			[
				'key'   => 'eyebrow',
				'type'  => 'text',
				'label' => 'Eyebrow',
			],
			[
				'key'   => 'title',
				'type'  => 'text',
				'label' => 'Heading',
			],
			[
				'key'   => 'phone_label',
				'type'  => 'text',
				'label' => 'Phone label',
			],
			[
				'key'   => 'book_label',
				'type'  => 'text',
				'label' => 'Book-appointment button label',
			],
			[
				'key'   => 'form_heading',
				'type'  => 'text',
				'label' => 'Form heading',
			],
			[
				'key'   => 'required_note',
				'type'  => 'text',
				'label' => 'Required-fields note',
			],
			[
				'key'   => 'topic_legend',
				'type'  => 'text',
				'label' => 'Topic legend',
			],
			[
				'key'   => 'topics',
				'type'  => 'list',
				'label' => 'Topics (one per line)',
			],
			[
				'key'   => 'submit',
				'type'  => 'text',
				'label' => 'Submit label',
			],
			[
				'key'   => 'call_prefix',
				'type'  => 'text',
				'label' => '"Or call" text',
			],
			[
				'key'   => 'success',
				'type'  => 'textarea',
				'label' => 'Success message',
			],
			[
				'key'   => 'error',
				'type'  => 'textarea',
				'label' => 'Error message',
			],
		],
	],
	'performance' => [
		'title'  => 'Performance',
		'fields' => [
			[
				'key'   => 'minify_html',
				'type'  => 'toggle',
				'label' => 'Minify HTML',
				'help'  => 'Removes comments and extra whitespace from front-end pages (View Source shows compact HTML). Turn off while debugging markup.',
			],
			[
				'key'   => 'disable_emojis',
				'type'  => 'toggle',
				'label' => 'Disable emojis',
			],
			[
				'key'   => 'disable_oembed',
				'type'  => 'toggle',
				'label' => 'Disable oEmbed / wp-embed',
			],
			[
				'key'   => 'remove_block_css',
				'type'  => 'toggle',
				'label' => 'Remove block library CSS',
			],
			[
				'key'   => 'remove_jquery',
				'type'  => 'toggle',
				'label' => 'Remove jQuery on the front end',
				'help'  => 'Skipped automatically when an enqueued script depends on jQuery.',
			],
			[
				'key'   => 'disable_dashicons',
				'type'  => 'toggle',
				'label' => 'Disable Dashicons for visitors',
			],
			[
				'key'   => 'clean_head',
				'type'  => 'toggle',
				'label' => 'Clean <head>',
				'help'  => 'Removes generator, RSD, WLW manifest, shortlink, REST link/header and adjacent post links.',
			],
			[
				'key'   => 'disable_xmlrpc',
				'type'  => 'toggle',
				'label' => 'Disable XML-RPC',
			],
			[
				'key'   => 'feed_links',
				'type'  => 'toggle',
				'label' => 'Keep RSS feed links',
			],
			[
				'key'   => 'heartbeat_front',
				'type'  => 'toggle',
				'label' => 'Heartbeat on the front end',
			],
			[
				'key'   => 'heartbeat_admin',
				'type'  => 'number',
				'label' => 'Heartbeat interval in admin (seconds)',
				'min'   => 15,
				'max'   => 120,
			],
			[
				'key'   => 'fonts_preload',
				'type'  => 'toggle',
				'label' => 'Preload critical fonts',
			],
			[
				'key'   => 'preconnect',
				'type'  => 'list',
				'label' => 'Preconnect origins (one per line)',
			],
			[
				'key'   => 'spa',
				'type'  => 'toggle',
				'label' => 'SPA navigation',
			],
			[
				'key'   => 'prefetch',
				'type'  => 'toggle',
				'label' => 'Link prefetch on hover',
			],
			[
				'key'   => 'lazy_calendly',
				'type'  => 'toggle',
				'label' => 'Lazy-load Calendly',
			],
			[
				'key'   => 'delay_code',
				'type'  => 'toggle',
				'label' => 'Delay Custom Code footer scripts until interaction',
				'help'  => 'Runs them on first scroll, click or keydown, or after 5 seconds.',
			],
		],
	],
	'code'        => [
		'title'  => 'Custom Code',
		'fields' => [
			[
				'key'   => 'head',
				'type'  => 'code',
				'label' => 'Head',
				'help'  => 'Printed inside <head>, e.g. Google Tag Manager.',
			],
			[
				'key'   => 'body_open',
				'type'  => 'code',
				'label' => 'After <body>',
			],
			[
				'key'   => 'footer',
				'type'  => 'code',
				'label' => 'Footer',
			],
		],
	],
	'appointment' => [
		'title'   => 'Book Appointment',
		'storage' => 'meta',
		'fields'  => [
			[
				'key'   => 'eyebrow',
				'type'  => 'text',
				'label' => 'Eyebrow',
			],
			[
				'key'   => 'title',
				'type'  => 'text',
				'label' => 'Title (H1)',
			],
			[
				'key'   => 'intro',
				'type'  => 'textarea',
				'label' => 'Intro',
			],
			[
				'key'   => 'list_title',
				'type'  => 'text',
				'label' => 'Sidebar list title',
			],
			[
				'key'   => 'list',
				'type'  => 'list',
				'label' => 'Sidebar items (one per line)',
			],
			[
				'key'   => 'card_image',
				'type'  => 'image',
				'label' => 'James card photo',
			],
			[
				'key'   => 'card_name',
				'type'  => 'text',
				'label' => 'James card name',
			],
			[
				'key'   => 'card_tagline',
				'type'  => 'text',
				'label' => 'James card tagline',
			],
			[
				'key'   => 'call_label',
				'type'  => 'text',
				'label' => 'Call label',
			],
			[
				'key'   => 'calendly_url',
				'type'  => 'url',
				'label' => 'Calendly URL override',
				'help'  => 'Optional. Falls back to General → Calendly URL.',
			],
			[
				'key'   => 'seo_description',
				'type'  => 'textarea',
				'label' => 'SEO description',
				'help'  => 'Only used when no SEO plugin is active.',
			],
			[
				'key'   => 'share_image',
				'type'  => 'image',
				'label' => 'Share image',
			],
		],
	],
];
