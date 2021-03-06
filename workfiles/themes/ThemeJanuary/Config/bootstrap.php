<?php
Configure::write('Themes.ThemeJanuary', array(
  'name' => __('January'),
  'author' => 'bakedcms.org',
  'url' => 'http://bakedcms.org/',
  'support' => array(
    'pc'     => FALSE,
    'mobile' => TRUE,
  ),
  'resources' => array(
    'CSS' => array(
      '/ThemeJanuary.css/jquery.sidr.light.css',
    ),
    'JS' => array(
      '/ThemeJanuary.js/jquery.sidr.min.js',
      '/ThemeJanuary.js/interface.js',
    )
  ),
));
