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
		// Display groups for the settings screen (React admin). Fields name theirs with 'group'.
		'groups' => [
			'header'  => [
				'icon'  => 'dashicons-menu-alt3',
				'title' => 'Header',
				'help'  => 'The logo, the black button and how the header behaves.',
			],
			'contact' => [
				'icon'  => 'dashicons-phone',
				'title' => 'Contact details',
				'help'  => 'Used by the Hero, Contact and Book Appointment sections and the enquiry emails.',
			],
			'social'  => [
				'icon'  => 'dashicons-share',
				'title' => 'Social links',
				'help'  => 'Shown in the footer.',
			],
			'footer'  => [
				'icon'  => 'dashicons-table-row-after',
				'title' => 'Footer',
				'help'  => 'The bottom of every page.',
			],
			'seo'     => [
				'icon'  => 'dashicons-search',
				'title' => 'SEO & sharing',
				'help'  => 'How the Home page appears in Google and when shared.',
			],
			'misc'    => [
				'icon'  => 'dashicons-admin-generic',
				'title' => 'Misc',
				'help'  => 'Small extras.',
			],
		],
		'fields' => [
			[
				'key'   => 'logo',
				'type'  => 'image',
				'label' => 'Logo image (optional)',
				'help'  => 'Replaces the coded JK lockup when set.',
				'group' => 'header',
			],
			[
				'key'   => 'cta',
				'type'  => 'link',
				'label' => 'Book-a-call button',
				'help'  => 'Clear the label or link to hide the button.',
				'group' => 'header',
			],
			[
				'key'   => 'sticky_header',
				'type'  => 'toggle',
				'label' => 'Sticky header',
				'help'  => 'Hides on scroll down, comes back on scroll up.',
				'group' => 'header',
			],
			[
				'key'   => 'phone_display',
				'type'  => 'text',
				'label' => 'Phone (display)',
				'group' => 'contact',
			],
			[
				'key'   => 'phone_tel',
				'type'  => 'tel',
				'label' => 'Phone (tel link, digits only)',
				'group' => 'contact',
			],
			[
				'key'   => 'emails',
				'type'  => 'email',
				'label' => 'Enquiry email recipient(s)',
				'help'  => 'Comma-separated. Empty uses the site admin email.',
				'group' => 'contact',
			],
			[
				'key'   => 'calendly_url',
				'type'  => 'url',
				'label' => 'Calendly URL',
				'help'  => 'The booking calendar on the Book Appointment page.',
				'group' => 'contact',
			],
			[
				'key'       => 'social',
				'type'      => 'repeater',
				'label'     => 'Social links',
				'max'       => 8,
				'row_title' => 'network',
				'group'     => 'social',
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
				'key'   => 'copyright',
				'type'  => 'text',
				'label' => 'Footer copyright',
				'help'  => '{year} is replaced with the current year.',
				'group' => 'footer',
			],
			[
				'key'   => 'back_to_top',
				'type'  => 'toggle',
				'label' => 'Show back-to-top button',
				'group' => 'footer',
			],
			[
				'key'   => 'seo_description',
				'type'  => 'textarea',
				'label' => 'Home SEO description',
				'help'  => 'Only used when no SEO plugin (Yoast, Rank Math) is active.',
				'group' => 'seo',
			],
			[
				'key'   => 'share_image',
				'type'  => 'image',
				'label' => 'Home share image',
				'help'  => 'Shown when the site is shared on social media. 1200 × 630 works best.',
				'group' => 'seo',
			],
			[
				'key'   => 'click_hints',
				'type'  => 'toggle',
				'label' => 'Show click hints',
				'help'  => 'The animated pointer that shows visitors what to click (Services, Experience, Skills, CTA).',
				'group' => 'misc',
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
	'captcha'     => [
		'title'  => 'Captcha',
		'fields' => [
			[
				'key'     => 'provider',
				'type'    => 'select',
				'label'   => 'Provider',
				'help'    => 'Protects the contact form. The honeypot, timing check and rate limit always run as well.',
				'choices' => [
					'none'      => 'Off (honeypot only)',
					'turnstile' => 'Cloudflare Turnstile',
					'recaptcha' => 'Google reCAPTCHA',
				],
			],
			[
				'key'     => 'turnstile_site',
				'type'    => 'text',
				'label'   => 'Turnstile site key',
				'help'    => 'From dash.cloudflare.com → Turnstile.',
				'show_if' => [ 'provider' => 'turnstile' ],
			],
			[
				'key'     => 'turnstile_secret',
				'type'    => 'password',
				'label'   => 'Turnstile secret key',
				'show_if' => [ 'provider' => 'turnstile' ],
			],
			[
				'key'     => 'recaptcha_version',
				'type'    => 'select',
				'label'   => 'reCAPTCHA version',
				'help'    => 'Must match the key type you created at google.com/recaptcha/admin.',
				'choices' => [
					'v3' => 'Invisible (v3)',
					'v2' => 'Checkbox (v2)',
				],
				'show_if' => [ 'provider' => 'recaptcha' ],
			],
			[
				'key'     => 'recaptcha_site',
				'type'    => 'text',
				'label'   => 'reCAPTCHA site key',
				'show_if' => [ 'provider' => 'recaptcha' ],
			],
			[
				'key'     => 'recaptcha_secret',
				'type'    => 'password',
				'label'   => 'reCAPTCHA secret key',
				'help'    => 'v3 also needs a score of at least 0.5.',
				'show_if' => [ 'provider' => 'recaptcha' ],
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
