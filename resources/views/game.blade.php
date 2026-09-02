<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Teyaqi Trivia</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --tg-theme-bg-color: #ffffff;
            --tg-theme-text-color: #222222;
            --tg-theme-button-color: #3390ec;
        }
        body {
            background-color: var(--tg-theme-bg-color);
            color: var(--tg-theme-text-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .animate-fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .btn-disabled { opacity: 0.6; cursor: not-allowed; }
    </style>
</head>
<body class="p-4 flex flex-col min-h-screen">

    <div id="game-header" class="hidden flex justify-between items-center mb-6 p-3 bg-gray-100 rounded-2xl">
        <div class="flex items-center gap-2">
            <span class="text-xl">⭐</span>
            <span id="stat-xp" class="font-bold text-blue-600">0 XP</span>
        </div>
        <div id="stat-lives" class="flex gap-1 text-xl">
            ❤️❤️❤️❤️❤️
        </div>
    </div>

    <div id="app" class="flex-1 flex flex-col items-center justify-center text-center">
        <div id="welcome-screen">
            <div class="text-6xl mb-4">🇪🇹</div>
            <h1 class="text-3xl font-bold mb-2 text-blue-600">Teyaqi</h1>
            <p class="mb-8 opacity-70">Ready for today's 5-question challenge?</p>
            <button id="main-btn" class="w-full max-w-xs bg-blue-500 text-white py-4 rounded-2xl font-bold text-lg shadow-lg active:scale-95 transition-all">
                Start Daily Quiz
            </button>
        </div>
    </div>

    <script>
        const tg = window.Telegram.WebApp;
        tg.expand();

        // --- GAME STATE ---
        let currentSessionId = null;
        let questions = [];
        let currentIndex = 0;
        let livesLost = 0;
        const DEV_TOKEN = "1|T1eU5myHbtkmNO5W0YdygTLouy8YInXthcp9ponp84c901f7"; // Replace with actual login token

        const app = document.getElementById('app');
        const header = document.getElementById('game-header');

        // --- 1. START GAME ---
        async function startQuiz() {
            app.innerHTML = '<div class="text-xl font-bold animate-pulse">Fetching Questions...</div>';
            
            try {
                const response = await fetch('/api/game/daily', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${DEV_TOKEN}`
                    }
                });
                const data = await response.json();

                if (data.status === 'success') {
                    questions = data.questions;
                    currentSessionId = data.session_id;
                    header.classList.remove('hidden');
                    renderQuestion();
                } else {
                    app.innerHTML = `<div class="p-4 bg-red-100 text-red-600 rounded-xl">${data.message}</div>`;
                }
            } catch (e) {
                app.innerHTML = '<div class="text-red-500">Connection Error. Is your server running?</div>';
            }
        }

        // --- 2. RENDER QUESTION ---
        function renderQuestion() {
            if (currentIndex >= questions.length) return showEnd();
            
            const q = questions[currentIndex];
            app.innerHTML = `
                <div class="w-full text-left animate-fade-in">
                    <p class="text-xs font-bold text-blue-400 uppercase mb-2">Question ${currentIndex + 1} of 5</p>
                    <h2 class="text-2xl font-bold mb-6 leading-tight">${q.question_text}</h2>
                    
                    <div id="feedback-msg" class="h-6 mb-4 text-center font-bold"></div>

                    <div id="options-grid" class="space-y-3">
                        ${['a', 'b', 'c', 'd'].map(opt => `
                            <button onclick="window.processAnswer('${opt}')" 
                                    id="btn-${opt}"
                                    class="w-full p-4 border-2 border-gray-200 rounded-2xl text-left font-semibold transition-all hover:bg-gray-50">
                                <span class="text-blue-500 mr-2">${opt.toUpperCase()}.</span> ${q['option_' + opt]}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // --- 3. SUBMIT ANSWER ---
        window.processAnswer = async function(choice) {
            const btns = document.querySelectorAll('#options-grid button');
            btns.forEach(b => b.disabled = true);
            
            const selectedBtn = document.getElementById(`btn-${choice}`);
            const feedback = document.getElementById('feedback-msg');

            try {
                const response = await fetch('/api/game/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${DEV_TOKEN}`
                    },
                    body: JSON.stringify({
                        question_id: questions[currentIndex].id,
                        session_id: currentSessionId,
                        selected_option: choice
                    })
                });

                const result = await response.json();

                if (result.correct) {
                    selectedBtn.classList.add('bg-green-500', 'text-white', 'border-green-600');
                    feedback.innerHTML = '<span class="text-green-500">✨ Correct! Excellent job.</span>';
                    document.getElementById('stat-xp').innerText = `${result.total_session_xp} XP`;
                    if(tg.HapticFeedback) tg.HapticFeedback.notificationOccurred('success');
                } else {
                    selectedBtn.classList.add('bg-red-500', 'text-white', 'border-red-600');
                    feedback.innerHTML = `<span class="text-red-500">❌ Ouch! Correct was ${result.correct_answer.toUpperCase()}</span>`;
                    updateLives(result.lives_lost);
                    if(tg.HapticFeedback) tg.HapticFeedback.notificationOccurred('error');
                }

                setTimeout(() => {
                    if (result.game_over || currentIndex >= 4) {
                        showEnd(result.game_over);
                    } else {
                        currentIndex++;
                        renderQuestion();
                    }
                }, 1500);

            } catch (e) {
                console.error(e);
                btns.forEach(b => b.disabled = false);
            }
        };

        function updateLives(lost) {
            const heartIcons = "❤️".repeat(Math.max(0, 5 - lost)) + "🖤".repeat(Math.min(5, lost));
            document.getElementById('stat-lives').innerText = heartIcons;
        }

        function showEnd(isGameOver) {
            header.classList.add('hidden');
            app.innerHTML = `
                <div class="animate-fade-in py-8">
                    <div class="text-7xl mb-4">${isGameOver ? '💀' : '🏆'}</div>
                    <h2 class="text-3xl font-bold mb-2">${isGameOver ? 'Game Over' : 'Daily Complete!'}</h2>
                    <p class="opacity-70 mb-8">${isGameOver ? 'You lost too many lives.' : 'See you tomorrow to keep the streak!'}</p>
                </div>
            `;
            tg.MainButton.setText("Close Teyaqi").show().onClick(() => tg.close());
        }

        document.getElementById('main-btn').addEventListener('click', startQuiz);
    </script>
</body>
</html>