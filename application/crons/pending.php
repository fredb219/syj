<?php
$mapper = new Syj_Model_PendingMapper();
foreach ($mapper->fetchAll() as $pending) {
    $pending->notify();
}
