<?php
/**
 * Default content: the live site's current copy (HANDOFF §8).
 * Used as the fallback for every setting and as the seed on theme activation.
 *
 * @package jameskellie
 */

defined( 'ABSPATH' ) || exit;

return [
	'general'     => [
		'phone_display'   => '0411 644 713',
		'phone_tel'       => '0411644713',
		'emails'          => '',
		'social'          => [
			// TODO: get the real URLs from James (HANDOFF §12).
			[
				'network' => 'facebook',
				'url'     => '',
			],
			[
				'network' => 'x',
				'url'     => '',
			],
			[
				'network' => 'instagram',
				'url'     => '',
			],
		],
		'calendly_url'    => 'https://calendly.com/clubweb/15min',
		'cta'             => [
			'label' => 'Book a call',
			'url'   => '/appointment/',
		],
		'logo'            => 0,
		'sticky_header'   => true,
		'back_to_top'     => true,
		'click_hints'     => true,
		'copyright'       => '© {year} JamesKellie.com.au',
		'seo_description' => 'James Kellie — SEO, Google Ads and web design for Australian businesses since 2003.',
		'share_image'     => 0,
	],
	'layout'      => [
		'order' => [ 'hero', 'stats', 'services', 'experience', 'cta', 'skills', 'contact' ],
	],
	'hero'        => [
		'enabled'     => true,
		'anchor'      => 'about',
		'eyebrow'     => 'Digital marketing solutions since 2003',
		'title'       => 'SEO, Google Ads & Web Design.',
		'intro'       => 'Since 2003 I have specialised in SEO services building up a solid range of business clients throughout Australia. Due to demand I started designing websites too (over 550 sites so far) and then in 2005 I was one of the early pioneers to sell Google Adwords to Australian business.',
		'primary'     => [
			'label' => 'Get In Touch!',
			'url'   => '#contact',
		],
		'show_phone'  => true,
		'phone_label' => 'PH',
		'portrait'    => 0,
		'greyscale'   => true,
	],
	'stats'       => [
		'enabled' => true,
		'anchor'  => '',
		'label'   => 'At a glance',
		'items'   => [
			[
				'number' => '2003',
				'label'  => 'Specialising in SEO',
			],
			[
				'number' => '550+',
				'label'  => 'Websites designed',
			],
			[
				'number' => '2005',
				'label'  => 'Early pioneer selling Google Adwords',
			],
			[
				'number' => '30+',
				'label'  => 'Years marketing & managing business',
			],
		],
	],
	'services'    => [
		'enabled'    => true,
		'anchor'     => 'services',
		'eyebrow'    => 'My core strengths',
		'title'      => 'Services',
		'side'       => 'I have continued to partner with Google and am very competent at Analytics, Tag Manager, Search Console and Google Business Profile (Maps).',
		'closing'    => 'It’s true that you get better with experience and having been in the digital marketplace for over 20 years full-time, my web design, SEO and Google Ads services are at a higher level.',
		'book_label' => 'Book a call about',
		'hint_label' => 'Click a service to explore',
		'items'      => [
			[
				'title'      => 'SEO',
				'slug'       => 'seo',
				'stat'       => '2003',
				'stat_label' => 'Specialising since',
				'body'       => 'Since 2003 I have specialised in SEO services, building up a solid range of business clients throughout Australia.',
				'tools'      => [ 'Search Engine Optimisation', 'Google Search Console', 'Google Business Profile (Maps)', 'Google Analytics' ],
			],
			[
				'title'      => 'Google Ads',
				'slug'       => 'google-ads',
				'stat'       => '2005',
				'stat_label' => 'Early Adwords pioneer',
				'body'       => 'In 2005 I was one of the early pioneers to sell Google Adwords to Australian business. I build and manage profitable Google Ad campaigns.',
				'tools'      => [ 'Google Ads', 'Facebook Ads', 'Bing Adcentre', 'Remarketing & Retargeting', 'Conversion Rate Tracking' ],
			],
			[
				'title'      => 'Web Design',
				'slug'       => 'web-design',
				'stat'       => '550+',
				'stat_label' => 'Sites designed',
				'body'       => 'Due to demand I started designing websites too — over 550 sites so far, built on advanced WordPress expertise.',
				'tools'      => [ 'Web Development', 'WordPress Expertise (Advanced)', 'Google Tag Manager', 'YouTube Video & Ads' ],
			],
		],
	],
	'experience'  => [
		'enabled'    => true,
		'anchor'     => 'experience',
		'eyebrow'    => 'My experience',
		'title'      => 'Dedicated to marketing and managing business for over 30 years.',
		'intro'      => "Since 1991 I have worked solely in the B2B market, providing business marketing services to a range of companies in various industries throughout Australia. I’ve lived in Adelaide, Melbourne and Sydney and setup offices and managed business in these cities. I still have clients in all states of Australia and can provide expert marketing advice primarily in the online space.\n\nBefore working in the B2B sector, I gained extensive experience during the 1980s in the retail and hospitality industries.",
		'open_first' => true,
		'hint_label' => 'Click a role to see more',
		'roles'      => [
			[
				'years'       => '2018 — Present',
				'company'     => 'Club Web Pty Ltd',
				'role'        => 'Founder',
				'description' => 'Expert digital marketing services including SEO, Google Ads, Social Media and Web Design.',
			],
			[
				'years'       => '2015 — 2018',
				'company'     => 'JamesKellie.com',
				'role'        => 'Digital Marketer',
				'description' => 'Web design and digital marketing consulting.',
			],
			[
				'years'       => '2003 — 2015',
				'company'     => 'SEO-Xpress.com',
				'role'        => 'CEO',
				'description' => 'Leading provider of search engine optimisation (SEO) and Google Adwords services in all states of Australia.',
			],
			[
				'years'       => '1991 — 2002',
				'company'     => 'Tradebart Ltd',
				'role'        => 'Executive Director',
				'description' => 'Public company with 25 offices in Australia and New Zealand with over 6000 business members.',
			],
		],
	],
	'cta'         => [
		'enabled' => true,
		'anchor'  => '',
		'strip'   => [
			[
				'meta'   => '01 · Since 2003',
				'name'   => 'SEO',
				'target' => 'seo',
			],
			[
				'meta'   => '02 · Since 2005',
				'name'   => 'Google Ads',
				'target' => 'google-ads',
			],
			[
				'meta'   => '03 · 550+ sites',
				'name'   => 'Web Design',
				'target' => 'web-design',
			],
		],
		'lead'    => 'I build and manage profitable SEO and Google Ad campaigns.',
		'heading' => 'Can I Help You?',
		'button'  => [
			'label' => 'Let’s Start a Conversation',
			'url'   => '#contact',
		],
	],
	'skills'      => [
		'enabled'          => true,
		'anchor'           => 'skills',
		'eyebrow'          => 'My skills',
		'title'            => 'Marketing, development & consulting.',
		'side'             => 'I build and manage profitable SEO and Google Ad campaigns for small and medium size B2B and B2C companies since 2003.',
		'hint_label'       => 'Click a tab to explore',
		'tabs'             => [
			[
				'label' => 'Marketing Strategy',
				'items' => [ 'Search Engine Optimisation – SEO', 'Google Ads', 'Facebook Ads', 'Bing Adcentre', 'Remarketing & Retargeting' ],
			],
			[
				'label' => 'Business Development',
				'items' => [ 'Web Development (over 550 sites)', 'WordPress Expertise (Advanced)', 'Social Media (Facebook, X, Instagram, Pinterest, TikTok)', 'YouTube Video & Ads' ],
			],
			[
				'label' => 'Business Consulting',
				'items' => [ 'Google Analytics', 'Google Tag Manager', 'Google Business Profile (Maps)', 'Google Search Console', 'Conversion Rate Tracking' ],
			],
		],
		'industries_label' => 'My industry experience includes',
		'industries'       => [ 'Travel & hospitality', 'Healthcare', 'Business services', 'Manufacturing', 'Sporting goods', 'Apparel', 'Retail', 'Local business' ],
	],
	'contact'     => [
		'enabled'       => true,
		'anchor'        => 'contact',
		'eyebrow'       => 'Get in touch',
		'title'         => 'Ready to Chat?',
		'phone_label'   => 'Phone',
		'book_label'    => 'Book an appointment',
		'form_heading'  => 'Send a message',
		'required_note' => 'Fields marked * are required.',
		'topic_legend'  => 'What can I help with?',
		'topics'        => [ 'SEO', 'Google Ads', 'Web Design', 'Something else' ],
		'submit'        => 'Send Message',
		'call_prefix'   => 'Or call',
		'success'       => 'Thanks — your message has been sent. I’ll be in touch shortly.',
		'error'         => 'Sorry, something went wrong. Please call 0411 644 713 or try again.',
	],
	'performance' => [
		'minify_html'       => true,
		'disable_emojis'    => true,
		'disable_oembed'    => true,
		'remove_block_css'  => true,
		'remove_jquery'     => true,
		'disable_dashicons' => true,
		'clean_head'        => true,
		'disable_xmlrpc'    => true,
		'feed_links'        => true,
		'heartbeat_front'   => false,
		'heartbeat_admin'   => 60,
		'fonts_preload'     => true,
		'preconnect'        => [ 'https://calendly.com', 'https://challenges.cloudflare.com' ],
		'spa'               => true,
		'prefetch'          => true,
		'lazy_calendly'     => true,
		'delay_code'        => false,
	],
	// Existing Turnstile keys are moved here from jk_general by Admin\Seeder::migrate_captcha().
	'captcha'     => [
		'provider'          => 'none',
		'turnstile_site'    => '',
		'turnstile_secret'  => '',
		'recaptcha_version' => 'v3',
		'recaptcha_site'    => '',
		'recaptcha_secret'  => '',
	],
	'mail'        => [
		'mailer'      => 'phpmail',
		'from_email'  => '',
		'from_name'   => '',
		'smtp_host'   => '',
		'smtp_port'   => 587,
		'smtp_secure' => 'tls',
		'smtp_user'   => '',
		'smtp_pass'   => '',
	],
	'code'        => [
		'head'      => '',
		'body_open' => '',
		'footer'    => '',
	],
	'appointment' => [
		'eyebrow'         => 'Book an appointment',
		'title'           => '15 minutes to talk about your business.',
		'intro'           => 'We’ll go through your current situation, challenges, strategies and goals.',
		'list_title'      => 'What we’ll cover',
		'list'            => [ 'Your current situation', 'Challenges', 'Strategies', 'Your goals' ],
		'card_image'      => 0,
		'card_name'       => 'James Kellie',
		'card_tagline'    => 'SEO specialist since 2003',
		'call_label'      => 'Rather just call?',
		'calendly_url'    => '',
		'seo_description' => 'Book a free 15-minute call with James Kellie to talk about your business, challenges, strategies and goals.',
		'share_image'     => 0,
	],
];
