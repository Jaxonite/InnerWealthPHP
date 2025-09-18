<?php
session_start();

// Simple database simulation using sessions
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [];
}
if (!isset($_SESSION['mood_data'])) {
    $_SESSION['mood_data'] = [];
}
if (!isset($_SESSION['chat_messages'])) {
    $_SESSION['chat_messages'] = [];
}

// Handle form submissions
$message = '';
$error = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $name = trim($_POST['name'] ?? '');
                $age = intval($_POST['age'] ?? 0);
                
                if ($age < 1 || $age > 120) {
                    $error = 'Please enter a valid age between 1 and 120.';
                } else {
                    $_SESSION['user'] = [
                        'name' => $name ?: 'Friend',
                        'age' => $age,
                        'login_time' => date('Y-m-d H:i:s')
                    ];
                    $_SESSION['chat_messages'] = [
                        [
                            'text' => "Hey " . $_SESSION['user']['name'] . "! I'm Gunjan, your new friend. What's on your mind today?",
                            'sender' => 'bot',
                            'timestamp' => date('H:i')
                        ]
                    ];
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=chat');
                    exit;
                }
                break;
                
            case 'chat':
                $user_message = trim($_POST['message'] ?? '');
                if ($user_message && isset($_SESSION['user'])) {
                    // Add user message
                    $_SESSION['chat_messages'][] = [
                        'text' => $user_message,
                        'sender' => 'user',
                        'timestamp' => date('H:i')
                    ];
                    
                    // Generate bot response (simplified)
                    $bot_responses = [
                        "That sounds really interesting! Tell me more about how that made you feel.",
                        "I can understand why you'd feel that way. It's completely normal to have those emotions.",
                        "Thank you for sharing that with me. What do you think helped you get through it?",
                        "That must have been challenging. How are you processing those feelings now?",
                        "I appreciate you opening up about that. What would make you feel better right now?",
                        "It sounds like you're going through a lot. Remember, it's okay to take things one step at a time.",
                        "That's a really thoughtful perspective. How long have you been feeling this way?",
                        "I'm here to listen. What's the most important thing you want to work through today?"
                    ];
                    
                    $_SESSION['chat_messages'][] = [
                        'text' => $bot_responses[array_rand($bot_responses)],
                        'sender' => 'bot',
                        'timestamp' => date('H:i')
                    ];
                }
                header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=chat');
                exit;
                
            case 'mood':
                if (isset($_SESSION['user'])) {
                    $mood = $_POST['mood'] ?? '';
                    $activities = $_POST['activities'] ?? [];
                    $journal = trim($_POST['journal'] ?? '');
                    
                    if ($mood) {
                        $_SESSION['mood_data'][] = [
                            'mood' => $mood,
                            'mood_value' => $_POST['mood_value'] ?? 3,
                            'activities' => $activities,
                            'journal' => $journal,
                            'date' => date('Y-m-d'),
                            'timestamp' => date('Y-m-d H:i:s')
                        ];
                        $message = 'Your mood entry has been saved successfully!';
                    } else {
                        $error = 'Please select a mood before submitting.';
                    }
                }
                break;
                
            case 'logout':
                session_destroy();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
        }
    }
}

$current_tab = $_GET['tab'] ?? 'chat';
$is_logged_in = isset($_SESSION['user']);

// Check if user checked in today
$checked_in_today = false;
if ($is_logged_in) {
    foreach ($_SESSION['mood_data'] as $entry) {
        if ($entry['date'] === date('Y-m-d')) {
            $checked_in_today = true;
            break;
        }
    }
}

$activities_list = [
    ['id' => 'exercise', 'label' => 'Exercise', 'emoji' => '🏃'],
    ['id' => 'meditate', 'label' => 'Meditate', 'emoji' => '🧘'],
    ['id' => 'socialize', 'label' => 'Socialize', 'emoji' => '💬'],
    ['id' => 'read', 'label' => 'Read', 'emoji' => '📖'],
    ['id' => 'work', 'label' => 'Work', 'emoji' => '💻'],
    ['id' => 'relax', 'label' => 'Relax', 'emoji' => '😌'],
    ['id' => 'creative', 'label' => 'Creative', 'emoji' => '🎨'],
    ['id' => 'nature', 'label' => 'Nature', 'emoji' => '🌳']
];

$moods = [
    ['label' => 'Excellent', 'emoji' => '😄', 'value' => 5, 'color' => '#10b981'],
    ['label' => 'Good', 'emoji' => '🙂', 'value' => 4, 'color' => '#3b82f6'],
    ['label' => 'Okay', 'emoji' => '😐', 'value' => 3, 'color' => '#f59e0b'],
    ['label' => 'Not Great', 'emoji' => '😟', 'value' => 2, 'color' => '#8b5cf6'],
    ['label' => 'Terrible', 'emoji' => '😭', 'value' => 1, 'color' => '#ef4444']
];
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inner Wealth - Your Personal Wellbeing Companion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'fade-in': 'fadeIn 0.3s ease-out'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        glow: {
                            '0%': { boxShadow: '0 0 5px rgba(59, 130, 246, 0.5)' },
                            '100%': { boxShadow: '0 0 20px rgba(59, 130, 246, 0.8)' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-morphism {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .chat-bubble {
            animation: slideUp 0.3s ease-out;
        }
        
        .mood-button:hover {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }
        
        .activity-chip {
            transition: all 0.3s ease;
        }
        
        .activity-chip:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-cyan-50 min-h-screen">

<?php if (!$is_logged_in): ?>
    <!-- Welcome Screen -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8 animate-slide-up">
                <div class="relative inline-block">
                    <h1 class="text-4xl md:text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 mb-2">
                        Inner Wealth
                    </h1>
                    <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg blur opacity-25 animate-glow"></div>
                </div>
                <p class="text-gray-600 text-lg">Your personal space for reflection, growth, and wellbeing</p>
                <div class="animate-float mt-4">
                    <span class="text-6xl">🌸</span>
                </div>
            </div>
            
            <div class="glass-morphism rounded-3xl p-8 shadow-2xl border border-white/20">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 animate-fade-in">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">What should I call you?</label>
                            <input 
                                type="text" 
                                name="name" 
                                placeholder="Your name (optional)"
                                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-white/80"
                                autocomplete="given-name"
                            >
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Your age</label>
                            <input 
                                type="number" 
                                name="age" 
                                placeholder="Enter your age"
                                required
                                min="1" 
                                max="120"
                                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-white/80"
                            >
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 px-6 rounded-2xl font-semibold text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 focus:ring-4 focus:ring-blue-500/30"
                    >
                        Begin Your Journey ✨
                    </button>
                </form>
                
                <div class="mt-6 text-center text-sm text-gray-500">
                    <p>A safe space to explore your thoughts and feelings</p>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Main App -->
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="glass-morphism shadow-lg sticky top-0 z-50">
            <div class="max-w-4xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">🌸</span>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800">Inner Wealth</h1>
                            <p class="text-sm text-gray-600">Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</p>
                        </div>
                    </div>
                    
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="p-2 rounded-xl hover:bg-white/20 transition-colors" title="Sign Out">
                            <span class="text-xl">🔄</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="glass-morphism border-t border-white/20">
            <div class="max-w-4xl mx-auto px-4">
                <div class="flex justify-center space-x-1 py-2">
                    <?php 
                    $tabs = [
                        ['id' => 'chat', 'label' => 'Chat', 'emoji' => '💬'],
                        ['id' => 'mood', 'label' => 'Mood', 'emoji' => '😊'],
                        ['id' => 'progress', 'label' => 'Progress', 'emoji' => '📊']
                    ];
                    foreach ($tabs as $tab): 
                    ?>
                        <a 
                            href="?tab=<?= $tab['id'] ?>" 
                            class="flex items-center space-x-2 px-6 py-3 rounded-2xl transition-all duration-300 <?= $current_tab === $tab['id'] ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg' : 'text-gray-600 hover:bg-white/50' ?>"
                        >
                            <span class="text-lg"><?= $tab['emoji'] ?></span>
                            <span class="font-medium"><?= $tab['label'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="flex-1 max-w-4xl mx-auto w-full px-4 py-6">
            
            <?php if ($current_tab === 'chat'): ?>
                <!-- Chat Screen -->
                <div class="glass-morphism rounded-3xl h-[70vh] flex flex-col shadow-xl">
                    <div class="p-6 border-b border-white/20">
                        <h2 class="text-2xl font-bold text-gray-800">Chat with Gunjan</h2>
                        <p class="text-gray-600">Your AI companion is here to listen</p>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6 space-y-4" id="chatContainer">
                        <?php foreach ($_SESSION['chat_messages'] as $msg): ?>
                            <div class="chat-bubble flex <?= $msg['sender'] === 'user' ? 'justify-end' : 'justify-start' ?>">
                                <div class="flex items-start space-x-3 max-w-sm md:max-w-md">
                                    <?php if ($msg['sender'] === 'bot'): ?>
                                        <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                            G
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="<?= $msg['sender'] === 'user' ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white' : 'bg-white' ?> p-4 rounded-2xl shadow-md <?= $msg['sender'] === 'user' ? 'rounded-tr-md' : 'rounded-tl-md' ?>">
                                        <p class="<?= $msg['sender'] === 'user' ? 'text-white' : 'text-gray-800' ?>"><?= htmlspecialchars($msg['text']) ?></p>
                                        <span class="text-xs <?= $msg['sender'] === 'user' ? 'text-purple-100' : 'text-gray-500' ?> mt-1 block"><?= $msg['timestamp'] ?></span>
                                    </div>
                                    
                                    <?php if ($msg['sender'] === 'user'): ?>
                                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                            👤
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="p-6 border-t border-white/20">
                        <form method="POST" class="flex space-x-4">
                            <input type="hidden" name="action" value="chat">
                            <input 
                                type="text" 
                                name="message" 
                                placeholder="Share what's on your mind..."
                                required
                                class="flex-1 px-4 py-3 rounded-2xl border border-gray-300 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white/80"
                                autofocus
                            >
                            <button 
                                type="submit" 
                                class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-2xl hover:shadow-lg transform hover:scale-105 transition-all duration-300"
                            >
                                Send ➤
                            </button>
                        </form>
                    </div>
                </div>
            
            <?php elseif ($current_tab === 'mood'): ?>
                <!-- Mood Tracker Screen -->
                <div class="glass-morphism rounded-3xl p-8 shadow-xl">
                    <?php if ($checked_in_today): ?>
                        <div class="text-center py-12">
                            <span class="text-6xl mb-4 block">✨</span>
                            <h2 class="text-3xl font-bold text-gray-800 mb-4">Already Checked In!</h2>
                            <p class="text-gray-600">You've already recorded your mood today. Come back tomorrow!</p>
                            <div class="mt-6">
                                <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-medium">
                                    Streak: Day 1 🔥
                                </span>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($message): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-2xl mb-6 animate-fade-in">
                                <span class="text-lg">✅</span> <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-6 animate-fade-in">
                                ❌ <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="space-y-8">
                            <input type="hidden" name="action" value="mood">
                            
                            <div class="text-center">
                                <h2 class="text-3xl font-bold text-gray-800 mb-2">How are you feeling today?</h2>
                                <p class="text-gray-600">Your feelings are valid and important</p>
                            </div>
                            
                            <div class="flex flex-wrap justify-center gap-4">
                                <?php foreach ($moods as $mood): ?>
                                    <label class="cursor-pointer mood-button">
                                        <input type="radio" name="mood" value="<?= $mood['label'] ?>" class="sr-only peer" required>
                                        <input type="hidden" name="mood_value" value="<?= $mood['value'] ?>">
                                        <div class="peer-checked:ring-4 peer-checked:ring-blue-500/30 peer-checked:scale-110 w-20 h-20 bg-white rounded-2xl shadow-lg flex flex-col items-center justify-center transition-all duration-300 hover:shadow-xl">
                                            <span class="text-2xl mb-1"><?= $mood['emoji'] ?></span>
                                            <span class="text-xs font-medium text-gray-700"><?= $mood['label'] ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-4 text-center">What activities did you do today?</h3>
                                <div class="flex flex-wrap justify-center gap-3">
                                    <?php foreach ($activities_list as $activity): ?>
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="activities[]" value="<?= $activity['id'] ?>" class="sr-only peer">
                                            <div class="activity-chip peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-blue-600 peer-checked:text-white bg-white text-gray-700 px-4 py-2 rounded-2xl shadow-md border border-gray-200 flex items-center space-x-2">
                                                <span><?= $activity['emoji'] ?></span>
                                                <span class="font-medium"><?= $activity['label'] ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xl font-bold text-gray-800 mb-4 text-center">Journal Entry (Optional)</label>
                                <textarea 
                                    name="journal" 
                                    rows="4" 
                                    placeholder="What's on your mind? How was your day? What are you grateful for?"
                                    class="w-full px-6 py-4 rounded-2xl border border-gray-300 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white/80 resize-none"
                                ></textarea>
                            </div>
                            
                            <div class="text-center">
                                <button 
                                    type="submit" 
                                    class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-4 rounded-2xl font-bold text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 focus:ring-4 focus:ring-blue-500/30"
                                >
                                    Save My Day ✨
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            
            <?php elseif ($current_tab === 'progress'): ?>
                <!-- Progress Screen -->
                <div class="glass-morphism rounded-3xl p-8 shadow-xl">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Your Wellbeing Journey</h2>
                        <p class="text-gray-600">Track your progress and celebrate your growth</p>
                    </div>
                    
                    <?php if (empty($_SESSION['mood_data'])): ?>
                        <div class="text-center py-12">
                            <span class="text-6xl mb-4 block">📊</span>
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">No Data Yet</h3>
                            <p class="text-gray-600 mb-6">Start tracking your mood to see your progress!</p>
                            <a href="?tab=mood" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-2xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300 inline-block">
                                Record Your First Mood
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Stats Cards -->
                            <div class="bg-white/50 rounded-2xl p-6 text-center">
                                <span class="text-3xl mb-2 block">🗓️</span>
                                <h3 class="text-lg font-bold text-gray-800">Days Tracked</h3>
                                <p class="text-3xl font-bold text-purple-600"><?= count($_SESSION['mood_data']) ?></p>
                            </div>
                            
                            <div class="bg-white/50 rounded-2xl p-6 text-center">
                                <span class="text-3xl mb-2 block">📈</span>
                                <h3 class="text-lg font-bold text-gray-800">Average Mood</h3>
                                <?php
                                $total_mood = array_sum(array_column($_SESSION['mood_data'], 'mood_value'));
                                $avg_mood = round($total_mood / count($_SESSION['mood_data']), 1);
                                $mood_emoji = $avg_mood >= 4.5 ? '😄' : ($avg_mood >= 3.5 ? '🙂' : ($avg_mood >= 2.5 ? '😐' : ($avg_mood >= 1.5 ? '😟' : '😭')));
                                ?>
                                <p class="text-3xl font-bold text-blue-600"><?= $avg_mood ?>/5 <?= $mood_emoji ?></p>
                            </div>
                        </div>
                        
                        <!-- Recent Entries -->
                        <div class="mt-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Recent Entries</h3>
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                <?php foreach (array_reverse($_SESSION['mood_data']) as $entry): ?>
                                    <div class="bg-white/50 rounded-2xl p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-3">
                                                <?php
                                                $mood_info = array_filter($moods, fn($m) => $m['label'] === $entry['mood'])[0] ?? $moods[2];
                                                ?>
                                                <span class="text-2xl"><?= $mood_info['emoji'] ?></span>
                                                <span class="font-bold text-gray-800"><?= $entry['mood'] ?></span>
                                            </div>
                                            <span class="text-sm text-gray-500"><?= date('M j, Y', strtotime($entry['date'])) ?></span>
                                        </div>
                                        
                                        <?php if (!empty($entry['activities'])): ?>
                                            <div class="flex flex-wrap gap-2 mb-2">
                                                <?php foreach ($entry['activities'] as $activity_id): ?>
                                                    <?php $activity = array_filter($activities_list, fn($a) => $a['id'] === $activity_id)[0] ?? null; ?>
                                                    <?php if ($activity): ?>
                                                        <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-lg text-sm flex items-center space-x-1">
                                                            <span><?= $activity['emoji'] ?></span>
                                                            <span><?= $activity['label'] ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($entry['journal']): ?>
                                            <p class="text-gray-700 text-sm italic">"<?= htmlspecialchars($entry['journal']) ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </main>
    </div>
<?php endif; ?>

<script>
    // Auto-scroll chat to bottom
    document.addEventListener('DOMContentLoaded', function() {
        const chatContainer = document.getElementById('chatContainer');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    });
    
    // Ad
