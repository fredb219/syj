<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

$mapper = new Syj_Model_PendingMapper();
foreach ($mapper->fetchAll() as $pending) {
    $pending->notify();
}
