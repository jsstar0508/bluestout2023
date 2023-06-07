<?php
/**
 *
 * Plugin Options Handler
 *
 * @version 1.0.0
 * @copyright 2019 WebFactory Ltd
 * @package EPS Boilerplate
 *
 *
 */

// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

if (!class_exists('EPS_Redirects_Plugin_Options')) {

  class EPS_Redirects_Plugin_Options
  {

    /**
     *
     * Will be populated with the JSON file.
     *
     * @var array
     *
     */
    public $settings = array();

    public $plugin;

    private $menu_locations = array(
      'menu', 'dashboard', 'posts', 'media', 'links', 'pages', 'comments', 'theme', 'plugins', 'users', 'management', 'options'
    );


    /**
     *
     * Initialize the Theme Options, and register some actions.
     *
     */
    public function __construct(EPS_Redirects_Plugin $Plugin)
    {
      $this->plugin = $Plugin;
      $this->build_settings();
      add_action('admin_init', array($this, 'options_defaults'));
      add_action('admin_init', array($this, 'register_settings'));
      add_action('admin_menu', array($this, 'add_options_page'));
    }

    /**
     * Tab menu items
     */
    private function build_settings()
    {
      $this->settings = array (
  'redirects' =>
  array (
    'title' => 'Redirect Rules',
    'description' => '',
    'callback' => 'redirects',
    'fields' => array(),
  ),
  '404s' =>
  array (
    'title' => '404 Error Log',
    'description' => '',
    'callback' => '404s',
    'fields' => array(),
  ),
  'link-scanner' =>
  array (
    'title' => 'Link Scanner',
    'description' => '',
    'callback' => 'link_scanner',
    'fields' => array(),
  ),
  'import-export' =>
  array (
    'title' => 'Tools &amp; Options',
    'description' => '',
    'callback' => 'import_export',
    'fields' => array(),
  ),
  'support' =>
  array (
    'title' => 'Support',
    'description' => '',
    'callback' => 'support',
    'fields' => array(),
  ),
  'pro' =>
  array (
    'title' => 'PRO',
    'description' => '',
    'callback' => 'pro',
    'class' => 'pro-ad',
    'fields' => array(),
  ),
);

    } // build_settings


    /**
     *
     * Build the setting slug based on section.
     *
     * @param string $section
     * @return string
     */
    private function setting_slug($section = 'general')
    {
      return $this->plugin->config('option_slug') . '_' . $section;
    }


    /**
     *
     * Registers the settings based on the JSON file we imported.
     *
     */
    public function register_settings()
    {

      foreach ($this->settings as $section => $args) {

        register_setting(
          $this->setting_slug($section),
          $this->setting_slug($section),
          array($this, 'sanitize_inputs')
        );

        add_settings_section(
          $this->setting_slug($section),
          $args['title'],
          // array( $this, 'section_'.$section.'_callback'),
          array($this, 'section_callback'),
          $this->plugin->config('option_slug')  . '_' . $section
        );

        foreach ($args['fields'] as $slug => $args) {
          $args['section'] = $section;
          add_settings_field(
            $slug,
            $args['label'],
            array($this, 'field_callback'),
            $this->setting_slug($section),
            $this->setting_slug($section),
            $args
          );
        }
      }
    }


    /**
     *
     * Sanitize inputs. TODO
     *
     * @param $args
     * @return mixed
     */
    public function sanitize_inputs($args)
    {
      return $args;
    }

    /**
     *
     * If this is the first time we're loading this, we can use the JSON file to populate some defaults.
     *
     */
    public function options_defaults()
    {

      foreach ($this->settings as $section => $args) {

        $settings = get_option($this->setting_slug($section));

        if (empty($settings)) {
          $settings = array();
          foreach ($args['fields'] as $slug => $args) {
            $settings[$slug] = $args['default'];
          }

          add_option($this->setting_slug($section), $settings, '', 'yes');
        }
      }
    }



    /**
     *
     * Outputs the Sections intro HTML. A callback.
     *
     * @param $args
     *
     */
    function section_callback($args)
    {
      if (isset($_GET['tab'])) {
        $tab = sanitize_text_field($_GET['tab']);
      } else {
        $sections = array_keys($this->settings);
        $tab = $sections[0];
      }
      EPS_Redirects::wp_kses_wf($this->settings[$tab]['description']);
    }

    /**
     *
     * Output the Field HTML based on the JSON and 'type' of input.
     *
     * @param $args
     *
     */
    function field_callback($args)
    {
      $option_slug = $this->setting_slug($args['section']);
      $setting = get_option($this->setting_slug($args['section']));
      printf(
        "<input type='text' name='%s[%s]' value='%s' /><small>%s</small>",
        esc_attr($option_slug),
        esc_attr($args['slug']),
        (isset($setting[$args['slug']]) ? esc_attr(setting[$args['slug']]) : null),
        esc_attr($args['description'])
      );
    }

    /**
     *
     * ADD_PLUGIN_PAGE
     *
     * This function initialize the plugin settings page.
     *
     * @return string
     * @author WebFactory Ltd
     *
     */
    public function add_options_page()
    {
      if (in_array($this->plugin->config('menu_location'), $this->menu_locations)) {
        $func = sprintf("add_%s_page", $this->plugin->config('menu_location'));
        return $func($this->plugin->name, $this->plugin->name, $this->plugin->config('page_permission'), $this->plugin->config('page_slug'), array($this, 'do_admin_page'));
      } else {
        // TODO proper errors dude.
        printf('ERROR: menu location "%s" not valid.', esc_attr($this->config['menu_location']));
      }
      return false;
    }

    /**
     *
     * DO_ADMIN_PAGE
     *
     * This function will create the admin page.
     *
     * @author WebFactory Ltd
     *
     */
    public function do_admin_page()
    {
      $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : false;
      if (!$current_tab) {
        $sections = $this->settings;
        $current_tab = key($sections);
      }
      ?>
<div class="wrap">
  <h1><img src="<?php echo esc_attr(EPS_REDIRECT_URL) . 'images/wp-301-logo.png' ?>" alt="<?php echo esc_attr($this->plugin->name); ?>" title="<?php echo esc_attr($this->plugin->name); ?>"><span><?php echo esc_attr($this->plugin->name); ?></span></h1><br>
  <div id="eps-tabs-wrapper">
    <?php $this->get_tab_nav($current_tab); ?>
    <?php $this->get_tab($current_tab); ?>
  </div>
  <div id="eps-sidebar-wrapper">
  <div class="sidebar-box pro-ad-box">
  <p class="text-center"><a href="https://wp301redirects.com/?ref=eps-free-sidebar-box" target="_blank"><img src="<?php echo esc_attr(EPS_REDIRECT_URL) . 'images/wp-301-logo-full.png'; ?>" alt="WP 301 Redirects PRO" title="WP 301 Redirects PRO"></a><br><b>PRO version</b> is here! Grab the launch discount - <b>all prices are LIFETIME!</b></p>

  <ul class="plain-list">
      <li>Advanced Redirects Management &amp; URL Matching Rules</li>
      <li>Auto-fix URL Typos (no rules needed)</li>
      <li>Detailed 404 &amp; Redirect Stats + Email Reports</li>
      <li>Link Scanner - check every single link on your site</li>
      <li>URL Cloaking + other features for affiliate marketers</li>
      <li>Licenses &amp; Sites Manager (remote SaaS dashboard)</li>
      <li>Remote Site Stats (stats for all your sites in one place)</li>
      <li>White-label Mode + Complete Plugin Rebranding</li>
      <li>Branded PDF Reports</li>
      <li>Email support from plugin developers</li>
    </ul>

    <p class="text-center"><a href="#" class="open-301-pro-dialog button button-buy" data-pro-feature="sidebar-box">Get PRO Now</a></p>
    </div>

    <div class="sidebar-box">
    <p>Please <a href="https://wordpress.org/support/plugin/eps-301-redirects/reviews/?filter=5#new-post" target="_blank">rate the plugin ★★★★★</a> to <b>keep it up-to-date &amp; maintained</b>. It only takes a second to rate. Thank you! 👋</p>
    </div>
  </div>
</div>
<?php
  EPS_Redirects::wp_kses_wf($this->pro_dialog());
}

/**
     *
     * Outputs the tab navigation based on our sections.
     *
     * @param string $current
     */
function get_tab_nav($current = 'general')
{
  echo '<h2 class="nav-tab-wrapper">';

  foreach ($this->settings as $tab => $args) {
    $class = @$args['class'];
    $class .= ($tab == $current) ? ' nav-tab-active' : '';
    printf(
      "<a class='nav-tab %s' href='?page=%s&tab=%s'>%s</a>",
      esc_attr($class),
      esc_attr($this->plugin->config('option_slug')),
      esc_attr($tab),
      esc_attr($args['title'])
    );
  }
  echo '</h2>';
}

/**
     *
     * Gets the content for the current tab.
     *
     * @param string $tab
     *
     */
public function get_tab($tab = 'general')
{
  if ($this->tab_exists($tab)) {


    if (has_action($tab . '_admin_tab')) {
      do_action($tab . '_admin_tab', $this->settings[$tab]);
    } else {
      ?>
<form method="post" action="<?php echo admin_url('options.php'); ?>">
  <?php

    settings_fields($this->setting_slug($tab));
    do_action($this->setting_section_callback($tab, "_before"));
    do_settings_sections($this->plugin->config('option_slug') . '_' . $tab);
    do_action($this->setting_section_callback($tab, '_after'));
    submit_button();

    ?>
</form>
<?php

}
}
}

private function setting_section_callback($tab, $suffix = '')
{
  return $this->plugin->config('option_slug') . '_settings_' . $this->settings[$tab]['callback'] . $suffix;
}


/**
     *
     * Checks to see if a tab exists.
     *
     * @param $tab
     * @return bool
     * @throws Exception
     *
     */
public function tab_exists($tab)
{
  if (!array_key_exists($tab, $this->settings)) {
    throw new Exception('Tab does not exist');
  }
  return true;
}

function pro_dialog() {
  $out = '';

  $out .= '<div id="eps-pro-dialog" style="display: none;" title="WP 301 Redirects PRO is here!"><span class="ui-helper-hidden-accessible"><input type="text"/></span>';

  $plugin_url = plugin_dir_url(__FILE__);

  $out .= '<div class="center logo"><a href="https://wp301redirects.com/?ref=eps-free-pricing-table" target="_blank"><img src="' . EPS_REDIRECT_URL . 'images/wp-301-logo-full.png' . '" alt="WP 301 Redirects PRO" title="WP 301 Redirects PRO"></a><br>';

  $out .= '<span>Limited PRO Launch Discount - <b>all prices are LIFETIME</b>! Pay once &amp; use forever!</span>';
  $out .= '</div>';

  $out .= '<table id="eps-pro-table">';
  $out .= '<tr>';
  $out .= '<td class="center">Lifetime Personal License</td>';
  $out .= '<td class="center">Lifetime Team License</td>';
  $out .= '<td class="center">Lifetime Agency License</td>';
  $out .= '</tr>';

  $out .= '<tr class="prices">';
  $out .= '<td class="center"><del>$79 /year</del><br><span>$49</span> /lifetime</td>';
  $out .= '<td class="center"><del>$159 /year</del><br><span>$59</span> /lifetime</td>';
  $out .= '<td class="center"><del>$299 /year</del><br><span>$99</span> /lifetime</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span><b>1 Site License</b> ($49 per site)</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span><b>5 Sites License</b> ($12 per site)</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span><b>100 Sites License</b> ($1 per site)</td>';
  $out .= '</tr>';

  /*
  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Advanced Redirects Management</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Advanced Redirects Management</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Advanced Redirects Management</td>';
  $out .= '</tr>';
  */

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Advanced URL Matching Rules</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Advanced URL Matching Rules</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Advanced URL Matching Rules</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Auto-fix URL Typos &amp; URL Cloaking</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Auto-fix URL Typos &amp; URL Cloaking</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Auto-fix URL Typos &amp; URL Cloaking</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Detailed 404 &amp; Redirect Stats</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Detailed 404 &amp; Redirect Stats</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Detailed 404 &amp; Redirect Stats</td>';
  $out .= '</tr>';

  /*
  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>URL Cloaking</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>URL Cloaking</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>URL Cloaking</td>';
  $out .= '</tr>';
  */

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Link Scanner</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Link Scanner</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Link Scanner</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Licenses & Sites Manager (SaaS)</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Licenses & Sites Manager (SaaS)</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Licenses & Sites Manager (SaaS)</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-no"></span>Remote Site Stats</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Remote Site Stats</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Remote Site Stats</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-no"></span>White-label Mode</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>White-label Mode</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>White-label Mode</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-no"></span>Full Plugin Rebranding</td>';
  $out .= '<td><span class="dashicons dashicons-no"></span>Full Plugin Rebranding</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Full Plugin Rebranding</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-no"></span>Branded PDF Reports</td>';
  $out .= '<td><span class="dashicons dashicons-no"></span>Branded PDF Reports</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Branded PDF Reports</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Lifetime Updates &amp; Support</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Lifetime Updates &amp; Support</td>';
  $out .= '<td><span class="dashicons dashicons-yes"></span>Lifetime Updates &amp; Support</td>';
  $out .= '</tr>';

  $out .= '<tr>';
  $out .= '<td><span>one-time payment</span><a class="button button-buy" data-href-org="https://wp301redirects.com/buy/?product=personal-launch&ref=pricing-table" href="https://wp301redirects.com/buy/?product=personal-launch&ref=pricing-table" target="_blank">BUY NOW</a><br>or <a target="_blank" class="button-buy" data-href-org="https://wp301redirects.com/buy/?product=personal-monthly&ref=pricing-table" href="https://wp301redirects.com/buy/?product=personal-monthly&ref=pricing-table">only $5.99 <small>/month</small></a></td>';
  $out .= '<td><span>one-time payment</span><a class="button button-buy" data-href-org="https://wp301redirects.com/buy/?product=team-launch&ref=pricing-table" href="https://wp301redirects.com/buy/?product=team-launch&ref=pricing-table" target="_blank">BUY NOW</a></td>';
  $out .= '<td><span>one-time payment</span><a class="button button-buy" data-href-org="https://wp301redirects.com/buy/?product=agency-launch&ref=pricing-table" href="https://wp301redirects.com/buy/?product=agency-launch&ref=pricing-table" target="_blank">BUY NOW</a></td>';
  $out .= '</tr>';

  $out .= '</table>';

  $out .= '<div class="center footer"><b>100% No-Risk Money Back Guarantee!</b> If you don\'t like the plugin over the next 7 days, we will happily refund 100% of your money. No questions asked! Payments are processed by our merchant of records - <a href="https://paddle.com/" target="_blank">Paddle</a>.</div></div>';

  return $out;
}

}
}
