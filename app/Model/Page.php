<?php
App::uses('AppModel', 'Model');

class Page extends AppModel
{
  public $name = 'Page';
  public $valid = array(
    'add' => array(
      'title'          => 'required | maxLen[255]',
      'name'           => 'required | maxLen[255] | alphaNumeric',
      'parent_page_id' => 'isExist[Page,id]',
      'hidden'         => 'valid_no_hidden',
    ),
    'update' => array(
      'id' => 'required | valid_isExist'
    ),
  );
  public $columnLabels = array();

  public function __construct($id = false, $table = null, $ds = null)
  {
    $this->columnLabels = array(
      'title'  => __('Page title'),
      'name'   => __('Page name'),
      'hidden' => __('Hide setting'),
    );

    return parent::__construct($id, $table, $ds);
  }

  public function loadValidate()
  {
    parent::loadValidate();

    if (isset($this->validate['hidden']['valid_no_hidden'])) {
      $this->validate['hidden']['valid_no_hidden']['message'] = __('Cat not set the index page hidden.');
    }
  }

  public function insertPage($title = NULL, $name = NULL, $beforePageId = 0)
  {
    try {
      $this->begin();

      if (empty($title)) $title = __('Blank page');
      if (empty($name)) $name = $this->getNewName();

      $parentPageId = 0;
      $depth = 0;

      $newPageIds = array();
      $pageIds = $this->find('list', array(
        FIELDS => array('Page.id'),
        ORDER => array('Page.order' => 'asc'),
      ));

      if (!empty($beforePageId)) {
        $beforePage = $this->find('first', array(
          CONDITIONS => array('Page.id' => $beforePageId),
          FIELDS => array('Page.depth', 'Page.parent_page_id'),
        ));
        $parentPageId = $beforePage['Page']['parent_page_id'];
        $depth = $beforePage['Page']['depth'];
      }

      $data = array(
        'title'          => $title,
        'name'           => $name,
        'parent_page_id' => $parentPageId,
        'depth'          => $depth,
      );
      $r = $this->add($data, FALSE);

      $inserted = FALSE;
      foreach ($pageIds as $pageId) {
        $newPageIds[] = $pageId;
        if ($pageId == $beforePageId) {
          $newPageIds[] = $this->id;
          $inserted = TRUE;
        }
      }
      if (!$inserted) $newPageIds[] = $this->id;

      $this->saveOrder($newPageIds);

      $this->commit();
      return TRUE;
    } catch (Exception $e) {
      $this->rollback();
      return $e;
    }
  }

  public function saveOrder($pageIds)
  {
    try {
      $this->begin();

      $order = 0;
      foreach ($pageIds as $pageId) {
        $data = array(
          'id' => $pageId,
          'order' => $order,
        );
        $r = $this->add($data, TRUE);
        if ($r !== TRUE) throw $r;
        $order++;
      }

      $this->commit();
      return TRUE;
    } catch (Exception $e) {
      $this->rollback();
      return $e;
    }
  }

  public function getNewName()
  {
    $i = 1;
    $name = NULL;
    while ($i <= 100) {
      $name = sprintf('page%d', $i);
      $current = $this->find('first', array(
        CONDITIONS => array("{$this->name}.name" => $name),
      ));
      if (empty($current)) break;
      $i++;
    }
    return $name;
  }

/**
 * @param array $pages Ordered pages.
 * @return mixed true on success. Exception on failed.
 */
  public function update($pages)
  {
    try {
      $this->begin();

      $parentPageIds = array(0);
      $beforePage = NULL;
      $hasIndex = FALSE;

      foreach ($pages as $page) {
        $page = arrayWithKeys($page, array('id', 'name', 'title', 'depth', 'order', 'hidden'));

        if ($page['depth'] == 0 && $page['name'] == 'index') {
          $hasIndex = TRUE;
        }

        if (!empty($beforePage)) {
          if ($beforePage['depth'] < $page['depth']) {
            $parentPageIds[] = $beforePage['id'];
          }
          elseif ($beforePage['depth'] > $page['depth']) {
            $diff = $beforePage['depth']-$page['depth'];
            for ($i=0; $i < $diff; $i++) array_pop($parentPageIds);
          }
        }
        $page['parent_page_id'] = $parentPageIds[count($parentPageIds)-1];
        $r = $this->add($page, TRUE);
        if ($r !== TRUE) throw $r;

        $beforePage = $page;
      }

      if (!$hasIndex) throw new Exception('There is no index page.');

      $subQuery = "EXISTS (SELECT TmpPage.id FROM pages as TmpPage WHERE TmpPage.id <> Page.id AND TmpPage.name = Page.name AND TmpPage.parent_page_id = Page.parent_page_id)";
      $page = $this->find('first', array(
        CONDITIONS => array($subQuery),
        FIELDS => array('Page.id', 'Page.name'),
      ));
      if (!empty($page)) throw new Exception(__('There is more than one page of the same name (%s) in the same directory.', $page['Page']['name']));

      $this->commit();
      return TRUE;
    } catch (Exception $e) {
      $this->rollback();
      return $e;
    }
  }

/**
 * Get menu list.
 *
 * @param array $path
 * @param pointer &$parentMenuP
 * @param pointer &$currentMenu
 * @param pointer &$pageId
 * @return array
 */
  public function menu($path, &$parentMenuP, &$currentMenuP, &$pageId)
  {
    $pages = $this->find('all', array(
      CONDITIONS => array(
        "{$this->name}.hidden" => 0,
      ),
      FIELDS => array(
        "{$this->name}.id", "{$this->name}.name", "{$this->name}.title", "{$this->name}.parent_page_id", "{$this->name}.depth",
      ),
      ORDER => array(
        #"{$this->name}.parent_page_id" => 'asc',
        "{$this->name}.order" => 'asc',
      )
    ));

    $menuList = array();
    $pagePointers = array();
    $parentPageId = NULL;

    foreach ($pages as &$page) {
      $pointer;
      if ($page['Page']['parent_page_id'] == 0) {
        $page['Page']['url'] = URL.$page['Page']['name'];
        $pointer = &$menuList;
      } else {
        $p = &$pagePointers[$page['Page']['parent_page_id']];
        $page['Page']['url'] = $p['Page']['url'].'/'.$page['Page']['name'];
        $pointer = &$p['sub'];
      }
      $page['sub'] = array();
      $depth = $page['Page']['depth'];
      $page['current'] = (count($path) > $depth && $path[$depth] == $page['Page']['name']);

      $pagePointers[$page['Page']['id']] = $page;
      $pointer[] = &$pagePointers[$page['Page']['id']];

      if ($page['current']) {
        $parentPageId = $page['Page']['parent_page_id'];
        $pageId = $page['Page']['id'];
      }
    }

    $currentMenuP = $pagePointers[$pageId];
    if (!empty($parentPageId)) {
      $parentMenuP = $pagePointers[$parentPageId];
    } else {
      $parentMenuP = $pagePointers[$pageId];
    }

    return $menuList;
  }

  public function delete($id = null, $cascade = true)
  {
    try {
      $this->begin();

      $current = $this->find('first', array(
        CONDITIONS => array('Page.id' => $id),
        FIELDS => array('Page.name', 'Page.depth')
      ));
      if ($current['Page']['name'] == 'index' && $current['Page']['depth'] == 0) {
        throw new Exception(__('Can not delete index page.'));
      }

      $this->loadModel('Block');
      $blockIds = $this->Block->find('list', array(
        FIELDS => array('Block.id'),
        CONDITIONS => array('Block.page_id' => $id),
        'limit' => FALSE,
      ));
      foreach ($blockIds as $blockId) {
        $r = $this->Block->delete($blockId);
        if ($r !== TRUE) throw new Exception(__('Failed to delete blocks #%d (%s)', $blockId, $r->getMessage()));
      }

      $r = parent::delete($id, $cascade);
      if ($r !== TRUE) throw new Exception(__('Failed to delete page record.'));

      $this->commit();
      return TRUE;
    } catch (Exception $e) {
      $this->rollback();
      return $e;
    }
  }

  public function valid_no_hidden($data)
  {
    list($k, $v) = each($data);
    if ($v === '') return TRUE;
    if (!isset($this->data[$this->name]['name'])) return;
    if (!isset($this->data[$this->name]['parent_page_id'])) return;

    if ($v == 0) return TRUE;
    if ($this->data[$this->name]['name'] !== 'index') return TRUE;
    if ($this->data[$this->name]['parent_page_id'] !== 0) return TRUE;

    return FALSE;
  }

}












