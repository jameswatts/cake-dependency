<?php
/**
 * Bootstrap configuration
 *
 * Cake Dependency (http://github.com/jameswatts/cake-dependency)
 * Copyright 2013, James Watts (http://github.com/jameswatts)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2013, James Watts (http://github.com/jameswatts)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Dependency.Config
 * @since         CakePHP(tm) v 2.4.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Di', 'Dependency.Utility');

$file = ROOT . DS . APP_DIR . DS . 'Config' . DS . 'dependency.php';

if (file_exists($file)) {
	require $file;
}

