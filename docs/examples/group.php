<?php
/**
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * Copyright [2012-2014], [Robert Allen]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package     Rhubarb
 * @category    ${NAMESPACE}
 */
use Rhubarb\Rhubarb;
use Rhubarb\Task\Body\Python;

$config = include('configuration/predis.php');
$rhubarb = new Rhubarb($config);

for ($i = 0; $i < 10; $i++) {
    $tasks[] = $rhubarb->task('app.add', new Python($i, $i * 10));
}

$group = $rhubarb->group($tasks);
$res = $group();
while (!$res->isReady()) {
    usleep(1000);
}
$res->get();
 