<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
final class BillingRestriction extends Constraint
{
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}