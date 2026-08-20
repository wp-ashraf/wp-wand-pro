=== WP Wand Pro ===
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 2.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

The paid add-on for WP Wand. Unlocks custom prompts, SEO and WooCommerce writing, history, and higher Bulk and Automation limits.

== Description ==

WP Wand Pro is an add-on. It needs WP Wand (the free plugin) 2.0.0 or newer installed and active — Pro adds screens and features to it rather than replacing it.

Activate your licence key on WP Wand → Activate Pro, and the Pro features switch on straight away. No re-install, no separate settings page.

== What Pro adds ==

- Create your own prompt templates and keep them alongside the 44 built-in ones
- Custom AI Character, so every generation writes in one consistent voice
- Business details and target-customer profile, fed into every template
- Rank Math and Yoast SEO panels — write meta titles, descriptions and keywords from the post editor (beta)
- WooCommerce panel in the product editor for titles, descriptions and short descriptions
- Generation history: search back through every past result
- Larger AI images: 512x512 and 1024x1024
- Higher Bulk Posts and Automation limits than the free monthly allowance
- White label the plugin with your own name, logo and colours (Agency plan)
- Licence activation and automatic plugin updates while the licence is active

Bulk Posts and Automation themselves ship in the free plugin. Pro doesn't add them — it raises how much you can run per month.

== Installation ==

1. Upload the `wp-wand-pro` folder to the `/wp-content/plugins/` directory.
2. Make sure WP Wand (free) 2.0.0 or newer is installed and active first.
3. Activate WP Wand Pro through the 'Plugins' menu in WordPress.
4. Go to WP Wand → Activate Pro and enter your licence key.

== Frequently Asked Questions ==

= Where do I enter my licence key? =

WP Wand → Activate Pro in the WordPress admin menu. Paste the key from your purchase email and click activate. The page shows which plan the key is on once it's through.

= Which plans are there? =

Solo, Growth and Agency. Each one sets how much Bulk and Automation you can run per month — Growth gets triple Solo's allowance, Agency has no monthly cap. White labelling is Agency only.

= What happens when I deactivate a licence? =

The site drops back to the free feature set and the free monthly allowance, and the activation is released so you can use the key on another site. Your settings, your generated content and your custom templates stay in the database.

= Do I still need an OpenAI key? =

Yes. WP Wand always uses your own provider key — OpenAI, Anthropic (Claude), OpenRouter or Deepseek — and you set it on the free plugin's settings page. The licence key unlocks the Pro features; it doesn't pay for generation.

= Pro is active but nothing changed. What's wrong? =

Almost always the free plugin is still on an older version. Pro 2.0.0 needs WP Wand 2.0.0 or newer, and it puts a notice in your admin when that isn't the case. Update WP Wand, then reload.

= Do I get updates? =

Yes, while the licence is active. They show up on the Plugins screen like any other plugin update.

== Changelog ==

= 2.0.0 =
* New: Rebuilt on the React + REST architecture alongside WP Wand 2.0.0.
* New: Custom Prompts, Custom AI Character, SEO (Rank Math and Yoast) and WooCommerce writing,
  and generation History, all as modern React screens.
* New: Licence activation, plugin updates and tier limits handled in the new architecture.
* Changed: Bulk Posts and Automation moved into the free plugin. Pro now raises their monthly
  limits instead of providing the features.
* Changed: The entire legacy codebase was removed; your licence and data migrate automatically
  with no re-activation required. Requires WP Wand (free) 2.0.0+.

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major rebuild. Update WP Wand (free) to 2.0.0+ first, or Pro's features won't load. Your licence
and data carry over on their own.
