<?php
/**
 * Grid of Options
 */
namespace Application;

return
/**
 * @privilege Management
 * @return \closure
 */
function () use ($view) {
    /**
     * @var \Bluz\Application $this
     * @var \Bluz\View\View $view
     */
    $this->getLayout()->setTemplate('dashboard.phtml');
    $this->getLayout()->breadCrumbs(
        [
            $view->ahref('Dashboard', ['dashboard', 'index']),
            __('Media')
        ]
    );
    $grid = new Media\Grid();


    $countCol = $this->getRequest()->getParam('col', 4);
    $lnCol = (int)(12/$countCol);

    $view->countCol = $countCol;
    $view->col = $lnCol;
    $view->grid = $grid;
};
