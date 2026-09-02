<?php

namespace App\Enums; // Check if this matches your folder structure exactly

enum ChallengeStatus: string // Or just enum if it's not backed
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}