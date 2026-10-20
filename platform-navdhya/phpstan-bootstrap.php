<?php

/*
 * PHPStan bootstrap: the local PHP CLI ships with memory_limit=128M,
 * which is too low for Larastan analysis of the kernel. PHPStan 2.x
 * removed the memoryLimit configuration key, so the limit is raised
 * here — bootstrap files are loaded by the analysis worker processes.
 */

ini_set('memory_limit', '1G');
