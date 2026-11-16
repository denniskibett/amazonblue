<?php

namespace App\Services\StaffMail;

/** The mail server could not be reached or answered with an error. Messages never carry mail content. */
class MailUnavailable extends \RuntimeException
{
}
