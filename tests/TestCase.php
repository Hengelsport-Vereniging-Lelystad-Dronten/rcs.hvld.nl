<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * tests/TestCase.php
 *
 * Basis TestCase voor alle unit- en feature-tests. Breidt de Laravel
 * BaseTestCase uit en kan gedeelde setup/teardown logica of helper methodes
 * bevatten voor de test-suite.
 */
abstract class TestCase extends BaseTestCase
{
    // Plaats hier gedeelde test-hulpmethoden of setup-logica indien nodig.
}
