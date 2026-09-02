<?php

namespace App\Enums;

enum QuestionSelectionType: string
{
    case RANDOM = 'random';
    case FIXED = 'fixed';
    case WEIGHTED = 'weighted';
}