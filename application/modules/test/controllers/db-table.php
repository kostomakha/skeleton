<?php
/**
 * Example of DB\Table usage
 *
 * @author   Anton Shevchuk
 * @created  18.07.13 13:35
 */
namespace Application;

use Application\Test;

return
/**
 * @TODO: need more informative example
 * @return bool
 */
function () {
    /**
     * @var Bootstrap $this
     */
    $table = Test\Table::getInstance();

    debug($table->saveTestRow());
    debug($table->updateTestRows());
    debug($table->deleteTestRows());

    $table = Users\Table::getInstance();

    var_dump($table->getColumns());
    var_dump($table->findRow(['1']));
    var_dump($table->findRowWhere(['id' => '0']));

    return false;
};
