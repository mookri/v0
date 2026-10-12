<?php

namespace App\Modules\Customers\Auth;

use App\Platform\Identity\Otp\CodeSource;

/**
 * OTP code generation port (module autonomy: intentionally parallel to
 * onboarding's port rather than a cross-module import — deptrac allows
 * cross-module use only via Contracts, and OTP generation is small
 * enough to own twice). Narrows into the Kernel CodeSource seam so
 * App\Platform\Identity\Otp providers (LocalCodeProvider) can wrap this
 * generator without Kernel importing the module.
 */
interface OtpCodeGenerator extends CodeSource
{
}
