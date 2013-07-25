<?php
App::uses('AppController', 'Controller');

class ApiPagesController extends AppController
{
  public $uses = array('Page');
  public $components = array('Api');

  public function beforeFilter()
  {
    parent::beforeFilter();
  }

/**
 * Load the pages manager html
 *
 * @return void
 */
  public function html_manager()
  {
    $this->tokenFilterApi();

    $pages = $this->Page->find('all', array(
      ORDER => array(
        'Page.order' => 'asc',
      ),
    ));

    $this->set(array(
      'pages' => $pages,
    ));

    $this->layout = 'ajax';
    $this->Api->ok(array(
      'html' => $this->render()->body(),
    ));
  }

  public function update_all()
  {
    $this->tokenFilterApi();

    usort($this->request->data['Page'], function($a, $b) {
      return $a['order'] > $b['order'];
    });
    $r = $this->Page->update($this->request->data['Page']);

    if ($r !== TRUE) $this->Api->ng($r->getMessage());

    $this->Api->ok();
  }

}