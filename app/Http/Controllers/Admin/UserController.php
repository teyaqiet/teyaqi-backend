<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('telegram_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('language')) {
            $query->where('language', $request->input('language'));
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('onboarded')) {
            $query->where(
                'has_onboarded',
                $request->input('onboarded') === 'yes'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'created_at',
            'total_xp',
            'weekly_xp',
            'current_sr',
            'best_sr',
            'current_streak',
            'best_streak',
            'total_coins',
            'total_wins',
            'total_answers_count',
            'last_played_date',
        ];

        $sort = $request->input('sort', 'created_at');

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        $direction = $request->input('direction', 'desc');

        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $users = $query
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filter Options
        |--------------------------------------------------------------------------
        */

        $languages = User::query()
            ->whereNotNull('language')
            ->where('language', '!=', '')
            ->distinct()
            ->orderBy('language')
            ->pluck('language');

        $genders = User::query()
            ->whereNotNull('gender')
            ->where('gender', '!=', '')
            ->distinct()
            ->orderBy('gender')
            ->pluck('gender');

        return view('admin.users.index', compact(
            'users',
            'languages',
            'genders'
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(User $user)
    {
        $user->load([
            'categoryRatings.category',
            'streak',
        ]);

        $gameStats = [
            'sessions' => $user->gameSessions()->count(),

            'completed_sessions' => $user->gameSessions()
                ->where('is_completed', true)
                ->count(),

            'questions' => $user->gameSessions()
                ->sum('total_questions'),

            'correct_answers' => $user->gameSessions()
                ->sum('correct_answers'),

            'xp_earned' => $user->gameSessions()
                ->sum('xp_earned'),

            'lives_lost' => $user->gameSessions()
                ->sum('lives_lost'),
        ];

        $gameStats['accuracy'] = $gameStats['questions'] > 0
            ? round(
                ($gameStats['correct_answers'] / $gameStats['questions']) * 100,
                1
            )
            : 0;

        $recentGames = $user->gameSessions()
            ->latest()
            ->limit(10)
            ->get();

        $streakHistory = $user->streakHistories()
            ->latest('activity_date')
            ->limit(30)
            ->get();

        return view('admin.users.show', compact(
            'user',
            'gameStats',
            'recentGames',
            'streakHistory'
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(User $user)
    {
        return view(
            'admin.users.edit',
            compact('user')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, User $user)
{
$validated = $request->validate([
'name'         => ['required', 'string', 'max:255'],
'username'     => ['nullable', 'string', 'max:255'],
'email'        => ['nullable', 'email'],
'gender'       => ['nullable', 'string'],
'language'     => ['nullable', 'string'],


    'total_xp'     => ['nullable', 'integer', 'min:0'],
    'total_coins'  => ['nullable', 'integer', 'min:0'],
    'current_sr'   => ['nullable', 'numeric', 'min:0'],
    'daily_lives'  => ['nullable', 'integer', 'min:0'],

    'has_onboarded' => ['nullable', 'boolean'],
]);

/*
|--------------------------------------------------------------------------
| Checkbox handling
|--------------------------------------------------------------------------
|
| A checkbox is not submitted at all when unchecked.
| Explicitly set it to false so the admin can turn onboarding
| off as well as on.
|
*/

$validated['has_onboarded'] = $request->boolean('has_onboarded');

$user->update($validated);

ActivityLogger::log(
    'updated',
    'Updated player profile/stats for user: ' . $user->name,
    $user
);

return redirect()
    ->route('admin.users.show', $user)
    ->with('success', 'User updated successfully');


}



    /*
    |--------------------------------------------------------------------------
    | Delete Single User
    |--------------------------------------------------------------------------
    */

    public function destroy(User $user)
    {
        $userName = $user->name;
        $userId = $user->id;

        DB::transaction(function () use ($user) {

            /*
             * Delete the user.
             *
             * If your database relationships use
             * ON DELETE CASCADE, related records will
             * be removed automatically.
             */
            $user->delete();
        });

        /*
         * Log after successful deletion.
         *
         * We cannot pass $user as the model because
         * it has already been deleted.
         */
        ActivityLogger::log(
            'deleted',
            "Deleted player: {$userName} (ID: {$userId})"
        );

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                "Player {$userName} was deleted successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Bulk Delete Users
    |--------------------------------------------------------------------------
    */

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'users' => [
                'required',
                'array',
                'min:1',
            ],

            'users.*' => [
                'integer',
                'exists:users,id',
            ],
        ]);

        $userIds = $validated['users'];

        /*
         * Never allow an empty bulk request.
         */

        if (empty($userIds)) {
            return back()
                ->with('error', 'No users were selected.');
        }

        /*
         * Get the users first so we can create
         * a useful activity log.
         */

        $users = User::whereIn('id', $userIds)->get();

        $count = $users->count();

        if ($count === 0) {
            return back()
                ->with('error', 'No valid users were found.');
        }

        DB::transaction(function () use ($users) {

            foreach ($users as $user) {
                $user->delete();
            }

        });

        /*
         * Activity log.
         */

        ActivityLogger::log(
            'deleted',
            "Bulk deleted {$count} player(s): " .
            $users->pluck('name')->implode(', ')
        );

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                "{$count} " .
                Str::plural('player', $count) .
                ' deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Adjust XP
    |--------------------------------------------------------------------------
    */

    public function adjustXp(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer'
        ]);

        $user->increment(
            'total_xp',
            $request->amount
        );

        ActivityLogger::log(
            'updated',
            "Adjusted XP by {$request->amount} for player: " . $user->name,
            $user
        );

        return back()
            ->with('success', 'XP updated');
    }


    /*
    |--------------------------------------------------------------------------
    | Adjust Coins
    |--------------------------------------------------------------------------
    */

    public function adjustCoins(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer'
        ]);

        $user->increment(
            'total_coins',
            $request->amount
        );

        ActivityLogger::log(
            'updated',
            "Adjusted coins by {$request->amount} for player: " . $user->name,
            $user
        );

        return back()
            ->with('success', 'Coins updated');
    }


    /*
    |--------------------------------------------------------------------------
    | Reset Streak
    |--------------------------------------------------------------------------
    */

    public function resetStreak(User $user)
    {
        $user->update([
            'current_streak' => 0
        ]);

        if ($user->streak) {
            $user->streak->update([
                'current_streak' => 0
            ]);
        }

        ActivityLogger::log(
            'updated',
            'Reset streak for player: ' . $user->name,
            $user
        );

        return back()
            ->with('success', 'Streak reset');
    }


    /*
    |--------------------------------------------------------------------------
    | Reset Lives
    |--------------------------------------------------------------------------
    */

    public function resetLives(User $user)
    {
        $user->update([
            'daily_lives' => 5
        ]);

        ActivityLogger::log(
            'updated',
            'Reset daily lives for player: ' . $user->name,
            $user
        );

        return back()
            ->with('success', 'Lives reset');
    }


    
public function bulkResetLives(Request $request)
{
    $validated = $request->validate([
        'users' => ['required', 'array', 'min:1'],
        'users.*' => ['integer', 'exists:users,id'],
    ]);

    $users = User::whereIn('id', $validated['users'])->get();

    foreach ($users as $user) {
        $user->update([
            'daily_lives' => 5,
        ]);

        ActivityLogger::log(
            'updated',
            'Reset lives for player: ' . $user->name,
            $user
        );
    }

    return back()->with(
        'success',
        $users->count() . ' user(s) lives reset successfully.'
    );
}

public function bulkResetStreak(Request $request)
{
    $validated = $request->validate([
        'users' => ['required', 'array', 'min:1'],
        'users.*' => ['integer', 'exists:users,id'],
    ]);

    $users = User::whereIn('id', $validated['users'])->get();

    foreach ($users as $user) {

        $user->update([
            'current_streak' => 0,
        ]);

        if ($user->streak) {
            $user->streak->update([
                'current_streak' => 0,
            ]);
        }

        ActivityLogger::log(
            'updated',
            'Reset streak for player: ' . $user->name,
            $user
        );
    }

    return back()->with(
        'success',
        $users->count() . ' user(s) streak reset successfully.'
    );
}


public function bulkActivate(Request $request)
{
    $validated = $request->validate([
        'users' => ['required', 'array', 'min:1'],
        'users.*' => ['integer', 'exists:users,id'],
    ]);

    $users = User::whereIn('id', $validated['users'])->get();

    foreach ($users as $user) {

        $user->update([
            'is_active' => true,
        ]);

        ActivityLogger::log(
            'updated',
            'Activated player: ' . $user->name,
            $user
        );
    }

    return back()->with(
        'success',
        $users->count() . ' user(s) activated successfully.'
    );
}


public function bulkDisable(Request $request)
{
    $validated = $request->validate([
        'users' => ['required', 'array', 'min:1'],
        'users.*' => ['integer', 'exists:users,id'],
    ]);

    $users = User::whereIn('id', $validated['users'])->get();

    foreach ($users as $user) {

        $user->update([
            'is_active' => false,
        ]);

        ActivityLogger::log(
            'updated',
            'Disabled player: ' . $user->name,
            $user
        );
    }

    return back()->with(
        'success',
        $users->count() . ' user(s) disabled successfully.'
    );
}

public function bulkAdjustXp(Request $request)
{
    $validated = $request->validate([
        'users' => ['required', 'array', 'min:1'],
        'users.*' => ['integer', 'exists:users,id'],
        'amount' => ['required', 'integer'],
    ]);

    $users = User::whereIn('id', $validated['users'])->get();

    foreach ($users as $user) {

        $user->increment(
            'total_xp',
            $validated['amount']
        );

        ActivityLogger::log(
            'updated',
            "Adjusted XP by {$validated['amount']} for player: " . $user->name,
            $user
        );
    }

    $action = $validated['amount'] >= 0
        ? 'added'
        : 'removed';

    return back()->with(
        'success',
        abs($validated['amount']) . " XP {$action} for {$users->count()} user(s)."
    );
}

public function bulkAdjustCoins(Request $request)
{
    $validated = $request->validate([
        'users' => ['required', 'array', 'min:1'],
        'users.*' => ['integer', 'exists:users,id'],
        'amount' => ['required', 'integer'],
    ]);

    $users = User::whereIn('id', $validated['users'])->get();

    foreach ($users as $user) {

        $user->increment(
            'total_coins',
            $validated['amount']
        );

        ActivityLogger::log(
            'updated',
            "Adjusted coins by {$validated['amount']} for player: " . $user->name,
            $user
        );
    }

    $action = $validated['amount'] >= 0
        ? 'added'
        : 'removed';

    return back()->with(
        'success',
        abs($validated['amount']) . " coins {$action} for {$users->count()} user(s)."
    );
}


}
