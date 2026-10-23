<?php

namespace App\Services\Partners;

/** Thrown inside a preview transaction so everything it did is rolled back. */
final class PreviewRollback extends \RuntimeException
{
}
