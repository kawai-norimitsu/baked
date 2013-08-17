<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php
  $pageTitle = '';
  if (!empty($title)) $pageTitle = $title.' - ';
  $pageTitle .= BK_SITE_NAME;
  echo h($pageTitle);
?></title>

<link rel="stylesheet" href="<?php echo URL ?>css/normalize.css">
<link rel="stylesheet" href="<?php echo URL ?>css/admin.css">
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css">
<link rel="stylesheet" href="<?php echo URL ?>css/buttons.css">
<link rel="shortcut icon" href="<?php echo URL ?>favicon.ico" type="image/x-icon">
<?php
if (Baked::read('ADMIN_CSS')) {
  $this->Html->css(Baked::read('ADMIN_CSS'), NULL, array('inline' => FALSE));
}
echo $this->fetch('css');
?>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>
<script src="<?php echo URL ?>js/class/Baked.js"></script>
<script src="<?php echo URL ?>js/interface/baked.interface.js"></script>
<script src="<?php echo URL ?>js/jquery.plugins/jquery.singlesender.js"></script>
<script src="http://bp.yahooapis.com/2.4.21/browserplus-min.js"></script>
<?php
if (Baked::read('ADMIN_JS')) {
  $this->Html->script(Baked::read('ADMIN_JS'), array('inline' => FALSE));
}
echo $this->fetch('script');
?>
<script>
$(function(){
  $('form').singlesender();
});
</script>
</head>
<body>

<ul id="toolbar">
  <li><a href="<?php echo URL ?>" class="sitename"><?php echo BK_SITE_NAME ?></a></li>
</ul>

<div id="wrap">

  <div id="main">
    <header class="header-01">
      <h1><i class="icon icon-large <?php echo h($adminInfo['navigation']['icon']) ?>"></i><?php echo h($adminInfo['navigation']['name']) ?></h1>
    </header>

    <?php echo $this->element('Baked/flash/type1') ?>

    <?php echo $this->fetch('content') ?>
    <?php
    ?>
  </div><!-- #main -->

</div><!-- #wrap -->

<div id="primary-navigation">
  <ul>
    <?php
    $admins = Configure::read('Admin');
    usort($admins, function($a, $b){
      return $a['order'] > $b['order'];
    });
    ?>
    <?php foreach ($admins as $admin) : ?>
      <?php
      $nav = $admin['navigation'];
      $classes = array();
      $url = URL.$nav['href'];
      ?>
      <li class="<?php echo implode(' ', $classes) ?>">
        <a href="<?php echo $url ?>"><i class="icon <?php echo $nav['icon'] ?>"></i><?php echo h($nav['name']) ?></a>
        <?php if (!empty($nav['sub'])) : ?>
          <ul>
            <?php foreach ($nav['sub'] as $nav) : ?>
              <?php
              $url = URL.$nav['href'];
              $classes = array();
              $grep = str_replace('/', "\/", $url);
              if (preg_match("/^{$grep}/i", $this->here)) $classes[] = 'current';
              ?>
              <li class="<?php echo implode(' ', $classes) ?>"><a href="<?php echo $url ?>"><?php echo h($nav['name']) ?></a></li>
            <?php endforeach ; ?>
          </ul>
        <?php endif ; ?>
      </li>
    <?php endforeach ; ?>
  </ul>
</div><!-- #primary-navigation -->


</body>
</html>
