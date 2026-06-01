<?php

use NumaxLab\Lunar\Redsys\Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');
