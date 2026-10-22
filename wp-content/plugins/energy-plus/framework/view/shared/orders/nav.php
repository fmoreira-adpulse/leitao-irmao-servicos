<?php
if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly
}
?>

<div class="energyplus-title--menu __A__Coupons_Mode_2">
  <div class="__A__Scroll">
    <div class="row energyplus-gp">
      <ul>

        <?php foreach (array('status' => 'Status', 'product' => 'Services', 'priority' => 'Priority', 'store' => 'Lojas') as $tab => $label) {
          $value = $_GET[$tab] ?? null;
          $has_filter = is_scalar($value) && false !== $value && '' !== (string) $value;
        ?>
          <li><a class="__A__Button1 filter_tab_opener<?php echo $has_filter ? ' adpulse-filter-applied' : ''; ?>" data-tab="<?php echo esc_attr($tab); ?>" href=""<?php if ($has_filter) { ?> aria-label="<?php echo esc_attr(__($label, 'energyplus') . ': filtro ativo'); ?>"<?php } ?>><?php echo esc_html(__($label, 'energyplus')); ?><?php if ($has_filter) { ?> <span class="adpulse-filter-dot" aria-hidden="true">●</span><?php } ?></a></li>
        <?php } ?>

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
