<?php

namespace App\AI\Agents;

use App\AI\AIManager;
use App\AI\Context\AdminContext;
use App\AI\Formatters\MarkdownFormatter;
use App\AI\Router\IntentRouter;
use App\AI\Router\ToolRouter;
use Illuminate\Support\Str;

class TeyaqiAdminAgent
{
    public function __construct(
        protected AIManager $ai,
        protected MarkdownFormatter $formatter,
        protected IntentRouter $intentRouter,
        protected ToolRouter $toolRouter,
        protected AdminContext $context
    ) {}

    public function handle(string $message): array
    {
        // 1. Detect Intent
        $intent = $this->intentRouter->detect($message);

        $this->context->setLastIntent($intent);
        $this->context->memory()->remember("last_message", $message);

        // 2. Resolve Tool
        $tool = $this->toolRouter->resolve($intent);

        // 3. Execute Tool
        if ($tool) {
            return $this->executeTool($tool, $message);
        }

        // 4. Knowledge Agent
        if ($intent === "knowledge") {
            return [
                "text" => $this->askAI($message)
            ];
        }

        // 5. Unknown Intent Fallback
        return [
            "text" => "I can help only with Teyaqi administration, players, analytics, questions, and challenges."
        ];
    }

    private function executeTool(string $tool, string $message): array
    {
        $tools = $this->ai->tools();

        if (!isset($tools[$tool])) {
            return [
                "text" => "⚠️ Tool not available."
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Player Lookup
        |--------------------------------------------------------------------------
        */
        if ($tool === "player_lookup") {
            $name = $this->extractPlayerName($message);

            // Guard against empty extraction
            if (empty($name)) {
                return [
                    "text" => "Please specify a player name or username to lookup."
                ];
            }

            $result = $tools[$tool]->execute($name);

            if ($result['found'] ?? false) {
                $this->context->setPlayer($result['player']);
            }

            return [
                "text" => $this->formatPlayer($result)
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Tools
        |--------------------------------------------------------------------------
        */
        $result = $tools[$tool]->execute();

        return [
            "text" => $this->formatToolResult($result, $message)
        ];
    }

    private function askAI(string $message): string
    {
        $memory = $this->context->memory()->all();

        $response = $this->ai->provider()->chat([
            [
                "role" => "system",
                "content" => "You are Teyaqi Admin Assistant. "
                    . "You ONLY answer questions about Teyaqi game, players, questions, challenges, analytics, and game systems. "
                    . "If the question is outside Teyaqi, politely decline to answer.\n\n"
                    . "Current conversation memory:\n" . json_encode($memory, JSON_PRETTY_PRINT)
            ],
            [
                "role" => "user",
                "content" => $message
            ]
        ]);

        // Normalize response output strictly to string
        if (is_array($response)) {
            return $response['content'] ?? json_encode($response);
        }

        return (string) $response;
    }

    private function extractPlayerName(string $message): string
    {
        $lower = strtolower($message);

        /*
        |--------------------------------------------------------------------------
        | Memory Reference Check
        |--------------------------------------------------------------------------
        */
        $pronouns = ["him", "her", "that player", "this player"];
        foreach ($pronouns as $pronoun) {
            if (str_contains($lower, $pronoun)) {
                $player = $this->context->getPlayer();
                if ($player && isset($player['username'])) {
                    return $player['username'];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Direct Name Extraction (regex boundary removal)
        |--------------------------------------------------------------------------
        */
        $stopWords = [
            "show me", "tell me", "about", "who is", 
            "profile", "stats", "player", "information"
        ];

        // Replace stop words using word boundaries to prevent accidental string stripping
        foreach ($stopWords as $word) {
            $lower = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', '', $lower);
        }

        return trim(preg_replace('/\s+/', ' ', $lower));
    }

    private function formatPlayer(array $data): string
    {
        if (!($data['found'] ?? false)) {
            return "❌ Player not found.";
        }

        $p = $data['player'];

        return sprintf(
            "### 🏆 Player Profile\n\n**%s** (@%s)\n\n" .
            "* **XP:** %s\n" .
            "* **Level:** %s\n" .
            "* **SR:** %s\n" .
            "* **Current Streak:** %s\n" .
            "* **Best Streak:** %s\n" .
            "* **Answers:** %s",
            $p['name'] ?? 'N/A',
            $p['username'] ?? 'N/A',
            number_format($p['xp'] ?? 0),
            $p['level'] ?? 0,
            $p['current_sr'] ?? 0,
            $p['current_streak'] ?? 0,
            $p['best_streak'] ?? 0,
            number_format($p['total_answers'] ?? 0)
        );
    }

    private function formatToolResult(array $data, string $message): string
    {
        if (isset($data['overview'], $data['top_players'])) {
            return $this->formatter->playerReport($data);
        }

        if (isset($data['health'])) {
            return $this->formatter->healthReport($data);
        }

        if (isset($data['insights'])) {
            return $this->formatter->insightReport($data);
        }

        return "```json\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n```";
    }
}