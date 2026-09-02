<?php

namespace App\AI\Router;

class ScopeGuard
{
    protected array $topics = [
        'player', 'players', 'user', 'users', 'question', 'questions',
        'challenge', 'challenges', 'category', 'categories', 'leaderboard',
        'xp', 'sr', 'streak', 'analytics', 'report', 'dashboard',
        'health', 'game', 'teyaqi', 'coin', 'coins', 'life',
        'lives', 'friend', 'friends', 'avatar', 'moderation', 'admin'
    ];

    protected array $intentTriggers = [
        'about', 'who is', 'show me', 'profile', 'stats', 'compare', 'list'
    ];

    public function allowed(string $message): bool
    {
        $message = strtolower($message);

        // Allow common action intents
        foreach ($this->intentTriggers as $trigger) {
            if (str_contains($message, $trigger)) {
                return true;
            }
        }

        // Allow keyword topics
        foreach ($this->topics as $topic) {
            if (str_contains($message, $topic)) {
                return true;
            }
        }

        return false;
    }

    public function rejectMessage(): string
    {
        return <<<TEXT
I'm the Teyaqi Admin Assistant.

I can only help with:

• Players
• Questions
• Categories
• Challenges
• Leaderboards
• XP & SR
• Analytics
• Reports
• Moderation
• System Health

Please ask a question related to Teyaqi.
TEXT;
    }
}