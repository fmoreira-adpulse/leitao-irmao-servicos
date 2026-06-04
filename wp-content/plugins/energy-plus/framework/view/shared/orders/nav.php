<?php
if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly
}
?>

<div class="energyplus-title--menu __A__Coupons_Mode_2">
  <div class="__A__Scroll">
    <div class="row energyplus-gp">
      <ul>

        <li><a  class="__A__Button1 filter_tab_opener" data-tab="status" href=""><?php esc_html_e('Status', 'energyplus'); ?></a></li>
        <li><a class="__A__Button1 filter_tab_opener" data-tab="product" href=""><?php esc_html_e('Services', 'energyplus'); ?></a></li>
        <li><a class="__A__Button1 filter_tab_opener" data-tab="priority" href=""><?php esc_html_e('Priority', 'energyplus'); ?></a></li>
        
        <?php do_action('energyplus_submenu', 'orders'); ?>

        <li class="__A__Li_Search">
          <?php if (0 < intval(EnergyPlus::option('reactors-tweaks-order-refresh', 0))) { ?>
            <div class="__A__Paused d-none" data-toggle="tooltip" data-placement="bottom" data-offset="-15px" title="<?php esc_attr_e('Auto refresh paused. Please close all preview boxes', 'energyplus')?>"><i class="fas fa-pause-circle pr-2"></i></div>
          <?php } ?>
          <a href="javascript:;" class="__A__Button1 __A__Search_Button"><?php esc_html_e('Search', 'energyplus'); ?></a>
        </li>
      </ul>
    </div>
  </div>
</div>
