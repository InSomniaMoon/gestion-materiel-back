<?php

namespace App\Enums;

enum TokenType: string
{
  case ACCESS = 'access';
  case REFRESH = 'refresh';
  case ACCESS_ICS = 'access_ics';
}
