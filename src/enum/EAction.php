<?php

namespace App\enum;

enum EAction: int
{
    case CREATE = 1;
    case READ = 2;
    case UPDATE = 3;
    case DELETE = 4;
}
